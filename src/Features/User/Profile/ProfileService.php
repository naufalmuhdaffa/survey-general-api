<?php

declare(strict_types=1);

namespace App\Features\User\Profile;

use App\Features\Auth\Register\RegisterVerificationService;
use App\Repositories\PrivilegeRepository;
use App\Services\FileUploadService;
use InvalidArgumentException;
use RuntimeException;

final class ProfileService
{
    private ProfileRepository $repository;
    private RegisterVerificationService $verificationService;
    private FileUploadService $fileUploadService;
    private PrivilegeRepository $privilegeRepository;

    public function __construct()
    {
        $this->repository = new ProfileRepository();
        $this->verificationService = new RegisterVerificationService();
        $this->fileUploadService = new FileUploadService();
        $this->privilegeRepository = new PrivilegeRepository();
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(int $userId, ?int $effectiveRoleId = null): array
    {
        $user = $this->repository->getById($userId);

        if (!$user) {
            throw new RuntimeException('User tidak ditemukan', 404);
        }

        return $this->formatProfile($user, $effectiveRoleId);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function updateProfile(int $userId, array $data, ?int $effectiveRoleId = null): array
    {
        $this->guardReadonlyFields($data);
        $currentUser = $this->repository->getById($userId);

        if (!$currentUser) {
            throw new RuntimeException('User tidak ditemukan', 404);
        }

        $fields = [];
        $resetEmailVerification = false;
        $resetPhoneVerification = false;

        if (array_key_exists('email', $data)) {
            $fields['email'] = $this->normalizeEmail($data['email']);
            $resetEmailVerification = $fields['email'] !== ($currentUser['email'] ?? null);
        }

        if (array_key_exists('phone', $data)) {
            $fields['phone'] = $this->normalizePhone($data['phone']);
            $resetPhoneVerification = $fields['phone'] !== ($currentUser['phone'] ?? null);
        }

        if (array_key_exists('username', $data)) {
            $fields['username'] = $this->normalizeUsername($data['username']);
        }

        if ($fields === []) {
            throw new RuntimeException('Tidak ada data profil yang bisa diperbarui', 422);
        }

        if (
            array_key_exists('email', $fields)
            && $fields['email'] !== null
            && $this->repository->emailExistsForOtherUser($fields['email'], $userId)
        ) {
            throw new RuntimeException('Email sudah digunakan user lain', 409);
        }

        if (
            array_key_exists('phone', $fields)
            && $fields['phone'] !== null
            && $this->repository->phoneExistsForOtherUser($fields['phone'], $userId)
        ) {
            throw new RuntimeException('Nomor telepon sudah digunakan user lain', 409);
        }

        if (
            array_key_exists('username', $fields)
            && $this->repository->usernameExistsForOtherUser($fields['username'], $userId)
        ) {
            throw new RuntimeException('Username sudah digunakan user lain', 409);
        }

        $this->repository->updateProfile(
            $userId,
            $fields,
            $resetEmailVerification,
            $resetPhoneVerification
        );

        return $this->profile($userId, $effectiveRoleId);
    }

    public function sendEmailCode(int $userId): void
    {
        $user = $this->requireUserWithContact($userId, 'email');
        $this->verificationService->sendEmailCode([
            'email' => $user['email'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function verifyEmailCode(int $userId, array $data, ?int $effectiveRoleId = null): array
    {
        $user = $this->requireUserWithContact($userId, 'email');
        $this->verificationService->verifyEmailCode([
            'code' => $data['code'] ?? null,
            'email' => $user['email'],
        ]);
        $this->repository->markEmailVerified($userId);

        return $this->profile($userId, $effectiveRoleId);
    }

    public function sendPhoneOtp(int $userId): void
    {
        $user = $this->requireUserWithContact($userId, 'phone');
        $this->verificationService->sendPhoneOtp([
            'phone' => $user['phone'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function verifyPhoneOtp(int $userId, array $data, ?int $effectiveRoleId = null): array
    {
        $user = $this->requireUserWithContact($userId, 'phone');
        $this->verificationService->verifyPhoneOtp([
            'code' => $data['code'] ?? null,
            'phone' => $user['phone'],
        ]);
        $this->repository->markPhoneVerified($userId);

        return $this->profile($userId, $effectiveRoleId);
    }

    /**
     * @param array<string, mixed>|null $photo
     * @return array<string, mixed>
     */
    public function updateProfilePhoto(int $userId, ?array $photo, ?int $effectiveRoleId = null): array
    {
        $user = $this->repository->getById($userId);

        if (!$user) {
            throw new RuntimeException('User tidak ditemukan', 404);
        }

        if ($photo === null || ($photo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Foto profil harus diupload', 422);
        }

        try {
            $newPhotoPath = $this->fileUploadService->storeProfilePhoto($photo);
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            throw new RuntimeException($e->getMessage(), 500);
        }

        try {
            $this->repository->updateProfilePhotoPath($userId, $newPhotoPath);
        } catch (RuntimeException $e) {
            $this->fileUploadService->deletePublicUpload($newPhotoPath);
            throw new RuntimeException($e->getMessage(), 500);
        }

        $oldPhotoPath = $user['profile_photo_path'] ?? null;
        $this->fileUploadService->deletePublicUpload(
            \is_string($oldPhotoPath) ? $oldPhotoPath : null
        );

        return $this->profile($userId, $effectiveRoleId);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function changePassword(int $userId, array $data): void
    {
        $currentPassword = isset($data['currentPassword']) && \is_string($data['currentPassword'])
            ? $data['currentPassword']
            : '';
        $password = isset($data['password']) && \is_string($data['password'])
            ? $data['password']
            : '';
        $passwordConfirmation = isset($data['passwordConfirmation']) && \is_string($data['passwordConfirmation'])
            ? $data['passwordConfirmation']
            : '';

        if (trim($currentPassword) === '') {
            throw new RuntimeException('Kata sandi saat ini harus diisi', 422);
        }

        if (trim($password) === '') {
            throw new RuntimeException('Kata sandi baru harus diisi', 422);
        }

        if (mb_strlen($password) < 6) {
            throw new RuntimeException('Kata sandi baru minimal 6 karakter', 422);
        }

        if (mb_strlen($password) > 255) {
            throw new RuntimeException('Kata sandi baru maksimal 255 karakter', 422);
        }

        if ($password !== $passwordConfirmation) {
            throw new RuntimeException('Konfirmasi kata sandi baru belum sama', 422);
        }

        if ($currentPassword === $password) {
            throw new RuntimeException('Kata sandi baru harus berbeda dari kata sandi saat ini', 422);
        }

        $user = $this->repository->getById($userId);

        if (!$user) {
            throw new RuntimeException('User tidak ditemukan', 404);
        }

        if (!password_verify($currentPassword, (string) $user['password'])) {
            throw new RuntimeException('Kata sandi saat ini tidak sesuai', 422);
        }

        $this->repository->updatePassword($userId, $password);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function guardReadonlyFields(array $data): void
    {
        $readonlyFields = [
            'id',
            'nik',
            'fullName',
            'full_name',
            'name',
            'address',
            'position',
            'role',
            'role_id',
            'is_active',
            'password',
        ];

        foreach ($readonlyFields as $field) {
            if (array_key_exists($field, $data)) {
                throw new RuntimeException(
                    'NIK, nama lengkap, alamat, posisi, role, dan password tidak bisa diubah dari profil',
                    422
                );
            }
        }
    }

    private function normalizeUsername(mixed $username): string
    {
        if (!\is_string($username)) {
            throw new RuntimeException('Username harus berupa string', 422);
        }

        $username = strtolower(trim($username));

        if ($username === '') {
            throw new RuntimeException('Username harus diisi', 422);
        }

        if (mb_strlen($username) > 25) {
            throw new RuntimeException('Username maksimal 25 karakter', 422);
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new RuntimeException('Username hanya boleh huruf, angka, dan underscore', 422);
        }

        return $username;
    }

    private function normalizeEmail(mixed $email): ?string
    {
        if ($email === null) {
            return null;
        }

        if (!\is_string($email)) {
            throw new RuntimeException('Email harus berupa string', 422);
        }

        $email = strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        if (mb_strlen($email) > 255) {
            throw new RuntimeException('Email maksimal 255 karakter', 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Format email tidak valid', 422);
        }

        return $email;
    }

    private function normalizePhone(mixed $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        if (!\is_string($phone)) {
            throw new RuntimeException('Nomor telepon harus berupa string', 422);
        }

        $phone = trim($phone);

        if ($phone === '') {
            return null;
        }

        $phone = preg_replace('/[\s().-]/', '', $phone);

        if (!\is_string($phone) || !preg_match('/^\+?[0-9]+$/', $phone)) {
            throw new RuntimeException('Format nomor telepon tidak valid', 422);
        }

        if (str_starts_with($phone, '+62')) {
            $normalizedPhone = $phone;
        } elseif (str_starts_with($phone, '62')) {
            $normalizedPhone = '+' . $phone;
        } elseif (str_starts_with($phone, '0')) {
            $normalizedPhone = '+62' . substr($phone, 1);
        } else {
            throw new RuntimeException('Format nomor telepon harus diawali dengan +62, 62, atau 08', 422);
        }

        if (!preg_match('/^\+62[0-9]{8,13}$/', $normalizedPhone)) {
            throw new RuntimeException('Format nomor telepon tidak valid', 422);
        }

        return $normalizedPhone;
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private function formatProfile(array $user, ?int $effectiveRoleId = null): array
    {
        $roleId = (int) $user['role_id'];
        $privilegeRoleId = $effectiveRoleId ?? $roleId;

        return [
            'id' => (int) $user['id'],
            'nik' => $user['nik'],
            'full_name' => $user['full_name'],
            'username' => $user['username'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'email_verified_at' => $user['email_verified_at'],
            'phone_verified_at' => $user['phone_verified_at'],
            'email_verified' => !empty($user['email_verified_at']),
            'phone_verified' => !empty($user['phone_verified_at']),
            'profile_photo_path' => $user['profile_photo_path'],
            'role_id' => $roleId,
            'role' => $user['role'],
            'position' => $user['position'],
            'opd_pengampu' => $user['opd_pengampu'],
            'privileges' => $this->privilegeRepository->getPrivilegeNamesByRoleId($privilegeRoleId),
            'address' => $this->identityAddress((string) $user['nik']),
            'is_active' => (bool) $user['is_active'],
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at'],
        ];
    }

    private function identityAddress(string $nik): ?string
    {
        $apiUrl = trim($_ENV['IDENTITY_API_URL'] ?? '');
        $apiKey = $_ENV['IDENTITY_API_KEY'] ?? '';

        if ($apiUrl === '' || $apiKey === '') {
            return null;
        }

        $ch = curl_init(rtrim($apiUrl, '/') . '/verify/' . $nik);

        if ($ch === false) {
            return null;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Key: {$apiKey}"]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false || $httpCode !== 200) {
            return null;
        }

        $identityData = json_decode((string) $response, true);
        $identity = \is_array($identityData) ? ($identityData['data'] ?? null) : null;
        $address = \is_array($identity) ? ($identity['address'] ?? null) : null;

        return \is_string($address) && trim($address) !== ''
            ? trim($address)
            : null;
    }

    private function requireUserWithContact(int $userId, string $contactField): array
    {
        $user = $this->repository->getById($userId);

        if (!$user) {
            throw new RuntimeException('User tidak ditemukan', 404);
        }

        $contact = $user[$contactField] ?? null;

        if (!\is_string($contact) || trim($contact) === '') {
            $message = $contactField === 'email'
                ? 'Email harus diisi sebelum verifikasi'
                : 'Nomor telepon harus diisi sebelum verifikasi';

            throw new RuntimeException($message, 422);
        }

        return $user;
    }
}
