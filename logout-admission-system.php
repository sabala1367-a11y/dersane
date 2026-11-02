<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: Neon_Orbit_Login.html');
exit;
