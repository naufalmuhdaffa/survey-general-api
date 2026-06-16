<?php

declare(strict_types=1);

namespace App\Features\Survey\Update;

use RuntimeException;
use function array_key_exists;

final class UpdateSurveyService
{
    private const array VALID_POSITIONS = ['public', 'asn', 'non_asn'];
    private const array VALID_STATUSES = ['draft', 'upcoming', 'open', 'closed'];

    private UpdateSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new UpdateSurveyRepository();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $surveyId, array $data): void
    {
        if (!$this->repository->surveyExists($surveyId)) {
            throw new RuntimeException('Survei tidak ditemukan', 404);
        }

        $currentStatus = $this->repository->getSurveyStatus($surveyId);
        $allowedFields = ['title', 'description', 'instructions', 'estimated_time', 'status', 'opens_at', 'closes_at'];
        $fields = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[$field] = $data[$field];
            }
        }

        $hasPositions = array_key_exists('position', $data);

        if (empty($fields) && !$hasPositions) {
            throw new RuntimeException('Tidak ada field yang diupdate', 422);
        }

        if (array_key_exists('title', $fields)) {
            if (!\is_string($fields['title']) || trim($fields['title']) === '') {
                throw new RuntimeException('Judul survei tidak boleh kosong', 422);
            }

            $fields['title'] = trim($fields['title']);
        }

        if (array_key_exists('description', $fields)) {
            $fields['description'] = $this->normalizeOptionalText(
                $fields['description'],
                'Deskripsi (description) harus berupa teks'
            );
        }

        if (array_key_exists('instructions', $fields)) {
            $fields['instructions'] = $this->normalizeOptionalText(
                $fields['instructions'],
                'Petunjuk pengisian (instructions) harus berupa teks'
            );
            $fields['instructions'] = $fields['instructions'] === '' ? null : $fields['instructions'];
        }

        if (array_key_exists('estimated_time', $fields)) {
            $fields['estimated_time'] = $this->normalizeEstimatedTime($fields['estimated_time']);
        }

        if (array_key_exists('status', $fields)) {
            $fields['status'] = $this->normalizeStatus($fields['status']);

            if ($currentStatus !== 'draft' && $fields['status'] !== $currentStatus) {
                throw new RuntimeException('Status survei tidak dapat diubah setelah dipublikasikan', 409);
            }
        }

        if (array_key_exists('opens_at', $fields)) {
            $fields['opens_at'] = $this->normalizeDateTime($fields['opens_at'], 'Format opens_at tidak valid');
        }

        if (array_key_exists('closes_at', $fields)) {
            $fields['closes_at'] = $this->normalizeDateTime($fields['closes_at'], 'Format closes_at tidak valid');
        }

        $opensAt = $fields['opens_at'] ?? null;
        $closesAt = $fields['closes_at'] ?? null;

        if ($opensAt !== null && $closesAt !== null && strtotime($opensAt) >= strtotime($closesAt)) {
            throw new RuntimeException('Waktu pembukaan (opens_at) harus lebih awal dari waktu penutupan (closes_at)', 422);
        }

        if ($hasPositions) {
            $positions = $this->normalizePositions($data['position']);
            $this->repository->deleteRestrictions($surveyId);
            $this->repository->createSurveyRestrictions($surveyId, $positions);
        }

        if (!empty($fields)) {
            $this->repository->updateSurvey($surveyId, $fields);
        }
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
        if (!\is_array($positions)) {
            throw new RuntimeException('Posisi (position) harus berupa array', 422);
        }

        if (empty($positions)) {
            return [];
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
