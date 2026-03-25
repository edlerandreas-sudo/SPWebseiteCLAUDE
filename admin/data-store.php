<?php
function sp_data_path(string $file): string {
    return dirname(__DIR__) . '/data/' . $file;
}

function sp_read_json(string $file, $default) {
    $path = sp_data_path($file);
    if (!is_file($path)) {
        return $default;
    }
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return $default;
    }
    $decoded = json_decode($raw, true);
    return $decoded === null ? $default : $decoded;
}

function sp_write_json(string $file, $data): void {
    $path = sp_data_path($file);
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function sp_json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sp_require_admin(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['sp_admin_auth'])) {
        sp_json_response(['error' => 'unauthorized'], 401);
    }
}
