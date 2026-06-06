<?php

declare(strict_types=1);

namespace App\Features\Survey\Create;

use App\Services\FileUploadService;
use RuntimeException;

final class CreateSurveyService
{
    private const array VALID_POSITIONS = ['public', 'asn', 'non_asn'];
    private const array VALID_STATUSES = ['draft', 'upcoming', 'open', 'closed'];

    private CreateSurveyRepository $repository;
    private FileUploadService $fileUploadService;

    public function __construct()
    {
        $this->repository = new CreateSurveyRepository();
        $this->fileUploadService = new FileUploadService();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $thumbnail
     */
    public function create(int $createdBy, array $data, ?array $thumbnail): int
    {
        $title = isset($data['title']) && \is_string($data['title'])
            ? trim($data['title'])
            : '';

        if ($title === '') {
            throw new RuntimeException('Judul survei harus diisi', 422);
        }

        $description = $this->normalizeOptionalText($data['description'] ?? null, 'Deskripsi (description) harus berupa teks');
        $instructions = $this->normalizeOptionalText($data['instructions'] ?? null, 'Petunjuk pengisian (instructions) harus berupa teks');
        $instructions = $instructions === '' ? null : $instructions;
        $estimatedTime = $this->normalizeEstimatedTime($data['estimated_time'] ?? null);
        $status = $this->normalizeStatus($data['status'] ?? 'draft');
        $opensAt = $this->normalizeDateTime($data['opens_at'] ?? null, 'Format opens_at tidak valid');
        $closesAt = $this->normalizeDateTime($data['closes_at'] ?? null, 'Format closes_at tidak valid');

        if ($opensAt !== null && $closesAt !== null && strtotime($opensAt) >= strtotime($closesAt)) {
            throw new RuntimeException('Waktu pembukaan (opens_at) harus lebih awal dari waktu penutupan (closes_at)', 422);
        }

        $positions = $this->normalizePositions($data['position'] ?? []);
        $thumbnailPath = null;

        if ($thumbnail !== null && ($thumbnail['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $thumbnailPath = $this->fileUploadService->storeSurveyThumbnail($thumbnail);
        }

        $surveyId = $this->repository->createSurvey(
            $title,
            $description,
            $instructions,
            $estimatedTime,
            $thumbnailPath,
            $status,
            $createdBy,
            $opensAt,
            $closesAt
        );

        $this->repository->createSurveyRestrictions($surveyId, $positions);

        return $surveyId;
    }

    private function normalizeStatus(mixed $status): string
    {
        $status = \is_string($status) ? trim($status) : '';

        if (!\in_array($status, self::VALID_STATUSES, true)) {
            throw new RuntimeException('Status survei tidak valid', 422);
        }

        return $status;
    }

    private function normalizeOptionalText(mixed $value, string $message): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            throw new RuntimeException($message, 422);
        }

        return trim($value);
    }

    private function normalizeEstimatedTime(mixed $estimatedTime): ?int
    {
        if ($estimatedTime === null) {
            return null;
        }

        if (\is_string($estimatedTime)) {
            $estimatedTime = trim($estimatedTime);

            if ($estimatedTime === '') {
                return null;
            }
        }

        if (!\is_int($estimatedTime) && !(\is_string($estimatedTime) && ctype_digit($estimatedTime))) {
            throw new RuntimeException('Estimasi waktu (estimated_time) harus berupa angka lebih dari 0', 422);
        }

        $estimatedTime = (int) $estimatedTime;

        if ($estimatedTime <= 0) {
            throw new RuntimeException('Estimasi waktu (estimated_time) harus berupa angka lebih dari 0', 422);
        }

        return $estimatedTime;
    }

    private function normalizeDateTime(mixed $value, string $message): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            throw new RuntimeException($message, 422);
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (strtotime($value) === false) {
            throw new RuntimeException($message, 422);
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private function normalizePositions(mixed $positions): array
    {
        if (!\is_array($positions) || empty($positions)) {
            throw new RuntimeException('Posisi (position) tidak boleh kosong', 422);
        }

        foreach ($positions as $position) {
            if (!\is_string($position) || !\in_array($position, self::VALID_POSITIONS, true)) {
                $invalidPosition = \is_scalar($position) ? ': ' . (string) $position : '';
                throw new RuntimeException('Posisi tidak valid' . $invalidPosition, 422);
            }
        }

        return array_values(array_unique($positions));
    }
}
