<?php

declare(strict_types=1);

namespace App\Features\Survey;

use App\Features\Survey\Create\CreateSurveyRoutes;
use App\Features\Survey\List\ListSurveyRoutes;
use App\Features\Survey\Question\QuestionRoutes;
use App\Features\Survey\Detail\DetailSurveyRoutes;
use App\Features\Survey\Update\UpdateSurveyRoutes;
use App\Interfaces\RoutesInterface;

final class SurveyRoutes implements RoutesInterface
{
    public static function dispatch(
        string $path,
        string $method,
        array $segments
    ): bool {
        if (CreateSurveyRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (ListSurveyRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (QuestionRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (DetailSurveyRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (UpdateSurveyRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}