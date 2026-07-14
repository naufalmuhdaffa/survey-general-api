<?php

declare(strict_types=1);

namespace App\Features\Survey\Analysis;

use App\Helpers\Response;
use App\Services\PrivilegeService;
use RuntimeException;

final class AnalysisSurveyController
{
    private AnalysisSurveyService $service;

    public function __construct()
    {
        $this->service = new AnalysisSurveyService();
    }

    public function list(): void
    {
        PrivilegeService::require('analytics:read');

        try {
            $analysis = $this->service->list($_GET);
        } catch (RuntimeException $e) {
            $this->error($e, 'Data analisis belum bisa dimuat.');
        }

        Response::json([
            'status' => 'success',
            'data' => $analysis,
        ], 200);
    }

    public function detail(int $surveyId): void
    {
        PrivilegeService::require('analytics:read');

        try {
            $analysis = $this->service->detail($surveyId, $_GET);
        } catch (RuntimeException $e) {
            $this->error($e, 'Detail analisis belum bisa dimuat.');
        }

        Response::json([
            'status' => 'success',
            'data' => $analysis,
        ], 200);
    }

    public function export(int $surveyId): void
    {
        PrivilegeService::require('analytics:read');

        try {
            $export = $this->service->export($surveyId);
        } catch (RuntimeException $e) {
            $this->error($e, 'Data export belum bisa dibuat.');
        }

        http_response_code(200);
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo "\xEF\xBB\xBF" . $export['content'];
        exit;
    }

    private function error(RuntimeException $e, string $fallback): never
    {
        $statusCode = $e->getCode();

        if ($statusCode < 400 || $statusCode > 599) {
            $statusCode = 500;
        }

        Response::json([
            'status' => 'error',
            'message' => $e->getMessage() ?: $fallback,
        ], $statusCode);
    }
}
