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
$row = [
    'id' => 'aktuell',
    'preis_gross' => (float)($input['preis_gross'] ?? 398),
    'preis_klein' => (float)($input['preis_klein'] ?? 418),
    'abschlauch' => (float)($input['abschlauch'] ?? 58),
    'updated_at_label' => (string)($input['updated_at_label'] ?? ''),
];
sp_write_json('preise.json', $row);
sp_json_response($row);
