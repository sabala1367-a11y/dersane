<?php
// Hali ya dirisha la usajili (wazi/limefungwa) - inahifadhiwa kwenye faili, HAKUNA database.
// GET  -> {"open":true|false}   (inatumiwa na applications-closed.html na Neon_Orbit_Login.html)
// POST -> {"open":true|false}   (inatumiwa na dashboard ya admin kubadilisha hali)
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$file = __DIR__ . '/data/admission_window.json';

function read_state(string $file): bool {
    if (!is_file($file)) return true;                       // bila faili = dirisha wazi
    $j = json_decode((string)@file_get_contents($file), true);
    return !is_array($j) || ($j['open'] ?? true) !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($in) || !array_key_exists('open', $in)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Ombi si sahihi.']);
        exit;
    }
    $open = (bool)$in['open'];
    $ok = @file_put_contents($file, json_encode(['open' => $open, 'updated_at' => date('c')]), LOCK_EX) !== false;
    if (!$ok) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Imeshindikana kuhifadhi. Hakikisha folda ya data inaruhusu kuandikwa.']);
        exit;
    }
    echo json_encode(['ok' => true, 'open' => $open]);
    exit;
}

echo json_encode(['ok' => true, 'open' => read_state($file)]);
