<?php

declare(strict_types=1);

namespace App\Features\Survey\Question;

use App\Interfaces\RoutesInterface;
use App\Features\Survey\Question\Create\CreateQuestionRoutes;

final class QuestionRoutes implements RoutesInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (CreateQuestionRoutes::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}