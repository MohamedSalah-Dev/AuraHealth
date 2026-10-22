<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('doctor');
$pageTitle = 'My Profile';
$user = current_user();
$saved = $_SESSION['profile_saved'] ?? '';
unset($_SESSION['profile_saved']);
$doctor = null;
foreach ($_SESSION['doctors'] as $item) {
    if ((int)$item['user_id'] === (int)$user['id']) {
        $doctor = $item;
        break;
    }
}
if (!$doctor) { header('Location: dashboard.php'); exit; }
include __DIR__ . '/../includes/header.php';
?>
<?php if ($saved): ?><div class="alert success"><?= htmlspecialchars($saved) ?></div><?php endif; ?>
<section class="profile-view card">
    <div class="profile-view-heading">
        <div class="profile-head">
            <?php if (!empty($doctor['image'])): ?>
                <img class="profile-img" src="<?= htmlspecialchars($doctor['image']) ?>" alt="Profile photo of <?= htmlspecialchars($user['name']) ?>">
            <?php else: ?>
                <div class="avatar profile-view-avatar avatar-default" aria-hidden="true"><?= default_avatar_icon() ?></div>
            <?php endif; ?>
            <div><span class="eyebrow">DOCTOR PROFILE</span><h2><?= htmlspecialchars($user['name']) ?></h2><p><?= htmlspecialchars($doctor['specialization']) ?></p></div>
        </div>
        <a class="btn primary" href="edit-profile.php">Edit profile</a>
    </div>
    <div class="profile-details-grid">
        <div class="profile-detail"><span>Full name</span><strong><?= htmlspecialchars($user['name']) ?></strong></div>
        <div class="profile-detail"><span>Email address</span><strong><?= htmlspecialchars($user['email']) ?></strong></div>
        <div class="profile-detail"><span>Phone number</span><strong><?= htmlspecialchars($user['phone'] ?: 'Not provided') ?></strong></div>
        <div class="profile-detail"><span>Specialty</span><strong><?= htmlspecialchars($doctor['specialization']) ?></strong></div>
        <div class="profile-detail"><span>Clinic address</span><strong><?= htmlspecialchars($doctor['address']) ?></strong></div>
        <div class="profile-detail profile-detail-wide"><span>Professional description</span><strong><?= nl2br(htmlspecialchars($doctor['description'])) ?></strong></div>
        <div class="profile-detail"><span>Member since</span><strong><?= htmlspecialchars($user['created_at']) ?></strong></div>
        <div class="profile-detail"><span>Working schedule</span><a href="schedule.php">View schedule</a></div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
