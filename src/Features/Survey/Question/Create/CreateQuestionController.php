<?php

declare(strict_types=1);

namespace App\Features\Survey\Question\Create;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

final class CreateQuestionController
{
    private CreateQuestionRepository $repository;

    public function __construct()
    {
        $this->repository = new CreateQuestionRepository();
    }

    public function create(int $surveyId): void
    {
        AuthMiddleware::handle('admin_opd', 'superadmin');

        if (!$this->repository->surveyExists($surveyId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Survei tidak ditemukan'
            ], 404);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $questionText = $data['question_text'] ?? null;
        $questionType = $data['question_type'] ?? null;

        if ($questionText === null || trim($questionText) === '') {
            Response::json([
                'status' => 'error',
                'message' => 'Teks pertanyaan harus diisi'
            ], 422);
        }

        $validTypes = ['free_text', 'radio_button', 'checkbox', 'dropdown', 'rating_scale', 'file_upload'];

        if ($questionType === null || !\in_array($questionType, $validTypes)) {
            Response::json([
                'status' => 'error',
                'message' => 'Tipe pertanyaan tidak valid'
            ], 422);
        }

        $page = isset($data['page']) ? (int) $data['page'] : 1;

        if ($page < 1) {
            Response::json([
                'status' => 'error',
                'message' => 'Page harus lebih dari 0'
            ], 422);
        }

        $parentOptionId = isset($data['parent_option_id']) ? (int) $data['parent_option_id'] : null;
        $questionOrder = $this->repository->getNextQuestionOrder($surveyId);

        $questionId = $this->repository->createQuestion(
            $surveyId,
            trim($questionText),
            $questionType,
            $questionOrder,
            $page,
            $parentOptionId
        );

        Response::json([
            'status' => 'success',
            'message' => 'Pertanyaan berhasil ditambahkan',
            'data' => ['id' => $questionId]
        ], 201);
    }
}