<?php

declare(strict_types=1);

namespace App\Features\Survey\List;

use App\Services\JwtService;
use RuntimeException;

final class ListSurveyService
{
    private const int DEFAULT_PER_PAGE = 5;
    private const int MAX_SEARCH_LENGTH = 120;
    private const array VALID_PER_PAGES = [5, 10, 25, 50, 100];
    private const array VALID_POSITIONS = ['asn', 'non_asn', 'public'];
    private const array VALID_STATUSES = ['open', 'upcoming'];

    private ListSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new ListSurveyRepository();
    }

    public function getAllSurveys(array $query = []): array
    {
        $accountPosition = null;
        $userId = null;
        $token = JwtService::token();

        if ($token !== null) {
            $payload = JwtService::verify($token);

            if ($payload === null) {
                throw new RuntimeException('Token tidak valid atau sudah kedaluwarsa', 401);
            }

            $accountPosition = $payload->data->position ?? null;
            $userId = isset($payload->data->userId) && \is_numeric($payload->data->userId)
                ? (int) $payload->data->userId
                : null;
        }

        $search = $this->normalizeSearch($query['search'] ?? '');
        $status = $this->normalizeOption($query['status'] ?? '', self::VALID_STATUSES);
        $position = $this->normalizeOption($query['position'] ?? '', self::VALID_POSITIONS);
        $page = $this->normalizePositiveInteger($query['page'] ?? 1, 1);
        $perPage = $this->normalizePerPage($query['per_page'] ?? self::DEFAULT_PER_PAGE);
        $total = $this->repository->countSurveys(
            $search,
            $status,
            $position,
            $this->normalizeOption($accountPosition, self::VALID_POSITIONS),
        );
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $surveys = $this->repository->getAllSurveys(
            $search,
            $status,
            $position,
            $this->normalizeOption($accountPosition, self::VALID_POSITIONS),
            $userId,
            $perPage,
            $offset,
        );

        return [
            'items' => array_map([$this, 'formatSurvey'], $surveys),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }

    private function normalizeSearch(mixed $search): string
    {
        if (!\is_string($search)) {
            return '';
        }

        return mb_substr(trim($search), 0, self::MAX_SEARCH_LENGTH);
    }

    private function normalizeOption(mixed $value, array $allowedValues): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $normalizedValue = strtolower(trim($value));

        return \in_array($normalizedValue, $allowedValues, true)
            ? $normalizedValue
            : null;
    }

    private function normalizePositiveInteger(mixed $value, int $fallback): int
    {
        if (\is_numeric($value)) {
            $number = (int) $value;

            return $number > 0 ? $number : $fallback;
        }

        return $fallback;
    }

    private function normalizePerPage(mixed $perPage): int
    {
        $normalizedPerPage = $this->normalizePositiveInteger(
            $perPage,
            self::DEFAULT_PER_PAGE,
        );

        return \in_array($normalizedPerPage, self::VALID_PER_PAGES, true)
            ? $normalizedPerPage
            : self::DEFAULT_PER_PAGE;
    }

    private function formatSurvey(array $survey): array
    {
        $positions = isset($survey['positions']) && \is_string($survey['positions'])
            ? array_values(array_filter(explode(',', $survey['positions'])))
            : [];

        return [
            'id' => $survey['id'],
            'title' => $survey['title'],
            'description' => $survey['description'],
            'estimated_time' => $survey['estimated_time'],
            'thumbnail_path' => $survey['thumbnail_path'],
            'status' => $survey['status'],
            'opens_at' => $survey['opens_at'],
            'closes_at' => $survey['closes_at'],
            'positions' => $positions,
            'user_response_status' => $survey['user_response_status'] ?? null,
            'user_response_submitted_at' => $survey['user_response_submitted_at'] ?? null,
            'user_response_current_page' => isset($survey['user_response_current_page'])
                ? (int) $survey['user_response_current_page']
                : null,
        ];
    }
}
