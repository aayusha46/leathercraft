<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT id, name, email, phone, address, role
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit;
    }

} catch (PDOException $e) {
    die("Could not load profile: " . $e->getMessage());
}

$cart_count = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += (int)($item['quantity'] ?? 0);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile - LeatherCraft</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap');
:root { --ink:#171411; --paper:#f7f5f0; --panel:#fff; --espresso:#211a15; --gold:#c09968; --gold-dark:#926b42; --muted:#91887d; --line:#e6e0d7; }
* { box-sizing:border-box; }
body { margin:0; min-height:100vh; color:var(--ink); background:var(--paper); font-family:'DM Sans',Arial,sans-serif; }
.navbar { padding:20px 0; border-bottom:1px solid var(--line); background:#fff; }
.navbar-brand { color:var(--espresso)!important; font-family:'Playfair Display',Georgia,serif; font-size:23px; font-weight:600; letter-spacing:-.04em; }
.navbar-brand i { margin-right:8px; color:var(--gold-dark); }
.nav-link { margin:0 8px; color:var(--muted)!important; font-size:11px; }
.nav-link:hover { color:var(--gold-dark)!important; }
.nav-icon { position:relative; margin-left:20px; color:var(--espresso); font-size:17px; text-decoration:none; }
.nav-icon:hover { color:var(--gold-dark); }
.cart-badge { position:absolute; top:-10px; right:-12px; display:flex; align-items:center; justify-content:center; width:19px; height:19px; color:#fff; border-radius:50%; background:var(--gold-dark); font-size:10px; font-weight:700; }
.profile-section { min-height:calc(100vh - 145px); padding:78px 15px 90px; }
.profile-wrapper { max-width:940px; margin:0 auto; }
.page-title { margin-bottom:44px; text-align:center; }
.page-title::before { display:block; margin-bottom:15px; color:var(--gold-dark); content:'LEATHERCRAFT ACCOUNT'; font-size:9px; font-weight:700; letter-spacing:.28em; }
.page-title h1 { margin:0 0 14px; color:var(--ink); font-family:'Playfair Display',Georgia,serif; font-size:clamp(45px,6vw,70px); font-weight:500; letter-spacing:-.06em; line-height:.98; }
.page-title h1 span { color:var(--gold); font-style:italic; }
.page-title p { margin:0; color:var(--muted); font-size:13px; }
.profile-card { overflow:hidden; border:1px solid var(--line); border-radius:6px; background:var(--panel); box-shadow:0 15px 38px rgba(33,26,21,.05); }
.profile-header { padding:45px 25px 38px; border-bottom:1px solid var(--line); background:linear-gradient(140deg,#fff,#fbf8f3); text-align:center; }
.profile-avatar { display:grid; place-items:center; width:100px; height:100px; margin:0 auto 18px; color:#fff; border:7px solid #f3ebe1; border-radius:50%; background:var(--espresso); font-size:36px; box-shadow:0 8px 20px rgba(33,26,21,.11); }
.profile-header h2 { margin:0 0 7px; color:var(--ink); font-family:'Playfair Display',Georgia,serif; font-size:29px; font-weight:500; }
.profile-header p { margin:0; color:var(--muted); font-size:12px; }
.profile-body { padding:31px; }
.profile-info { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
.info-box { min-height:102px; padding:18px; border:1px solid var(--line); border-radius:4px; background:#fcfbf8; }
.info-label { margin-bottom:9px; color:var(--muted); font-size:9px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; }
.info-value { display:flex; align-items:flex-start; gap:9px; color:var(--ink); font-size:12px; font-weight:700; line-height:1.55; word-break:break-word; }
.info-value i { flex:0 0 auto; width:18px; margin-top:2px; color:var(--gold-dark); text-align:center; }
.role-badge { display:inline-block; padding:5px 11px; color:var(--gold-dark); border:1px solid #dfccb4; border-radius:20px; background:#f5ede4; font-size:10px; font-weight:700; text-transform:capitalize; }
.profile-actions { display:flex; gap:11px; margin-top:28px; flex-wrap:wrap; }
.btn-gold,.btn-dark { display:inline-flex; align-items:center; gap:8px; padding:13px 19px; border-radius:4px; font-size:10px; font-weight:700; letter-spacing:.05em; text-decoration:none; text-transform:uppercase; transition:.2s ease; }
.btn-gold { color:#fff; border:1px solid var(--espresso); background:var(--espresso); }
.btn-gold:hover { color:#fff; background:var(--gold-dark); }
.btn-dark { color:var(--gold-dark); border:1px solid #d6c4ac; background:#fff; }
.btn-dark:hover { color:#fff; border-color:var(--espresso); background:var(--espresso); }
footer { padding:25px 0; color:var(--muted); border-top:1px solid var(--line); background:#fff; font-size:11px; text-align:center; }
footer span { color:var(--gold-dark); }
@media (max-width:768px) { .profile-section { padding:52px 15px 65px; } .profile-info { grid-template-columns:1fr; } .profile-body { padding:22px; } .page-title h1 { font-size:48px; } .profile-actions { flex-direction:column; } .profile-actions a { justify-content:center; } .nav-icon { margin-left:10px; } }
</style>
</head>

<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">

        <a class="navbar-brand" href="index.php">
            <i class="fa-solid fa-shoe-prints"></i>
            LeatherCraft
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNav"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">

            <ul class="navbar-nav mx-auto">

                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="products.php">Shop</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="index.php#categories">
                        Categories
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="index.php#why">
                        About Us
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="index.php#footer">
                        Contact
                    </a>
                </li>

            </ul>

            <div class="d-flex align-items-center">

                <a href="profile.php"
                   class="nav-icon"
                   title="My Profile">
                    <i class="fa-solid fa-user"></i>
                </a>

                <!-- Cart -->
                <a href="cart.php"
                   class="nav-icon"
                   title="Cart">

                    <i class="fa-solid fa-cart-shopping"></i>

                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge">
                            <?= $cart_count ?>
                        </span>
                    <?php endif; ?>

                </a>

            </div>

        </div>
    </div>
</nav>

<section class="profile-section">

    <div class="profile-wrapper">

        <div class="page-title">

            <h1>
                My <span>Profile</span>
            </h1>

            <p>
                View your LeatherCraft account information
            </p>

        </div>


        <div class="profile-card">

            <div class="profile-header">

                <div class="profile-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>

                <h2>
                    <?= htmlspecialchars($user['name']) ?>
                </h2>

                <p>
                    <?= htmlspecialchars($user['email']) ?>
                </p>

            </div>

            <div class="profile-body">

                <div class="profile-info">

                    <div class="info-box">

                        <div class="info-label">
                            Full Name
                        </div>

                        <div class="info-value">
                            <i class="fa-solid fa-user"></i>
                            <?= htmlspecialchars($user['name'] ?? 'Not provided') ?>
                        </div>

                    </div>

                    <div class="info-box">

                        <div class="info-label">
                            Email Address
                        </div>

                        <div class="info-value">
                            <i class="fa-solid fa-envelope"></i>
                            <?= htmlspecialchars($user['email'] ?? 'Not provided') ?>
                        </div>

                    </div>

                    <div class="info-box">

                        <div class="info-label">
                            Phone Number
                        </div>

                        <div class="info-value">
                            <i class="fa-solid fa-phone"></i>
                            <?= htmlspecialchars($user['phone'] ?? 'Not provided') ?>
                        </div>

                    </div>

                    <div class="info-box">

                        <div class="info-label">
                            Address
                        </div>

                        <div class="info-value">
                            <i class="fa-solid fa-location-dot"></i>
                            <?= htmlspecialchars($user['address'] ?? 'Not provided') ?>
                        </div>

                    </div>


                    <div class="info-box">

                        <div class="info-label">
                            Account Type
                        </div>

                        <div class="info-value">

                            <i class="fa-solid fa-shield-halved"></i>

                            <span class="role-badge">
                                <?= htmlspecialchars($user['role'] ?? 'customer') ?>
                            </span>

                        </div>

                    </div>

                    <div class="info-box">

                        <div class="info-label">
                            Customer ID
                        </div>

                        <div class="info-value">
                            <i class="fa-solid fa-id-card"></i>
                            #<?= htmlspecialchars($user['id']) ?>
                        </div>

                    </div>

                </div>
                <div class="profile-actions">

                    <a href="products.php" class="btn-gold">
                        <i class="fa-solid fa-bag-shopping"></i>
                        Continue Shopping
                    </a>

                    <a href="orders.php" class="btn-dark">
                        <i class="fa-solid fa-box"></i>
                        My Orders
                    </a>

                    <a href="logout.php" class="btn-dark">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Logout
                    </a>

                </div>

            </div>

        </div>

    </div>

</section>

<footer>
    © <?= date('Y') ?> <span>LeatherCraft</span>. All Rights Reserved.
</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>