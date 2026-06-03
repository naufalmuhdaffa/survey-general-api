<?php

declare(strict_types=1);

namespace App\Features\Survey\Detail;

use App\Services\JwtService;
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

        $survey = $this->repository->getSurveyById($surveyId);

        if (!$survey) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        $survey['restrictions'] = $this->repository->getRestrictionsBySurveyId($surveyId);

        return $survey;
    }
}
