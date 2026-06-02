<?php

declare(strict_types=1);

namespace App\Features\Auth\Register;

use App\Services\JwtService;
use RuntimeException;

final class RegisterService
{
    private RegisterRepository $repository;

    public function __construct()
    {
        $this->repository = new RegisterRepository();
    }

    public function register(array $data): string
    {
        $nik = isset($data['nik']) && \is_string($data['nik'])
            ? trim($data['nik'])
            : '';
        $fullName = isset($data['fullName']) && \is_string($data['fullName'])
            ? trim($data['fullName'])
            : '';
        $username = isset($data['username']) && \is_string($data['username'])
            ? strtolower(trim($data['username']))
            : '';
        $password = isset($data['password']) && \is_string($data['password'])
            ? $data['password']
            : '';

        if ($nik === '') {
            throw new RuntimeException('NIK harus diisi', 422);
        }

        if ($fullName === '') {
            throw new RuntimeException('Nama lengkap harus diisi', 422);
        }

        if ($username === '') {
            throw new RuntimeException('Username harus diisi', 422);
        }

        if (trim($password) === '') {
            throw new RuntimeException('Password harus diisi', 422);
        }

        if (!ctype_digit($nik) || mb_strlen($nik) !== 16) {
            throw new RuntimeException('NIK harus 16 digit angka', 422);
        }

        if (mb_strlen($username) > 25) {
            throw new RuntimeException('Username maksimal 25 karakter', 422);
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new RuntimeException('Username hanya boleh huruf, angka, dan underscore', 400);
        }

        if (mb_strlen($password) < 6) {
            throw new RuntimeException('Password minimal 6 karakter', 422);
        }

        if (mb_strlen($password) > 255) {
            throw new RuntimeException('Password maksimal 255 karakter', 422);
        }

        if ($this->repository->getUserByNik($nik)) {
            throw new RuntimeException('NIK sudah terdaftar', 409);
        }

        if ($this->repository->getUserByUsername($username)) {
            throw new RuntimeException('Username sudah terdaftar', 409);
        }

        $identityData = $this->verifyNik($nik);
        $position = $identityData['position'];
        $verifiedFullName = $identityData['name'];

        try {
            $userId = $this->repository->registerUser(
                $nik,
                $verifiedFullName,
                $username,
                $password,
                $position
            );
        } catch (RuntimeException $e) {
            throw new RuntimeException($e->getMessage(), 500);
        }

        return JwtService::generate([
            'userId' => $userId,
            'username' => $username,
            'role' => 'user',
            'position' => $position
        ]);
    }

    public function verifyNik(string $nik): array
    {
        $nik = trim($nik);

        if ($nik === '') {
            throw new RuntimeException('NIK harus diisi', 422);
        }

        $apiUrl = $_ENV['IDENTITY_API_URL'] . '/verify/' . $nik;
        $apiKey = $_ENV['IDENTITY_API_KEY'];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Key: $apiKey"]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            throw new RuntimeException('Gagal terhubung ke layanan verifikasi identitas', 502);
        }

        if ($httpCode === 404) {
            throw new RuntimeException('NIK tidak ditemukan di data identitas', 404);
        }

        if ($httpCode !== 200) {
            throw new RuntimeException('Gagal memverifikasi identitas', 502);
        }

        $identityData = json_decode($response, true);
        $identity = \is_array($identityData) ? ($identityData['data'] ?? null) : null;

        if (!\is_array($identity)) {
            throw new RuntimeException('Format data identitas dari layanan verifikasi tidak sesuai', 502);
        }

        $position = $identity['position'] ?? null;

        if (!\in_array($position, ['asn', 'non_asn', 'public'], true)) {
            throw new RuntimeException('Posisi (position) pengguna pada data identitas tidak valid', 422);
        }

        $fullName = isset($identity['name']) && \is_string($identity['name'])
            ? trim($identity['name'])
            : '';

        if ($fullName === '') {
            throw new RuntimeException('Nama pada data identitas tidak valid', 422);
        }

        return [
            'name' => $fullName,
            'address' => $identity['address'] ?? null,
            'position' => $position,
        ];
    }
}
