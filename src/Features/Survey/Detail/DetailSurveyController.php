<?php

declare(strict_types=1);

namespace App\Features\Survey\Detail;

use App\Helpers\Response;
use App\Services\JwtService;

final class DetailSurveyController
{
    private DetailSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new DetailSurveyRepository();
    }

    public function detail(int $surveyId): void
    {
        $position = null;
        $token = JwtService::token();

        if ($token !== null) {
            $payload = JwtService::verify($token);

            if ($payload === null) {
                Response::json([
                    'status' => 'error',
                    'message' => 'Token tidak valid atau sudah kedaluwarsa'
                ], 401);
            }

            $position = $payload->data->position ?? null;
        }

        if (!$this->repository->canAccessSurvey($surveyId, $position)) {
            Response::json([
                'status' => 'error',
                'message' => 'Survei tidak ditemukan'
            ], 404);
        }
        
        $survey = $this->repository->getSurveyById($surveyId);

        if (!$survey) {
            Response::json([
                'status' => 'error',
                'message' => 'Survei tidak ditemukan'
            ], 404);
        }

        $survey['restrictions'] = $this->repository->getRestrictionsBySurveyId($surveyId);
        $pages = $this->repository->getPagesBySurveyId($surveyId);
        $questions = $this->repository->getQuestionsBySurveyId($surveyId);

        foreach ($questions as &$question) {
            $page = (int) $question['page'];
            $question['is_required'] = (bool) $question['is_required'];
            $question['options'] = $this->repository->getOptionsByQuestionId($question['id']);

            if (!isset($pages[$page])) {
                $pages[$page] = [
                    'page' => $page,
                    'section' => null,
                    'questions' => [],
                ];
            }

            $pages[$page]['questions'][] = $question;
        }

        unset($question);
        ksort($pages);

        $survey['questions'] = $questions;
        $survey['pages'] = array_values($pages);

        Response::json([
            'status' => 'success',
            'data' => $survey
        ]);
    }
}
