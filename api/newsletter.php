<?php
/**
 * Newsletter Anmeldung
 *
 * POST /api/newsletter.php  -> Speichert Subscriber + sendet Benachrichtigung via Web3Forms
 */

// ── CORS ──
header('Access-Control-Allow-Origin: https://www.steirerpellets.at');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../admin/data-store.php';

define('WEB3FORMS_KEY', '8b18adc8-a507-499e-95a0-54c1485b341d');

// ── Rate Limiting (max 3 Anmeldungen pro IP / 5 Min) ──
function nl_check_rate_limit(): bool {
    $rateFile = dirname(__DIR__) . '/data/nl_rate.json';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $now = time();
    $window = 300; // 5 Minuten
    $maxAttempts = 3;

    $data = [];
    if (is_file($rateFile)) {
        $raw = file_get_contents($rateFile);
        $data = json_decode($raw, true) ?: [];
    }

    // Alte Einträge bereinigen
    foreach ($data as $k => $timestamps) {
        $data[$k] = array_values(array_filter($timestamps, fn($t) => $t > $now - $window));
        if (empty($data[$k])) unset($data[$k]);
    }

    // Prüfen
    $attempts = $data[$ip] ?? [];
    if (count($attempts) >= $maxAttempts) {
        file_put_contents($rateFile, json_encode($data));
        return false;
    }

    // Eintragen
    $data[$ip][] = $now;
    file_put_contents($rateFile, json_encode($data));
    return true;
}

// ── Hilfsfunktionen ──
function nl_read(): array {
    return sp_read_json('newsletter.json', []);
}

function nl_write(array $data): void {
    sp_write_json('newsletter.json', $data);
}

function nl_notify_web3forms(string $email): bool {
    $payload = json_encode([
        'access_key' => WEB3FORMS_KEY,
        'subject'    => 'Neue Newsletter-Anmeldung: ' . $email,
        'from_name'  => 'Newsletter Steirer Pellets',
        'email'      => $email,
        'message'    => "Neue Newsletter-Anmeldung:\n\nE-Mail: " . $email . "\nDatum: " . date('d.m.Y H:i') . " Uhr"
    ]);

    $ch = curl_init('https://api.web3forms.com/submit');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $result = curl_exec($ch);
    $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    return $ok;
}

// ── POST: Neue Anmeldung ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Rate Limit prüfen
    if (!nl_check_rate_limit()) {
        http_response_code(429);
        echo json_encode(['error' => 'Zu viele Anfragen. Bitte versuchen Sie es in einigen Minuten erneut.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.']);
        exit;
    }

    $email = strtolower($email);
    $subscribers = nl_read();

    // Prüfe ob bereits angemeldet
    foreach ($subscribers as $sub) {
        if ($sub['email'] === $email) {
            echo json_encode(['ok' => true, 'message' => 'Sie sind bereits für unseren Newsletter angemeldet.']);
            exit;
        }
    }

    // Neuen Eintrag anlegen
    $subscribers[] = [
        'email'      => $email,
        'created_at' => date('c')
    ];

    nl_write($subscribers);

    // Admin-Benachrichtigung via Web3Forms
    nl_notify_web3forms($email);

    echo json_encode([
        'ok'      => true,
        'message' => 'Vielen Dank! Sie wurden erfolgreich für unseren Newsletter angemeldet.'
    ]);
    exit;
}

// Fallback
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
