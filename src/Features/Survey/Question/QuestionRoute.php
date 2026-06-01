<?php

declare(strict_types=1);

namespace App\Features\Survey\Question;

use App\Interfaces\RouteInterface;
use App\Features\Survey\Question\Create\CreateQuestionRoute;
use App\Features\Survey\Question\Option\OptionRoute;
use App\Features\Survey\Question\Update\UpdateQuestionRoute;
use App\Features\Survey\Question\Delete\DeleteQuestionRoute;

final class QuestionRoute implements RouteInterface
{
    public static function dispatch(string $path, string $method, array $segments): bool
    {
        if (CreateQuestionRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (OptionRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (UpdateQuestionRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (DeleteQuestionRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}