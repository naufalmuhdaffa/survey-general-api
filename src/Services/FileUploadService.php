<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;
use function is_string;

final class FileUploadService
{
    private const SURVEY_THUMBNAIL_MAX_SIZE = 2 * 1024 * 1024;
    private const PROFILE_PHOTO_MAX_SIZE = 3 * 1024 * 1024;

    private const array SURVEY_THUMBNAIL_MIME_TYPES = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    private const array PROFILE_PHOTO_MIME_TYPES = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    /**
     * @param  array $file  Contoh: `$_FILES['thumbnail']` yang berisi: `['name', 'type', 'tmp_name', 'size', 'error']`
     * 
     * @return string       <br>Public path: `/uploads/survey-thumbnails/{date}-{random}.png`
     */
    public function storeSurveyThumbnail(array $file): string
    {
        return $this->storePublicUpload(
            $file,
            'survey-thumbnails',
            self::SURVEY_THUMBNAIL_MAX_SIZE,
            self::SURVEY_THUMBNAIL_MIME_TYPES,
            'thumbnail',
            'Tipe file thumbnail hanya boleh berupa png, jpg, gif, dan webp'
        );
    }

    /**
     * @param  array $file  Contoh: `$_FILES['photo']` yang berisi: `['name', 'type', 'tmp_name', 'size', 'error']`
     *
     * @return string       <br>Public path: `/uploads/profiles/{date}-{random}.jpg`
     */
    public function storeProfilePhoto(array $file): string
    {
        return $this->storePublicUpload(
            $file,
            'profiles',
            self::PROFILE_PHOTO_MAX_SIZE,
            self::PROFILE_PHOTO_MIME_TYPES,
            'foto profil',
            'Tipe file foto profil hanya boleh berupa png, jpg, dan webp'
        );
    }

    /**
     * @param  string|null $path  Public path yang tersimpan di database, contoh: `/uploads/survey-thumbnails/{date}-{random}.png`
     * @return bool               Mengembalikan true jika file berhasil dihapus, false jika sebaliknya
     */
    public function deletePublicUpload(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        $relativePath = ltrim(str_replace('\\', '/', $path), '/');

        if (!str_starts_with($relativePath, 'uploads/')) {
            return false;
        }

        if (basename($relativePath) === 'default.svg') {
            return false;
        }

        $fullPath = $this->resolvePublicPath($relativePath);

        if (!is_file($fullPath)) {
            return false;
        }

        return unlink($fullPath);
    }

    /**
     * @param  array    $file               Contoh: `$_FILES['thumbnail']` yang berisi: `['name', 'type', 'tmp_name', 'size', 'error']`
     * @param  string   $directory          Subdirektori di dalam folder `/public/uploads/`.
     *                                      <br>Contoh: `survey-thumbnails` yang berarti di `/public/uploads/survey-thumbnails/`
     * @param  int      $maxSize            Batas maksimal ukuran file yang diterima. 
     *                                      <br>Contoh: `2 * 1024 * 1024` yang berarti 2MB
     * @param  array    $allowedMimeTypes   Katalog tipe file yang diizinkan. 
     *                                      <br>Contoh: `['image/png' => 'png']`
     * @param  string   $label              Jenis file yang akan ditampilkan pada pesan error. 
     *                                      <br>Contoh: `thumbnail`, pesan error yang muncul: `Gagal upload thumbnail`
     * @param  string   $invalidTypeMessage Isi pesan error yang muncul ketika tipe file yang diperiksa bukan termasuk ke dalam argumen pada @param `$allowedMimeTypes`. 
     *                                      <br>Contoh: `Tipe file thumbnail hanya boleh berupa png`
     * 
     * @return string   Public path: `/uploads/survey-thumbnails/{date}-{random}.png`
     *
     * @throws InvalidArgumentException  Gagal upload `$label` | Ukuran file `$label` maksimal `$maxSize` | File `$label` tidak valid | `$invalidTypeMessage`
     * @throws RuntimeException          Gagal membuat folder upload | Gagal memindahkan `$label`
     */
    private function storePublicUpload(
        array $file,
        string $directory,
        int $maxSize,
        array $allowedMimeTypes,
        string $label,
        string $invalidTypeMessage
    ): string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Gagal upload ' . $label);
        }

        if (($file['size'] ?? 0) > $maxSize) {
            throw new InvalidArgumentException('Ukuran file ' . $label . ' maksimal ' . $this->formatBytes($maxSize));
        }

        $tmpName = $file['tmp_name'] ?? null;

        if (!is_string($tmpName) || $tmpName === '') {
            throw new InvalidArgumentException('File ' . $label . ' tidak valid');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpName);

        if (!is_string($mimeType) || !isset($allowedMimeTypes[$mimeType])) {
            throw new InvalidArgumentException($invalidTypeMessage);
        }

        $safeDirectory = $this->normalizeDirectory($directory);
        $extension = $allowedMimeTypes[$mimeType];

        $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(16)) . '.' . $extension;
        $uploadDir = $this->resolvePublicPath('uploads/' . $safeDirectory);

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new RuntimeException('Gagal membuat folder upload');
        }

        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('Gagal memindahkan ' . $label);
        }

        return '/uploads/' . $safeDirectory . '/' . $fileName;
    }

    /**
     * Melakukan format ukuran file dari byte ke string yang lebih mudah dibaca (KB, MB, GB).
     * 
     * @param int $bytes Ukuran file dalam byte, Contoh: `2 * 1024 * 1024` atau `2097152` yang berarti 2MB
     * 
     * @return string Ukuran file yang sudah diformat, contoh: `2MB`
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 1) . 'GB';
        }

        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . 'MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . 'KB';
        }

        return $bytes . 'B';
    }

    /**
     * Sanitasi subdirektori - cegah path traversal.
     *
     * Contoh:
     *   <br>`survey-thumbnails`  -> `survey-thumbnails` => lolos
     *   <br>`/survey-thumbnails` -> `survey-thumbnails` => slash di awal di-trim
     *   <br>`../config`          -> InvalidArgumentException => ditolak karena ada `..`
     *   <br>`""`                 -> InvalidArgumentException => ditolak karena (string) kosong
     * 
     * @param string $directory     Subdirektori di dalam folder `/public/uploads/`.
     *                              <br>Contoh: `survey-thumbnails` yang berarti di `/public/uploads/survey-thumbnails/`
     * 
     * @return string               Subdirektori yang sudah dinormalisasi
     *
     * @throws InvalidArgumentException Folder upload tidak valid
     */
    private function normalizeDirectory(string $directory): string
    {
        $directory = trim(str_replace('\\', '/', $directory), '/');

        if ($directory === '' || str_contains($directory, '..')) {
            throw new InvalidArgumentException('Folder upload tidak valid');
        }

        return $directory;
    }

    /**
     * Mendapatkan absolute path ke public folder, dengan opsi subpath tambahan.
     * Environment-agnostic: bekerja di Windows maupun Linux tanpa masalah separator path, `/` maupun `\`.
     * 
     * @param string $path Relative subpath di dalam public folder, contoh: `uploads/survey-thumbnails/{date}-{random}.png`
     * 
     * @return string Path absolut ke file di disk, contoh: `C:\path\to\project\public\uploads\survey-thumbnails\{date}-{random}.png`
     */
    private function resolvePublicPath(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
        $path = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

        return $path === '' ? $basePath : $basePath . DIRECTORY_SEPARATOR . $path;
    }
}
