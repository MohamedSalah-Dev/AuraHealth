<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('patient');
$pageTitle = 'Appointment Ticket';
$user = current_user();
$id = (int)($_GET['id'] ?? 0);
$a = appointment_by_id($id);
if (!$a || $a['user_id'] !== $user['id']) {
    header('Location: appointments.php');
    exit;
}
$d = doctor_by_id($a['doctor_id']);
include __DIR__ . '/../includes/header.php';
?>
<div class="ticket">
    <div class="ticket-top">
        <div class="brand"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none"><path class="logo-heart" d="M32 53C25 47 12 36 12 24a12 12 0 0 1 20-9 12 12 0 0 1 20 9c0 12-13 23-20 29Z"/><path class="logo-pulse" d="M18 32h9l4-8 6 17 5-12 4 3h4"/></svg></span>AuraHealth</div>
        <div class="ticket-number"><span>TICKET NUMBER</span><strong><?= htmlspecialchars($a['ticket_number']) ?></strong></div>
    </div>
    <div class="ticket-body">
        <div class="profile-head"><img class="profile-img" src="<?= htmlspecialchars($d['image']) ?>" alt="">
            <div><span class="eyebrow">APPOINTMENT</span>
                <h2><?= htmlspecialchars($d['name']) ?></h2>
                <p><?= htmlspecialchars($d['specialization']) ?></p>
            </div>
        </div>
        <div class="ticket-grid">
            <div class="ticket-item"><span>Patient</span><strong><?= htmlspecialchars($user['name']) ?></strong></div>
            <div class="ticket-item"><span>Status</span><span class="badge <?= status_class($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></div>
            <div class="ticket-item"><span>Date</span><strong><?= date('l, d M Y', strtotime($a['appointment_date'])) ?></strong></div>
            <div class="ticket-item"><span>Time</span><strong><?= date('g:i A', strtotime($a['appointment_time'])) ?></strong></div>
            <div class="ticket-item"><span>Location</span><strong><?= htmlspecialchars($d['address']) ?></strong></div>
            <div class="ticket-item"><span>Ticket</span><strong><?= htmlspecialchars($a['ticket_number']) ?></strong></div>
        </div>
    </div>
    <div class="ticket-actions"><a class="btn secondary" href="appointments.php">Back to appointments</a></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
