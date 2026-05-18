<?php

declare(strict_types=1);

namespace App\Features\Survey\Question;

use App\Interfaces\RoutesInterface;
use App\Features\Survey\Question\Create\CreateQuestionRoutes;
use App\Features\Survey\Question\Option\OptionRoutes;
use App\Features\Survey\Question\Update\UpdateQuestionRoutes;

final class QuestionRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (CreateQuestionRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (OptionRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        if (UpdateQuestionRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}