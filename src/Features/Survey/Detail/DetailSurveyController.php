<?php

declare(strict_types=1);

namespace App\Features\Survey\Detail;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use RuntimeException;

final class DetailSurveyController
{
    private DetailSurveyService $service;

    public function __construct()
    {
        $this->service = new DetailSurveyService();
    }

    public function detail(int $surveyId): void
    {
        PrivilegeService::require('survey:read');

        try {
            $survey = $this->service->getDetail($surveyId);
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
            'data' => $survey
        ], 200);
    }
}
