<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('patient');
$pageTitle = 'Notifications';
$user = current_user();
if (isset($_POST['mark_read'])) {
    db_mark_notification_read((int)$_POST['mark_read'], (int)$user['id']);
    header('Location: notifications.php');
    exit;
}
if (isset($_POST['mark_all'])) {
    db_mark_all_notifications_read((int)$user['id']);
    header('Location: notifications.php');
    exit;
}
$notes = array_reverse(db_notifications_for_user((int)$user['id']));
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="section-head" style="margin-top:0">
        <div>
            <h3>Your notifications</h3>
            <p>Updates about your appointments.</p>
        </div>
        <form method="post"><button class="btn secondary small" name="mark_all" value="1">Mark all as read</button></form>
    </div>
    <?php foreach ($notes as $n): ?><div class="notification <?= $n['is_read'] ? 'read' : '' ?>">
            <div class="notification-dot"></div>
            <div style="flex:1"><strong><?= htmlspecialchars($n['message']) ?></strong>
                <p><?= $n['is_read'] ? 'Read notification' : 'New notification' ?></p><time><?= htmlspecialchars($n['created_at']) ?></time>
            </div><?php if (!$n['is_read']): ?><form method="post"><button class="btn secondary small" name="mark_read" value="<?= $n['id'] ?>">Mark read</button></form><?php endif; ?>
        </div><?php endforeach; ?>
    <?php if (!$notes): ?><div class="empty">No notifications.</div><?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
