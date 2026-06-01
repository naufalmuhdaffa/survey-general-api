<?php

$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Dependensi belum terpasang. Jalankan `composer install` di terminal direktori proyek.'
    ]);
    exit;
}

require_once $autoload;

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
// $dotenv->required(['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS']); // Sudah ditangani dengan throw di Database.php

if ($_ENV['APP_ENV'] === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
}

use App\Helpers\Response;
use App\Route;

if ($_ENV['APP_ENV'] === 'development' || !empty($_ENV['CORS_ALLOWED_ORIGINS'])) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    $allowedOrigins = array_filter(array_map('trim', explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '')));
    $originAllowed = $origin !== null
        && (($_ENV['APP_ENV'] === 'development' && empty($allowedOrigins)) || in_array($origin, $allowedOrigins, true));

    if ($originAllowed) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-API-Key');
    header('Access-Control-Max-Age: 86400');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (str_starts_with($path, $scriptName)) {
    $path = substr($path, strlen($scriptName));
}

$path = '/' . trim($path, '/');

if ($path === '//') {
    $path = '/';
}

$method = $_SERVER['REQUEST_METHOD'];
$segments = array_values(array_filter(explode('/', $path)));

try {
    if (Route::dispatch($path, $method, $segments)) {
        exit;
    }
    
    Response::json([
        'status' => 'error',
        'message' => 'Endpoint tidak ditemukan'
    ], 404);
} catch (Throwable $e) {

    if ($_ENV['APP_ENV'] === 'development') {
        Response::json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    } else {
        Response::json(['error' => 'Kesalahan tak terduga'], 500);
    }
}
