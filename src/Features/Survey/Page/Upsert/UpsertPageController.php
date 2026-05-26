<?php

declare(strict_types=1);

namespace App\Features\Survey\Page\Upsert;

use App\Helpers\Response;
use App\Services\PermissionService;

final class UpsertPageController
{
    private UpsertPageRepository $repository;

    public function __construct()
    {
        $this->repository = new UpsertPageRepository();
    }

    public function upsert(int $surveyId, int $page): void
    {
        PermissionService::require('survey_page:update');

        if ($page < 1) {
            Response::json([
                'status' => 'error',
                'message' => 'Value halaman (page) harus lebih dari 0'
            ], 422);
        }

        if (!$this->repository->surveyExists($surveyId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Survei tidak ditemukan'
            ], 404);
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!\is_array($data) || !\array_key_exists('section', $data)) {
            Response::json([
                'status' => 'error',
                'message' => 'Field section harus dikirim'
            ], 422);
        }

        $section = trim((string) $data['section']);
        $section = $section === '' ? null : $section;

        if ($section !== null && mb_strlen($section) > 255) {
            Response::json([
                'status' => 'error',
                'message' => 'Section maksimal 255 karakter'
            ], 422);
        }

        $this->repository->upsertPageSection($surveyId, $page, $section);

        Response::json([
            'status' => 'success',
            'message' => 'Section halaman survei berhasil diperbarui',
            'data' => [
                'page' => $page,
                'section' => $section,
            ]
        ]);
    }
}
