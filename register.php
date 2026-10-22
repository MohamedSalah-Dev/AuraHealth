<?php
require_once __DIR__ . '/dbc/dbc.php';
require_once __DIR__ . '/config.php';
if (current_user()) { header('Location: index.php'); exit; }

$error = '';
$role = $_POST['role'] ?? 'patient';
$form = [
    'name' => trim($_POST['name'] ?? ''),
    'phone' => trim($_POST['phone'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'specialization' => trim($_POST['specialization'] ?? ''),
    'address' => trim($_POST['address'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $validRoles = ['patient', 'doctor'];

    if (!in_array($role, $validRoles, true)) {
        $error = 'Please choose whether you are a Patient or Doctor.';
    } else {
        if (db_user_by_email($form['email'])) {
            $error = 'This email is already registered.';
        }
    }

    if (!$error && ($form['name'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($password) < 6)) {
        $error = 'Please enter a valid name and email. Password must be at least 6 characters.';
    }

    if (!$error && $role === 'doctor' && ($form['specialization'] === '' || $form['address'] === '' || $form['description'] === '')) {
        $error = 'Doctors must complete specialization, clinic address, and professional description.';
    }

    if (!$error && $role === 'doctor' && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK || !in_array($_FILES['image']['type'], $allowed, true)) {
            $error = 'Doctor photo must be a JPG, PNG, or WEBP image.';
        } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
            $error = 'Doctor photo must be smaller than 3 MB.';
        }
    }

    if (!$error) {
        $userData = [
            'name' => $form['name'],
            'phone' => $form['phone'],
            'email' => $form['email'],
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'created_at' => date('Y-m-d')
        ];
        $id = db_create_user($userData);
        $_SESSION['users'] = db_users();
        $_SESSION['notifications'][$id] = [];

        if ($role === 'doctor') {
            $portraitGroup = ($id % 2 === 0) ? 'women' : 'men';
            $portraitNumber = 20 + ($id % 70);
            $imagePath = 'https://randomuser.me/api/portraits/' . $portraitGroup . '/' . $portraitNumber . '.jpg';

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/doctors';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
                $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $filename = 'doctor_' . $id . '_' . time() . '.' . $extension;
                $destination = $uploadDir . '/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $imagePath = '../uploads/doctors/' . $filename;
                }
            }

            db_create_doctor([
                'user_id' => $id,
                'specialization' => $form['specialization'],
                'description' => $form['description'],
                'image' => $imagePath,
                'address' => $form['address']
            ]);
            $_SESSION['doctors'] = db_doctors();
            $_SESSION['user_id'] = $id;
            header('Location: doctor/dashboard.php'); exit;
        }

        $_SESSION['user_id'] = $id;
        header('Location: patient/dashboard.php'); exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>AuraHealth &#8212; Create Account</title>
<script>
    try {
        const portalLanguage = localStorage.getItem('medibook-language') === 'ar' ? 'ar' : 'en';
        document.documentElement.lang = portalLanguage;
        document.documentElement.dir = portalLanguage === 'ar' ? 'rtl' : 'ltr';
    } catch (error) {}
</script>
<link rel="stylesheet" href="assets/css/app.css?v=20261022-auth-card-gradient">
</head>
<body class="auth-page register-page">
<div class="auth-shell">
<header class="auth-header">
    <a class="auth-header-brand" href="login.php" aria-label="AuraHealth home">
        <span class="auth-header-mark" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none"><path class="logo-heart" d="M32 53C25 47 12 36 12 24a12 12 0 0 1 20-9 12 12 0 0 1 20 9c0 12-13 23-20 29Z"/><path class="logo-pulse" d="M18 32h9l4-8 6 17 5-12 4 3h4"/></svg></span>
        <span class="brand-word"><span class="brand-aura">Aura</span><span class="brand-health">Health</span></span>
    </a>
    <nav class="auth-preferences" aria-label="Display preferences">
        <div class="language-switch" role="group" aria-label="Language">
            <button type="button" data-language="en" aria-pressed="true">EN</button>
            <button type="button" data-language="ar" aria-pressed="false">&#1593;&#1585;&#1576;&#1610;</button>
        </div>
        <button class="theme-switch" type="button" aria-label="Switch to dark mode" aria-pressed="false"><span class="theme-icon" aria-hidden="true">&#9790;</span><span class="theme-label sr-only">Dark</span></button>
    </nav>
</header>
<section class="auth-visual">
</section>
<section class="auth-card-wrap">
<div class="auth-card register-card">
    <div class="mobile-brand"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none"><path class="logo-heart" d="M32 53C25 47 12 36 12 24a12 12 0 0 1 20-9 12 12 0 0 1 20 9c0 12-13 23-20 29Z"/><path class="logo-pulse" d="M18 32h9l4-8 6 17 5-12 4 3h4"/></svg></span>AuraHealth</div>
    <span class="eyebrow" data-en="CREATE ACCOUNT" data-ar="&#1573;&#1606;&#1588;&#1575;&#1569; &#1581;&#1587;&#1575;&#1576;">CREATE ACCOUNT</span>
    <h2 data-en="Choose your account type" data-ar="&#1575;&#1582;&#1578;&#1585; &#1606;&#1608;&#1593; &#1581;&#1587;&#1575;&#1576;&#1603;">Choose your account type</h2>
    <p class="muted" data-en="Your choice controls the dashboard and the information you can manage." data-ar="&#1610;&#1581;&#1583;&#1583; &#1575;&#1582;&#1578;&#1610;&#1575;&#1585;&#1603; &#1604;&#1608;&#1581;&#1577; &#1575;&#1604;&#1578;&#1581;&#1603;&#1605; &#1608;&#1575;&#1604;&#1605;&#1593;&#1604;&#1608;&#1605;&#1575;&#1578; &#1575;&#1604;&#1578;&#1610; &#1610;&#1605;&#1603;&#1606;&#1603; &#1573;&#1583;&#1575;&#1585;&#1578;&#1607;&#1575;.">Your choice controls the dashboard and the information you can manage.</p>

    <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form-grid">
        <div class="role-picker">
            <label class="role-option">
                <input id="role-patient" type="radio" name="role" value="patient" <?= $role === 'patient' ? 'checked' : '' ?>>
                <span><b data-en="Patient" data-ar="&#1605;&#1585;&#1610;&#1590;">Patient</b><small data-en="Find doctors & book appointments" data-ar="&#1575;&#1576;&#1581;&#1579; &#1593;&#1606; &#1571;&#1591;&#1576;&#1575;&#1569; &#1608;&#1575;&#1581;&#1580;&#1586; &#1605;&#1608;&#1575;&#1593;&#1610;&#1583;">Find doctors & book appointments</small></span>
            </label>
            <label class="role-option">
                <input id="role-doctor" type="radio" name="role" value="doctor" <?= $role === 'doctor' ? 'checked' : '' ?>>
                <span><b data-en="Doctor" data-ar="&#1591;&#1576;&#1610;&#1576;">Doctor</b><small data-en="Professional profile & schedule" data-ar="&#1605;&#1604;&#1601; &#1605;&#1607;&#1606;&#1610; &#1608;&#1580;&#1583;&#1608;&#1604; &#1575;&#1604;&#1605;&#1608;&#1575;&#1593;&#1610;&#1583;">Professional profile & schedule</small></span>
            </label>
        </div>

        <div class="divider"></div>
        <div class="form-grid two">
            <label><span data-en="Full name" data-ar="&#1575;&#1604;&#1575;&#1587;&#1605; &#1575;&#1604;&#1603;&#1575;&#1605;&#1604;">Full name</span><input name="name" value="<?= htmlspecialchars($form['name']) ?>" required placeholder="Ahmed Mohamed"></label>
            <label><span data-en="Phone" data-ar="&#1575;&#1604;&#1607;&#1575;&#1578;&#1601;">Phone</span><input name="phone" value="<?= htmlspecialchars($form['phone']) ?>" placeholder="01012345678"></label>
        </div>
        <div class="form-grid two">
            <label><span data-en="Email" data-ar="&#1575;&#1604;&#1576;&#1585;&#1610;&#1583; &#1575;&#1604;&#1573;&#1604;&#1603;&#1578;&#1585;&#1608;&#1606;&#1610;">Email</span><input type="email" name="email" value="<?= htmlspecialchars($form['email']) ?>" required placeholder="you@example.com"></label>
            <label><span data-en="Password" data-ar="&#1603;&#1604;&#1605;&#1577; &#1575;&#1604;&#1605;&#1585;&#1608;&#1585;">Password</span><input type="password" name="password" required minlength="6" placeholder="At least 6 characters"></label>
        </div>

        <div class="doctor-registration-fields">
            <div class="section-label"><span data-en="DOCTOR PROFILE" data-ar="&#1605;&#1604;&#1601; &#1575;&#1604;&#1591;&#1576;&#1610;&#1576;">DOCTOR PROFILE</span><small data-en="These fields are stored in DOCTORS." data-ar="&#1610;&#1578;&#1605; &#1581;&#1601;&#1592; &#1607;&#1584;&#1607; &#1575;&#1604;&#1576;&#1610;&#1575;&#1606;&#1575;&#1578; &#1601;&#1610; &#1605;&#1604;&#1601; &#1575;&#1604;&#1571;&#1591;&#1576;&#1575;&#1569;.">These fields are stored in DOCTORS.</small></div>
            <div class="form-grid two">
                <label><span data-en="Specialization" data-ar="&#1575;&#1604;&#1578;&#1582;&#1589;&#1589;">Specialization</span><input name="specialization" value="<?= htmlspecialchars($form['specialization']) ?>" placeholder="Cardiology"></label>
                <label><span data-en="Clinic / Address" data-ar="&#1575;&#1604;&#1593;&#1610;&#1575;&#1583;&#1577; / &#1575;&#1604;&#1593;&#1606;&#1608;&#1575;&#1606;">Clinic / Address</span><input name="address" value="<?= htmlspecialchars($form['address']) ?>" placeholder="Nasr City, Cairo"></label>
            </div>
            <label><span data-en="Professional description" data-ar="&#1608;&#1589;&#1601; &#1605;&#1607;&#1606;&#1610;">Professional description</span><textarea name="description" placeholder="Tell patients about your experience and services..."><?= htmlspecialchars($form['description']) ?></textarea></label>
            <label><span data-en="Doctor photo" data-ar="&#1589;&#1608;&#1585;&#1577; &#1575;&#1604;&#1591;&#1576;&#1610;&#1576;">Doctor photo</span> <span class="optional" data-en="(JPG, PNG or WEBP &#8212; max 3 MB)" data-ar="(JPG, PNG or WEBP &#8212; &#1581;&#1583; &#1571;&#1602;&#1589;&#1609; 3 MB)">(JPG, PNG or WEBP &#8212; max 3 MB)</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
            <div class="notice" data-en="No Admin is required. After registration, the Doctor can edit this profile and manage working hours from the Doctor Dashboard." data-ar="&#1604;&#1575; &#1610;&#1604;&#1586;&#1605; &#1608;&#1580;&#1608;&#1583; &#1605;&#1583;&#1610;&#1585;. &#1576;&#1593;&#1583; &#1575;&#1604;&#1578;&#1587;&#1580;&#1610;&#1604;&#1548; &#1610;&#1605;&#1603;&#1606; &#1604;&#1604;&#1591;&#1576;&#1610;&#1576; &#1578;&#1593;&#1583;&#1610;&#1604; &#1605;&#1604;&#1601;&#1607; &#1608;&#1573;&#1583;&#1575;&#1585;&#1577; &#1587;&#1575;&#1593;&#1575;&#1578; &#1575;&#1604;&#1593;&#1605;&#1604;.">No Admin is required. After registration, the Doctor can edit this profile and manage working hours from the Doctor Dashboard.</div>
        </div>

        <button class="btn primary full" type="submit" data-en="Create my account" data-ar="&#1573;&#1606;&#1588;&#1575;&#1569; &#1581;&#1587;&#1575;&#1576;&#1610;">Create my account</button>
    </form>
    <p class="auth-footer"><span data-en="Already have an account?" data-ar="&#1604;&#1583;&#1610;&#1603; &#1581;&#1587;&#1575;&#1576; &#1576;&#1575;&#1604;&#1601;&#1593;&#1604;&#1567;">Already have an account?</span> <a href="login.php" data-en="Sign in" data-ar="&#1578;&#1587;&#1580;&#1610;&#1604; &#1575;&#1604;&#1583;&#1582;&#1608;&#1604;">Sign in</a></p>
</div>
</section>
</div>
<script>
(() => {
    const root = document.documentElement;
    const languageButtons = document.querySelectorAll('[data-language]');
    const themeButton = document.querySelector('.theme-switch');
    const setLanguage = lang => {
        root.lang = lang;
        root.dir = lang === 'ar' ? 'rtl' : 'ltr';
        document.querySelectorAll('[data-en][data-ar]').forEach(el => { el.textContent = el.dataset[lang]; });
        languageButtons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.language === lang)));
        localStorage.setItem('medibook-language', lang);
    };
    const setTheme = dark => {
        root.dataset.theme = dark ? 'dark' : 'light';
        themeButton.setAttribute('aria-pressed', String(dark));
        themeButton.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
        themeButton.querySelector('.theme-label').textContent = dark ? 'Light' : 'Dark';
        themeButton.querySelector('.theme-icon').textContent = dark ? String.fromCharCode(9728) : String.fromCharCode(9790);
        localStorage.setItem('medibook-theme', dark ? 'dark' : 'light');
    };
    languageButtons.forEach(button => button.addEventListener('click', () => setLanguage(button.dataset.language)));
    themeButton.addEventListener('click', () => setTheme(root.dataset.theme !== 'dark'));
    setLanguage(localStorage.getItem('medibook-language') || 'en');
    setTheme(localStorage.getItem('medibook-theme') === 'dark');
})();
</script>
</body>
</html>
