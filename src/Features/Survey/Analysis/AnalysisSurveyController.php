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
        $user = PrivilegeService::require('analytics:read');

        try {
            $analysis = $this->service->list($this->createdByScope($user), $_GET);
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
        $user = PrivilegeService::require('analytics:read');

        try {
            $analysis = $this->service->detail($this->createdByScope($user), $surveyId, $_GET);
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
        $user = PrivilegeService::require('analytics:read');

        try {
            $export = $this->service->export($this->createdByScope($user), $surveyId);
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

    /**
     * @param array<string, mixed> $user
     */
    private function createdByScope(array $user): ?int
    {
        return ($user['effective_role'] ?? null) === 'superadmin'
            ? null
            : (int) $user['id'];
    }

    private function error(RuntimeException $e, string $fallback): never
    {
        $code = $e->getCode();
        $statusCode = \is_int($code) && $code >= 400 && $code <= 599
            ? $code
            : 500;

        $message = $statusCode === 500
            ? $fallback
            : ($e->getMessage() ?: $fallback);

        Response::json([
            'status' => 'error',
            'message' => $message,
        ], $statusCode);
    }
}
