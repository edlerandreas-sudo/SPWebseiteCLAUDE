<?php
require __DIR__ . '/data-store.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $row = sp_read_json('preise.json', [
        'id' => 'aktuell',
        'preis_gross' => 398,
        'preis_klein' => 418,
        'abschlauch' => 58,
        'updated_at_label' => ''
    ]);
    sp_json_response(['data' => [$row]]);
}

sp_require_admin();
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Validierung
$pg = $input['preis_gross'] ?? 398;
$pk = $input['preis_klein'] ?? 418;
$ab = $input['abschlauch'] ?? 58;
$label = (string)($input['updated_at_label'] ?? '');
if (!is_numeric($pg) || !is_numeric($pk) || !is_numeric($ab)) {
    sp_json_response(['error' => 'invalid_number'], 400);
}
if (mb_strlen($label) > 100) {
    sp_json_response(['error' => 'label_too_long'], 400);
}

$row = [
    'id' => 'aktuell',
    'preis_gross' => (float)$pg,
    'preis_klein' => (float)$pk,
    'abschlauch' => (float)$ab,
    'updated_at_label' => $label,
];
sp_write_json('preise.json', $row);
sp_json_response($row);
