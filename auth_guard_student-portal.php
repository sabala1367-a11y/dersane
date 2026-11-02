<?php
/**
 * auth_guard_student-portal.php — mlinzi wa kurasa za MWANAFUNZI (Student_Portal_student-portal.php, receipt_student-portal.php).
 * Mtu ambaye hajaingia kupitia login_student-portal.php anarudishwa login_student-portal.php. Hakuna "mwanafunzi wa kwanza" tena.
 * Baada ya faili hili, $_SESSION['reg_no'] ni namba ya usajili ya mwanafunzi aliyeingia.
 */
require_once __DIR__ . '/config_student-portal.php';

if (empty($_SESSION['reg_no']) || ($_SESSION['role'] ?? '') !== 'student') {
    // Admin aliyeingia lakini hana token ya kuangalia akaunti ya mwanafunzi -> dashboard yake.
    if (!empty($_SESSION['is_admin'])) sp_redirect('admin_dashboard_student-portal.php');
    sp_redirect('login_student-portal.php');
}
