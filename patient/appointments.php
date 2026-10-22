<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('patient');
$pageTitle = 'My Appointments';
$user = current_user();
$mine = array_filter($_SESSION['appointments'], fn($a) => $a['user_id'] === $user['id']);
if (isset($_POST['cancel_id'])) {
    $id = (int)$_POST['cancel_id'];
    if (isset($_SESSION['appointments'][$id]) && $_SESSION['appointments'][$id]['user_id'] === $user['id']) {
        db_update_appointment_status($id, 'cancelled');
        $_SESSION['appointments'] = db_appointments();
        db_create_notification(['user_id' => (int)$user['id'], 'appointment_id' => $id, 'message' => 'Your appointment was cancelled successfully.', 'created_at' => date('Y-m-d')]);
    }
    header('Location: appointments.php');
    exit;
}
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Ticket</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mine as $a): $d = doctor_by_id($a['doctor_id']); ?><tr>
                        <td><strong><?= htmlspecialchars($d['name']) ?></strong>
                            <div class="muted"><?= htmlspecialchars($d['specialization']) ?></div>
                        </td>
                        <td><?= date('d M Y', strtotime($a['appointment_date'])) ?></td>
                        <td><?= date('g:i A', strtotime($a['appointment_time'])) ?></td>
                        <td><strong><?= htmlspecialchars($a['ticket_number']) ?></strong></td>
                        <td><span class="badge <?= status_class($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></td>
                        <td><a class="btn secondary small" href="ticket.php?id=<?= $a['id'] ?>">Ticket</a><?php if (!in_array($a['status'], ['cancelled', 'completed'])): ?><form class="inline-form" method="post"><input type="hidden" name="cancel_id" value="<?= $a['id'] ?>"><button class="btn danger small" type="submit">Cancel</button></form><?php endif; ?></td>
                    </tr><?php endforeach; ?>
                <?php if (!$mine): ?><tr>
                        <td colspan="6">
                            <div class="empty">No appointments yet. <a href="doctors.php">Find a doctor</a></div>
                        </td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
