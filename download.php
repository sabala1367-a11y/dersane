<?php
// Barua ya kujiunga na maelekezo: wanaruhusiwa waliokubaliwa tu. PDF ziko kwenye folda ya private/ (haifunguki moja kwa moja).
require 'config-admission-system.php';
$u = current_user($pdo);
if (!$u) { http_response_code(401); exit('Ingia kwanza.'); }
$q = $pdo->prepare("SELECT 1 FROM applications WHERE user_id = ? AND status = 'admitted'");
$q->execute([$u['id']]);
if (!$q->fetch()) { http_response_code(403); exit('Huna ruhusa.'); }
$files = ['letter' => ['admission-letter.pdf', 'Admission-Letter.pdf'], 'joining' => ['joining-instructions.pdf', 'Joining-Instructions.pdf']];
$f = $files[$_GET['f'] ?? ''] ?? null;
$path = $f ? __DIR__ . '/private/' . $f[0] : '';
if (!$f || !is_file($path)) { http_response_code(404); exit('Faili halipatikani.'); }
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $f[1] . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
