<?php
/**
 * Steirer Pellets – Bestellformular Mailer
 * Empfänger-Adresse unten anpassen!
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://www.steirerpellets.at');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// ── EMPFÄNGER HIER EINTRAGEN ──────────────────
$empfaenger = 'andreas.edler@bioenergie.at';
$betreff_prefix = '[Steirer Pellets] Neue Bestellung';
// ─────────────────────────────────────────────

// Eingabe lesen (JSON oder POST)
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) $data = $_POST;

// Felder sichern
function s($v) { return htmlspecialchars(strip_tags(trim((string)$v)), ENT_QUOTES, 'UTF-8'); }

$menge       = s($data['menge'] ?? '');
$lieferkw    = s($data['lieferkw'] ?? '');
$plz         = s($data['plz'] ?? '');
$ort         = s($data['ort'] ?? '');
$strasse     = s($data['strasse'] ?? '');
$zufahrt     = s($data['zufahrt'] ?? '');
$vorname     = s($data['vorname'] ?? '');
$nachname    = s($data['nachname'] ?? '');
$email       = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
$email       = str_replace(["\r", "\n", "%0a", "%0d"], '', $email);
$telefon     = s($data['telefon'] ?? '');
$gesamtpreis = s($data['gesamtpreis'] ?? '');
$sent_at     = date('d.m.Y H:i') . ' Uhr';

// Rate-Limiting (einfach via Session)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$now = time();
$lastSubmit = $_SESSION['sp_last_order'] ?? 0;
if ($now - $lastSubmit < 30) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Bitte warten Sie 30 Sekunden zwischen Bestellungen']);
    exit;
}
$_SESSION['sp_last_order'] = $now;

// Pflichtfelder prüfen
if (!$menge || !$vorname || !$nachname || !$email || !$telefon || !$plz) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Pflichtfelder fehlen']);
    exit;
}

// Format-Validierung
if (!preg_match('/^[0-9]{4}$/', $plz)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Ungültige PLZ']);
    exit;
}
if (mb_strlen($vorname) > 100 || mb_strlen($nachname) > 100 || mb_strlen($strasse) > 200 || mb_strlen($ort) > 100) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Feldlänge überschritten']);
    exit;
}

// ── E-MAIL AN BÜRO ────────────────────────────
$betreff = "{$betreff_prefix}: {$menge} t – {$vorname} {$nachname} ({$plz} {$ort})";

$nachricht  = "╔══════════════════════════════════════════════╗\n";
$nachricht .= "║       NEUE PELLETS-BESTELLUNG                ║\n";
$nachricht .= "╚══════════════════════════════════════════════╝\n\n";

$nachricht .= "📦 BESTELLUNG\n";
$nachricht .= "─────────────────────────────────────────────\n";
$nachricht .= "Menge:           {$menge} Tonnen (lose)\n";
$nachricht .= "Lieferwoche:     {$lieferkw}\n";
$nachricht .= "Ges. Preis:      {$gesamtpreis} € (Schätzung)\n\n";

$nachricht .= "📍 LIEFERADRESSE\n";
$nachricht .= "─────────────────────────────────────────────\n";
$nachricht .= "Straße:          {$strasse}\n";
$nachricht .= "Ort:             {$plz} {$ort}\n";
$nachricht .= "Zufahrt:         {$zufahrt}\n\n";

$nachricht .= "👤 KONTAKT\n";
$nachricht .= "─────────────────────────────────────────────\n";
$nachricht .= "Name:            {$vorname} {$nachname}\n";
$nachricht .= "E-Mail:          {$email}\n";
$nachricht .= "Telefon:         {$telefon}\n\n";

$nachricht .= "─────────────────────────────────────────────\n";
$nachricht .= "Eingegangen:     {$sent_at}\n";
$nachricht .= "─────────────────────────────────────────────\n";

$headers  = "From: Steirer Pellets Website <noreply@steirerpellets.at>\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$ok = mail($empfaenger, '=?UTF-8?B?' . base64_encode($betreff) . '?=', $nachricht, $headers);

// ── BESTÄTIGUNGS-MAIL AN KUNDEN ───────────────
if ($ok && $email) {
    $kunden_betreff = "Ihre Pellets-Bestellung bei Steirer Pellets";
    $kunden_mail  = "Sehr geehrte/r {$vorname} {$nachname},\n\n";
    $kunden_mail .= "vielen Dank für Ihre Bestellung bei Steirer Pellets!\n\n";
    $kunden_mail .= "Wir haben Ihre Anfrage erhalten und melden uns innerhalb von\n";
    $kunden_mail .= "24 Stunden telefonisch oder per E-Mail bei Ihnen.\n\n";
    $kunden_mail .= "Ihre Bestellung im Überblick:\n";
    $kunden_mail .= "─────────────────────────────\n";
    $kunden_mail .= "Menge:        {$menge} Tonnen lose\n";
    $kunden_mail .= "Lieferwoche:  {$lieferkw}\n";
    $kunden_mail .= "Lieferort:    {$plz} {$ort}\n";
    $kunden_mail .= "Ges. Preis:   ca. {$gesamtpreis} € (inkl. 58 € Abschlauch)\n\n";
    $kunden_mail .= "Bei Fragen erreichen Sie uns unter:\n";
    $kunden_mail .= "📞 0676 7060300  (Mo–Fr 08:00–17:00)\n";
    $kunden_mail .= "✉  office@steirerpellets.at\n\n";
    $kunden_mail .= "Mit freundlichen Grüßen\n";
    $kunden_mail .= "Ihr Steirer Pellets Team\n\n";
    $kunden_mail .= "──────────────────────────────────────────\n";
    $kunden_mail .= "Steirer Pellets GmbH · 8580 Köflach · Alte Hauptstraße 9\n";
    $kunden_mail .= "www.steirerpellets.at\n";

    $kunden_headers  = "From: Steirer Pellets <office@steirerpellets.at>\r\n";
    $kunden_headers .= "MIME-Version: 1.0\r\n";
    $kunden_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $kunden_headers .= "Content-Transfer-Encoding: 8bit\r\n";

    mail($email, '=?UTF-8?B?' . base64_encode($kunden_betreff) . '?=', $kunden_mail, $kunden_headers);
}

echo json_encode(['ok' => $ok]);
