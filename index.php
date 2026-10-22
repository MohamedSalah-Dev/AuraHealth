<?php
require_once __DIR__ . '/dbc/dbc.php';
require_once __DIR__ . '/config.php';
$user = current_user();
if ($user) {
    header('Location: ' . ($user['role'] === 'doctor' ? 'doctor/dashboard.php' : 'patient/dashboard.php'));
    exit;
}
header('Location: login.php');
exit;
