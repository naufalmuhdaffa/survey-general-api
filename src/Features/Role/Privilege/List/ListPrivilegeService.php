<?php

declare(strict_types=1);

namespace App\Features\Role\Privilege\List;

final class ListPrivilegeService
{
    private ListPrivilegeRepository $repository;

    public function __construct()
    {
        $this->repository = new ListPrivilegeRepository();
    }

    public function list(): array
    {
        return $this->repository->getPrivileges();
    }
}
