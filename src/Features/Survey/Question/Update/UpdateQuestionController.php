<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Update;

use App\Helpers\Response;
use App\Services\PermissionService;
use function in_array;

final class UpdateQuestionController
{
    private UpdateQuestionRepository $repository;

    public function __construct()
    {
        $this->repository = new UpdateQuestionRepository();
    }

    public function update(int $surveyId, int $questionId): void
    {
        PermissionService::require('survey_question:update');

        if (!$this->repository->questionBelongsToSurvey($questionId, $surveyId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Pertanyaan tidak ditemukan di survei ini'
            ], 404);
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $allowedFields = ['question_text', 'question_type', 'is_required', 'question_order', 'page', 'parent_option_id'];
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

        if (isset($fields['question_text']) && trim($fields['question_text']) === '') {
            Response::json([
                'status' => 'error',
                'message' => 'Teks pertanyaan tidak boleh kosong'
            ], 422);
        }

        $validTypes = ['free_text', 'radio_button', 'checkbox', 'dropdown', 'rating_scale', 'file_upload'];

        if (isset($fields['question_type']) && !in_array($fields['question_type'], $validTypes)) {
            Response::json([
                'status' => 'error',
                'message' => 'Tipe pertanyaan tidak valid'
            ], 422);
        }

        if (isset($fields['question_order']) && (int) $fields['question_order'] < 1) {
            Response::json([
                'status' => 'error',
                'message' => 'Value urutan pertanyaan (question_order) harus lebih dari 0'
            ], 422);
        }

        if (isset($fields['page']) && (int) $fields['page'] < 1) {
            Response::json([
                'status' => 'error',
                'message' => 'Value halaman (page) harus lebih dari 0'
            ], 422);
        }

        if (isset($fields['question_text'])) {
            $fields['question_text'] = trim($fields['question_text']);
        }

        if (isset($fields['is_required'])) {
            $fields['is_required'] = $this->normalizeIsRequired($fields['is_required']);
        }

        if (isset($fields['question_order'])) {
            $fields['question_order'] = (int) $fields['question_order'];
        }

        if (isset($fields['page'])) {
            $fields['page'] = (int) $fields['page'];
        }

        if (isset($fields['parent_option_id'])) {
            $fields['parent_option_id'] = (int) $fields['parent_option_id'];
        }

        $this->repository->updateQuestion($questionId, $fields);

        Response::json([
            'status' => 'success',
            'message' => 'Pertanyaan berhasil diperbarui'
        ]);
    }

    private function normalizeIsRequired(mixed $isRequired): bool
    {
        if (\is_bool($isRequired)) {
            return $isRequired;
        }

        if (\is_int($isRequired) && in_array($isRequired, [0, 1], true)) {
            return (bool) $isRequired;
        }

        if (\is_string($isRequired)) {
            $isRequired = strtolower(trim($isRequired));

            if (in_array($isRequired, ['1', 'true'], true)) {
                return true;
            }

            if (in_array($isRequired, ['0', 'false', ''], true)) {
                return false;
            }
        }

        Response::json([
            'status' => 'error',
            'message' => 'Field wajib diisi (is_required) harus berupa boolean'
        ], 422);
    }
}
