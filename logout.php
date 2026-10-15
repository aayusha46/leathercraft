<?php
session_start();

$_SESSION = [];

/* Delete the session cookie when cookies are enabled. */
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

/* Send the user to login.php after the styled confirmation screen. */
header('Refresh: 2; url=login.php');
?>

<style>


:root {
    --black: #111111;
    --dark: #1a1a1a;
    --gray: #666666;
    --light-gray: #999999;
    --border: #e4e4e4;
    --soft: #f7f7f7;
    --white: #ffffff;
}

* {
    box-sizing: border-box;
}

html,
body {
    min-height: 100%;
}

body {
    margin: 0;
    color: var(--black);
    background: var(--white);

    font-family: "DM Sans", Arial, Helvetica, sans-serif;

    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

a {
    color: inherit;
    text-decoration: none;
}


.logout-shell {
    min-height: 100vh;

    display: flex;
    flex-direction: column;

    background: #ffffff;
}

.logout-panel {
    position: relative;

    flex: 1;

    width: 100%;
    min-height: 100vh;

    display: flex;
    flex-direction: column;

    background: #ffffff;
}


.logout-panel .form-panel__topbar {
    width: 100%;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 28px 55px;

    border-bottom: 1px solid #eeeeee;

    color: #777777;

    font-size: 11px;
}

.back-link {
    display: inline-flex;
    align-items: center;

    color: #555555;

    font-size: 11px;
    font-weight: 500;

    transition:
        color 0.2s ease,
        transform 0.2s ease;
}

.back-link span {
    margin-right: 8px;

    color: #111111;

    font-size: 18px;
    line-height: 1;
}

.back-link:hover {
    color: #111111;

    transform: translateX(-2px);
}

.form-panel__location {
    color: #999999;

    font-size: 10px;
    letter-spacing: 0.08em;
}

.logout-wrap {
    width: 100%;
    max-width: 500px;

    margin: auto;

    padding: 80px 25px 70px;

    text-align: center;
}
.logout-icon {
    display: flex;
    align-items: center;
    justify-content: center;

    width: 58px;
    height: 58px;

    margin: 0 auto 25px;

    border: 1px solid #111111;
    border-radius: 50%;

    color: #111111;
    background: #ffffff;

    font-size: 22px;
    font-weight: 400;

    box-shadow: none;
}

.logout-wrap .eyebrow {
    margin: 0 0 17px;

    color: #777777;

    font-size: 9px;
    font-weight: 700;

    letter-spacing: 0.25em;

    text-transform: uppercase;
}
.logout-wrap h2 {
    margin: 0;

    color: #111111;

    font-family: "Playfair Display", Georgia, "Times New Roman", serif;

    font-size: clamp(48px, 6vw, 72px);

    font-weight: 500;

    letter-spacing: -0.055em;

    line-height: 0.98;
}

.logout-wrap h2 em {
    color: #111111;

    font-style: italic;
    font-weight: 500;
}

.logout-copy {
    max-width: 350px;

    margin: 24px auto 0;

    color: #777777;

    font-size: 12px;

    line-height: 1.8;
}

.logout-progress {
    width: 100%;
    height: 2px;

    margin: 32px auto 12px;

    overflow: hidden;

    background: #e8e8e8;
}

.logout-progress span {
    display: block;

    width: 100%;
    height: 100%;

    background: #111111;

    transform: scaleX(0);

    transform-origin: left center;

    animation: logoutProgress 2s linear forwards;
}

@keyframes logoutProgress {

    from {
        transform: scaleX(0);
    }

    to {
        transform: scaleX(1);
    }

}

.logout-redirect {
    margin: 0;

    color: #999999;

    font-size: 10px;

    letter-spacing: 0.02em;
}

.logout-button {
    display: inline-flex;
    align-items: center;
    justify-content: space-between;

    gap: 35px;

    min-width: 205px;

    margin-top: 30px;

    padding: 15px 18px;

    border: 1px solid #111111;

    color: #ffffff;

    background: #111111;

    font-size: 10px;
    font-weight: 700;

    letter-spacing: 0.12em;

    text-transform: uppercase;

    transition:
        background 0.2s ease,
        color 0.2s ease,
        transform 0.2s ease;
}

.logout-button span {
    color: #ffffff;

    font-size: 18px;
    font-weight: 400;

    transition: color 0.2s ease;
}

.logout-button:hover {
    color: #111111;

    background: #ffffff;

    transform: translateY(-2px);
}

.logout-button:hover span {
    color: #111111;
}

.logout-button:active {
    transform: translateY(0);
}

.logout-panel .legal-copy {
    width: 100%;

    margin: 0;

    padding: 20px 20px 25px;

    border-top: 1px solid #eeeeee;

    color: #999999;

    font-size: 9px;

    text-align: center;
}

.logout-shell .brand-panel {
    display: none;
}

.logout-shell .brand-panel__texture {
    display: none;
}

.logout-shell .brand-panel__content {
    display: none;
}
@media (max-width: 768px) {

    .logout-panel {
        min-height: 100vh;
    }

    .logout-panel .form-panel__topbar {
        padding: 23px 25px;
    }

    .logout-wrap {
        max-width: 460px;

        padding: 70px 25px 60px;
    }

    .logout-wrap h2 {
        font-size: 55px;
    }
}

@media (max-width: 480px) {

    .logout-panel .form-panel__topbar {
        padding: 20px;
    }

    .form-panel__location {
        display: none;
    }

    .logout-wrap {
        padding: 65px 20px 55px;
    }

    .logout-icon {
        width: 52px;
        height: 52px;

        margin-bottom: 22px;

        font-size: 19px;
    }

    .logout-wrap .eyebrow {
        font-size: 8px;

        letter-spacing: 0.2em;
    }

    .logout-wrap h2 {
        font-size: 46px;

        letter-spacing: -0.04em;
    }

    .logout-copy {
        max-width: 300px;

        font-size: 11px;
    }

    .logout-progress {
        margin-top: 28px;
    }

    .logout-button {
        width: 100%;
        max-width: 240px;

        justify-content: space-between;
    }

    .logout-panel .legal-copy {
        padding-bottom: 20px;
    }

}


@media (prefers-reduced-motion: reduce) {

    .logout-progress span {
        animation: none;
        transform: scaleX(1);
    }

    .back-link,
    .logout-button {
        transition: none;
    }
}
```

</style>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="2;url=login.php">
    <meta name="description" content="You have been signed out of LeatherCraft.">
    <title>Signed Out | LeatherCraft</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="register.css">
    <link rel="stylesheet" href="logout.css">
</head>
<body>
    <main class="auth-shell logout-shell">
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

        <section class="form-panel logout-panel">
            <div class="form-panel__topbar">
                <a class="back-link" href="index.php"><span aria-hidden="true">←</span> Back to shop</a>
                <span class="form-panel__location">Kathmandu, Nepal</span>
            </div>

            <div class="logout-wrap">
                <div class="logout-icon" aria-hidden="true">✓</div>
                <p class="eyebrow">ACCOUNT SECURE</p>
                <h2>You are<br><em>signed out.</em></h2>
                <p class="logout-copy">Your LeatherCraft session has been safely closed. We hope to see you again soon.</p>
                <div class="logout-progress" aria-hidden="true"><span></span></div>
                <p class="logout-redirect">Returning to the login page...</p>
                <a class="logout-button" href="login.php">Return to login <span aria-hidden="true">→</span></a>
            </div>

            <p class="legal-copy">Thank you for visiting LeatherCraft.</p>
        </section>
    </main>
</body>
</html>
