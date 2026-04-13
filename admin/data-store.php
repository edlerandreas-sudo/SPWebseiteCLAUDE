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
    // Backup before overwrite
    if (is_file($path)) {
        $backupDir = $dir . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }
        copy($path, $backupDir . '/' . basename($file, '.json') . '_' . date('Y-m-d_H-i-s') . '.json');
        // Keep only last 20 backups per file
        $prefix = basename($file, '.json') . '_';
        $backups = glob($backupDir . '/' . $prefix . '*.json');
        if ($backups && count($backups) > 20) {
            sort($backups);
            foreach (array_slice($backups, 0, count($backups) - 20) as $old) {
                @unlink($old);
            }
        }
    }
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

/** Ensure essential data files exist (creates empty defaults if missing) */
function sp_ensure_data_files(): void {
    $defaults = [
        'blog_articles.json' => '[]',
        'preise.json'        => '{"id":"aktuell","preis_gross":398,"preis_klein":418,"abschlauch":58,"updated_at_label":""}',
    ];
    foreach ($defaults as $file => $content) {
        $path = sp_data_path($file);
        if (!is_file($path)) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            file_put_contents($path, $content, LOCK_EX);
        }
    }
}

// Auto-init on every include
sp_ensure_data_files();

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
    // CSRF-Schutz für state-ändernde Requests
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!sp_verify_csrf($token)) {
            sp_json_response(['error' => 'invalid_csrf_token'], 403);
        }
    }
}

function sp_generate_csrf(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['sp_csrf_token'])) {
        $_SESSION['sp_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['sp_csrf_token'];
}

function sp_verify_csrf(string $token): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return !empty($token) && hash_equals($_SESSION['sp_csrf_token'] ?? '', $token);
}
