<?php

declare(strict_types=1);

namespace App\Features\Auth\Login;

use App\Helpers\Response;
use App\Services\JwtService;

final class LoginController
{
    private LoginRepository $repository;

    public function __construct()
    {
        $this->repository = new LoginRepository();
    }

    public function login(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $nik = trim($data['nik'] ?? '');
        $username = trim($data['username'] ?? '');

        if ($nik === '' && $username === '') {
            Response::json([
                'status' => 'error',
                'message' => 'NIK/username harus diisi'
            ], 422);
        }

        if (!isset($data['password']) || trim($data['password']) === '') {
            Response::json([
                'status' => 'error',
                'message' => 'Password harus diisi'
            ], 422);
        }

        $password = $data['password'];

        $identity = $nik !== ''
            ? $this->repository->getUserByNik($nik)
            : $this->repository->getUserByUsername($username);

        if (!$identity) {
            Response::json([
                'status' => 'error',
                'message' => 'NIK/username atau password salah'
            ], 401);
        }

        if (!$identity['is_active']) {
            Response::json([
                'status' => 'error',
                'message' => 'Akun Anda telah dinonaktifkan'
            ], 403);
        }

        if (!password_verify($password, $identity['password'])) {
            Response::json([
                'status' => 'error',
                'message' => 'NIK/username atau password salah'
            ], 401);
        }

        $token = JwtService::generate([
            'userId' => $identity['id'],
            'username' => $identity['username'],
            'role' => $identity['role'],
            'position' => $identity['position']
        ]);

        Response::json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'token' => $token
        ], 200);
    }
}