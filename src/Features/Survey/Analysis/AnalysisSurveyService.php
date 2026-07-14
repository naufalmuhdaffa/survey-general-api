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
    public function list(int $userId, array $query): array
    {
        $search = $this->normalizeSearch($query['search'] ?? '');
        $status = $this->normalizeOption($query['status'] ?? '', self::VALID_STATUSES);
        $year = $this->normalizeYear($query['year'] ?? date('Y'));
        $perPage = $this->normalizePositiveInteger($query['per_page'] ?? self::DEFAULT_PER_PAGE, self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE);
        $total = $this->repository->countSurveys($userId, $search, $status, $year);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($this->normalizePositiveInteger($query['page'] ?? 1, 1), $totalPages);
        $offset = ($page - 1) * $perPage;

        return [
            'summary' => $this->formatSummary($this->repository->getSummary($userId, $year)),
            'available_years' => $this->formatAvailableYears($this->repository->getAvailableYears($userId), $year),
            'selected_year' => $year,
            'response_volume' => $this->buildResponseVolume($userId, $year),
            'items' => array_map(
                fn (array $survey): array => $this->formatSurveyRow($survey),
                $this->repository->getSurveys($userId, $search, $status, $year, $perPage, $offset),
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
    public function detail(int $userId, int $surveyId, array $query): array
    {
        $survey = $this->repository->getSurveyById($surveyId, $userId);

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
    public function export(int $userId, int $surveyId): array
    {
        $survey = $this->repository->getSurveyById($surveyId, $userId);

        if (!$survey) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        $responses = $this->repository->getAllSubmittedResponses($surveyId);
        $totalRespondents = \count($responses);
        $pages = $this->formatPages(
            $this->repository->getPagesWithQuestions($surveyId),
            $this->repository->getOptionAnswerCounts($surveyId),
            $this->repository->getFreeTextAnswers($surveyId),
            $totalRespondents,
        );
        $questions = $this->flattenExportQuestions($pages);

        $answers = $this->groupAnswersByResponse(
            $this->repository->getAnswersByResponseIds(array_column($responses, 'response_id')),
        );

        $filename = $this->slugify((string) $survey['title']) . '-responses.xls';

        return [
            'filename' => $filename,
            'content' => $this->toExcelHtml($survey, $pages, $questions, $responses, $answers, $totalRespondents),
        ];
    }

    private function toExcelHtml(
        array $survey,
        array $pages,
        array $questions,
        array $responses,
        array $answers,
        int $totalRespondents
    ): string {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>
            body { font-family: Arial, sans-serif; color: #111827; }
            h1 { color: #800000; font-size: 22px; margin: 0 0 6px; }
            h2 { color: #800000; font-size: 16px; margin: 24px 0 8px; }
            p { color: #4b5563; font-size: 12px; margin: 0 0 16px; }
            table { border-collapse: collapse; margin-bottom: 18px; width: 100%; }
            th { background: #800000; color: #ffffff; font-weight: 700; }
            th, td { border: 1px solid #d8b4ae; font-size: 12px; padding: 8px; vertical-align: top; }
            td { background: #ffffff; }
            .section-row td { background: #fff3f2; color: #570000; font-weight: 700; }
            .summary-label { background: #fff3f2; color: #570000; font-weight: 700; width: 220px; }
            .text { mso-number-format: "\@"; }
            .muted { color: #6b7280; }
        </style></head><body>';
        $html .= '<h1>' . $this->escapeCell($survey['title'] ?? 'Survey') . '</h1>';
        $html .= '<p>Export data analisis survey dari Survey Pemkot Jogja.</p>';

        $html .= '<h2>Ringkasan Survey</h2><table>';
        $summaryRows = [
            ['Judul Survey', $survey['title'] ?? '-'],
            ['Deskripsi', $survey['description'] ?? '-'],
            ['OPD Pengampu', $survey['opd_pengampu'] ?? '-'],
            ['Status', $survey['status'] ?? '-'],
            ['Audiens', $this->formatAudience($this->normalizePositions($survey['positions'] ?? ''))],
            ['Tanggal Mulai', $survey['opens_at'] ?? '-'],
            ['Tanggal Selesai', $survey['closes_at'] ?? '-'],
            ['Total Responden', $totalRespondents],
        ];

        foreach ($summaryRows as [$label, $value]) {
            $html .= '<tr><td class="summary-label text">' . $this->escapeCell($label) . '</td>';
            $html .= '<td class="text">' . $this->escapeCell($value) . '</td></tr>';
        }

        $html .= '</table>';

        $html .= '<h2>Jawaban Per Responden</h2><table><thead><tr>';
        foreach (['No', 'Nama Responden', 'NIK', 'Tanggal Submit'] as $header) {
            $html .= '<th class="text">' . $this->escapeCell($header) . '</th>';
        }

        foreach ($questions as $question) {
            $html .= '<th class="text">' . $this->escapeCell($this->formatExportQuestionLabel($question)) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        if ($responses === []) {
            $html .= '<tr><td class="text muted" colspan="' . (4 + \count($questions)) . '">Belum ada responden submitted.</td></tr>';
        }

        foreach ($responses as $index => $response) {
            $answerByQuestion = [];

            foreach ($answers[(int) $response['response_id']] ?? [] as $answer) {
                $answerByQuestion[(int) $answer['question_id']][] = $answer['value'];
            }

            $html .= '<tr>';
            $html .= '<td class="text">' . $this->escapeCell($index + 1) . '</td>';
            $html .= '<td class="text">' . $this->escapeCell($response['full_name'] ?? '-') . '</td>';
            $html .= '<td class="text">' . $this->escapeCell($response['nik'] ?? '-') . '</td>';
            $html .= '<td class="text">' . $this->escapeCell($response['submitted_at'] ?? '-') . '</td>';

            foreach ($questions as $question) {
                $html .= '<td class="text">' . $this->escapeCell(implode('; ', $answerByQuestion[(int) $question['id']] ?? [])) . '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<h2>Rekap Opsi Jawaban</h2><table>';
        $html .= '<thead><tr><th class="text">Halaman</th><th class="text">Section</th><th class="text">Pertanyaan</th><th class="text">Opsi</th><th class="text">Jumlah Responden</th><th class="text">Persentase</th></tr></thead><tbody>';
        $hasOptionRecap = false;

        foreach ($questions as $question) {
            if (($question['question_type'] ?? '') === 'free_text') {
                continue;
            }

            foreach ($question['options'] ?? [] as $option) {
                $hasOptionRecap = true;
                $html .= '<tr>';
                $html .= '<td class="text">' . $this->escapeCell('Halaman ' . $question['page']) . '</td>';
                $html .= '<td class="text">' . $this->escapeCell($question['section']) . '</td>';
                $html .= '<td class="text">' . $this->escapeCell($this->formatExportQuestionLabel($question, false)) . '</td>';
                $html .= '<td class="text">' . $this->escapeCell($option['option_text'] ?? '-') . '</td>';
                $html .= '<td class="text">' . $this->escapeCell($option['count'] ?? 0) . '</td>';
                $html .= '<td class="text">' . $this->escapeCell(($option['percentage'] ?? 0) . '%') . '</td>';
                $html .= '</tr>';
            }
        }

        if (!$hasOptionRecap) {
            $html .= '<tr><td class="text muted" colspan="6">Belum ada pertanyaan pilihan.</td></tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<h2>Jawaban Teks</h2><table>';
        $html .= '<thead><tr><th class="text">Halaman</th><th class="text">Section</th><th class="text">Pertanyaan</th><th class="text">Responden</th><th class="text">Tanggal Submit</th><th class="text">Jawaban</th></tr></thead><tbody>';
        $hasTextAnswer = false;

        foreach ($questions as $question) {
            if (($question['question_type'] ?? '') !== 'free_text') {
                continue;
            }

            foreach ($question['text_answers'] ?? [] as $answer) {
                $hasTextAnswer = true;
                $html .= '<tr>';
                $html .= '<td class="text">' . $this->escapeCell('Halaman ' . $question['page']) . '</td>';
                $html .= '<td class="text">' . $this->escapeCell($question['section']) . '</td>';
                $html .= '<td class="text">' . $this->escapeCell($this->formatExportQuestionLabel($question, false)) . '</td>';
                $html .= '<td class="text">' . $this->escapeCell($answer['respondent_name'] ?? '-') . '</td>';
                $html .= '<td class="text">' . $this->escapeCell($answer['submitted_at'] ?? '-') . '</td>';
                $html .= '<td class="text">' . $this->escapeCell($answer['answer_text'] ?? '-') . '</td>';
                $html .= '</tr>';
            }
        }

        if (!$hasTextAnswer) {
            $html .= '<tr><td class="text muted" colspan="6">Belum ada jawaban teks.</td></tr>';
        }

        $html .= '</tbody></table>';

        foreach ($pages as $page) {
            $section = $page['section'] ?: 'Tanpa nama section';
            $html .= '<h2>Struktur Halaman ' . $this->escapeCell($page['page']) . ' - ' . $this->escapeCell($section) . '</h2>';
            $html .= '<table><thead><tr><th class="text">Urutan</th><th class="text">Tipe</th><th class="text">Wajib</th><th class="text">Pertanyaan</th><th class="text">Cabang Dari Opsi</th></tr></thead><tbody>';

            foreach ($this->flattenExportQuestions([$page]) as $index => $question) {
                $html .= '<tr>';
                $html .= '<td class="text">' . $this->escapeCell($index + 1) . '</td>';
                $html .= '<td class="text">' . $this->escapeCell($question['question_type']) . '</td>';
                $html .= '<td class="text">' . $this->escapeCell($question['is_required'] ? 'Ya' : 'Tidak') . '</td>';
                $html .= '<td class="text">' . $this->escapeCell($question['question_text']) . '</td>';
                $html .= '<td class="text">' . $this->escapeCell($question['parent_option_label'] ?? '-') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $html .= '</body></html>';

        return $html;
    }

    private function flattenExportQuestions(array $pages): array
    {
        $optionLabels = [];

        foreach ($pages as $page) {
            foreach ($page['questions'] ?? [] as $question) {
                foreach ($question['options'] ?? [] as $option) {
                    if (isset($option['id'])) {
                        $optionLabels[(int) $option['id']] = (string) ($option['option_text'] ?? '-');
                    }
                }
            }
        }

        $questions = [];

        foreach ($pages as $page) {
            foreach ($page['questions'] ?? [] as $question) {
                $parentOptionId = $question['parent_option_id'] !== null
                    ? (int) $question['parent_option_id']
                    : null;

                $questions[] = [
                    'id' => (int) $question['id'],
                    'page' => (int) ($page['page'] ?? 1),
                    'section' => $page['section'] ?: 'Tanpa nama section',
                    'question_text' => $question['question_text'] ?? '-',
                    'question_type' => $question['question_type'] ?? '-',
                    'is_required' => (bool) ($question['is_required'] ?? false),
                    'parent_option_id' => $parentOptionId,
                    'parent_option_label' => $parentOptionId !== null
                        ? ($optionLabels[$parentOptionId] ?? 'Opsi #' . $parentOptionId)
                        : null,
                    'options' => $question['options'] ?? [],
                    'text_answers' => $question['text_answers'] ?? [],
                ];
            }
        }

        return $questions;
    }

    private function formatExportQuestionLabel(array $question, bool $includeSection = true): string
    {
        $label = $includeSection
            ? sprintf('Halaman %s - %s - %s', $question['page'], $question['section'], $question['question_text'])
            : (string) $question['question_text'];

        if (($question['parent_option_label'] ?? null) !== null) {
            $label .= sprintf(' [Cabang: jika memilih "%s"]', $question['parent_option_label']);
        }

        return $label;
    }

    private function buildResponseVolume(int $userId, int $year): array
    {
        $rawVolume = $this->repository->getResponseVolume($userId, $year);
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

    private function escapeCell(mixed $cell): string
    {
        return htmlspecialchars($this->sanitizeCell($cell), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
