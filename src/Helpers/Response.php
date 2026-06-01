<?php

declare(strict_types=1);

namespace App\Helpers;

final class Response
{
    /**
     * @param mixed $data
     * @param array<string, string> $headers
     */
    public static function json($data, int $code, array $headers = []): never
    {
        http_response_code($code);
        header('Content-Type: application/json');

        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        exit;
    }
}
