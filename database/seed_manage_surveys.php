<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();

$pdo = App\Database::connection();
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

$creatorId = (int) $creator['id'];
$surveys = [
    [
        'title' => 'Evaluasi Layanan Transportasi Publik 2026',
        'description' => 'Mengukur kepuasan warga terhadap akses, rute, dan kenyamanan transportasi publik di Kota Yogyakarta.',
        'status' => 'open',
        'estimated_time' => 8,
        'opens_at' => '-10 days',
        'closes_at' => '+18 days',
        'positions' => ['public', 'asn'],
        'questions' => 6,
        'has_response' => true,
    ],
    [
        'title' => 'Kesiapan Digitalisasi Layanan Kelurahan',
        'description' => 'Survei internal untuk memetakan kesiapan perangkat kelurahan dalam menggunakan layanan digital baru.',
        'status' => 'upcoming',
        'estimated_time' => 12,
        'opens_at' => '+7 days',
        'closes_at' => '+37 days',
        'positions' => ['asn', 'non_asn'],
        'questions' => 8,
        'has_response' => false,
    ],
    [
        'title' => 'Penilaian Fasilitas Ruang Publik Ramah Anak',
        'description' => 'Mengumpulkan aspirasi warga tentang keamanan, kebersihan, dan aksesibilitas ruang publik ramah anak.',
        'status' => 'open',
        'estimated_time' => 10,
        'opens_at' => '-5 days',
        'closes_at' => '+25 days',
        'positions' => ['public'],
        'questions' => 7,
        'has_response' => true,
    ],
    [
        'title' => 'Audit Internal Pelayanan Administrasi OPD',
        'description' => 'Evaluasi proses administrasi lintas OPD untuk memperbaiki waktu layanan dan akurasi data.',
        'status' => 'draft',
        'estimated_time' => 15,
        'opens_at' => '+20 days',
        'closes_at' => '+50 days',
        'positions' => ['asn'],
        'questions' => 5,
        'has_response' => false,
    ],
    [
        'title' => 'Kepuasan Program Bantuan UMKM',
        'description' => 'Menilai dampak program bantuan, pelatihan, dan pendampingan terhadap pelaku UMKM lokal.',
        'status' => 'closed',
        'estimated_time' => 9,
        'opens_at' => '-60 days',
        'closes_at' => '-15 days',
        'positions' => ['public'],
        'questions' => 9,
        'has_response' => true,
    ],
    [
        'title' => 'Prioritas Perbaikan Infrastruktur Kampung',
        'description' => 'Memetakan prioritas warga terhadap jalan lingkungan, drainase, penerangan, dan fasilitas umum.',
        'status' => 'open',
        'estimated_time' => 7,
        'opens_at' => '-2 days',
        'closes_at' => '+21 days',
        'positions' => ['public', 'non_asn'],
        'questions' => 6,
        'has_response' => false,
    ],
    [
        'title' => 'Evaluasi Kanal Aduan Warga',
        'description' => 'Mengevaluasi kemudahan, kecepatan respons, dan kejelasan tindak lanjut pada kanal aduan warga.',
        'status' => 'upcoming',
        'estimated_time' => 6,
        'opens_at' => '+14 days',
        'closes_at' => '+44 days',
        'positions' => ['public', 'asn', 'non_asn'],
        'questions' => 4,
        'has_response' => false,
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
    ['public', 'asn'],
    ['public', 'non_asn'],
    ['asn', 'non_asn'],
    ['public', 'asn', 'non_asn'],
    [],
];

$statuses = ['open', 'upcoming', 'closed', 'draft'];

for ($index = count($surveys) + 1; $index <= 100; $index++) {
    $status = $statuses[$index % count($statuses)];

    if ($status === 'open') {
        $opensAt = '-' . (($index % 14) + 1) . ' days';
        $closesAt = '+' . (($index % 35) + 7) . ' days';
    } elseif ($status === 'upcoming') {
        $opensAt = '+' . (($index % 30) + 1) . ' days';
        $closesAt = '+' . (($index % 30) + 31) . ' days';
    } elseif ($status === 'closed') {
        $opensAt = '-' . (($index % 90) + 45) . ' days';
        $closesAt = '-' . (($index % 30) + 1) . ' days';
    } else {
        $opensAt = '+' . (($index % 45) + 10) . ' days';
        $closesAt = '+' . (($index % 45) + 40) . ' days';
    }

    $subject = $subjects[$index % count($subjects)];
    $surveys[] = [
        'title' => sprintf('Survey %03d - %s', $index, $subject),
        'description' => $descriptionTemplates[$index % count($descriptionTemplates)],
        'status' => $status,
        'estimated_time' => 5 + ($index % 13),
        'opens_at' => $opensAt,
        'closes_at' => $closesAt,
        'positions' => $positionSets[$index % count($positionSets)],
        'questions' => 4 + ($index % 8),
        'has_response' => $status !== 'draft' && $index % 3 === 0,
    ];
}

$findSurvey = $pdo->prepare('SELECT id FROM surveys WHERE title = ? LIMIT 1');
$insertSurvey = $pdo->prepare("
    INSERT INTO surveys (
        title,
        description,
        instructions,
        estimated_time,
        status,
        created_by,
        opens_at,
        closes_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$insertRestriction = $pdo->prepare("
    INSERT IGNORE INTO survey_restrictions (survey_id, position)
    VALUES (?, ?)
");
$insertPage = $pdo->prepare("
    INSERT IGNORE INTO survey_pages (survey_id, page, section)
    VALUES (?, 1, 'Halaman Utama')
");
$insertQuestion = $pdo->prepare("
    INSERT INTO questions (
        survey_id,
        question_text,
        question_type,
        is_required,
        question_order,
        page
    )
    VALUES (?, ?, 'radio_button', 1, ?, 1)
");
$insertResponse = $pdo->prepare("
    INSERT IGNORE INTO responses (survey_id, user_id)
    VALUES (?, ?)
");

$inserted = 0;

foreach ($surveys as $survey) {
    $findSurvey->execute([$survey['title']]);

    if ($findSurvey->fetch()) {
        continue;
    }

    $insertSurvey->execute([
        $survey['title'],
        $survey['description'],
        'Jawab pertanyaan sesuai pengalaman dan kondisi terbaru.',
        $survey['estimated_time'],
        $survey['status'],
        $creatorId,
        date('Y-m-d H:i:s', strtotime($survey['opens_at'])),
        date('Y-m-d H:i:s', strtotime($survey['closes_at'])),
    ]);

    $surveyId = (int) $pdo->lastInsertId();
    $insertPage->execute([$surveyId]);

    foreach ($survey['positions'] as $position) {
        $insertRestriction->execute([$surveyId, $position]);
    }

    for ($order = 1; $order <= $survey['questions']; $order++) {
        $insertQuestion->execute([
            $surveyId,
            "Pertanyaan {$order} untuk {$survey['title']}",
            $order,
        ]);
    }

    if ($survey['has_response']) {
        $insertResponse->execute([$surveyId, $creatorId]);
    }

    $inserted++;
}

echo "Seed kelola survey selesai. Data baru: {$inserted}." . PHP_EOL;
