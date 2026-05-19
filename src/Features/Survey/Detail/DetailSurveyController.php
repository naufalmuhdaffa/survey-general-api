<?php

declare(strict_types=1);

namespace App\Features\Survey\Detail;

use App\Helpers\Response;

final class DetailSurveyController
{
    private DetailSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new DetailSurveyRepository();
    }

    public function detail(int $surveyId): void
    {
        $survey = $this->repository->getSurveyById($surveyId);

        if (!$survey) {
            Response::json([
                'status' => 'error',
                'message' => 'Survei tidak ditemukan'
            ], 404);
        }

        $survey['restrictions'] = $this->repository->getRestrictionsBySurveyId($surveyId);
        $questions = $this->repository->getQuestionsBySurveyId($surveyId);

        foreach ($questions as &$question) {
            $question['options'] = $this->repository->getOptionsByQuestionId($question['id']);
        }

        $survey['questions'] = $questions;

        Response::json([
            'status' => 'success',
            'data' => $survey
        ]);
    }
}