<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('patient');
$pageTitle = 'Doctor Profile';
$id = (int)($_GET['id'] ?? 0);
$d = doctor_by_id($id);
if (!$d) {
    header('Location: doctors.php');
    exit;
}
$schedules = schedules_for_doctor($id);
include __DIR__ . '/../includes/header.php';
?>
<div class="card detail-hero">
    <img src="<?= htmlspecialchars($d['image']) ?>" alt="">
    <div><span class="eyebrow">DOCTOR PROFILE</span>
        <h2><?= htmlspecialchars($d['name']) ?></h2>
        <div class="chip-row"><span class="chip"><?= htmlspecialchars($d['specialization']) ?></span><span class="chip">⌖ <?= htmlspecialchars($d['address']) ?></span></div>
        <p><?= htmlspecialchars($d['description']) ?></p><a class="btn primary" href="book.php?doctor_id=<?= $d['id'] ?>">Book an appointment</a>
    </div>
</div>
<div class="section-head">
    <div>
        <h3>Working schedule</h3>
        <p>Available days and appointment duration.</p>
    </div>
</div>
<div class="card"><?php foreach ($schedules as $s): ?><div class="schedule-card">
            <div class="schedule-day"><?= htmlspecialchars($s['day']) ?></div>
            <div class="schedule-time"><?= date('g:i A', strtotime($s['start'])) ?> — <?= date('g:i A', strtotime($s['end'])) ?></div><span class="badge info"><?= (int)$s['duration'] ?> min slots</span>
        </div><?php endforeach; ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
