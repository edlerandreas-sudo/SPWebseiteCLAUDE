<?php
require __DIR__ . '/data-store.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ── Öffentliche Validierung (GET mit ?code=XXX&menge=X) ──
if ($method === 'GET' && !empty($_GET['code'])) {
    $code  = strtoupper(trim($_GET['code']));
    $menge = (int)($_GET['menge'] ?? 0);
    $codes = sp_read_json('discounts.json', []);

    foreach ($codes as $c) {
        if (strtoupper($c['code']) !== $code) continue;
        if (!$c['active']) {
            sp_json_response(['valid' => false, 'error' => 'Code ist nicht mehr aktiv.']);
        }
        if (!empty($c['valid_from']) && date('Y-m-d') < $c['valid_from']) {
            sp_json_response(['valid' => false, 'error' => 'Code ist noch nicht gültig.']);
        }
        if (!empty($c['valid_to']) && date('Y-m-d') > $c['valid_to']) {
            sp_json_response(['valid' => false, 'error' => 'Code ist abgelaufen.']);
        }
        if ($c['max_uses'] > 0 && $c['used_count'] >= $c['max_uses']) {
            sp_json_response(['valid' => false, 'error' => 'Code wurde bereits vollständig eingelöst.']);
        }
        if ($menge > 0 && !empty($c['min_menge']) && $menge < $c['min_menge']) {
            sp_json_response(['valid' => false, 'error' => "Mindestbestellmenge: {$c['min_menge']} Tonnen."]);
        }

        // Rabatt berechnen
        $discount = 0;
        if (!empty($c['discount_percent'])) {
            $discount_info = ['type' => 'percent', 'value' => (float)$c['discount_percent']];
        } else {
            $discount_info = ['type' => 'fixed', 'value' => (float)($c['discount_fixed'] ?? 0)];
        }

        sp_json_response([
            'valid'    => true,
            'code'     => $c['code'],
            'discount' => $discount_info,
            'label'    => $c['label'] ?? $c['code']
        ]);
    }

    sp_json_response(['valid' => false, 'error' => 'Ungültiger Rabattcode.']);
}

// ── Öffentlich: Code einlösen (POST ohne Admin, mit code-Feld) ──
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    // Einlöse-Tracking (von Bestellformular aufgerufen)
    if (!empty($input['redeem_code'])) {
        $code  = strtoupper(trim($input['redeem_code']));
        $codes = sp_read_json('discounts.json', []);
        foreach ($codes as &$c) {
            if (strtoupper($c['code']) === $code && $c['active']) {
                $c['used_count'] = ($c['used_count'] ?? 0) + 1;
                sp_write_json('discounts.json', $codes);
                sp_json_response(['ok' => true]);
            }
        }
        sp_json_response(['ok' => false], 400);
    }
}

// ── Admin: Codes auflisten (GET ohne ?code) ──
if ($method === 'GET') {
    sp_require_admin();
    $codes = sp_read_json('discounts.json', []);
    sp_json_response(['data' => $codes]);
}

// ── Admin: CRUD ──
sp_require_admin();
$input = json_decode(file_get_contents('php://input'), true) ?: [];

if ($method === 'PUT') {
    // Neuen Code anlegen oder bestehenden aktualisieren
    $codes = sp_read_json('discounts.json', []);
    $code  = strtoupper(trim($input['code'] ?? ''));
    if (strlen($code) < 2 || strlen($code) > 30) {
        sp_json_response(['error' => 'Code muss 2-30 Zeichen lang sein.'], 400);
    }
    if (!preg_match('/^[A-Z0-9\-_]+$/', $code)) {
        sp_json_response(['error' => 'Nur Buchstaben, Zahlen, Bindestrich und Unterstrich erlaubt.'], 400);
    }

    $entry = [
        'code'             => $code,
        'label'            => trim($input['label'] ?? $code),
        'discount_percent' => max(0, min(100, (float)($input['discount_percent'] ?? 0))),
        'discount_fixed'   => max(0, (float)($input['discount_fixed'] ?? 0)),
        'active'           => (bool)($input['active'] ?? true),
        'max_uses'         => max(0, (int)($input['max_uses'] ?? 0)),
        'used_count'       => 0,
        'min_menge'        => max(0, (int)($input['min_menge'] ?? 0)),
        'valid_from'       => $input['valid_from'] ?? '',
        'valid_to'         => $input['valid_to'] ?? '',
        'created_at'       => date('Y-m-d H:i:s'),
    ];

    // Prüfen ob Code schon existiert – bestehende Werte als Fallback
    $found = false;
    foreach ($codes as &$c) {
        if (strtoupper($c['code']) === $code) {
            // Felder nur überschreiben wenn explizit gesendet
            $entry['discount_percent'] = isset($input['discount_percent']) ? max(0, min(100, (float)$input['discount_percent'])) : ($c['discount_percent'] ?? 0);
            $entry['discount_fixed']   = isset($input['discount_fixed']) ? max(0, (float)$input['discount_fixed']) : ($c['discount_fixed'] ?? 0);
            $entry['label']            = isset($input['label']) ? trim($input['label']) : ($c['label'] ?? $code);
            $entry['max_uses']         = isset($input['max_uses']) ? max(0, (int)$input['max_uses']) : ($c['max_uses'] ?? 0);
            $entry['min_menge']        = isset($input['min_menge']) ? max(0, (int)$input['min_menge']) : ($c['min_menge'] ?? 0);
            $entry['valid_from']       = $input['valid_from'] ?? ($c['valid_from'] ?? '');
            $entry['valid_to']         = $input['valid_to'] ?? ($c['valid_to'] ?? '');
            $entry['used_count']       = $c['used_count'] ?? 0;
            $entry['created_at']       = $c['created_at'] ?? date('Y-m-d H:i:s');
            $c = $entry;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $codes[] = $entry;
    }

    sp_write_json('discounts.json', $codes);
    sp_json_response($entry);
}

if ($method === 'DELETE') {
    $code  = strtoupper(trim($input['code'] ?? ''));
    $codes = sp_read_json('discounts.json', []);
    $codes = array_values(array_filter($codes, fn($c) => strtoupper($c['code']) !== $code));
    sp_write_json('discounts.json', $codes);
    sp_json_response(['ok' => true]);
}
