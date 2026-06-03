<?php

declare(strict_types=1);

namespace App\Features\Survey\Page\Upsert;

use RuntimeException;

final class UpsertPageService
{
    private UpsertPageRepository $repository;

    public function __construct()
    {
        $this->repository = new UpsertPageRepository();
    }

    public function upsert(int $surveyId, int $page, mixed $data): array
    {
        if ($page < 1) {
            throw new RuntimeException('Value halaman (page) harus lebih dari 0', 422);
        }

        if (!$this->repository->surveyExists($surveyId)) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        if (!\is_array($data) || !\array_key_exists('section', $data)) {
            throw new RuntimeException('Field section harus dikirim', 422);
        }

        $section = trim((string) $data['section']);
        $section = $section === '' ? null : $section;

        if ($section !== null && mb_strlen($section) > 255) {
            throw new RuntimeException('Section maksimal 255 karakter', 422);
        }

        $this->repository->upsertPageSection($surveyId, $page, $section);

        return [
            'page' => $page,
            'section' => $section,
        ];
    }
}
