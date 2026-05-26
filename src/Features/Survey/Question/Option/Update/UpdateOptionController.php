<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option\Update;

use App\Helpers\Response;
use App\Services\PermissionService;

final class UpdateOptionController
{
    private UpdateOptionRepository $repository;

    public function __construct()
    {
        $this->repository = new UpdateOptionRepository();
    }

    public function update(int $surveyId, int $questionId, int $optionId): void
    {
        PermissionService::require('survey_option:update');

        if (!$this->repository->optionExists($optionId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Opsi jawaban tidak ditemukan'
            ], 404);
        }

        if (!$this->repository->questionBelongsToSurvey($questionId, $surveyId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Pertanyaan tidak ditemukan di survei ini'
            ], 404);
        }

        if (!$this->repository->optionBelongsToQuestion($optionId, $questionId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Opsi jawaban tidak ditemukan di pertanyaan ini'
            ], 404);
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $allowedFields = ['option_text', 'option_order'];
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

        if (isset($fields['option_text']) && trim($fields['option_text']) === '') {
            Response::json([
                'status' => 'error',
                'message' => 'Teks opsi jawaban tidak boleh kosong'
            ], 422);
        }

        if (isset($fields['option_order']) && (int) $fields['option_order'] < 1) {
            Response::json([
                'status' => 'error',
                'message' => 'Value urutan opsi (option_order) harus lebih dari 0'
            ], 422);
        }

        if (isset($fields['option_text'])) {
            $fields['option_text'] = trim($fields['option_text']);
        }

        if (isset($fields['option_order'])) {
            $fields['option_order'] = (int) $fields['option_order'];
        }

        $this->repository->updateOption($optionId, $fields);

        Response::json([
            'status' => 'success',
            'message' => 'Opsi jawaban berhasil diperbarui'
        ]);
    }
}
