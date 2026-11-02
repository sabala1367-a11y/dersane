<?php
// Admin anafuta maombi YOTE yaliyotumwa (jedwali la applications). Akaunti za watumiaji hazifutwi.
// Inahitaji ombi la POST lenye {"confirm":"FUTA"} ili kuzuia kufuta kwa bahati mbaya.
require 'config-admission-system.php';
require_admin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json_out(false, 'Njia ya ombi si sahihi.');
}
$in = json_decode(file_get_contents('php://input'), true) ?? [];
if (($in['confirm'] ?? '') !== 'FUTA') json_out(false, 'Andika FUTA ili kuthibitisha.');

$n = $pdo->exec('DELETE FROM applications');
json_out(true, 'Maombi yote yamefutwa.', ['deleted' => (int)$n]);
