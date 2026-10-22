<?php
require_once __DIR__ . '/dbc/dbc.php';
require_once __DIR__ . '/config.php';
if (current_user()) {
    header('Location: index.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $u = db_user_by_email($email);
    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $u['id'];
        if (isset($_POST['remember'])) {
            $cookie = session_get_cookie_params();
            setcookie(session_name(), session_id(), [
                'expires' => time() + 30 * 24 * 60 * 60,
                'path' => $cookie['path'],
                'domain' => $cookie['domain'],
                'secure' => $cookie['secure'],
                'httponly' => $cookie['httponly'],
                'samesite' => $cookie['samesite'] ?? 'Lax',
            ]);
        }
        header('Location: ' . ($u['role'] === 'doctor' ? 'doctor/dashboard.php' : 'patient/dashboard.php'));
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AuraHealth &#8212; Login</title>
    <script>
        try {
            const portalLanguage = localStorage.getItem('medibook-language') === 'ar' ? 'ar' : 'en';
            document.documentElement.lang = portalLanguage;
            document.documentElement.dir = portalLanguage === 'ar' ? 'rtl' : 'ltr';
        } catch (error) {}
    </script>
    <link rel="stylesheet" href="assets/css/app.css?v=20261022-auth-card-gradient">
</head>

<body class="auth-page">
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
            <div class="auth-card">
                <div class="mobile-brand"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none"><path class="logo-heart" d="M32 53C25 47 12 36 12 24a12 12 0 0 1 20-9 12 12 0 0 1 20 9c0 12-13 23-20 29Z"/><path class="logo-pulse" d="M18 32h9l4-8 6 17 5-12 4 3h4"/></svg></span>AuraHealth</div>
                <span class="eyebrow" data-en="WELCOME BACK" data-ar="&#1605;&#1585;&#1581;&#1576;&#1611;&#1575; &#1576;&#1593;&#1608;&#1583;&#1578;&#1603;">WELCOME BACK</span>
                <h2 data-en="Sign in to your account" data-ar="&#1587;&#1580;&#1617;&#1604; &#1575;&#1604;&#1583;&#1582;&#1608;&#1604; &#1573;&#1604;&#1609; &#1581;&#1587;&#1575;&#1576;&#1603;">Sign in to your account</h2>
                <p class="muted" data-en="Access your appointments and medical dashboard." data-ar="&#1578;&#1575;&#1576;&#1593; &#1605;&#1608;&#1575;&#1593;&#1610;&#1583;&#1603; &#1608;&#1604;&#1608;&#1581;&#1577; &#1575;&#1604;&#1578;&#1581;&#1603;&#1605; &#1575;&#1604;&#1591;&#1576;&#1610;&#1577;.">Access your appointments and medical dashboard.</p>
                <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <form method="post" class="form-grid">
    <label><span data-en="Email" data-ar="&#1575;&#1604;&#1576;&#1585;&#1610;&#1583; &#1575;&#1604;&#1573;&#1604;&#1603;&#1578;&#1585;&#1608;&#1606;&#1610;">Email</span><span class="input-icon-wrap"><svg class="field-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg><input type="email" name="email" required placeholder="you@example.com" autocomplete="username"></span></label>
    <label><span data-en="Password" data-ar="&#1603;&#1604;&#1605;&#1577; &#1575;&#1604;&#1605;&#1585;&#1608;&#1585;">Password</span><span class="input-icon-wrap password-field"><svg class="field-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><input id="login-password" type="password" name="password" required placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" autocomplete="current-password"><button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></span></label>
    <div class="form-options"><label class="remember-option"><input type="checkbox" name="remember" value="1"><span data-en="Remember me" data-ar="&#1578;&#1584;&#1603;&#1585;&#1606;&#1610;">Remember me</span></label><a href="#forgot-password" class="forgot-password" data-en="Forgot password?" data-ar="&#1606;&#1587;&#1610;&#1578; &#1603;&#1604;&#1605;&#1577; &#1575;&#1604;&#1605;&#1585;&#1608;&#1585;&#1567;">Forgot password?</a></div>
    <p class="reset-help" id="reset-help" hidden data-en="Password reset is not set up yet. Please contact your clinic administrator." data-ar="&#1575;&#1587;&#1578;&#1593;&#1575;&#1583;&#1577; &#1603;&#1604;&#1605;&#1577; &#1575;&#1604;&#1605;&#1585;&#1608;&#1585; &#1594;&#1610;&#1585; &#1605;&#1601;&#1593;&#1617;&#1604;&#1577; &#1581;&#1575;&#1604;&#1610;&#1611;&#1575;. &#1610;&#1615;&#1585;&#1580;&#1609; &#1575;&#1604;&#1578;&#1608;&#1575;&#1589;&#1604; &#1605;&#1593; &#1605;&#1587;&#1572;&#1608;&#1604; &#1575;&#1604;&#1593;&#1610;&#1575;&#1583;&#1577;.">Password reset is not set up yet. Please contact your clinic administrator.</p>
    <button class="btn primary full" type="submit"><span data-en="Sign in" data-ar="&#1578;&#1587;&#1580;&#1610;&#1604; &#1575;&#1604;&#1583;&#1582;&#1608;&#1604;">Sign in</span></button>
</form>
<p class="auth-footer"><span data-en="New patient?" data-ar="&#1605;&#1585;&#1610;&#1590; &#1580;&#1583;&#1610;&#1583;&#1567;">New patient?</span> <a href="register.php" data-en="Create an account" data-ar="&#1571;&#1606;&#1588;&#1574; &#1581;&#1587;&#1575;&#1576;&#1611;&#1575;">Create an account</a></p>
            </div>
        </section>
    </div>
    <script>
        (() => {
            const root = document.documentElement;
            const languageButtons = document.querySelectorAll('[data-language]');
            const themeButton = document.querySelector('.theme-switch');
            const passwordInput = document.getElementById('login-password');
            const passwordToggle = document.querySelector('.password-toggle');
            const forgotLink = document.querySelector('.forgot-password');
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
            passwordToggle.addEventListener('click', () => {
                const visible = passwordInput.type === 'password';
                passwordInput.type = visible ? 'text' : 'password';
                passwordToggle.setAttribute('aria-pressed', String(visible));
                passwordToggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
            });
            forgotLink.addEventListener('click', event => {
                event.preventDefault();
                document.getElementById('reset-help').hidden = false;
            });
            setLanguage(localStorage.getItem('medibook-language') || 'en');
            setTheme(localStorage.getItem('medibook-theme') === 'dark');
        })();
    </script>
</body>

</html>
