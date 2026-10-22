<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('patient');
$pageTitle = 'Book Appointment';
$user = current_user();
$doctorId = (int)($_GET['doctor_id'] ?? $_POST['doctor_id'] ?? 0);
$d = doctor_by_id($doctorId);
if (!$d) {
    header('Location: doctors.php');
    exit;
}
$schedules = schedules_for_doctor($doctorId);
$error = '';
$successId = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['appointment_date'] ?? '';
    $time = $_POST['appointment_time'] ?? '';
    $day = date('l', strtotime($date));
    $valid = false;
    foreach ($schedules as $s) {
        if ($s['day'] === $day && $time >= $s['start'] && $time < $s['end']) $valid = true;
    }
    foreach ($_SESSION['appointments'] as $a) {
        if ($a['doctor_id'] === $doctorId && $a['appointment_date'] === $date && $a['appointment_time'] === $time && $a['status'] !== 'cancelled') $valid = false;
    }
    if (!$date || !$time || !$valid) $error = 'Please choose a valid available slot. The selected time may already be booked.';
    else {
        $nextId = empty($_SESSION['appointments']) ? 1 : max(array_keys($_SESSION['appointments'])) + 1;
        $ticketNumber = 'MED-' . (1000 + $nextId);
        $newId = db_create_appointment(['user_id' => $user['id'], 'doctor_id' => $doctorId, 'appointment_date' => $date, 'appointment_time' => $time, 'status' => 'pending', 'ticket_number' => $ticketNumber, 'created_at' => date('Y-m-d')]);
        $_SESSION['appointments'] = db_appointments();
        db_create_notification(['user_id' => (int)$user['id'], 'appointment_id' => $newId, 'message' => 'Your appointment request was created successfully.', 'created_at' => date('Y-m-d')]);
        $successId = $newId;
    }
}
$chosenDate = $_POST['appointment_date'] ?? $_GET['appointment_date'] ?? date('Y-m-d');
// Start on the next working day instead of showing an empty list when today
// is not one of this doctor's working days.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['appointment_date']) && $schedules) {
    for ($offset = 0; $offset <= 7; $offset++) {
        $candidate = date('Y-m-d', strtotime('+' . $offset . ' days'));
        $candidateDay = date('l', strtotime($candidate));
        foreach ($schedules as $schedule) {
            if ($schedule['day'] === $candidateDay) {
                $chosenDate = $candidate;
                break 2;
            }
        }
    }
}
$day = date('l', strtotime($chosenDate));
$selectedSchedule = null;
foreach ($schedules as $s) if ($s['day'] === $day) $selectedSchedule = $s;
$slots = [];
if ($selectedSchedule) {
    $start = strtotime($chosenDate . ' ' . $selectedSchedule['start']);
    $end = strtotime($chosenDate . ' ' . $selectedSchedule['end']);
    for ($t = $start; $t < $end; $t += $selectedSchedule['duration'] * 60) {
        $slots[] = date('H:i', $t);
    }
}
include __DIR__ . '/../includes/header.php';
?>
<?php if ($successId): ?>
    <div class="card" style="text-align:center;padding:45px">
        <div class="stat-icon" style="margin:auto">✓</div>
        <h2 style="margin-top:15px">Appointment requested</h2>
        <p class="muted">Your booking with <?= htmlspecialchars($d['name']) ?> was created successfully.</p><a class="btn primary" href="ticket.php?id=<?= $successId ?>">View ticket</a> <a class="btn secondary" href="appointments.php">My appointments</a>
    </div>
<?php else: ?>
    <div class="grid grid-2">
        <div class="card">
            <div class="profile-head"><img class="profile-img" src="<?= htmlspecialchars($d['image']) ?>" alt="">
                <div><span class="eyebrow">BOOKING WITH</span>
                    <h2><?= htmlspecialchars($d['name']) ?></h2>
                    <p><?= htmlspecialchars($d['specialization']) ?></p>
                </div>
            </div>
            <div class="notice">Choose a date first, then select one of the available slots for that working day.</div>
            <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post" class="form-grid"><input type="hidden" name="doctor_id" value="<?= $doctorId ?>">
                <label>Appointment date<input type="date" name="appointment_date" value="<?= htmlspecialchars($chosenDate) ?>" min="<?= date('Y-m-d') ?>" required></label>
                <div>
                    <div style="font-size:12px;font-weight:700;margin-bottom:8px;color:#42545c">Available time</div>
                    <div id="available-times" class="radio-grid"><?php foreach ($slots as $slot): $taken = false;
                                                foreach ($_SESSION['appointments'] as $a) {
                                                    if ($a['doctor_id'] === $doctorId && $a['appointment_date'] === $chosenDate && $a['appointment_time'] === $slot && $a['status'] !== 'cancelled') $taken = true;
                                                }
                                                if (!$taken): ?><label><input type="radio" name="appointment_time" value="<?= $slot ?>" required><span><?= date('g:i A', strtotime($slot)) ?></span></label><?php endif;
                                                                                                                                                                                                                                                                                                                                                                                                                                    endforeach; ?></div>
                    <?php if (!$slots): ?><div class="empty" style="padding:20px">No schedule for <?= htmlspecialchars($day) ?>. Choose another date.</div><?php endif; ?>
                </div>
                <button class="btn primary full" type="submit">Confirm appointment request</button>
            </form>
        </div>
        <div class="card">
            <h3>Doctor schedule</h3>
            <p class="muted">Regular working hours</p><?php foreach ($schedules as $s): ?><div class="schedule-card">
                    <div class="schedule-day"><?= htmlspecialchars($s['day']) ?></div>
                    <div class="schedule-time"><?= date('g:i A', strtotime($s['start'])) ?> — <?= date('g:i A', strtotime($s['end'])) ?></div>
                </div><?php endforeach; ?><div class="divider"></div><span class="eyebrow">BOOKING NOTE</span>
            <p class="muted" style="font-size:12px">Your appointment starts as <strong>Pending</strong>. The doctor can confirm or cancel it from the doctor dashboard.</p>
        </div>
    </div>
<?php endif; ?>
<?php if (!$successId): ?>
<script>
const scheduleData = <?= json_encode(array_values($schedules), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const bookedData = <?= json_encode(array_values(array_filter($_SESSION['appointments'], function ($a) use ($doctorId) {
    return (int)$a['doctor_id'] === $doctorId && $a['status'] !== 'cancelled';
})), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const dateInput = document.querySelector('input[name="appointment_date"]');
const timesBox = document.getElementById('available-times');

function renderAvailableTimes() {
    if (!dateInput || !timesBox) return;
    const selectedDate = dateInput.value;
    const dayName = new Date(selectedDate + 'T12:00:00').toLocaleDateString('en-US', { weekday: 'long' });
    const schedule = scheduleData.find(item => item.day === dayName);
    timesBox.innerHTML = '';

    if (!schedule) {
        timesBox.innerHTML = '<div class="empty" style="padding:20px">No schedule for ' + dayName + '. Choose another date.</div>';
        return;
    }

    const start = new Date(selectedDate + 'T' + schedule.start + ':00');
    const end = new Date(selectedDate + 'T' + schedule.end + ':00');
    let count = 0;
    for (let time = new Date(start); time < end; time.setMinutes(time.getMinutes() + Number(schedule.duration))) {
        const value = time.toTimeString().slice(0, 5);
        const isTaken = bookedData.some(item => item.appointment_date === selectedDate && item.appointment_time === value);
        if (isTaken) continue;
        const label = document.createElement('label');
        label.innerHTML = '<input type="radio" name="appointment_time" value="' + value + '" required><span>' + time.toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'}) + '</span>';
        timesBox.appendChild(label);
        count++;
    }
    if (!count) timesBox.innerHTML = '<div class="empty" style="padding:20px">No available time for this date.</div>';
}

dateInput?.addEventListener('change', renderAvailableTimes);
</script>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
