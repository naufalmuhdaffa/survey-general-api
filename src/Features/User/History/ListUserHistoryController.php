<?php

declare(strict_types=1);

namespace App\Features\User\History;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use RuntimeException;

final class ListUserHistoryController
{
    private ListUserHistoryService $service;

    public function __construct()
    {
        $this->service = new ListUserHistoryService();
    }

    public function list(): void
    {
        $user = AuthMiddleware::handle();
        $userId = (int) $user['id'];

        try {
            $history = $this->service->list($userId, $_GET);
        } catch (RuntimeException $e) {
            $statusCode = $e->getCode();

            if ($statusCode < 400 || $statusCode > 599) {
                throw $e;
            }

            Response::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $statusCode);
        }

        Response::json([
            'status' => 'success',
            'data' => $history
        ], 200);
    }
}
