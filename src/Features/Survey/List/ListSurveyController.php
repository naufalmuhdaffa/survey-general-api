<?php

declare(strict_types=1);

namespace App\Features\Survey\List;

use App\Helpers\Response;
use App\Services\JwtService;

final class ListSurveyController
{
    private ListSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new ListSurveyRepository();
    }

    public function list(): void
    {
        $position = null;
        $token = JwtService::bearerToken();

        if ($token !== null) {
            $payload = JwtService::verify($token);

            if ($payload === null) {
                Response::json([
                    'status' => 'error',
                    'message' => 'Token tidak valid atau sudah kedaluwarsa'
                ], 401);
            }

            $position = $payload->data->position ?? null; // null = belum login
        }

        $surveys = $this->repository->getAllSurveys($position);

        Response::json([
            'status' => 'success',
            'data' => $surveys
        ]);
    }
}