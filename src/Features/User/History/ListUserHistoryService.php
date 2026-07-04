<?php

declare(strict_types=1);

namespace App\Features\User\History;

final class ListUserHistoryService
{
    private const int DEFAULT_PER_PAGE = 5;
    private const int MAX_SEARCH_LENGTH = 120;
    private const array VALID_PER_PAGES = [5, 10, 25, 50, 100];
    private const array VALID_POSITIONS = ['asn', 'non_asn', 'public'];
    private const array VALID_SORTS = ['newest', 'oldest'];
    private const array VALID_STATUSES = ['draft', 'submitted'];

    private ListUserHistoryRepository $repository;

    public function __construct()
    {
        $this->repository = new ListUserHistoryRepository();
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function list(int $userId, array $query = []): array
    {
        $search = $this->normalizeSearch($query['search'] ?? '');
        $position = $this->normalizeOption($query['position'] ?? '', self::VALID_POSITIONS);
        $status = $this->normalizeOption($query['status'] ?? '', self::VALID_STATUSES);
        $sort = $this->normalizeSort($query['sort'] ?? 'newest');
        $page = $this->normalizePositiveInteger($query['page'] ?? 1, 1);
        $perPage = $this->normalizePerPage($query['per_page'] ?? self::DEFAULT_PER_PAGE);
        $total = $this->repository->countHistory($userId, $search, $position, $status);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_map(
                fn (array $history): array => $this->formatHistory($history),
                $this->repository->getHistory(
                    $userId,
                    $search,
                    $position,
                    $status,
                    $sort,
                    $perPage,
                    $offset,
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

    private function normalizeSort(mixed $sort): string
    {
        if (!\is_string($sort)) {
            return 'newest';
        }

        $normalizedSort = strtolower(trim($sort));

        return \in_array($normalizedSort, self::VALID_SORTS, true)
            ? $normalizedSort
            : 'newest';
    }

    /**
     * @param array<string, mixed> $history
     * @return array<string, mixed>
     */
    private function formatHistory(array $history): array
    {
        $positions = isset($history['positions']) && \is_string($history['positions'])
            ? array_values(array_filter(explode(',', $history['positions'])))
            : [];

        return [
            'response_id' => (int) $history['response_id'],
            'survey_id' => (int) $history['survey_id'],
            'title' => $history['title'],
            'description' => $history['description'],
            'estimated_time' => $history['estimated_time'],
            'thumbnail_path' => $history['thumbnail_path'],
            'survey_status' => $history['survey_status'],
            'opens_at' => $history['opens_at'],
            'closes_at' => $history['closes_at'],
            'positions' => $positions,
            'response_status' => $history['response_status'],
            'current_page' => (int) ($history['current_page'] ?? 0),
            'submitted_at' => $history['submitted_at'],
            'response_created_at' => $history['response_created_at'],
            'response_updated_at' => $history['response_updated_at'],
        ];
    }
}
