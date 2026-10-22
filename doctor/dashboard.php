<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('doctor');
$pageTitle = 'Dashboard';
$user = current_user();
$doc = null;
foreach ($_SESSION['doctors'] as $d) {
    if ($d['user_id'] === $user['id']) {
        $doc = $d;
        break;
    }
}
$all = array_filter($_SESSION['appointments'], fn($a) => $a['doctor_id'] === $doc['id']);
$today = array_filter($all, fn($a) => $a['appointment_date'] === date('Y-m-d'));
$pending = count(array_filter($all, fn($a) => $a['status'] === 'pending'));
$confirmed = count(array_filter($all, fn($a) => $a['status'] === 'confirmed'));
include __DIR__ . '/../includes/header.php';
?>
<section class="hero">
    <div><span class="eyebrow" style="color:#8ce0d7">DOCTOR DASHBOARD</span>
        <h2>Welcome, <?= htmlspecialchars($user['name']) ?></h2>
        <p>Keep your schedule, appointments and professional profile under your control.</p>
        <div style="margin-top:18px"><a class="btn" style="background:white;color:#155f5d" href="schedule.php">Manage schedule →</a></div>
    </div>
    <div class="hero-badge"><strong><?= count($today) ?></strong><span>Appointments today</span></div>
</section>
<div class="grid grid-4" style="margin-top:20px">
    <div class="card stat">
        <div><span>Total appointments</span><strong><?= count($all) ?></strong></div>
        <div class="stat-icon">▣</div>
    </div>
    <div class="card stat">
        <div><span>Pending</span><strong><?= $pending ?></strong></div>
        <div class="stat-icon">◷</div>
    </div>
    <div class="card stat">
        <div><span>Confirmed</span><strong><?= $confirmed ?></strong></div>
        <div class="stat-icon">✓</div>
    </div>
    <div class="card stat">
        <div><span>Schedule days</span><strong><?= count(schedules_for_doctor((int)$doc['id'])) ?></strong></div>
        <div class="stat-icon">⌚</div>
    </div>
</div>
<div class="section-head">
    <div>
        <h3>Today's appointments</h3>
        <p>Manage your patient queue.</p>
    </div><a class="btn secondary small" href="appointments.php">View all</a>
</div>
<div class="card"><?php $rows = array_values($today);
                    if (!$rows): ?><div class="empty">No appointments scheduled for today.</div><?php else: foreach ($rows as $a): $p = $_SESSION['users'][$a['user_id']] ?? []; ?><div class="appointment-row">
                <div class="patient-mini">
                    <div class="avatar avatar-default" aria-hidden="true"><?= default_avatar_icon() ?></div>
                    <div><strong><?= htmlspecialchars($p['name'] ?? 'Patient') ?></strong>
                        <div class="muted"><?= htmlspecialchars($p['phone'] ?? '') ?></div>
                    </div>
                </div>
                <div><?= date('g:i A', strtotime($a['appointment_time'])) ?></div>
                <div><strong><?= htmlspecialchars($a['ticket_number']) ?></strong></div>
                <div><span class="badge <?= status_class($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></div>
                <div><a class="btn primary small" href="appointments.php">Manage</a></div>
            </div><?php endforeach;
                                                                                            endif; ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
