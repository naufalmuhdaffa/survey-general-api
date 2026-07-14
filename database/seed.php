<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();

$pdo = App\Database::connection();
$shouldResetSurveys = \in_array('--reset-surveys', $argv, true);

$creator = $pdo->query("
    SELECT u.id
    FROM users u
    JOIN roles r ON r.id = u.role_id
    ORDER BY r.name = 'superadmin' DESC, r.name = 'admin_opd' DESC, u.id ASC
    LIMIT 1
")->fetch();

if (!$creator) {
    echo "Seed dilewati: belum ada user untuk created_by." . PHP_EOL;
    exit(0);
}

$users = $pdo->query("
    SELECT id, full_name, position
    FROM users
    ORDER BY id ASC
")->fetchAll();

if ($users === []) {
    echo "Seed dilewati: belum ada user untuk response dummy." . PHP_EOL;
    exit(0);
}

if ($shouldResetSurveys) {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ([
        'answers',
        'responses',
        'options',
        'questions',
        'survey_pages',
        'survey_restrictions',
        'surveys',
    ] as $table) {
        $pdo->exec("TRUNCATE TABLE {$table}");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

$creatorId = (int) $creator['id'];
$opdOptions = [
    'Dinas Komunikasi Informatika dan Persandian Kota Yogyakarta',
    'Dinas Pendidikan Pemuda dan Olahraga Kota Yogyakarta',
    'Dinas Kesehatan Kota Yogyakarta',
    'Dinas Perhubungan Kota Yogyakarta',
    'Dinas Pekerjaan Umum Perumahan dan Kawasan Permukiman Kota Yogyakarta',
    'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu Kota Yogyakarta',
    'Badan Perencanaan Pembangunan Daerah Kota Yogyakarta',
];

$baseSurveys = [
    [
        'title' => 'Evaluasi Layanan Transportasi Publik 2026',
        'description' => 'Mengukur kepuasan warga terhadap akses, rute, dan kenyamanan transportasi publik di Kota Yogyakarta.',
        'status' => 'open',
        'estimated_time' => 8,
        'opens_at' => '2026-01-10 09:00:00',
        'closes_at' => '2026-08-12 17:00:00',
        'positions' => ['public'],
        'responses' => 6,
    ],
    [
        'title' => 'Kesiapan Digitalisasi Layanan Kelurahan',
        'description' => 'Memetakan kesiapan perangkat kelurahan dalam menggunakan layanan digital baru.',
        'status' => 'upcoming',
        'estimated_time' => 12,
        'opens_at' => '2026-08-05 09:00:00',
        'closes_at' => '2026-09-05 17:00:00',
        'positions' => ['asn', 'non_asn'],
        'responses' => 0,
    ],
    [
        'title' => 'Penilaian Fasilitas Ruang Publik Ramah Anak',
        'description' => 'Mengumpulkan aspirasi warga tentang keamanan, kebersihan, dan aksesibilitas ruang publik ramah anak.',
        'status' => 'open',
        'estimated_time' => 10,
        'opens_at' => '2026-03-03 09:00:00',
        'closes_at' => '2026-09-20 17:00:00',
        'positions' => ['public'],
        'responses' => 5,
    ],
    [
        'title' => 'Audit Internal Pelayanan Administrasi OPD',
        'description' => 'Evaluasi proses administrasi lintas OPD untuk memperbaiki waktu layanan dan akurasi data.',
        'status' => 'draft',
        'estimated_time' => 15,
        'opens_at' => '2025-11-04 09:00:00',
        'closes_at' => '2025-12-04 17:00:00',
        'positions' => ['asn'],
        'responses' => 0,
    ],
    [
        'title' => 'Kepuasan Program Bantuan UMKM',
        'description' => 'Menilai dampak program bantuan, pelatihan, dan pendampingan terhadap pelaku UMKM lokal.',
        'status' => 'closed',
        'estimated_time' => 9,
        'opens_at' => '2025-05-12 09:00:00',
        'closes_at' => '2025-06-20 17:00:00',
        'positions' => ['public'],
        'responses' => 7,
    ],
    [
        'title' => 'Prioritas Perbaikan Infrastruktur Kampung',
        'description' => 'Memetakan prioritas warga terhadap jalan lingkungan, drainase, penerangan, dan fasilitas umum.',
        'status' => 'open',
        'estimated_time' => 7,
        'opens_at' => '2026-06-01 09:00:00',
        'closes_at' => '2026-09-15 17:00:00',
        'positions' => ['public'],
        'responses' => 4,
    ],
    [
        'title' => 'Evaluasi Kanal Aduan Warga',
        'description' => 'Mengevaluasi kemudahan, kecepatan respons, dan kejelasan tindak lanjut pada kanal aduan warga.',
        'status' => 'upcoming',
        'estimated_time' => 6,
        'opens_at' => '2026-10-01 09:00:00',
        'closes_at' => '2026-11-01 17:00:00',
        'positions' => ['public'],
        'responses' => 0,
    ],
];

$subjects = [
    'Pelayanan Administrasi Kependudukan',
    'Kualitas Jalan Lingkungan',
    'Kebersihan Kawasan Wisata',
    'Akses Layanan Kesehatan',
    'Pengelolaan Sampah Rumah Tangga',
    'Keamanan Ruang Publik',
    'Ketersediaan Air Bersih',
    'Layanan Perizinan Usaha',
    'Akses Transportasi Sekolah',
    'Kesiapan Sistem Informasi OPD',
    'Program Bantuan Sosial',
    'Fasilitas Pedestrian',
    'Penerangan Jalan Umum',
    'Kualitas Taman Kota',
    'Layanan Aduan Warga',
    'Digitalisasi Arsip Kelurahan',
    'Kinerja Layanan Kecamatan',
    'Kepuasan Layanan Pajak Daerah',
    'Pengawasan Bangunan Publik',
    'Kesiapsiagaan Bencana Lingkungan',
];

$descriptionTemplates = [
    'Mengumpulkan penilaian responden terhadap mutu, akses, dan kecepatan layanan terbaru.',
    'Memetakan kebutuhan prioritas untuk mendukung perencanaan program pemerintah kota.',
    'Mengukur tingkat kepuasan dan hambatan yang masih dirasakan oleh pengguna layanan.',
    'Mendukung evaluasi internal OPD melalui data persepsi dari target responden terkait.',
    'Mengidentifikasi peluang perbaikan layanan berdasarkan pengalaman responden selama satu tahun terakhir.',
];

$positionSets = [
    ['public'],
    ['asn'],
    ['non_asn'],
    ['public'],
    ['asn', 'non_asn'],
];

$statuses = ['open', 'upcoming', 'closed', 'draft'];
$surveys = $baseSurveys;
$currentYear = (int) date('Y');

for ($index = \count($surveys) + 1; $index <= 100; $index++) {
    $status = $statuses[$index % \count($statuses)];

    if ($status === 'open') {
        $month = (($index * 3) % 7) + 1;
        $day = ($index % 20) + 1;
        $opensAt = sprintf('%d-%02d-%02d 09:00:00', $currentYear, $month, $day);
        $closesAt = sprintf('%d-%02d-%02d 17:00:00', $currentYear, min(12, $month + 5), min(28, $day + 4));
        $responses = 2 + ($index % 5);
    } elseif ($status === 'upcoming') {
        $month = 8 + ($index % 5);
        $day = ($index % 20) + 1;
        $opensAt = sprintf('%d-%02d-%02d 09:00:00', $currentYear, $month, $day);
        $closesAt = sprintf('%d-%02d-%02d 17:00:00', $currentYear, min(12, $month + 1), min(28, $day + 6));
        $responses = 0;
    } elseif ($status === 'closed') {
        $year = $currentYear - 2 + ($index % 3);
        $month = ($index % 12) + 1;

        if ($year >= $currentYear && $month > 6) {
            $month = ($index % 6) + 1;
        }

        $day = min(24, ($index % 20) + 1);
        $opensAt = sprintf('%d-%02d-%02d 09:00:00', $year, $month, $day);
        $closesAt = date('Y-m-d 17:00:00', strtotime('+24 days', strtotime($opensAt)));
        $responses = 1 + ($index % 6);
    } else {
        $year = $currentYear - 2 + ($index % 3);
        $month = ($index % 12) + 1;
        $day = min(24, ($index % 20) + 1);
        $opensAt = sprintf('%d-%02d-%02d 09:00:00', $year, $month, $day);
        $closesAt = date('Y-m-d 17:00:00', strtotime('+32 days', strtotime($opensAt)));
        $responses = 0;
    }

    $subject = $subjects[$index % \count($subjects)];
    $surveys[] = [
        'title' => sprintf('Survey %03d - %s', $index, $subject),
        'description' => $descriptionTemplates[$index % \count($descriptionTemplates)],
        'status' => $status,
        'estimated_time' => 5 + ($index % 13),
        'opens_at' => $opensAt,
        'closes_at' => $closesAt,
        'positions' => $positionSets[$index % \count($positionSets)],
        'responses' => $responses,
    ];
}

$findSurvey = $pdo->prepare('SELECT id FROM surveys WHERE title = ? LIMIT 1');
$insertSurvey = $pdo->prepare("
    INSERT INTO surveys (
        title,
        description,
        instructions,
        opd_pengampu,
        estimated_time,
        status,
        created_by,
        opens_at,
        closes_at,
        created_at,
        updated_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$insertRestriction = $pdo->prepare("
    INSERT IGNORE INTO survey_restrictions (survey_id, position)
    VALUES (?, ?)
");
$insertPage = $pdo->prepare("
    INSERT IGNORE INTO survey_pages (survey_id, page, section)
    VALUES (?, ?, ?)
");
$insertQuestion = $pdo->prepare("
    INSERT INTO questions (
        survey_id,
        question_text,
        question_type,
        is_required,
        question_order,
        page,
        parent_option_id
    )
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$insertOption = $pdo->prepare("
    INSERT INTO options (question_id, option_text, option_order)
    VALUES (?, ?, ?)
");
$insertResponse = $pdo->prepare("
    INSERT INTO responses (
        survey_id,
        user_id,
        status,
        current_page,
        submitted_at,
        created_at,
        updated_at
    )
    VALUES (?, ?, 'submitted', 2, ?, ?, ?)
");
$insertAnswer = $pdo->prepare("
    INSERT INTO answers (response_id, question_id, answer_text, option_id)
    VALUES (?, ?, ?, ?)
");

$questionBlueprints = [
    [
        'text' => 'Seberapa puas Anda terhadap layanan ini secara keseluruhan?',
        'type' => 'radio_button',
        'page' => 1,
        'options' => ['Sangat puas', 'Puas', 'Cukup puas', 'Kurang puas'],
    ],
    [
        'text' => 'Aspek apa saja yang menurut Anda perlu menjadi prioritas perbaikan?',
        'type' => 'checkbox',
        'page' => 1,
        'options' => ['Kecepatan layanan', 'Kejelasan informasi', 'Kemudahan akses', 'Kenyamanan fasilitas'],
    ],
    [
        'text' => 'Wilayah atau unit layanan yang paling sering Anda gunakan',
        'type' => 'dropdown',
        'page' => 1,
        'options' => ['Kecamatan Jetis', 'Kecamatan Gondokusuman', 'Kecamatan Umbulharjo', 'Kecamatan Kotagede'],
    ],
    [
        'text' => 'Saran utama Anda untuk peningkatan layanan ini',
        'type' => 'free_text',
        'page' => 2,
        'options' => [],
    ],
    [
        'text' => 'Apakah Anda bersedia mengikuti survei lanjutan jika diperlukan?',
        'type' => 'radio_button',
        'page' => 2,
        'options' => ['Ya, bersedia', 'Tidak saat ini'],
    ],
];

$inserted = 0;
$skipped = 0;
$responseInserted = 0;
$answerInserted = 0;

foreach ($surveys as $index => $survey) {
    $findSurvey->execute([$survey['title']]);

    if ($findSurvey->fetch()) {
        $skipped++;
        continue;
    }

    $pdo->beginTransaction();

    try {
        $opensAtTimestamp = strtotime($survey['opens_at']);
        $closesAtTimestamp = strtotime($survey['closes_at']);
        $createdAtTimestamp = strtotime('-14 days', $opensAtTimestamp);
        $updatedAtTimestamp = min(time(), max($createdAtTimestamp, $closesAtTimestamp));

        $insertSurvey->execute([
            $survey['title'],
            $survey['description'],
            'Jawab pertanyaan sesuai pengalaman dan kondisi terbaru. Jawaban akan dipakai untuk bahan evaluasi layanan.',
            $opdOptions[$index % \count($opdOptions)],
            $survey['estimated_time'],
            $survey['status'],
            $creatorId,
            date('Y-m-d H:i:s', $opensAtTimestamp),
            date('Y-m-d H:i:s', $closesAtTimestamp),
            date('Y-m-d H:i:s', $createdAtTimestamp),
            date('Y-m-d H:i:s', $updatedAtTimestamp),
        ]);

        $surveyId = (int) $pdo->lastInsertId();
        $insertPage->execute([$surveyId, 1, 'Profil Pengalaman Responden']);
        $insertPage->execute([$surveyId, 2, 'Evaluasi dan Saran Layanan']);

        foreach ($survey['positions'] as $position) {
            $insertRestriction->execute([$surveyId, $position]);
        }

        $questions = [];

        foreach ($questionBlueprints as $order => $blueprint) {
            $insertQuestion->execute([
                $surveyId,
                $blueprint['text'],
                $blueprint['type'],
                1,
                $order + 1,
                $blueprint['page'],
                null,
            ]);

            $questionId = (int) $pdo->lastInsertId();
            $optionIds = [];

            foreach ($blueprint['options'] as $optionOrder => $optionText) {
                $insertOption->execute([$questionId, $optionText, $optionOrder + 1]);
                $optionIds[] = (int) $pdo->lastInsertId();
            }

            $questions[] = [
                'id' => $questionId,
                'type' => $blueprint['type'],
                'options' => $optionIds,
            ];
        }

        $followUpParentOptionId = $questions[4]['options'][1] ?? null;

        if ($followUpParentOptionId !== null) {
            $insertQuestion->execute([
                $surveyId,
                'Apa alasan utama Anda belum bersedia mengikuti survei lanjutan?',
                'free_text',
                0,
                6,
                2,
                $followUpParentOptionId,
            ]);

            $questions[] = [
                'id' => (int) $pdo->lastInsertId(),
                'type' => 'free_text',
                'options' => [],
                'parent_option_id' => $followUpParentOptionId,
            ];
        }

        $respondents = resolveRespondents($users, $survey['positions'], (int) $survey['responses']);

        foreach ($respondents as $responseIndex => $respondent) {
            $submittedAt = makeSubmittedAt($opensAtTimestamp, $closesAtTimestamp, $responseIndex, $index);
            $insertResponse->execute([
                $surveyId,
                (int) $respondent['id'],
                $submittedAt,
                $submittedAt,
                $submittedAt,
            ]);

            $responseId = (int) $pdo->lastInsertId();
            $selectedFinalOption = null;

            foreach ($questions as $questionIndex => $question) {
                if (isset($question['parent_option_id']) && $selectedFinalOption !== $question['parent_option_id']) {
                    continue;
                }

                if ($question['type'] === 'free_text') {
                    $answer = generateTextAnswer($survey['title'], $responseIndex, isset($question['parent_option_id']));
                    $insertAnswer->execute([$responseId, $question['id'], $answer, null]);
                    $answerInserted++;
                    continue;
                }

                if ($question['type'] === 'checkbox') {
                    $selectedOptions = selectManyOptions($question['options'], $responseIndex + $questionIndex);

                    foreach ($selectedOptions as $optionId) {
                        $insertAnswer->execute([$responseId, $question['id'], null, $optionId]);
                        $answerInserted++;
                    }

                    continue;
                }

                $optionId = selectOneOption($question['options'], $responseIndex + $questionIndex);

                if ($questionIndex === 4) {
                    $selectedFinalOption = $optionId;
                }

                $insertAnswer->execute([$responseId, $question['id'], null, $optionId]);
                $answerInserted++;
            }

            $responseInserted++;
        }

        $pdo->commit();
        $inserted++;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

echo "Seed survey selesai. Data baru: {$inserted}. Dilewati: {$skipped}. Response: {$responseInserted}. Answer: {$answerInserted}." . PHP_EOL;

function makeSubmittedAt(int $opensAtTimestamp, int $closesAtTimestamp, int $responseIndex, int $surveyIndex): string
{
    $endTimestamp = min($closesAtTimestamp, time());

    if ($endTimestamp <= $opensAtTimestamp) {
        $endTimestamp = $opensAtTimestamp + (7 * 24 * 60 * 60);
    }

    $span = max(24 * 60 * 60, $endTimestamp - $opensAtTimestamp);
    $offsetSeed = ($responseIndex + 1) * (($surveyIndex % 9) + 1) * 13 * 60 * 60;
    $offset = $offsetSeed % $span;

    return date('Y-m-d H:i:s', $opensAtTimestamp + $offset);
}

/**
 * @param array<int, array<string, mixed>> $users
 * @param array<int, string> $positions
 * @return array<int, array<string, mixed>>
 */
function resolveRespondents(array $users, array $positions, int $limit): array
{
    if ($limit <= 0) {
        return [];
    }

    $allowedPositions = $positions === [] || \in_array('public', $positions, true)
        ? ['asn', 'non_asn', 'public']
        : $positions;
    $eligibleUsers = array_values(array_filter(
        $users,
        static fn (array $user): bool => \in_array((string) $user['position'], $allowedPositions, true),
    ));

    if ($eligibleUsers === []) {
        $eligibleUsers = $users;
    }

    $respondents = [];

    for ($index = 0; $index < min($limit, \count($eligibleUsers)); $index++) {
        $respondents[] = $eligibleUsers[$index % \count($eligibleUsers)];
    }

    return $respondents;
}

/**
 * @param array<int, int> $optionIds
 */
function selectOneOption(array $optionIds, int $seed): int
{
    return $optionIds[$seed % \count($optionIds)];
}

/**
 * @param array<int, int> $optionIds
 * @return array<int, int>
 */
function selectManyOptions(array $optionIds, int $seed): array
{
    $first = $optionIds[$seed % \count($optionIds)];
    $second = $optionIds[($seed + 1) % \count($optionIds)];

    return array_values(array_unique([$first, $second]));
}

function generateTextAnswer(string $surveyTitle, int $responseIndex, bool $isFollowUp): string
{
    if ($isFollowUp) {
        return 'Belum dapat mengikuti survei lanjutan karena jadwal responden belum sesuai.';
    }

    $answers = [
        'Informasi layanan perlu dibuat lebih jelas dan mudah ditemukan.',
        'Waktu respons sudah baik, namun kanal digital masih bisa dipermudah.',
        'Petugas cukup membantu dan alur layanan sudah lebih tertata.',
        'Fasilitas pendukung perlu diperbarui agar lebih nyaman digunakan.',
    ];

    return $answers[$responseIndex % \count($answers)] . ' (' . $surveyTitle . ')';
}
