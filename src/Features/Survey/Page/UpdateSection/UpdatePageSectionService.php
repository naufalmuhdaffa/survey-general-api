<?php

declare(strict_types=1);

namespace App\Features\Survey\Page\UpdateSection;

use RuntimeException;

final class UpdatePageSectionService
{
    private UpdatePageSectionRepository $repository;

    public function __construct()
    {
        $this->repository = new UpdatePageSectionRepository();
    }

    public function update(int $surveyId, int $page, mixed $data): array
    {
        if ($page < 1) {
            throw new RuntimeException('Value halaman (page) harus lebih dari 0', 422);
        }

        if (!$this->repository->surveyExists($surveyId)) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        if (!$this->repository->surveyIsDraft($surveyId)) {
            throw new RuntimeException('Isi survey tidak dapat diubah setelah dipublikasikan', 409);
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
