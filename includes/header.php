<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_login();
$user = current_user();
$role = $user['role'];
$base = $role === 'doctor' ? '../' : '../';
$profileUrl = $role === 'doctor' ? '../doctor/profile.php' : '../patient/profile.php';
$headerProfileImage = $user['image'] ?? '';
if ($role === 'doctor') {
    foreach ($_SESSION['doctors'] as $headerDoctor) {
        if ((int)$headerDoctor['user_id'] === (int)$user['id']) {
            $headerProfileImage = $headerDoctor['image'];
            break;
        }
    }
}
$pageTitle = $pageTitle ?? 'AuraHealth';
$pageTitlesArabic = [
    'Dashboard' => 'لوحة التحكم', 'Find Doctors' => 'اكتشف الأطباء', 'My Appointments' => 'مواعيدي',
    'Appointments' => 'المواعيد', 'My Schedule' => 'جدول مواعيدي', 'Notifications' => 'الإشعارات',
    'My Profile' => 'ملفي الشخصي', 'Edit Profile' => 'تعديل الملف الشخصي', 'Appointment Ticket' => 'تذكرة الموعد',
    'Book Appointment' => 'حجز موعد', 'Doctor Profile' => 'ملف الطبيب', 'Medical Records' => 'السجلات الطبية', 'Patient Records' => 'سجلات المرضى', 'Help & Getting Started' => 'المساعدة والدليل'
];
$pageTitleArabic = $pageTitlesArabic[$pageTitle] ?? $pageTitle;
$headerNotifications = db_notifications_for_user((int)$user['id']);
$headerUnread = count(array_filter($headerNotifications, fn($notification) => empty($notification['is_read'])));
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> — AuraHealth</title>
    <script>
        try {
            const savedPortalLanguage = localStorage.getItem('medibook-language');
            const savedPortalTheme = localStorage.getItem('medibook-theme');
            const portalLanguage = savedPortalLanguage === 'ar' ? 'ar' : 'en';
            document.documentElement.lang = portalLanguage;
            document.documentElement.dir = portalLanguage === 'ar' ? 'rtl' : 'ltr';
            if (savedPortalTheme) document.documentElement.dataset.theme = savedPortalTheme;
        } catch (error) {}
    </script>
    <link rel="stylesheet" href="<?= $base ?>assets/css/app.css?v=20261022-locale-direction">
</head>

<body class="<?= $role === 'patient' && basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'patient-dashboard' : '' ?>">
    <div class="app-shell">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="main">
            <header class="topbar">
                <div>
                    <span class="eyebrow" data-en="<?= $role === 'doctor' ? 'DOCTOR PORTAL' : 'PATIENT PORTAL' ?>" data-ar="<?= $role === 'doctor' ? 'بوابة الطبيب' : 'بوابة المريض' ?>"><?= $role === 'doctor' ? 'DOCTOR PORTAL' : 'PATIENT PORTAL' ?></span>
                    <h1 data-en="<?= htmlspecialchars($pageTitle, ENT_QUOTES) ?>" data-ar="<?= htmlspecialchars($pageTitleArabic, ENT_QUOTES) ?>"><?= htmlspecialchars($pageTitle) ?></h1>
                </div>
                <div class="top-actions">
                    <nav class="portal-preferences" aria-label="Display preferences">
                        <div class="language-switch" role="group" aria-label="Language">
                            <button type="button" data-language="en" aria-pressed="true">EN</button>
                            <button type="button" data-language="ar" aria-pressed="false">عربي</button>
                        </div>
                        <button class="theme-switch portal-theme-switch" type="button" aria-label="Switch to dark mode" aria-pressed="false">
                            <span class="theme-icon" aria-hidden="true">&#9790;</span><span class="theme-label" data-en="Dark" data-ar="داكن">Dark</span><span class="switch-track" aria-hidden="true"><span></span></span>
                        </button>
                    </nav>
                    <a class="icon-btn notification-action" href="<?= $role === 'doctor' ? '../doctor/notifications.php' : '../patient/notifications.php' ?>" aria-label="Notifications<?= $headerUnread ? ', ' . $headerUnread . ' unread' : '' ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                        <?php if ($headerUnread): ?><span class="notification-count"><?= $headerUnread > 9 ? '9+' : $headerUnread ?></span><?php endif; ?>
                    </a>
                    <a class="user-chip" href="<?= $profileUrl ?>" aria-label="Open profile settings">
                        <?php if ($headerProfileImage): ?><img class="avatar avatar-photo" src="<?= htmlspecialchars($headerProfileImage) ?>" alt=""><?php else: ?><div class="avatar avatar-default"><?= default_avatar_icon() ?></div><?php endif; ?>
                        <div><strong><?= htmlspecialchars($user['name']) ?></strong><span><?= ucfirst($role) ?></span></div>
                    </a>
                </div>
            </header>
            <div class="content">
