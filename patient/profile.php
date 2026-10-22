<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('patient');
$pageTitle = 'My Profile';
$user = current_user();
$saved = $_SESSION['profile_saved'] ?? '';
unset($_SESSION['profile_saved']);
include __DIR__ . '/../includes/header.php';
?>
<?php if ($saved): ?><div class="alert success"><?= htmlspecialchars($saved) ?></div><?php endif; ?>
<section class="profile-view card">
    <div class="profile-view-heading">
        <div class="profile-head">
            <?php if (!empty($user['image'])): ?>
                <img class="profile-img" src="<?= htmlspecialchars($user['image']) ?>" alt="Profile photo of <?= htmlspecialchars($user['name']) ?>">
            <?php else: ?>
                <div class="avatar profile-view-avatar avatar-default" aria-hidden="true"><?= default_avatar_icon() ?></div>
            <?php endif; ?>
            <div><span class="eyebrow">PATIENT ACCOUNT</span><h2><?= htmlspecialchars($user['name']) ?></h2><p><?= htmlspecialchars($user['email']) ?></p></div>
        </div>
        <a class="btn primary" href="edit-profile.php">Edit profile</a>
    </div>
    <div class="profile-details-grid">
        <div class="profile-detail"><span>Full name</span><strong><?= htmlspecialchars($user['name']) ?></strong></div>
        <div class="profile-detail"><span>Email address</span><strong><?= htmlspecialchars($user['email']) ?></strong></div>
        <div class="profile-detail"><span>Phone number</span><strong><?= htmlspecialchars($user['phone'] ?: 'Not provided') ?></strong></div>
        <div class="profile-detail"><span>Account type</span><strong>Patient</strong></div>
        <div class="profile-detail"><span>Member since</span><strong><?= htmlspecialchars($user['created_at']) ?></strong></div>
        <div class="profile-detail"><span>Booking history</span><a href="appointments.php">View appointments</a></div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
