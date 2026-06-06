<?php

declare(strict_types=1);

namespace App\Features\Auth\Login;

use App\Services\JwtService;
use RuntimeException;

final class LoginService
{
    private LoginRepository $repository;

    public function __construct()
    {
        $this->repository = new LoginRepository();
    }

    public function login(array $data): string
    {
        $nik = isset($data['nik']) && \is_string($data['nik'])
            ? trim($data['nik'])
            : '';
        $username = isset($data['username']) && \is_string($data['username'])
            ? trim($data['username'])
            : '';
        $password = isset($data['password']) && \is_string($data['password'])
            ? $data['password']
            : '';

        if ($nik === '' && $username === '') {
            throw new RuntimeException('NIK/username harus diisi', 422);
        }

        if (trim($password) === '') {
            throw new RuntimeException('Password harus diisi', 422);
        }

        $identity = $nik !== ''
            ? $this->repository->getUserByNik($nik)
            : $this->repository->getUserByUsername($username);

        if (!$identity || !password_verify($password, $identity['password'])) {
            throw new RuntimeException('NIK/username atau password salah', 401);
        }

        return JwtService::generate([
            'userId' => $identity['id'],
            'username' => $identity['username'],
            'roleId' => $identity['role_id'],
            'role' => $identity['role'],
            'position' => $identity['position']
        ]);
    }
}
