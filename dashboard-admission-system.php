<?php
// Kipanga njia cha waombaji tu: hajatuma fomu/inasubiri/amekataliwa -> fomu au hali; amekubaliwa -> dashboard yake.
require 'config-admission-system.php';
$u = current_user($pdo);
if (!$u || $u['role'] !== 'user') { header('Location: Neon_Orbit_Login.html'); exit; }
$q = $pdo->prepare('SELECT status FROM applications WHERE user_id = ?');
$q->execute([$u['id']]);
header('Location: ' . ($q->fetchColumn() === 'admitted' ? 'admission-user-dashboard.html' : 'application_form.html'));
