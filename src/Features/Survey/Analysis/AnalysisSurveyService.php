<?php

declare(strict_types=1);

namespace App\Features\Survey\Analysis;

use DateTimeImmutable;
use RuntimeException;

final class AnalysisSurveyService
{
    private const int DEFAULT_PER_PAGE = 5;
    private const int RESPONDENT_PER_PAGE = 5;
    private const int MAX_PER_PAGE = 100;
    private const array VALID_STATUSES = ['draft', 'upcoming', 'open', 'closed'];

    private AnalysisSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new AnalysisSurveyRepository();
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function list(array $query): array
    {
        $search = $this->normalizeSearch($query['search'] ?? '');
        $status = $this->normalizeOption($query['status'] ?? '', self::VALID_STATUSES);
        $year = $this->normalizeYear($query['year'] ?? date('Y'));
        $perPage = $this->normalizePositiveInteger($query['per_page'] ?? self::DEFAULT_PER_PAGE, self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE);
        $total = $this->repository->countSurveys($search, $status, $year);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($this->normalizePositiveInteger($query['page'] ?? 1, 1), $totalPages);
        $offset = ($page - 1) * $perPage;

        return [
            'summary' => $this->formatSummary($this->repository->getSummary($year)),
            'available_years' => $this->formatAvailableYears($this->repository->getAvailableYears(), $year),
            'selected_year' => $year,
            'response_volume' => $this->buildResponseVolume($year),
            'items' => array_map(
                fn (array $survey): array => $this->formatSurveyRow($survey),
                $this->repository->getSurveys($search, $status, $year, $perPage, $offset),
            ),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function detail(int $surveyId, array $query): array
    {
        $survey = $this->repository->getSurveyById($surveyId);

        if (!$survey) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        $search = $this->normalizeSearch($query['search'] ?? '');
        $perPage = $this->normalizePositiveInteger($query['per_page'] ?? self::RESPONDENT_PER_PAGE, self::RESPONDENT_PER_PAGE, self::MAX_PER_PAGE);
        $totalRespondents = $this->repository->countSubmittedResponses($surveyId);
        $filteredRespondents = $this->repository->countSubmittedResponses($surveyId, $search);
        $totalPages = max(1, (int) ceil($filteredRespondents / $perPage));
        $page = min($this->normalizePositiveInteger($query['page'] ?? 1, 1), $totalPages);
        $offset = ($page - 1) * $perPage;
        $respondents = $this->repository->getRespondents($surveyId, $search, $perPage, $offset);
        $answers = $this->groupAnswersByResponse(
            $this->repository->getAnswersByResponseIds(array_column($respondents, 'response_id')),
        );

        return [
            'survey' => $this->formatSurveyDetail($survey),
            'summary' => [
                'total_respondents' => $totalRespondents,
                'status' => $survey['status'],
                'audience' => $this->formatAudience($this->normalizePositions($survey['positions'] ?? '')),
            ],
            'respondents' => [
                'items' => array_map(
                    fn (array $respondent): array => $this->formatRespondent(
                        $respondent,
                        $answers[(int) $respondent['response_id']] ?? [],
                    ),
                    $respondents,
                ),
                'meta' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $filteredRespondents,
                    'total_pages' => $totalPages,
                ],
            ],
            'pages' => $this->formatPages(
                $this->repository->getPagesWithQuestions($surveyId),
                $this->repository->getOptionAnswerCounts($surveyId),
                $this->repository->getFreeTextAnswers($surveyId),
                $totalRespondents,
            ),
        ];
    }

    /**
     * @return array{filename: string, content: string}
     */
    public function export(int $surveyId): array
    {
        $survey = $this->repository->getSurveyById($surveyId);

        if (!$survey) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        $responses = $this->repository->getAllSubmittedResponses($surveyId);
        $pages = $this->repository->getPagesWithQuestions($surveyId);
        $questions = [];

        foreach ($pages as $page) {
            foreach ($page['questions'] ?? [] as $question) {
                $questions[(int) $question['id']] = $question['question_text'];
            }
        }

        $answers = $this->groupAnswersByResponse(
            $this->repository->getAnswersByResponseIds(array_column($responses, 'response_id')),
        );

        $rows = [];
        $rows[] = array_merge(['Nama Responden', 'NIK', 'Tanggal Submit'], array_values($questions));

        foreach ($responses as $response) {
            $answerByQuestion = [];

            foreach ($answers[(int) $response['response_id']] ?? [] as $answer) {
                $questionId = (int) $answer['question_id'];
                $answerByQuestion[$questionId][] = $answer['value'];
            }

            $row = [
                $response['full_name'],
                $response['nik'],
                $response['submitted_at'],
            ];

            foreach (array_keys($questions) as $questionId) {
                $row[] = implode('; ', $answerByQuestion[$questionId] ?? []);
            }

            $rows[] = $row;
        }

        $filename = $this->slugify((string) $survey['title']) . '-responses.xls';

        return [
            'filename' => $filename,
            'content' => $this->toTabSeparatedValues($rows),
        ];
    }

    private function buildResponseVolume(int $year): array
    {
        $rawVolume = $this->repository->getResponseVolume($year);
        $volumeByMonth = [];

        foreach ($rawVolume as $item) {
            $volumeByMonth[(int) $item['month']] = (int) $item['total'];
        }

        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $date = new DateTimeImmutable(sprintf('%d-%02d-01 00:00:00', $year, $month));

            $months[] = [
                'period' => $date->format('Y-m'),
                'label' => $this->monthLabel($month),
                'total' => $volumeByMonth[$month] ?? 0,
            ];
        }

        return $months;
    }

    private function formatAvailableYears(array $years, int $selectedYear): array
    {
        $availableYears = array_map(
            static fn (array $year): int => (int) ($year['year_value'] ?? 0),
            $years,
        );
        $availableYears = array_values(array_filter($availableYears, static fn (int $year): bool => $year > 0));

        if (!\in_array($selectedYear, $availableYears, true)) {
            $availableYears[] = $selectedYear;
        }

        rsort($availableYears);

        return array_values(array_unique($availableYears));
    }

    private function formatSummary(array $summary): array
    {
        return [
            'total_surveys' => (int) ($summary['total_surveys'] ?? 0),
            'active_surveys' => (int) ($summary['active_surveys'] ?? 0),
            'closed_surveys' => (int) ($summary['closed_surveys'] ?? 0),
            'total_respondents' => (int) ($summary['total_respondents'] ?? 0),
        ];
    }

    private function formatSurveyRow(array $survey): array
    {
        return [
            'id' => (int) $survey['id'],
            'title' => $survey['title'],
            'description' => $survey['description'],
            'opd_pengampu' => $survey['opd_pengampu'],
            'opens_at' => $survey['opens_at'],
            'closes_at' => $survey['closes_at'],
            'status' => $survey['status'],
            'response_count' => (int) $survey['response_count'],
            'positions' => $this->normalizePositions($survey['positions'] ?? ''),
        ];
    }

    private function formatSurveyDetail(array $survey): array
    {
        return [
            'id' => (int) $survey['id'],
            'title' => $survey['title'],
            'description' => $survey['description'],
            'opd_pengampu' => $survey['opd_pengampu'],
            'opens_at' => $survey['opens_at'],
            'closes_at' => $survey['closes_at'],
            'estimated_time' => $survey['estimated_time'] !== null
                ? (int) $survey['estimated_time']
                : null,
            'status' => $survey['status'],
            'positions' => $this->normalizePositions($survey['positions'] ?? ''),
        ];
    }

    private function formatRespondent(array $respondent, array $answers): array
    {
        return [
            'response_id' => (int) $respondent['response_id'],
            'user_id' => (int) $respondent['user_id'],
            'full_name' => $respondent['full_name'],
            'nik' => $respondent['nik'],
            'profile_photo_path' => $respondent['profile_photo_path'],
            'status' => $respondent['status'],
            'submitted_at' => $respondent['submitted_at'],
            'answers' => $answers,
        ];
    }

    private function formatPages(
        array $pages,
        array $optionCounts,
        array $freeTextAnswers,
        int $totalRespondents,
    ): array {
        $optionCountsByQuestion = [];

        foreach ($optionCounts as $item) {
            $optionCountsByQuestion[(int) $item['question_id']][(int) $item['option_id']] = (int) $item['response_count'];
        }

        $freeTextByQuestion = [];

        foreach ($freeTextAnswers as $answer) {
            $freeTextByQuestion[(int) $answer['question_id']][] = [
                'answer_text' => $answer['answer_text'],
                'submitted_at' => $answer['submitted_at'],
                'respondent_name' => $answer['full_name'],
            ];
        }

        return array_map(function (array $page) use ($optionCountsByQuestion, $freeTextByQuestion, $totalRespondents): array {
            $questions = array_map(function (array $question) use ($optionCountsByQuestion, $freeTextByQuestion, $totalRespondents): array {
                $questionId = (int) $question['id'];
                $options = array_map(function (array $option) use ($optionCountsByQuestion, $questionId, $totalRespondents): array {
                    $count = $optionCountsByQuestion[$questionId][(int) $option['id']] ?? 0;

                    return [
                        'id' => (int) $option['id'],
                        'option_text' => $option['option_text'],
                        'count' => $count,
                        'percentage' => $totalRespondents > 0
                            ? round(($count / $totalRespondents) * 100)
                            : 0,
                    ];
                }, $question['options'] ?? []);

                return [
                    'id' => $questionId,
                    'question_text' => $question['question_text'],
                    'question_type' => $question['question_type'],
                    'is_required' => (bool) $question['is_required'],
                    'parent_option_id' => $question['parent_option_id'],
                    'options' => $options,
                    'text_answers' => $freeTextByQuestion[$questionId] ?? [],
                ];
            }, $page['questions'] ?? []);

            return [
                'page' => (int) $page['page'],
                'section' => $page['section'],
                'questions' => $questions,
            ];
        }, $pages);
    }

    private function groupAnswersByResponse(array $answers): array
    {
        $grouped = [];

        foreach ($answers as $answer) {
            $value = $answer['option_text'] ?? $answer['answer_text'] ?? '-';
            $responseId = (int) $answer['response_id'];

            $grouped[$responseId][] = [
                'question_id' => (int) $answer['question_id'],
                'question_text' => $answer['question_text'],
                'question_type' => $answer['question_type'],
                'value' => $value,
            ];
        }

        return $grouped;
    }

    private function normalizeSearch(mixed $search): string
    {
        if (!\is_string($search)) {
            return '';
        }

        return mb_substr(trim($search), 0, 120);
    }

    private function normalizeOption(mixed $value, array $allowedValues): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $value = strtolower(trim($value));

        return \in_array($value, $allowedValues, true) ? $value : null;
    }

    private function normalizeYear(mixed $value): int
    {
        if (\is_string($value)) {
            $value = trim($value);
        }

        if (!\is_int($value) && !(\is_string($value) && ctype_digit($value))) {
            return (int) date('Y');
        }

        $year = (int) $value;
        $currentYear = (int) date('Y');

        if ($year < 2000 || $year > $currentYear + 5) {
            return $currentYear;
        }

        return $year;
    }

    private function normalizePositiveInteger(mixed $value, int $default, ?int $max = null): int
    {
        if (\is_string($value)) {
            $value = trim($value);
        }

        if (!\is_int($value) && !(\is_string($value) && ctype_digit($value))) {
            return $default;
        }

        $number = (int) $value;

        if ($number <= 0) {
            return $default;
        }

        return $max !== null ? min($number, $max) : $number;
    }

    private function normalizePositions(string $positions): array
    {
        if ($positions === '') {
            return [];
        }

        return array_values(array_filter(explode(',', $positions)));
    }

    private function formatAudience(array $positions): string
    {
        if ($positions === [] || \in_array('public', $positions, true)) {
            return 'Masyarakat Umum';
        }

        $labels = [
            'asn' => 'Pegawai ASN',
            'non_asn' => 'Pegawai Non-ASN',
        ];

        return implode(', ', array_map(
            static fn (string $position): string => $labels[$position] ?? $position,
            $positions,
        ));
    }

    private function monthLabel(int $month): string
    {
        $labels = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ];

        return $labels[$month] ?? (string) $month;
    }

    private function toTabSeparatedValues(array $rows): string
    {
        return implode("\r\n", array_map(
            fn (array $row): string => implode("\t", array_map(
                fn (mixed $cell): string => $this->sanitizeCell($cell),
                $row,
            )),
            $rows,
        ));
    }

    private function sanitizeCell(mixed $cell): string
    {
        $value = trim((string) ($cell ?? ''));
        $value = str_replace(["\t", "\r", "\n"], ' ', $value);

        if (preg_match('/^[=+\-@]/', $value) === 1) {
            return "'" . $value;
        }

        return $value;
    }

    private function slugify(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title) ?? 'survey'));
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'survey';
    }
}
