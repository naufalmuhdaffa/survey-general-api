<?php

declare(strict_types=1);

namespace App\Features\Survey\Form;

use App\Services\JwtService;
use RuntimeException;

final class FormSurveyService
{
    private FormSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new FormSurveyRepository();
    }

    public function getForm(int $surveyId): array
    {
        $position = null;
        $token = JwtService::token();

        if ($token !== null) {
            $payload = JwtService::verify($token);

            if ($payload === null) {
                throw new RuntimeException('Token tidak valid atau sudah kedaluwarsa', 401);
            }

            $position = $payload->data->position ?? null;
        }

        if (!$this->repository->canAccessSurvey($surveyId, $position)) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        $pages = $this->repository->getPagesBySurveyId($surveyId);
        $questions = $this->repository->getQuestionsBySurveyId($surveyId);
        $optionsByQuestionId = $this->repository->getOptionsByQuestionIds(
            array_column($questions, 'id')
        );

        foreach ($questions as $question) {
            $page = (int) $question['page'];
            $questionId = (int) $question['id'];
            $question['is_required'] = (bool) $question['is_required'];
            $question['options'] = $optionsByQuestionId[$questionId] ?? [];

            if (!isset($pages[$page])) {
                $pages[$page] = [
                    'page' => $page,
                    'section' => null,
                    'questions' => [],
                ];
            }

            $pages[$page]['questions'][] = $question;
        }

        ksort($pages);

        return [
            'survey_id' => $surveyId,
            'pages' => array_values($pages),
        ];
    }
}
