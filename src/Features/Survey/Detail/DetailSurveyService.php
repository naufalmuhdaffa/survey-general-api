<?php

declare(strict_types=1);

namespace App\Features\Survey\Detail;

use RuntimeException;

final class DetailSurveyService
{
    private DetailSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new DetailSurveyRepository();
    }

    public function getDetail(int $surveyId): array
    {
        $survey = $this->repository->getSurveyById($surveyId);

        if (!$survey) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        $survey['restrictions'] = $this->repository->getRestrictionsBySurveyId($surveyId);
        $survey['pages'] = $this->repository->getPagesWithQuestionsBySurveyId($surveyId);

        return $survey;
    }
}
