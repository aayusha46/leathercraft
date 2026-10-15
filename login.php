<?php
session_start();
include 'database.php';

$errors = [];
$success = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '') {
        $errors[] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Please enter your password.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'SELECT id, name, email, password, role
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['remember'] = isset($_POST['remember']);

            header('Location: products.php');
            exit;
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}
?>
<style>

:root {
    --black: #111111;
    --dark: #1a1a1a;
    --gray: #666666;
    --light-gray: #999999;
    --border: #dddddd;
    --soft: #f7f7f7;
    --white: #ffffff;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    min-height: 100%;
    scroll-behavior: smooth;
}

body {
    min-height: 100vh;
    background: var(--white);
    color: var(--black);
    font-family: "DM Sans", Arial, Helvetica, sans-serif;
    -webkit-font-smoothing: antialiased;
}


.site-navbar {
    min-height: 76px;
    background: #ffffff !important;
    border-bottom: 1px solid #eeeeee;
    box-shadow: none;
}

.site-navbar .container-fluid {
    height: 76px;
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 10px;

    color: #111111 !important;

    font-family: Georgia, "Times New Roman", serif;
    font-size: 25px;
    font-weight: 700;
    letter-spacing: -0.7px;
}

.navbar-brand i {
    font-size: 20px;
}

.navbar-brand:hover {
    color: #111111 !important;
}

.site-navbar .nav-link {
    position: relative;

    margin-left: 28px;
    padding: 27px 0 24px !important;

    color: #555555 !important;

    font-size: 13px;
    font-weight: 500;

    transition: color 0.2s ease;
}

.site-navbar .nav-link:hover {
    color: #111111 !important;
}

.site-navbar .nav-link.active {
    color: #111111 !important;
    font-weight: 700;
}

.site-navbar .nav-link.active::after {
    position: absolute;
    content: "";

    left: 0;
    right: 0;
    bottom: 17px;

    height: 2px;

    background: #111111;
}

/* Mobile navbar button */

.site-navbar .navbar-toggler {
    border: 1px solid #222222;
    padding: 7px 9px;
}

.site-navbar .navbar-toggler:focus {
    box-shadow: none;
}

.site-navbar .navbar-toggler-icon {
    filter: invert(1);
}


.login-section {
    min-height: calc(100vh - 76px - 90px);

    display: flex;
    justify-content: center;
    align-items: center;

    padding: 70px 20px;

    background: #ffffff;
}

.login-box {
    width: 100%;
    max-width: 430px;

    padding: 10px 0;

    background: #ffffff;
}

.login-title {
    margin-bottom: 38px;
}

.login-title > i {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    width: 46px;
    height: 46px;

    margin-bottom: 20px;

    border: 1px solid #111111;
    border-radius: 50%;

    color: #111111;

    font-size: 17px;
}

.login-title h1 {
    margin: 0;

    color: #111111;

    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(42px, 5vw, 58px);
    font-weight: 500;

    line-height: 1;
    letter-spacing: -2.5px;
}

.login-title h1 span {
    font-style: italic;
}

.login-title p {
    max-width: 360px;

    margin-top: 18px;
    margin-bottom: 0;

    color: #777777;

    font-size: 13px;
    line-height: 1.7;
}

.custom-alert,
.custom-success {
    display: flex;
    align-items: center;
    gap: 11px;

    width: 100%;

    margin-bottom: 25px;
    padding: 12px 14px;

    font-size: 12px;
    line-height: 1.5;
}

.custom-alert {
    border: 1px solid #e3c5c5;
    background: #fff8f8;
    color: #8b3d3d;
}

.custom-success {
    border: 1px solid #cbdcca;
    background: #f7fbf7;
    color: #416b47;
}

.custom-alert i,
.custom-success i {
    flex-shrink: 0;
}

.custom-alert span,
.custom-success span {
    flex: 1;
}

.custom-alert button {
    flex-shrink: 0;

    border: 0;
    background: transparent;

    color: inherit;

    font-size: 20px;
    line-height: 1;

    cursor: pointer;
}
.login-box form {
    width: 100%;
}

.login-box .mb-3 {
    margin-bottom: 24px !important;
}

.form-label {
    display: block;

    margin-bottom: 9px;

    color: #222222;

    font-size: 11px;
    font-weight: 700;

    letter-spacing: 0.04em;
}


.form-control {
    width: 100%;

    min-height: 50px;

    padding: 0 2px;

    border: 0;
    border-bottom: 1px solid #cccccc;
    border-radius: 0;

    outline: none;

    color: #111111;
    background: #ffffff;

    font-size: 13px;

    box-shadow: none !important;

    transition:
        border-color 0.2s ease,
        background 0.2s ease;
}

.form-control:focus {
    border-color: #111111;

    background: #ffffff;
}

.form-control::placeholder {
    color: #aaaaaa;
}


.password-label-row {
    display: flex;
    align-items: center;
    justify-content: space-between;

    margin-bottom: 9px;
}

.password-label-row .form-label {
    margin-bottom: 0;
}

.password-label-row a {
    color: #666666;

    font-size: 10px;
    font-weight: 600;

    text-decoration: none;

    transition: color 0.2s ease;
}

.password-label-row a:hover {
    color: #111111;
    text-decoration: underline;
}


.password-control {
    position: relative;
}

.password-control .form-control {
    padding-right: 52px;
}

.password-toggle {
    position: absolute;

    top: 50%;
    right: 0;

    transform: translateY(-50%);

    border: 0;
    background: transparent;

    color: #555555;

    font-size: 10px;
    font-weight: 700;

    cursor: pointer;

    transition: color 0.2s ease;
}

.password-toggle:hover {
    color: #111111;
}

.remember-check {
    display: flex;
    align-items: center;
    gap: 8px;

    margin-top: 4px;
}

.remember-check .form-check-input {
    width: 15px;
    height: 15px;

    margin: 0;

    border: 1px solid #999999;
    border-radius: 2px;

    box-shadow: none !important;

    cursor: pointer;
}

.remember-check .form-check-input:checked {
    border-color: #111111;
    background-color: #111111;
}

.remember-check .form-check-label {
    color: #666666;

    font-size: 11px;

    cursor: pointer;
}

.login-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 11px;

    width: 100%;
    min-height: 54px;

    margin-top: 8px;

    border: 1px solid #111111;

    background: #111111;
    color: #ffffff;

    font-size: 11px;
    font-weight: 700;

    letter-spacing: 0.15em;
    text-transform: uppercase;

    cursor: pointer;

    transition:
        background 0.2s ease,
        color 0.2s ease,
        transform 0.2s ease;
}

.login-btn:hover {
    background: #ffffff;
    color: #111111;

    transform: translateY(-2px);
}

.login-btn:active {
    transform: translateY(0);
}

.login-btn i {
    font-size: 12px;
}

.register-text {
    margin-top: 28px;

    color: #777777;

    font-size: 11px;

    text-align: center;
}

.register-text a {
    color: #111111;

    font-weight: 700;

    text-decoration: none;

    border-bottom: 1px solid #111111;

    transition: opacity 0.2s ease;
}

.register-text a:hover {
    opacity: 0.6;
}
footer {
    min-height: 90px;

    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;

    border-top: 1px solid #eeeeee;

    background: #ffffff;
}

.footer-brand {
    display: flex;
    align-items: center;
    gap: 8px;

    color: #111111;

    font-family: Georgia, "Times New Roman", serif;

    font-size: 15px;
    font-weight: 700;
}

.footer-brand i {
    font-size: 12px;
}

.footer-text {
    margin: 5px 0 0;

    color: #999999;

    font-size: 9px;
    letter-spacing: 0.05em;
}
@media (max-width: 768px) {

    .site-navbar {
        min-height: 68px;
    }

    .site-navbar .container-fluid {
        height: 68px;
    }

    .navbar-brand {
        font-size: 22px;
    }

    .site-navbar .nav-link {
        margin-left: 0;
        padding: 12px 0 !important;
    }

    .site-navbar .nav-link.active::after {
        display: none;
    }

    .site-navbar .navbar-nav {
        padding: 15px 0;
    }

    .login-section {
        min-height: calc(100vh - 68px - 90px);

        padding: 55px 25px;
    }

    .login-title {
        margin-bottom: 32px;
    }

    .login-title h1 {
        font-size: 48px;
    }
}

@media (max-width: 480px) {

    .site-navbar .container-fluid {
        padding-left: 18px !important;
        padding-right: 18px !important;
    }

    .navbar-brand {
        font-size: 20px;
    }

    .navbar-brand i {
        font-size: 17px;
    }

    .login-section {
        align-items: flex-start;

        min-height: auto;

        padding: 55px 20px 70px;
    }

    .login-box {
        max-width: 100%;
    }

    .login-title {
        margin-bottom: 32px;
    }

    .login-title > i {
        width: 42px;
        height: 42px;

        margin-bottom: 17px;

        font-size: 15px;
    }

    .login-title h1 {
        font-size: 42px;
        letter-spacing: -1.8px;
    }

    .login-title p {
        font-size: 12px;
    }

    .form-control {
        min-height: 48px;
    }

    .login-btn {
        min-height: 52px;
    }

    footer {
        min-height: 85px;
    }
}


@media (prefers-reduced-motion: reduce) {

    *,
    *::before,
    *::after {
        transition: none !important;
        animation: none !important;
        scroll-behavior: auto !important;
    }
}


</style>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to your LeatherCraft account.">
    <title>Login | LeatherCraft</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark site-navbar">
        <div class="container-fluid px-4 px-md-5">
            <a class="navbar-brand" href="index.php">
                <i class="fa-solid fa-shoe-prints"></i>
                LeatherCraft
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMenu">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="login-section">
        <div class="login-box">
            <div class="login-title">
                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                <h1>Welcome <span>Back</span></h1>
                <p>Login to continue shopping premium men's leather shoes.</p>
            </div>

            <?php if ($errors): ?>
                <div class="custom-alert" role="alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($errors[0], ENT_QUOTES, 'UTF-8') ?></span>
                    <button type="button" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="custom-success" role="status">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>" novalidate>
                <div class="mb-3">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="example@gmail.com" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required>
                </div>

                <div class="mb-3">
                    <div class="password-label-row">
                        <label class="form-label mb-0" for="password">Password</label>
                        <a href="forgot-password.php">Forgot password?</a>
                    </div>
                    <div class="password-control">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" data-password-toggle aria-label="Show password">Show</button>
                    </div>
                </div>

                <div class="form-check remember-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                    Login
                </button>
            </form>

            <div class="register-text">
                Don't have an account?
                <a href="register.php">Register here</a>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-brand">
            <i class="fa-solid fa-shoe-prints" aria-hidden="true"></i>
            LeatherCraft
        </div>
        <p class="footer-text">Premium Men's Leather Shoes Collection.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const passwordInput = document.querySelector('#password');
        const passwordToggle = document.querySelector('[data-password-toggle]');

        passwordToggle?.addEventListener('click', () => {
            const showPassword = passwordInput.type === 'password';
            passwordInput.type = showPassword ? 'text' : 'password';
            passwordToggle.textContent = showPassword ? 'Hide' : 'Show';
            passwordToggle.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
        });
    </script>
</body>
</html>
