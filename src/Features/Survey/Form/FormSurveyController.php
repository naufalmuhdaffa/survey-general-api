<?php

declare(strict_types=1);

namespace App\Features\Survey\Form;

use App\Helpers\Response;
use RuntimeException;

final class FormSurveyController
{
    private FormSurveyService $service;

    public function __construct()
    {
        $this->service = new FormSurveyService();
    }

    public function form(int $surveyId): void
    {
        try {
            $form = $this->service->getForm($surveyId);
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
            'data' => $form
        ], 200);
    }
}
