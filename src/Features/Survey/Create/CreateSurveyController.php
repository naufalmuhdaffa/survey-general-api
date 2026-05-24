<?php

declare(strict_types=1);

namespace App\Features\Survey\Create;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Services\FileUploadService;
use App\Services\JwtService;
use InvalidArgumentException;
use RuntimeException;

final class CreateSurveyController
{
    private CreateSurveyRepository $repository;
    private FileUploadService $fileUploadService;

    public function __construct()
    {
        $this->repository = new CreateSurveyRepository();
        $this->fileUploadService = new FileUploadService();
    }

    public function create(): void
    {
        AuthMiddleware::handle('admin_opd', 'superadmin');

        $token = JwtService::bearerToken();
        $payload = JwtService::verify($token);
        $createdBy = (int) $payload->data->userId;

        $isMultipart = str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data');
        $data = $isMultipart ? $_POST : json_decode(file_get_contents('php://input'), true);

        if (!isset($data['title']) || trim($data['title']) === '') {
            Response::json([
                'status' => 'error',
                'message' => 'Judul survei harus diisi'
            ], 422);
        }

        $title = trim($data['title']);
        $description = isset($data['description']) ? trim($data['description']) : null;
        $opensAt = $data['opens_at'] ?? null;
        $closesAt = $data['closes_at'] ?? null;

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
        $positions = $data['position'] ?? [];

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

        $thumbnailPath = null;

        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $thumbnailPath = $this->fileUploadService->storeSurveyThumbnail($_FILES['thumbnail']);
            } catch (InvalidArgumentException $e) {
                Response::json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 422);
            } catch (RuntimeException $e) {
                Response::json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        $surveyId = $this->repository->createSurvey(
            $title,
            $description,
            $thumbnailPath,
            $createdBy,
            $opensAt,
            $closesAt
        );

        if (!empty($positions)) {
            $this->repository->createSurveyRestrictions($surveyId, $positions);
        }

        Response::json([
            'status' => 'success',
            'message' => 'Survei berhasil dibuat',
            'data' => ['id' => $surveyId]
        ], 201);
    }
}
