<?php

declare(strict_types=1);

namespace App\Features\Survey\Manage;

final class ManageSurveyService
{
    private const int DEFAULT_PAGE = 1;
    private const int DEFAULT_PER_PAGE = 10;
    private const int MAX_PER_PAGE = 50;
    private const array VALID_POSITIONS = ['asn', 'non_asn', 'public'];
    private const array VALID_SORT_FIELDS = ['opens_at', 'positions', 'status', 'title'];
    private const array VALID_SORT_DIRECTIONS = ['asc', 'desc'];
    private const array VALID_STATUSES = ['draft', 'upcoming', 'open', 'closed'];

    private ManageSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new ManageSurveyRepository();
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function list(array $query): array
    {
        $search = $this->normalizeSearch($query['search'] ?? '');
        $status = $this->normalizeOption($query['status'] ?? '', self::VALID_STATUSES);
        $position = $this->normalizeOption($query['position'] ?? '', self::VALID_POSITIONS);
        $sortBy = $this->normalizeOption($query['sort_by'] ?? '', self::VALID_SORT_FIELDS);
        $sortDirection = $this->normalizeOption(
            $query['sort_direction'] ?? '',
            self::VALID_SORT_DIRECTIONS
        );
        $perPage = $this->normalizePositiveInteger(
            $query['per_page'] ?? self::DEFAULT_PER_PAGE,
            self::DEFAULT_PER_PAGE,
            self::MAX_PER_PAGE
        );
        $total = $this->repository->countSurveys($search, $status, $position);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(
            $this->normalizePositiveInteger($query['page'] ?? self::DEFAULT_PAGE, self::DEFAULT_PAGE),
            $totalPages
        );
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_map(
                fn (array $survey): array => $this->formatSurvey($survey),
                $this->repository->getSurveys(
                    $search,
                    $status,
                    $position,
                    $sortBy,
                    $sortDirection ?? 'asc',
                    $perPage,
                    $offset
                )
            ),
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

        return mb_substr(trim($search), 0, 120);
    }

    /**
     * @param list<string> $validOptions
     */
    private function normalizeOption(mixed $value, array $validOptions): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return \in_array($value, $validOptions, true) ? $value : null;
    }

    private function normalizePositiveInteger(
        mixed $value,
        int $default,
        ?int $max = null
    ): int {
        if (\is_string($value)) {
            $value = trim($value);
        }

        if (!\is_int($value) && !(\is_string($value) && ctype_digit($value))) {
            return $default;
        }

        $value = (int) $value;

        if ($value <= 0) {
            return $default;
        }

        if ($max !== null && $value > $max) {
            return $max;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $survey
     * @return array<string, mixed>
     */
    private function formatSurvey(array $survey): array
    {
        $positions = isset($survey['positions']) && \is_string($survey['positions'])
            ? array_values(array_filter(explode(',', $survey['positions'])))
            : [];

        return [
            'id' => (int) $survey['id'],
            'title' => $survey['title'],
            'description' => $survey['description'],
            'instructions' => $survey['instructions'],
            'estimated_time' => $survey['estimated_time'] !== null
                ? (int) $survey['estimated_time']
                : null,
            'thumbnail_path' => $survey['thumbnail_path'],
            'status' => $survey['status'],
            'opens_at' => $survey['opens_at'],
            'closes_at' => $survey['closes_at'],
            'created_at' => $survey['created_at'],
            'updated_at' => $survey['updated_at'],
            'creator_name' => $survey['creator_name'],
            'question_count' => (int) $survey['question_count'],
            'response_count' => (int) $survey['response_count'],
            'positions' => $positions,
        ];
    }
}
