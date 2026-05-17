<?php

declare(strict_types=1);

namespace App\Features\Survey\Update;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

final class UpdateSurveyController
{
    private UpdateSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new UpdateSurveyRepository();
    }

    public function update(int $surveyId): void
    {
        AuthMiddleware::handle('admin_opd', 'superadmin');

        if (!$this->repository->surveyExists($surveyId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Survei tidak ditemukan'
            ], 404);
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $allowedFields = ['title', 'description', 'opens_at', 'closes_at'];
        $fields = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            Response::json([
                'status' => 'error',
                'message' => 'Tidak ada field yang diupdate'
            ], 422);
        }

        if (isset($fields['title']) && trim($fields['title']) === '') {
            Response::json([
                'status' => 'error',
                'message' => 'Judul survei tidak boleh kosong'
            ], 422);
        }

        $opensAt = $fields['opens_at'] ?? null;
        $closesAt = $fields['closes_at'] ?? null;

        if ($opensAt !== null && strtotime($opensAt) === false) {
            Response::json([
                'status' => 'error',
                'message' => 'Format opens_at tidak valid'
            ], 422);
        }

        if ($closesAt !== null && strtotime($closesAt) === false) {
            Response::json([
                'status' => 'error',
                'message' => 'Format closes_at tidak valid'
            ], 422);
        }

        if ($opensAt !== null && $closesAt !== null && strtotime($opensAt) >= strtotime($closesAt)) {
            Response::json([
                'status' => 'error',
                'message' => 'Waktu pembukaan (opens_at) harus lebih awal dari waktu penutupan (closes_at)'
            ], 422);
        }

        $this->repository->updateSurvey($surveyId, $fields);

        Response::json([
            'status' => 'success',
            'message' => 'Survei berhasil diperbarui'
        ]);
    }
}