<?php

declare(strict_types=1);

namespace App\Features\Survey\Update;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

final class UpdateSurveyController
{
    private UpdateSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new UpdateSurveyRepository();
    }

    public function update(int $surveyId): void
    {
        AuthMiddleware::handle('admin_opd', 'superadmin');

        if (!$this->repository->surveyExists($surveyId)) {
            Response::json([
                'status' => 'error',
                'message' => 'Survei tidak ditemukan'
            ], 404);
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $allowedFields = ['title', 'description', 'instructions', 'estimated_time', 'opens_at', 'closes_at'];
        $fields = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[$field] = $data[$field];
            }
        }

        $hasPositions = \array_key_exists('position', $data);

        if (empty($fields) && !$hasPositions) {
            Response::json([
                'status' => 'error',
                'message' => 'Tidak ada field yang diupdate'
            ], 422);
        }

        if (isset($fields['title']) && trim($fields['title']) === '') {
            Response::json([
                'status' => 'error',
                'message' => 'Judul survei tidak boleh kosong'
            ], 422);
        }

        if (isset($fields['instructions'])) {
            $fields['instructions'] = trim((string) $fields['instructions']);
            $fields['instructions'] = $fields['instructions'] === '' ? null : $fields['instructions'];
        }

        if (isset($fields['estimated_time'])) {
            $fields['estimated_time'] = $this->normalizeEstimatedTime($fields['estimated_time']);
        }

        $opensAt = $fields['opens_at'] ?? null;
        $closesAt = $fields['closes_at'] ?? null;

        if ($opensAt !== null && strtotime($opensAt) === false) {
            Response::json([
                'status' => 'error',
                'message' => 'Format opens_at tidak valid'
            ], 422);
        }

        if ($closesAt !== null && strtotime($closesAt) === false) {
            Response::json([
                'status' => 'error',
                'message' => 'Format closes_at tidak valid'
            ], 422);
        }

        if ($opensAt !== null && $closesAt !== null && strtotime($opensAt) >= strtotime($closesAt)) {
            Response::json([
                'status' => 'error',
                'message' => 'Waktu pembukaan (opens_at) harus lebih awal dari waktu penutupan (closes_at)'
            ], 422);
        }

        $validPositions = ['public', 'asn', 'non_asn'];

        if ($hasPositions) {
            $positions = $data['position'];

            if (!\is_array($positions) || empty($positions)) {
                Response::json([
                    'status' => 'error',
                    'message' => 'Posisi (position) tidak boleh kosong'
                ], 422);
            }

            $positions = array_values(array_unique($positions));

            foreach ($positions as $position) {
                if (!\in_array($position, $validPositions, true)) {
                    Response::json([
                        'status' => 'error',
                        'message' => 'Posisi tidak valid: ' . $position
                    ], 422);
                }
            }

            $this->repository->deleteRestrictions($surveyId);
            $this->repository->createSurveyRestrictions($surveyId, $positions);
        }

        if (!empty($fields)) {
            $this->repository->updateSurvey($surveyId, $fields);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Survei berhasil diperbarui'
        ]);
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
            Response::json([
                'status' => 'error',
                'message' => 'Estimasi waktu (estimated_time) harus berupa angka lebih dari 0'
            ], 422);
        }

        $estimatedTime = (int) $estimatedTime;

        if ($estimatedTime <= 0) {
            Response::json([
                'status' => 'error',
                'message' => 'Estimasi waktu (estimated_time) harus berupa angka lebih dari 0'
            ], 422);
        }

        return $estimatedTime;
    }
}
