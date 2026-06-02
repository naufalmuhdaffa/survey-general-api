<?php

declare(strict_types=1);

namespace App\Features\Survey\List;

use App\Services\JwtService;
use RuntimeException;

final class ListSurveyService
{
    private ListSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new ListSurveyRepository();
    }

    public function getAllSurveys(): array
    {
        $position = null;
        $token = JwtService::token();

        if ($token !== null) {
            $payload = JwtService::verify($token);

            if ($payload === null) {
                throw new RuntimeException('Token tidak valid atau sudah kedaluwarsa', 401);
            }

            $position = $payload->data->position ?? null;
        }

        return $this->repository->getAllSurveys($position);
    }
}
