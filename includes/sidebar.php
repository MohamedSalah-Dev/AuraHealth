<?php
$dashboard = $role === 'doctor' ? '../doctor/dashboard.php' : '../patient/dashboard.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$sidebarNotifications = db_notifications_for_user((int)$user['id']);
$sidebarUnread = count(array_filter($sidebarNotifications, fn($notification) => empty($notification['is_read'])));
$navIcon = static function (string $name): string {
    $paths = [
        'overview' => '<rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/>',
        'search' => '<circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/><path d="M8 10.8h5.6M10.8 8v5.6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 15l2.2 2.2L16 12"/>',
        'records' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h6"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.6.9l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.6-.9l-1.7.7-1.4-2.4 1.4-1.1a7 7 0 0 1 0-1.9l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.6-.9l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.6.9l1.7-.7 1.4 2.4-1.4 1.1a7 7 0 0 1 0 1.8Z"/>',
        'schedule' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.6 9a2.5 2.5 0 1 1 4.3 1.8c-1.1 1.1-1.9 1.4-1.9 3M12 17.5h.01"/>',
        'logout' => '<path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>',
    ];
    return '<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
};
?>
<aside class="sidebar">
    <a class="brand" href="<?= $dashboard ?>"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none"><path class="logo-heart" d="M32 53C25 47 12 36 12 24a12 12 0 0 1 20-9 12 12 0 0 1 20 9c0 12-13 23-20 29Z"/><path class="logo-pulse" d="M18 32h9l4-8 6 17 5-12 4 3h4"/></svg></span>AuraHealth</a>
    <div class="sidebar-label" data-en="<?= $role === 'doctor' ? 'CLINIC' : 'YOUR HEALTH' ?>" data-ar="<?= $role === 'doctor' ? 'العيادة' : 'صحتك' ?>"><?= $role === 'doctor' ? 'CLINIC' : 'YOUR HEALTH' ?></div>
    <nav>
        <?php if ($role === 'patient'): ?>
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="../patient/dashboard.php"><?= $navIcon('overview') ?><span data-en="Overview" data-ar="نظرة عامة">Overview</span></a>
            <a class="<?= in_array($currentPage, ['doctors.php', 'doctor.php', 'book.php']) ? 'active' : '' ?>" href="../patient/doctors.php"><?= $navIcon('search') ?><span data-en="Explore Doctors" data-ar="استكشف الأطباء">Explore Doctors</span></a>
            <a class="<?= in_array($currentPage, ['appointments.php', 'ticket.php']) ? 'active' : '' ?>" href="../patient/appointments.php"><?= $navIcon('calendar') ?><span data-en="My Visits &amp; Bookings" data-ar="زياراتي وحجوزاتي">My Visits &amp; Bookings</span></a>
            <a class="<?= $currentPage === 'records.php' ? 'active' : '' ?>" href="../patient/records.php"><?= $navIcon('records') ?><span data-en="Medical Records" data-ar="السجلات الطبية">Medical Records</span></a>
            <a class="<?= $currentPage === 'notifications.php' ? 'active' : '' ?>" href="../patient/notifications.php"><?= $navIcon('bell') ?><span data-en="Notifications &amp; Alerts" data-ar="الإشعارات والتنبيهات">Notifications &amp; Alerts</span><?php if ($sidebarUnread): ?><span class="nav-badge"><?= $sidebarUnread > 9 ? '9+' : $sidebarUnread ?></span><?php endif; ?></a>
            <a class="<?= in_array($currentPage, ['profile.php', 'edit-profile.php'], true) ? 'active' : '' ?>" href="../patient/profile.php"><?= $navIcon('settings') ?><span data-en="Account Settings" data-ar="إعدادات الحساب">Account Settings</span></a>
        <?php else: ?>
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="../doctor/dashboard.php"><?= $navIcon('overview') ?><span data-en="Overview" data-ar="نظرة عامة">Overview</span></a>
            <a class="<?= $currentPage === 'appointments.php' ? 'active' : '' ?>" href="../doctor/appointments.php"><?= $navIcon('calendar') ?><span data-en="Appointments" data-ar="المواعيد">Appointments</span></a>
            <a class="<?= $currentPage === 'records.php' ? 'active' : '' ?>" href="../doctor/records.php"><?= $navIcon('records') ?><span data-en="Patient Records" data-ar="ملفات المرضى">Patient Records</span></a>
            <a class="<?= $currentPage === 'schedule.php' ? 'active' : '' ?>" href="../doctor/schedule.php"><?= $navIcon('schedule') ?><span data-en="My Schedule" data-ar="جدولي">My Schedule</span></a>
            <a class="<?= in_array($currentPage, ['profile.php', 'edit-profile.php'], true) ? 'active' : '' ?>" href="../doctor/profile.php"><?= $navIcon('settings') ?><span data-en="Account Settings" data-ar="إعدادات الحساب">Account Settings</span></a>
            <a class="<?= $currentPage === 'notifications.php' ? 'active' : '' ?>" href="../doctor/notifications.php"><?= $navIcon('bell') ?><span data-en="Notifications" data-ar="الإشعارات">Notifications</span><?php if ($sidebarUnread): ?><span class="nav-badge"><?= $sidebarUnread > 9 ? '9+' : $sidebarUnread ?></span><?php endif; ?></a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-bottom">
        <a class="help-card" href="../patient/help.php">
            <span class="help-icon"><?= $navIcon('help') ?></span>
            <strong data-en="Need Assistance?" data-ar="هل تحتاج إلى مساعدة؟">Need Assistance?</strong>
            <span data-en="How to use AuraHealth" data-ar="دليل استخدام AuraHealth">How to use AuraHealth</span>
        </a>
        <a class="logout" href="../logout.php"><?= $navIcon('logout') ?><span data-en="Sign out" data-ar="تسجيل الخروج">Sign out</span></a>
    </div>
</aside>
