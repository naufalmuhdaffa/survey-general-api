<?php

declare(strict_types=1);

namespace App\Features\Survey\List;

use App\Helpers\Response;
use RuntimeException;

final class ListSurveyController
{
    private ListSurveyService $service;

    public function __construct()
    {
        $this->service = new ListSurveyService();
    }

    public function list(): void
    {
        try {
            $surveys = $this->service->getAllSurveys($_GET);
        } catch (RuntimeException $e) {
            $statusCode = $e->getCode();

            if ($statusCode < 400 || $statusCode > 599) {
                throw $e;
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
