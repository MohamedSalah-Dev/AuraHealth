<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('doctor');
$pageTitle = 'Appointments';
$user = current_user();
$doc = null;
foreach ($_SESSION['doctors'] as $d) if ($d['user_id'] === $user['id']) $doc = $d;
if (isset($_POST['status_id'], $_POST['status'])) {
    $id = (int)$_POST['status_id'];
    $new = $_POST['status'];
    $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (isset($_SESSION['appointments'][$id]) && $_SESSION['appointments'][$id]['doctor_id'] === $doc['id'] && in_array($new, $allowed)) {
        db_update_appointment_status($id, $new);
        $_SESSION['appointments'] = db_appointments();
        $a = $_SESSION['appointments'][$id];
        db_create_notification(['user_id' => $a['user_id'], 'appointment_id' => $id, 'message' => 'Your appointment status was updated to ' . ucfirst($new) . '.', 'created_at' => date('Y-m-d')]);
    }
    header('Location: appointments.php');
    exit;
}
$rows = array_filter($_SESSION['appointments'], fn($a) => $a['doctor_id'] === $doc['id']);
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Date & Time</th>
                    <th>Ticket</th>
                    <th>Status</th>
                    <th>Update status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $a): $p = $_SESSION['users'][$a['user_id']] ?? []; ?><tr>
                        <td><strong><?= htmlspecialchars($p['name'] ?? 'Patient') ?></strong>
                            <div class="muted"><?= htmlspecialchars($p['phone'] ?? '') ?></div>
                        </td>
                        <td><?= date('d M Y', strtotime($a['appointment_date'])) ?><br><span class="muted"><?= date('g:i A', strtotime($a['appointment_time'])) ?></span></td>
                        <td><?= htmlspecialchars($a['ticket_number']) ?></td>
                        <td><span class="badge <?= status_class($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></td>
                        <td>
                            <form method="post" style="display:flex;gap:7px"><input type="hidden" name="status_id" value="<?= $a['id'] ?>"><select name="status">
                                    <option value="pending" <?= $a['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $a['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="completed" <?= $a['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= $a['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select><button class="btn primary small" type="submit">Save</button></form>
                        </td>
                    </tr><?php endforeach; ?></tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
