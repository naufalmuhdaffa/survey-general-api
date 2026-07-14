<?php

declare(strict_types=1);

namespace App\Features\Survey;

use App\Features\Survey\Analysis\AnalysisSurveyRoute;
use App\Features\Survey\Create\CreateSurveyRoute;
use App\Features\Survey\List\ListSurveyRoute;
use App\Features\Survey\Manage\ManageSurveyRoute;
use App\Features\Survey\Page\PageRoute;
use App\Features\Survey\Question\QuestionRoute;
use App\Features\Survey\Response\ResponseRoute;
use App\Features\Survey\Thumbnail\ThumbnailRoute;
use App\Features\Survey\Detail\DetailSurveyRoute;
use App\Features\Survey\Form\FormSurveyRoute;
use App\Features\Survey\Update\UpdateSurveyRoute;
use App\Features\Survey\Delete\DeleteSurveyRoute;
use App\Interfaces\RouteInterface;

final class SurveyRoute implements RouteInterface
{
    public static function dispatch(
        string $path,
        string $method,
        array $segments
    ): bool {
        if (CreateSurveyRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (ListSurveyRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (ManageSurveyRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (AnalysisSurveyRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (QuestionRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (PageRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (ResponseRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (ThumbnailRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (FormSurveyRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (DetailSurveyRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (UpdateSurveyRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        if (DeleteSurveyRoute::dispatch($path, $method, $segments)) {
            return true;
        }

        return false;
    }
}
