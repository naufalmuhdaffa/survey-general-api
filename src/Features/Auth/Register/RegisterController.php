<?php

declare(strict_types=1);

namespace App\Features\Auth\Register;

use App\Helpers\Response;
use App\Services\CookieService;
use App\Services\JwtService;

final class RegisterController
{
    private RegisterRepository $repository;

    public function __construct()
    {
        $this->repository = new RegisterRepository();
    }

    public function register(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['nik']) || trim($data['nik']) === '') {
            Response::json([
                'status' => 'error',
                'message' => 'NIK harus diisi'
            ], 422);
        }

        $nik = trim($data['nik']);

        if (!isset($data['fullName']) || trim($data['fullName']) === '') {
            Response::json([
                'status' => 'error',
                'message' => 'Nama lengkap harus diisi'
            ], 422);
        }

        $fullName = trim($data['fullName']);

        if (!isset($data['username']) || trim($data['username']) === '') {
            Response::json([
                'status' => 'error',
                'message' => 'Username harus diisi'
            ], 422);
        }

        $username = strtolower(trim($data['username']));

        if (!isset($data['password']) || trim($data['password']) === '') {
            Response::json([
                'status' => 'error',
                'message' => 'Password harus diisi'
            ], 422);
        }

        $password = $data['password'];

        if (!$nik || !$fullName || !$username || !$password) {
            Response::json([
                'status' => 'error',
                'message' => 'Silakan isi semua input terlebih dulu'
            ], 422);
        }

        if (!ctype_digit($nik) || mb_strlen($nik) !== 16) {
            Response::json([
                'status' => 'error',
                'message' => 'NIK harus 16 digit angka'
            ], 422);
        }

        if (mb_strlen($username) > 25) {
            Response::json([
                'status' => 'error',
                'message' => 'Username maksimal 25 karakter'
            ], 422);
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            Response::json([
                'status' => 'error',
                'message' => 'Username hanya boleh huruf, angka, dan underscore'
            ], 400);
        }

        if (mb_strlen($password) < 6) {
            Response::json([
                'status' => 'error',
                'message' => 'Password minimal 6 karakter'
            ], 422);
        }

        if (mb_strlen($password) > 255) {
            Response::json([
                'status' => 'error',
                'message' => 'Password maksimal 255 karakter'
            ], 422);
        }

        $data = $this->repository->getUserByNik($nik);

        if ($data) {
            Response::json([
                'status' => 'error',
                'message' => 'NIK sudah terdaftar'
            ], 409);
        }

        $data = $this->repository->getUserByUsername($username);

        if ($data) {
            Response::json([
                'status' => 'error',
                'message' => 'Username sudah terdaftar'
            ], 409);
        }

        $apiUrl = $_ENV['EMPLOYEE_API_URL'] . '/verify/' . $nik;
        $apiKey = $_ENV['EMPLOYEE_API_KEY'];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Key: $apiKey"]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close($ch);

        if ($response === false) {
            Response::json([
                'status' => 'error',
                'message' => 'Gagal menghubungi layanan verifikasi NIK'
            ], 500);
        }

        if ($httpCode === 404) {
            Response::json([
                'status' => 'error',
                'message' => 'NIK tidak ditemukan di data referensi'
            ], 404);
        }

        if ($httpCode !== 200) {
            Response::json([
                'status' => 'error',
                'message' => 'Gagal verifikasi NIK'
            ], 500);
        }

        $employeeData = json_decode($response, true);

        $position = $employeeData['data']['position'] ?? null;
        $validPositions = ['asn', 'non_asn', 'public'];

        if (!\in_array($position, $validPositions, true)) {
            Response::json([
                'status' => 'error',
                'message' => 'Data posisi NIK tidak valid'
            ], 422);
        }

        $verifiedFullName = trim($employeeData['data']['name'] ?? '');

        if ($verifiedFullName === '') {
            Response::json([
                'status' => 'error',
                'message' => 'Data nama dari verifikasi NIK tidak valid'
            ], 422);
        }

        try {
            $userId = $this->repository->registerUser(
                $nik,
                $verifiedFullName,
                $username,
                $password,
                $position
            );
        } catch (\RuntimeException $e) {
            Response::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }

        $token = JwtService::generate([
            'userId' => $userId,
            'username' => $username,
            'role' => 'user',
            'position' => $position
        ]);
        CookieService::setToken($token);

        Response::json([
            'status' => 'success',
            'message' => 'Registrasi berhasil'
        ], 201);
    }

    public function verifyNik(string $nik): void
    {
        if (trim($nik) === '') {
            Response::json([
                'status' => 'error',
                'message' => 'NIK harus diisi'
            ], 422);
        }

        $apiUrl = $_ENV['EMPLOYEE_API_URL'] . '/verify/' . $nik;
        $apiKey = $_ENV['EMPLOYEE_API_KEY'];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Key: $apiKey"]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close($ch);

        if ($httpCode === 404) {
            Response::json([
                'status' => 'error',
                'message' => 'NIK tidak ditemukan di data referensi'
            ], 404);
        }

        if ($httpCode !== 200) {
            Response::json([
                'status' => 'error',
                'message' => 'Gagal verifikasi NIK'
            ], 500);
        }

        $employeeData = json_decode($response, true);

        Response::json([
            'status' => 'success',
            'data' => [
                'name' => $employeeData['data']['name'],
                'address' => $employeeData['data']['address'],
                'position' => $employeeData['data']['position'],
            ]
        ], 200);
    }
}
