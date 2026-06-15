<?php

declare(strict_types=1);

namespace App\Features\Survey\Manage;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use RuntimeException;

final class ManageSurveyController
{
    private ManageSurveyService $service;

    public function __construct()
    {
        $this->service = new ManageSurveyService();
    }

    public function list(): void
    {
        PrivilegeService::require('survey:read');

        try {
            $surveys = $this->service->list($_GET);
        } catch (RuntimeException $e) {
            $statusCode = $e->getCode();

            if ($statusCode < 400 || $statusCode > 599) {
                $statusCode = 500;
            }

            Response::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $statusCode);
        }

        Response::json([
            'status' => 'success',
            'data' => $surveys
        ], 200);
    }
}
