<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('doctor');
$pageTitle = 'My Schedule';
$user = current_user();
$doc = null;
foreach ($_SESSION['doctors'] as $d) if ($d['user_id'] === $user['id']) $doc = $d;
$error = '';
$success = '';
if (isset($_POST['add_schedule'])) {
    $allowedDays = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $day = $_POST['day'] ?? '';
    $start = $_POST['start'] ?? '';
    $end = $_POST['end'] ?? '';
    $duration = (int)$_POST['duration'];
    if (!$doc) $error = 'Doctor profile was not found.';
    elseif (!in_array($day, $allowedDays, true) || !preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) $error = 'Please enter valid schedule data.';
    elseif ($start >= $end) $error = 'End time must be after start time.';
    elseif (!in_array($duration, [15, 30, 45, 60], true)) $error = 'Please choose a valid slot duration.';
    else {
        db_create_schedule(['doctor_id' => $doc['id'], 'day' => $day, 'start' => $start, 'end' => $end, 'duration' => $duration]);
        schedules_for_doctor((int)$doc['id']);
        $success = 'Schedule added successfully.';
    }
}
if (isset($_POST['delete_schedule_id'])) {
    db_delete_schedule((int)$_POST['delete_schedule_id'], $doc['id']);
    schedules_for_doctor((int)$doc['id']);
    $success = 'Schedule removed.';
}
include __DIR__ . '/../includes/header.php';
$schedules = $doc ? schedules_for_doctor((int)$doc['id']) : [];
?>
<div class="grid grid-2">
    <div class="card"><span class="eyebrow">ADD WORKING HOURS</span>
        <h3 style="margin:8px 0 20px">Create a schedule slot</h3><?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?><?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?><form method="post" class="form-grid"><label>Day<select name="day"><?php foreach (['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day): ?><option value="<?= htmlspecialchars($day, ENT_QUOTES) ?>"><?= $day ?></option><?php endforeach; ?></select></label>
            <div class="form-grid two"><label>Start time<input type="time" name="start" required></label><label>End time<input type="time" name="end" required></label></div><label>Slot duration<select name="duration">
                    <option value="15">15 minutes</option>
                    <option value="30" selected>30 minutes</option>
                    <option value="45">45 minutes</option>
                    <option value="60">60 minutes</option>
                </select></label><button class="btn primary" name="add_schedule" value="1">Add schedule</button>
        </form>
    </div>
    <div class="card"><span class="eyebrow">CURRENT SCHEDULE</span>
        <h3 style="margin:8px 0">Your working hours</h3><?php foreach ($schedules as $i => $s): ?><div class="schedule-card">
                <div>
                    <div class="schedule-day"><?= htmlspecialchars($s['day']) ?></div>
                    <div class="schedule-time"><?= date('g:i A', strtotime($s['start'])) ?> — <?= date('g:i A', strtotime($s['end'])) ?></div>
                </div>
                <div style="display:flex;align-items:center;gap:8px"><span class="badge info"><?= $s['duration'] ?> min</span>
                    <form method="post"><input type="hidden" name="delete_schedule_id" value="<?= $s['id'] ?>"><button class="btn danger small" type="submit">Remove</button></form>
                </div>
            </div><?php endforeach; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
