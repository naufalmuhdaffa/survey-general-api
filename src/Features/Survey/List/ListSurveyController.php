<?php

declare(strict_types=1);

namespace App\Features\Survey\List;

use App\Helpers\Response;

final class ListSurveyController
{
    private ListSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new ListSurveyRepository();
    }

    public function list(): void
    {
        $surveys = $this->repository->getAllSurveys();

        Response::json([
            'status' => 'success',
            'data' => $surveys
        ]);
    }
}