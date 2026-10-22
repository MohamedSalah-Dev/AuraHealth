<?php
require_once __DIR__ . '/../dbc/dbc.php';
require_once __DIR__ . '/../config.php';
require_role('patient');
$pageTitle = 'Edit Profile';
$user = current_user();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_account'])) {
        db_delete_user((int)$user['id']);
        $_SESSION = [];
        session_destroy();
        header('Location: ../login.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $existingEmail = db_user_by_email($email);

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid name and email address.';
    } elseif ($existingEmail && (int)$existingEmail['id'] !== (int)$user['id']) {
        $error = 'That email address is already in use.';
    } elseif ($newPassword !== '' && !password_verify($currentPassword, $user['password'])) {
        $error = 'Enter your current password to set a new one.';
    } elseif ($newPassword !== '' && strlen($newPassword) < 6) {
        $error = 'Your new password must contain at least 6 characters.';
    } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
        $error = 'The new password and confirmation do not match.';
    }

    $uploadedImage = null;
    if (!$error && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = $_FILES['image']['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']) : '';
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK || !isset($allowedTypes[$mime])) {
            $error = 'Choose a valid JPG, PNG, or WEBP image.';
        } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
            $error = 'The profile photo must be smaller than 3 MB.';
        } else {
            $uploadedImage = ['mime' => $mime, 'extension' => $allowedTypes[$mime]];
        }
    }

    if (!$error) {
        $updatedUser = $user;
        $updatedUser['name'] = $name;
        $updatedUser['phone'] = $phone;
        $updatedUser['email'] = $email;
        if ($newPassword !== '') $updatedUser['password'] = password_hash($newPassword, PASSWORD_DEFAULT);

        if ($uploadedImage) {
            $uploadDir = __DIR__ . '/../uploads/patients';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
            $filename = 'patient_' . (int)$user['id'] . '_' . bin2hex(random_bytes(5)) . '.' . $uploadedImage['extension'];
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . '/' . $filename)) {
                $updatedUser['image'] = '../uploads/patients/' . $filename;
            } else {
                $error = 'The profile photo could not be saved. Please try again.';
            }
        }

        if (!$error) {
            db_update_user($updatedUser);
            $_SESSION['users'] = db_users();
            $_SESSION['profile_saved'] = 'Your profile was updated successfully.';
            header('Location: profile.php');
            exit;
        }
    }
}
include __DIR__ . '/../includes/header.php';
?>
<div class="edit-profile-page">
    <div class="page-heading-row"><div><span class="eyebrow">ACCOUNT SETTINGS</span><h2>Edit profile</h2><p class="muted">Update your personal information, password, or profile photo.</p></div><a class="btn secondary" href="profile.php">Back to profile</a></div>
    <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <section class="card edit-profile-card">
        <form method="post" enctype="multipart/form-data" class="form-grid">
            <div class="section-label"><span>PERSONAL INFORMATION</span><small>Shown on your account</small></div>
            <div class="form-grid two">
                <label>Full name<input name="name" value="<?= htmlspecialchars($user['name']) ?>" autocomplete="name" required></label>
                <label>Phone number<input name="phone" value="<?= htmlspecialchars($user['phone']) ?>" autocomplete="tel"></label>
            </div>
            <label>Email address<input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" autocomplete="email" required></label>
            <div class="profile-photo-edit">
                <?php if (!empty($user['image'])): ?><img class="profile-img" src="<?= htmlspecialchars($user['image']) ?>" alt="Current profile photo"><?php else: ?><div class="avatar profile-view-avatar avatar-default" aria-hidden="true"><?= default_avatar_icon() ?></div><?php endif; ?>
                <label>Profile photo <span class="optional">(JPG, PNG, or WEBP — max 3 MB)</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
            </div>
            <div class="divider"></div>
            <div><div class="section-label"><span>CHANGE PASSWORD</span><small>Leave the new password empty to keep your current one</small></div></div>
            <div class="form-grid two">
                <label>Current password<input type="password" name="current_password" autocomplete="current-password"></label>
                <label>New password<input type="password" name="new_password" autocomplete="new-password" minlength="6"></label>
            </div>
            <label>Confirm new password<input type="password" name="confirm_password" autocomplete="new-password" minlength="6"></label>
            <div class="actions-row"><a class="btn secondary" href="profile.php">Cancel</a><button class="btn primary" type="submit">Save changes</button></div>
        </form>
    </section>
    <section class="card danger-zone">
        <div class="danger-zone-heading"><span class="danger-zone-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7h16M10 11v6m4-6v6M5 7l1 13h12l1-13M9 7V4h6v3"/></svg></span><div><span class="danger-eyebrow">DANGER ZONE</span><h3>Delete account</h3></div></div>
        <p class="danger-zone-copy">Deleting your patient account permanently removes your account data. This action cannot be undone.</p>
        <form method="post" class="delete-account-form">
            <label class="delete-account-confirm" for="confirm-patient-delete"><input type="checkbox" id="confirm-patient-delete" onchange="document.getElementById('delete-patient-btn').disabled = !this.checked"><span><strong>I understand this action is permanent</strong><small>Your profile and access will be removed.</small></span></label>
            <button id="delete-patient-btn" class="btn danger delete-account-btn" name="delete_account" value="1" type="submit" disabled onclick="return confirm('Are you absolutely sure you want to delete your patient account?')"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M5 7l1 13h12l1-13M9 7V4h6v3"/></svg>Delete account</button>
        </form>
    </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
