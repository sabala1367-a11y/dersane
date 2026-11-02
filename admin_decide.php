<?php
// Admin anakubali / anakataa / anarudisha ombi kwenye "inasubiri".
require 'config-admission-system.php';
$admin = require_admin($pdo);
$in     = json_decode(file_get_contents('php://input'), true) ?? [];
$id     = (int)($in['id'] ?? 0);
$status = (string)($in['status'] ?? '');
if ($id < 1 || !in_array($status, ['pending', 'admitted', 'rejected'], true)) json_out(false, 'Ombi au hali si sahihi.');

$adm = $house = $why = null;
if ($status === 'admitted') {
    // Namba ya kujiunga sasa inatengenezwa na seva yenyewe (haitoki tena kwenye fomu).
    $house = mb_substr(trim((string)($in['house'] ?? '')), 0, 100);
    if ($house === '') json_out(false, 'Weka eneo la makazi.');
} elseif ($status === 'rejected') {
    $why = trim((string)($in['reason'] ?? ''));
    if ($why === '') json_out(false, 'Andika sababu ya kukataliwa.');
}
$at = $status === 'pending' ? null : date('Y-m-d H:i:s');
$by = $status === 'pending' ? null : $admin['id'];

const ADM_START = 202611890; // namba ya kwanza itakuwa 202611891

try {
    if ($status !== 'admitted') {
        $st = $pdo->prepare('UPDATE applications SET status=?, admission_no=?, house_location=?, reject_reason=?, decided_at=?, decided_by=? WHERE id=?');
        $st->execute([$status, $adm, $house, $why, $at, $by, $id]);
        json_out(true, 'Uamuzi umehifadhiwa.');
    }

    // Kama mwombaji tayari ana namba (alikubaliwa awali), aendelee nayo.
    $cur = $pdo->prepare("SELECT admission_no FROM applications WHERE id=? AND status='admitted' AND admission_no IS NOT NULL");
    $cur->execute([$id]);
    $existing = $cur->fetchColumn();

    for ($try = 0; $try < 8; $try++) {
        if ($existing) {
            $adm = $existing;
        } else {
            $max = (int)$pdo->query('SELECT COALESCE(MAX(CAST(admission_no AS UNSIGNED)), 0) FROM applications')->fetchColumn();
            $adm = (string)(max($max, ADM_START) + 1);
        }
        try {
            $st = $pdo->prepare('UPDATE applications SET status=?, admission_no=?, house_location=?, reject_reason=?, decided_at=?, decided_by=? WHERE id=?');
            $st->execute([$status, $adm, $house, $why, $at, $by, $id]);
            json_out(true, 'Uamuzi umehifadhiwa.', ['admission_no' => $adm]);
        } catch (PDOException $e) {
            // Watu wawili wakikubali kwa wakati mmoja: namba ikigongana, jaribu namba inayofuata.
            if ($e->getCode() === '23000' && stripos($e->getMessage(), 'admission_no') !== false && !$existing) continue;
            throw $e;
        }
    }
    http_response_code(500);
    json_out(false, 'Imeshindikana kutengeneza namba ya kujiunga. Jaribu tena.');
} catch (PDOException $e) {
    http_response_code(500);
    json_out(false, 'Imeshindikana kuhifadhi uamuzi.');
}
