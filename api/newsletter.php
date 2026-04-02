<?php
/**
 * Newsletter Double Opt-In Endpoint
 *
 * POST /api/newsletter.php          -> Anmeldung (speichert pending, sendet Bestätigungs-Mail)
 * GET  /api/newsletter.php?token=X  -> Bestätigung (aktiviert Abo)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../admin/data-store.php';

// ── Konfiguration ──
define('NL_FROM_EMAIL', 'info@steirerpellets.at');
define('NL_FROM_NAME',  'Steirer Pellets');
define('NL_SITE_URL',   'https://www.steirerpellets.at');

// ── Hilfsfunktionen ──
function nl_read(): array {
    return sp_read_json('newsletter.json', []);
}

function nl_write(array $data): void {
    sp_write_json('newsletter.json', $data);
}

function nl_token(): string {
    return bin2hex(random_bytes(32));
}

function nl_send_confirmation(string $email, string $token): bool {
    $confirmUrl = NL_SITE_URL . '/api/newsletter.php?token=' . urlencode($token);

    $subject = '=?UTF-8?B?' . base64_encode('Bitte bestätigen Sie Ihre Newsletter-Anmeldung') . '?=';

    $body = '
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f4f4f4;padding:40px 20px;">
  <div style="max-width:520px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    <div style="background:#2E7D32;padding:28px 32px;text-align:center;">
      <h1 style="color:#fff;margin:0;font-size:22px;">Steirer Pellets</h1>
      <p style="color:rgba(255,255,255,0.8);margin:6px 0 0;font-size:14px;">Newsletter-Anmeldung bestätigen</p>
    </div>
    <div style="padding:32px;">
      <p style="color:#333;font-size:15px;line-height:1.7;">
        Vielen Dank für Ihr Interesse an unserem Newsletter!<br><br>
        Bitte klicken Sie auf den folgenden Button, um Ihre Anmeldung zu bestätigen:
      </p>
      <div style="text-align:center;margin:28px 0;">
        <a href="' . htmlspecialchars($confirmUrl) . '"
           style="display:inline-block;background:#2E7D32;color:#fff;text-decoration:none;
                  padding:14px 36px;border-radius:10px;font-weight:bold;font-size:15px;">
          Anmeldung bestätigen
        </a>
      </div>
      <p style="color:#888;font-size:13px;line-height:1.6;">
        Falls Sie diese Anmeldung nicht angefordert haben, können Sie diese E-Mail einfach ignorieren.
      </p>
    </div>
    <div style="background:#f9f9f9;padding:16px 32px;text-align:center;border-top:1px solid #eee;">
      <p style="color:#aaa;font-size:12px;margin:0;">Steirer Pellets GmbH &middot; Köflach, Steiermark</p>
    </div>
  </div>
</body>
</html>';

    $headers  = "From: " . NL_FROM_NAME . " <" . NL_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . NL_FROM_EMAIL . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    return mail($email, $subject, $body, $headers);
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

    // Prüfe ob bereits bestätigt
    foreach ($subscribers as $sub) {
        if ($sub['email'] === $email && $sub['status'] === 'confirmed') {
            echo json_encode(['ok' => true, 'message' => 'Sie sind bereits angemeldet.']);
            exit;
        }
    }

    // Vorhandenen pending-Eintrag entfernen (erneuter Versuch)
    $subscribers = array_values(array_filter($subscribers, function($s) use ($email) {
        return $s['email'] !== $email;
    }));

    // Neuen Eintrag anlegen
    $token = nl_token();
    $subscribers[] = [
        'email'      => $email,
        'token'      => $token,
        'status'     => 'pending',
        'created_at' => date('c'),
        'confirmed_at' => null
    ];

    nl_write($subscribers);

    // Bestätigungs-Mail senden
    $mailSent = nl_send_confirmation($email, $token);

    echo json_encode([
        'ok'      => true,
        'message' => 'Bitte prüfen Sie Ihr Postfach und bestätigen Sie Ihre Anmeldung.',
        'mail_sent' => $mailSent
    ]);
    exit;
}

// ── GET: Bestätigung per Token ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    $token = trim($_GET['token']);

    if (!$token) {
        http_response_code(400);
        echo 'Ungültiger Token.';
        exit;
    }

    $subscribers = nl_read();
    $found = false;
    $email = '';

    foreach ($subscribers as &$sub) {
        if ($sub['token'] === $token) {
            $sub['status'] = 'confirmed';
            $sub['confirmed_at'] = date('c');
            $found = true;
            $email = $sub['email'];
            break;
        }
    }
    unset($sub);

    if (!$found) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Fehler</title></head><body style="font-family:Arial,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f4f4f4;"><div style="text-align:center;background:#fff;padding:48px;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.08);"><h1 style="color:#c62828;">Link ungültig</h1><p style="color:#666;">Dieser Bestätigungslink ist ungültig oder wurde bereits verwendet.</p><a href="' . NL_SITE_URL . '" style="display:inline-block;margin-top:20px;color:#2E7D32;font-weight:bold;text-decoration:none;">Zur Startseite</a></div></body></html>';
        exit;
    }

    nl_write($subscribers);

    // Erfolgsseite anzeigen
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Newsletter bestätigt – Steirer Pellets</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f4f4f4;margin:0;">
  <div style="text-align:center;background:#fff;padding:48px;border-radius:16px;max-width:480px;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    <div style="width:72px;height:72px;border-radius:50%;background:#2E7D32;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
    </div>
    <h1 style="color:#2E7D32;font-size:1.6rem;margin:0 0 12px;">Anmeldung bestätigt!</h1>
    <p style="color:#555;font-size:1rem;line-height:1.7;">Vielen Dank! Ihre E-Mail-Adresse <strong>' . htmlspecialchars($email) . '</strong> wurde erfolgreich für unseren Newsletter registriert.</p>
    <p style="color:#888;font-size:0.9rem;margin-top:16px;">Sie erhalten ab sofort Preisalarme, exklusive Angebote und Heiztipps.</p>
    <a href="' . NL_SITE_URL . '" style="display:inline-block;margin-top:24px;background:#2E7D32;color:#fff;text-decoration:none;padding:12px 32px;border-radius:10px;font-weight:bold;">Zur Startseite</a>
  </div>
</body>
</html>';
    exit;
}

// Fallback
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
