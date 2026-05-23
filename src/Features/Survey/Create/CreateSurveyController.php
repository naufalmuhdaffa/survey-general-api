<?php

declare(strict_types=1);

namespace App\Features\Survey\Create;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Services\JwtService;

final class CreateSurveyController
{
    private CreateSurveyRepository $repository;

    public function __construct()
    {
        $this->repository = new CreateSurveyRepository();
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
            $thumbnailPath = $this->storeThumbnail($_FILES['thumbnail']);
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

    private function storeThumbnail(array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::json([
                'status' => 'error',
                'message' => 'Gagal upload thumbnail'
            ], 422);
        }

        $maxSize = 2 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            Response::json([
                'status' => 'error',
                'message' => 'Ukuran file thumbnail maksimal 2MB'
            ], 422);
        }

        $allowedMimeTypes = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'image/vnd.microsoft.icon' => 'ico',
            'image/tiff' => 'tiff',
        ];

        $mimeType = mime_content_type($file['tmp_name']);

        if (!isset($allowedMimeTypes[$mimeType])) {
            Response::json([
                'status' => 'error',
                'message' => 'Tipe file thumbnail hanya boleh berupa png, jpg, gif, webp, bmp, ico, dan tiff'
            ], 422);
        }

        $extension = $allowedMimeTypes[$mimeType];
        $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;

        $uploadDir = dirname(__DIR__, 5) . '/public/uploads/survey-thumbnails';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetPath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            Response::json([
                'status' => 'error',
                'message' => 'Gagal memindahkan thumbnail'
            ], 500);
        }

        return '/uploads/survey-thumbnails/' . $fileName;
    }
}