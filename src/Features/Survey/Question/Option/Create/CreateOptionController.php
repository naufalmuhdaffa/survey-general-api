<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Option\Create;

use App\Helpers\Response;
use App\Services\PermissionService;

final class CreateOptionController
{
    private CreateOptionRepository $repository;

    public function __construct()
    {
        $this->repository = new CreateOptionRepository();
    }

    public function create(int $surveyId, int $questionId): void
    {
        PermissionService::require('survey:update');

        if (!$this->repository->questionExists($questionId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Pertanyaan tidak ditemukan'
            ], 404);
        }

        if (!$this->repository->questionBelongsToSurvey($questionId, $surveyId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Pertanyaan tidak ditemukan di survei ini'
            ], 404);
        }

        if (!$this->repository->questionTypeAllowsOptions($questionId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Tipe pertanyaan ini tidak mendukung opsi jawaban'
            ], 422);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $optionText = $data['option_text'] ?? null;

        if ($optionText === null || trim($optionText) === '') {
            Response::json([
                'status' => 'error',
                'message' => 'Teks opsi jawaban harus diisi'
            ], 422);
        }

        $optionOrder = $this->repository->getNextOptionOrder($questionId);
        $optionId = $this->repository->createOption($questionId, trim($optionText), $optionOrder);

        Response::json([
            'status' => 'success',
            'message' => 'Opsi jawaban berhasil ditambahkan',
            'data' => ['id' => $optionId]
        ], 201);
    }
}
