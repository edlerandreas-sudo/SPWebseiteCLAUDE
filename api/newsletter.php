<?php
/**
 * Newsletter Anmeldung
 *
 * POST /api/newsletter.php  -> Speichert Subscriber + sendet Benachrichtigung via Web3Forms
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../admin/data-store.php';

define('WEB3FORMS_KEY', '8b18adc8-a507-499e-95a0-54c1485b341d');

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
