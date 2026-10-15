<?php
session_start();
include 'database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $error = 'Please enter your name.';
    } elseif ($email === '') {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($phone === '') {
        $error = 'Please enter your phone number.';
    } elseif ($address === '') {
        $error = 'Please enter your address.';
    } elseif ($password === '') {
        $error = 'Please enter your password.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($confirmPassword === '') {
        $error = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password, phone, address, role)
                 VALUES (:name, :email, :password, :phone, :address, :role)'
            );
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':phone' => $phone,
                ':address' => $address,
                ':role' => 'customer',
            ]);

            $success = 'Account created successfully. You can now login.';
        }
    }
}
?>
<style>
    :root {
    --ink: #171717;
    --muted: #7e776d;
    --line: #e5dfd5;
    --paper: #fbfaf7;
    --tan: #b99161;
    --tan-dark: #966e43;
    --espresso: #251d17;
    --serif: "Playfair Display", Georgia, serif;
    --sans: "DM Sans", Arial, sans-serif;
}

* { box-sizing: border-box; }
html, body { min-height: 100%; }
body { margin: 0; color: var(--ink); background: var(--paper); font-family: var(--sans); -webkit-font-smoothing: antialiased; }
a { color: inherit; text-decoration: none; }
button, input, textarea { font: inherit; }
button { cursor: pointer; }

.auth-shell { display: grid; grid-template-columns: minmax(360px, 42%) 1fr; min-height: 100vh; }

.brand-panel { position: relative; overflow: hidden; min-height: 100vh; color: #fff; background: linear-gradient(145deg, rgba(17, 13, 10, .55), rgba(17, 12, 8, .84)), radial-gradient(circle at 18% 12%, rgba(213, 171, 117, .36), transparent 31%), linear-gradient(125deg, #3c2b1d, #16110e 74%); }
.brand-panel::before, .brand-panel::after { position: absolute; content: ""; pointer-events: none; }
.brand-panel::before { width: 530px; height: 700px; right: -180px; bottom: -210px; border: 1px solid rgba(205, 168, 119, .32); border-radius: 50%; transform: rotate(-34deg); }
.brand-panel::after { width: 460px; height: 580px; right: -140px; bottom: -175px; border: 1px solid rgba(205, 168, 119, .18); border-radius: 50%; transform: rotate(-34deg); }
.brand-panel__texture { position: absolute; inset: 0; opacity: .18; background-image: repeating-linear-gradient(115deg, transparent 0, transparent 3px, rgba(255,255,255,.055) 4px, transparent 5px); mix-blend-mode: screen; }
.brand-panel__content { position: relative; z-index: 1; display: flex; flex-direction: column; justify-content: space-between; height: 100%; min-height: 100vh; padding: 44px clamp(34px, 5vw, 76px) 40px; }

.brand { display: inline-flex; align-items: center; gap: 11px; width: max-content; }
.brand__mark { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: var(--tan); transform: rotate(-28deg); }
.brand__mark::before, .brand__mark::after, .brand__mark span { position: absolute; content: ""; display: block; border-radius: 70% 20% 70% 20%; background: #fff; transform: rotate(28deg); }
.brand__mark::before { width: 15px; height: 9px; top: 8px; left: 11px; }
.brand__mark::after { width: 15px; height: 9px; bottom: 8px; right: 10px; }
.brand__mark span { width: 6px; height: 6px; background: var(--tan); }
.brand__mark span:last-child { transform: translate(8px, 8px) rotate(28deg); }
.brand__copy { display: flex; flex-direction: column; gap: 1px; }
.brand__copy strong { font-family: var(--serif); font-size: 20px; font-weight: 600; letter-spacing: -.04em; line-height: 1; }
.brand__copy small { font-size: 6px; font-weight: 700; letter-spacing: .27em; opacity: .63; }
.brand--dark .brand__copy small { color: var(--tan-dark); opacity: .9; }

.brand-panel__message { max-width: 380px; margin-top: -20px; }
.eyebrow { margin: 0 0 17px; color: var(--tan-dark); font-size: 10px; font-weight: 700; letter-spacing: .28em; }
.eyebrow--light { color: #cba77a; }
.brand-panel h1, .form-intro h2 { margin: 0; font-family: var(--serif); font-weight: 500; letter-spacing: -.055em; line-height: .98; }
.brand-panel h1 { font-size: clamp(48px, 5.3vw, 80px); }
.brand-panel h1 em, .form-intro h2 em { color: var(--tan); font-style: italic; font-weight: 500; }
.brand-panel__description { max-width: 280px; margin: 27px 0 0; color: rgba(255,255,255,.66); font-size: 13px; line-height: 1.8; }
.brand-panel__footer { display: flex; align-items: center; gap: 15px; color: rgba(255,255,255,.55); font-size: 10px; letter-spacing: .06em; }
.footer-line { width: 35px; height: 1px; background: var(--tan); }

.form-panel { display: flex; flex-direction: column; min-height: 100vh; padding: 31px clamp(28px, 5vw, 86px) 25px; background: var(--paper); }
.form-panel__topbar { display: flex; align-items: center; justify-content: space-between; color: var(--muted); font-size: 11px; }
.back-link { transition: color 160ms ease; }
.back-link span { margin-right: 8px; color: var(--tan-dark); font-size: 17px; vertical-align: -1px; }
.back-link:hover, .form-footer a:hover, .legal-copy a:hover { color: var(--tan-dark); }
.form-panel__location { letter-spacing: .08em; }

.form-wrap { width: 100%; max-width: 425px; margin: auto; }
.form-wrap--register { max-width: 520px; }
.mobile-brand { display: none; }
.form-intro { margin-bottom: 28px; }
.form-intro h2 { font-size: clamp(40px, 4.2vw, 61px); }
.form-intro__copy { max-width: 390px; margin: 20px 0 0; color: var(--muted); font-size: 12px; line-height: 1.75; }

.auth-form { display: grid; gap: 23px; }
.register-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 22px; }
.register-field--full { grid-column: 1 / -1; }
.field { display: grid; gap: 8px; min-width: 0; }
.field label { color: #3d3832; font-size: 11px; font-weight: 700; letter-spacing: .03em; }
.field__control { position: relative; display: flex; align-items: center; border-bottom: 1px solid #d8d0c5; transition: border-color 180ms ease; }
.field__control:focus-within { border-color: var(--tan-dark); }
.field__icon { width: 20px; color: #b9aa98; font-size: 14px; }
.field input, .field textarea { width: 100%; padding: 0 8px 0 3px; border: 0; outline: 0; color: var(--ink); background: transparent; font-size: 12px; }
.field input { min-height: 39px; }
.field textarea { min-height: 55px; padding-top: 10px; resize: vertical; }
.field input::placeholder, .field textarea::placeholder { color: #b6afa5; }
.password-toggle { border: 0; color: var(--tan-dark); background: transparent; font-size: 10px; font-weight: 700; }

.primary-button { display: flex; align-items: center; justify-content: space-between; min-height: 52px; padding: 0 18px 0 22px; border: 1px solid var(--espresso); color: #fff; background: var(--espresso); font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; transition: transform 160ms ease, background 160ms ease; }
.primary-button:hover { background: var(--tan-dark); transform: translateY(-2px); }
.primary-button:active { transform: scale(.98); }
.primary-button__arrow { color: #d8b486; font-size: 20px; font-weight: 400; }

.form-footer { margin: 27px 0 0; color: var(--muted); font-size: 11px; text-align: center; }
.form-footer a { color: var(--tan-dark); font-weight: 700; transition: color 160ms ease; }
.legal-copy { margin: 0; color: #aaa299; font-size: 9px; line-height: 1.7; text-align: center; }
.legal-copy a { color: #82786d; text-decoration: underline; text-underline-offset: 2px; transition: color 160ms ease; }

.alert { position: relative; display: grid; gap: 3px; margin: -2px 0 20px; padding: 12px 36px 12px 14px; border: 1px solid; font-size: 11px; line-height: 1.5; }
.alert strong { font-size: 11px; }
.alert--error { color: #8a4438; border-color: #e7c9c2; background: #fff6f3; }
.alert--success { color: #47644b; border-color: #c7d9c8; background: #f0f7f0; }
.alert-close { position: absolute; top: 8px; right: 10px; border: 0; color: inherit; background: transparent; font-size: 18px; }

@media (max-width: 900px) {
    .auth-shell { grid-template-columns: minmax(300px, 38%) 1fr; }
    .brand-panel__content { padding-right: 36px; padding-left: 36px; }
    .brand-panel h1 { font-size: 58px; }
    .form-panel { padding-right: 42px; padding-left: 42px; }
}

@media (max-width: 760px) {
    .auth-shell { display: block; }
    .brand-panel { min-height: 330px; }
    .brand-panel__content { min-height: 330px; padding: 28px 25px; }
    .brand-panel__message { margin-top: 30px; }
    .brand-panel h1 { font-size: 47px; }
    .brand-panel__description, .brand-panel__footer { display: none; }
    .form-panel { min-height: auto; padding: 24px 24px 25px; }
    .form-panel__location { display: none; }
    .mobile-brand { display: none; }
    .form-wrap, .form-wrap--register { max-width: 520px; margin: 48px auto 65px; }
}

@media (max-width: 500px) {
    .register-grid { display: block; }
    .register-field--full, .field { margin-bottom: 19px; }
    .form-panel { padding-right: 20px; padding-left: 20px; }
    .brand-panel { min-height: 280px; }
    .brand-panel__content { min-height: 280px; }
    .brand-panel h1 { font-size: 42px; }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; }
}

    </style>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create your LeatherCraft account.">
    <title>Create Account | LeatherCraft</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="register.css">
</head>
<body>
    <main class="auth-shell">
        <section class="brand-panel" aria-label="LeatherCraft brand message">
            <div class="brand-panel__texture"></div>
            <div class="brand-panel__content">
                <a class="brand brand--light" href="index.php" aria-label="LeatherCraft home">
                    <span class="brand__mark" aria-hidden="true"><span></span><span></span></span>
                    <span class="brand__copy">
                        <strong>LeatherCraft</strong>
                        <small>STEP IN QUALITY</small>
                    </span>
                </a>

                <div class="brand-panel__message">
                    <p class="eyebrow eyebrow--light">THE LEATHERCRAFT STANDARD</p>
                    <h1>Walk with<br><em>confidence.</em></h1>
                    <p class="brand-panel__description">Premium leather footwear, thoughtfully made for the moments that matter.</p>
                </div>

                <div class="brand-panel__footer">
                    <span class="footer-line"></span>
                    <span>Crafted for the journey ahead</span>
                </div>
            </div>
        </section>

        <section class="form-panel">
            <div class="form-panel__topbar">
                <a class="back-link" href="index.php"><span aria-hidden="true">←</span> Back to shop</a>
                <span class="form-panel__location">Kathmandu, Nepal</span>
            </div>

            <div class="form-wrap form-wrap--register">
                <div class="mobile-brand brand brand--dark" aria-label="LeatherCraft">
                    <span class="brand__mark" aria-hidden="true"><span></span><span></span></span>
                    <span class="brand__copy">
                        <strong>LeatherCraft</strong>
                        <small>STEP IN QUALITY</small>
                    </span>
                </div>

                <div class="form-intro">
                    <p class="eyebrow">JOIN LEATHERCRAFT</p>
                    <h2>Create your<br><em>account.</em></h2>
                    <p class="form-intro__copy">Set up your account and discover premium leather footwear made for the journey ahead.</p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert alert--error" role="alert">
                        <strong>We couldn't create your account.</strong>
                        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                        <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="alert alert--success" role="status">
                        <strong>Account ready.</strong>
                        <span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <form class="auth-form register-form" method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>" novalidate>
                    <div class="register-grid">
                        <div class="field register-field--full">
                            <label for="name">Full name</label>
                            <div class="field__control">
                                <span class="field__icon" aria-hidden="true">✦</span>
                                <input id="name" name="name" type="text" value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Your full name" autocomplete="name" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="email">Email address</label>
                            <div class="field__control">
                                <span class="field__icon" aria-hidden="true">✉</span>
                                <input id="email" name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="you@example.com" autocomplete="email" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="phone">Phone number</label>
                            <div class="field__control">
                                <span class="field__icon" aria-hidden="true">⌕</span>
                                <input id="phone" name="phone" type="tel" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="98XXXXXXXX" autocomplete="tel" required>
                            </div>
                        </div>

                        <div class="field register-field--full">
                            <label for="address">Address</label>
                            <div class="field__control field__control--textarea">
                                <textarea id="address" name="address" placeholder="Enter your address" autocomplete="street-address" required><?= htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                        <div class="field">
                            <label for="password">Password</label>
                            <div class="field__control">
                                <span class="field__icon" aria-hidden="true">⌁</span>
                                <input id="password" name="password" type="password" placeholder="Minimum 6 characters" autocomplete="new-password" required>
                                <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle="password">Show</button>
                            </div>
                        </div>

                        <div class="field">
                            <label for="confirm_password">Confirm password</label>
                            <div class="field__control">
                                <span class="field__icon" aria-hidden="true">⌁</span>
                                <input id="confirm_password" name="confirm_password" type="password" placeholder="Re-enter password" autocomplete="new-password" required>
                                <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle="confirm_password">Show</button>
                            </div>
                        </div>
                    </div>

                    <button class="primary-button" type="submit">
                        <span>Create account</span>
                        <span class="primary-button__arrow" aria-hidden="true">→</span>
                    </button>
                </form>

                <p class="form-footer">Already have an account? <a href="login.php">Sign in here</a></p>
            </div>

            <p class="legal-copy">By continuing, you agree to our <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a>.</p>
        </section>
    </main>

    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
            toggle.addEventListener('click', () => {
                const input = document.getElementById(toggle.dataset.passwordToggle);
                const showPassword = input.type === 'password';
                input.type = showPassword ? 'text' : 'password';
                toggle.textContent = showPassword ? 'Hide' : 'Show';
                toggle.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
            });
        });
    </script>
</body>
</html>
