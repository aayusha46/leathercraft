<?php

session_start();
require_once 'database.php';

$is_logged_in = isset($_SESSION['user_id']);


$cart_count = 0;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (is_array($item)) {
            $cart_count += (int)($item['quantity'] ?? 1);
        }
    }
}

$notification_count = 0;

if ($is_logged_in) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM notifications
            WHERE user_id = ? AND is_read = 0
        ");

        $stmt->execute([
            (int)$_SESSION['user_id']
        ]);

        $notification_count = (int)$stmt->fetchColumn();

    } catch (PDOException $e) {
        $notification_count = 0;
    }
}

function getCategoryImage($name)
{
    $images = [
        'boots'   => 'images/boot.png',
        'casual'  => 'images/casuals.png',
        'derby'   => 'images/derby.png',
        'formal'  => 'images/formal.png',
        'loafers' => 'images/loafer.png',
        'oxford'  => 'images/oxford.png'
    ];

    $key = strtolower(trim((string)$name));

    return $images[$key] ?? 'images/category/default.png';
}

function getProductImage($image)
{
    $image = trim((string)$image);

    if ($image === '') {
        return 'images/default-shoe.jpg';
    }

    if (
        strpos($image, 'images/') === 0 ||
        strpos($image, 'images\\') === 0
    ) {
        return str_replace('\\', '/', $image);
    }

    return 'images/' . $image;
}

$categories = [];

try {

    $stmt = $pdo->query("
        SELECT *
        FROM categories
        ORDER BY name ASC
    ");

    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $categories = [];

}
$products = [];

try {

    $stmt = $pdo->query("
        SELECT
            p.*,
            c.name AS category_name,
            COALESCE(AVG(r.rating), 0) AS average_rating,
            COUNT(r.id) AS review_count
        FROM products p

        LEFT JOIN categories c
            ON p.category_id = c.id

        LEFT JOIN reviews r
            ON p.id = r.product_id

        GROUP BY p.id

        ORDER BY p.id DESC

        LIMIT 8
    ");

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $products = [];

}

$categoryDescriptions = [

    'boots' =>
        'Durable leather boots designed for confidence and everyday performance.',

    'casual' =>
        'Comfortable leather footwear for relaxed everyday style.',

    'derby' =>
        'Classic lace-up shoes for smart and professional occasions.',

    'formal' =>
        'Elegant leather footwear for formal events and special occasions.',

    'loafers' =>
        'Easy slip-on leather shoes combining comfort and sophistication.',

    'oxford' =>
        'Timeless leather shoes with a classic and polished appearance.'
];

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    LeatherCraft | Premium Leather Shoes
</title>



<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>



<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
    rel="stylesheet"
>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: 'DM Sans', sans-serif;
    background: #ffffff;
    color: #17120e;
}

a {
    text-decoration: none;
}

:root {

    --black: #17120e;

    --brown: #5b3926;

    --brown-dark: #3b2418;

    --brown-light: #8b5e3c;

    --gold: #c59b45;

    --gold-light: #e0bd70;

    --cream: #f8f4ee;

    --cream-dark: #eee6da;

    --text: #24201c;

    --muted: #77716b;

    --border: #e7e0d8;

}


.topbar {

    background: var(--black);

    color: #fff;

    padding: 9px 6%;

    font-size: 12px;
}

.topbar-inner {

    max-width: 1400px;

    margin: auto;

    display: flex;

    justify-content: space-between;

    align-items: center;
}

.topbar-left i {

    color: var(--gold);

    margin-right: 7px;
}

.topbar-right {

    display: flex;

    gap: 18px;

    color: #ddd;
}


.navbar-custom {

    background: #fff;

    border-bottom: 1px solid var(--border);

    position: sticky;

    top: 0;

    z-index: 1000;
}

.nav-inner {

    max-width: 1400px;

    margin: auto;

    min-height: 82px;

    padding: 0 6%;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 35px;
}


.logo {

    display: flex;

    align-items: center;

    gap: 11px;

    color: var(--black);

    min-width: 220px;
}

.logo:hover {

    color: var(--black);
}

.logo-icon {

    width: 43px;

    height: 43px;

    border-radius: 50%;

    background: var(--brown);

    color: var(--gold-light);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;

    transform: rotate(-10deg);
}

.logo-name {

    font-family: 'Playfair Display', serif;

    font-size: 27px;

    font-weight: 700;

    line-height: 1;
}

.logo-name span {

    color: var(--brown-light);
}

.logo-small {

    color: #958d84;

    font-size: 8px;

    letter-spacing: 3px;

    margin-top: 5px;
}




.nav-links {

    display: flex;

    align-items: center;

    gap: 31px;
}

.nav-links a {

    color: #4d4742;

    font-size: 13px;

    font-weight: 600;

    position: relative;

    padding: 31px 0;

    transition: .25s;
}

.nav-links a:hover,

.nav-links a.active {

    color: var(--brown);
}

.nav-links a.active::after {

    content: '';

    position: absolute;

    left: 0;

    right: 0;

    bottom: 20px;

    height: 2px;

    background: var(--gold);
}



.nav-actions {

    display: flex;

    align-items: center;

    gap: 19px;
}

.nav-icon {

    color: var(--black);

    font-size: 17px;

    position: relative;

    transition: .25s;
}

.nav-icon:hover {

    color: var(--brown-light);
}

.nav-badge {

    position: absolute;

    top: -9px;

    right: -9px;

    width: 18px;

    height: 18px;

    border-radius: 50%;

    background: var(--gold);

    color: #fff;

    font-size: 9px;

    font-weight: 700;

    display: flex;

    align-items: center;

    justify-content: center;
}

.login-btn {

    border: 1px solid var(--brown);

    color: var(--brown);

    padding: 9px 18px;

    border-radius: 4px;

    font-size: 12px;

    font-weight: 700;

    transition: .25s;
}

.login-btn:hover {

    background: var(--brown);

    color: #fff;
}


.hero {

    background:

        linear-gradient(
            90deg,
            rgba(28,17,11,.93),
            rgba(28,17,11,.72),
            rgba(28,17,11,.18)
        ),

        url("images/hero-shoes.jpg");

    background-size: cover;

    background-position: center;

    min-height: 570px;

    display: flex;

    align-items: center;
}

.hero-inner {

    width: 88%;

    max-width: 1400px;

    margin: auto;
}

.hero-content {

    max-width: 600px;

    color: #fff;
}

.hero-label {

    color: var(--gold-light);

    font-size: 12px;

    font-weight: 700;

    letter-spacing: 3px;

    margin-bottom: 15px;
}

.hero h1 {

    font-family: 'Playfair Display', serif;

    font-size: clamp(42px, 5vw, 68px);

    line-height: 1.08;

    margin-bottom: 20px;
}

.hero h1 span {

    color: var(--gold-light);
}

.hero-text {

    color: #e9e3dd;

    font-size: 15px;

    line-height: 1.8;

    max-width: 500px;

    margin-bottom: 30px;
}

.hero-buttons {

    display: flex;

    gap: 13px;

    flex-wrap: wrap;
}

.btn-primary-custom {

    background: var(--gold);

    color: #fff;

    border: 1px solid var(--gold);

    padding: 13px 27px;

    border-radius: 4px;

    font-size: 12px;

    font-weight: 700;

    transition: .25s;
}

.btn-primary-custom:hover {

    background: var(--gold-light);

    border-color: var(--gold-light);

    color: #fff;

    transform: translateY(-2px);
}

.btn-outline-custom {

    border: 1px solid #fff;

    color: #fff;

    padding: 13px 27px;

    border-radius: 4px;

    font-size: 12px;

    font-weight: 600;

    transition: .25s;
}

.btn-outline-custom:hover {

    background: #fff;

    color: var(--black);
}


.trust-strip {

    width: 90%;

    max-width: 1300px;

    margin: -38px auto 0;

    position: relative;

    z-index: 5;

    background: #fff;

    border: 1px solid var(--border);

    box-shadow: 0 10px 35px rgba(0,0,0,.07);

    display: grid;

    grid-template-columns: repeat(4, 1fr);

    border-radius: 8px;

    overflow: hidden;
}

.trust-item {

    display: flex;

    align-items: center;

    gap: 13px;

    padding: 22px 25px;

    border-right: 1px solid var(--border);
}

.trust-item:last-child {

    border-right: none;
}

.trust-icon {

    width: 43px;

    height: 43px;

    border-radius: 50%;

    background: var(--cream);

    color: var(--brown-light);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 17px;
}

.trust-item strong {

    display: block;

    font-size: 12px;

    margin-bottom: 3px;
}

.trust-item span {

    color: var(--muted);

    font-size: 10px;
}


.section {

    padding: 85px 5%;
}

.section-header {

    text-align: center;

    max-width: 650px;

    margin: 0 auto 45px;
}

.section-label {

    color: var(--gold);

    font-size: 10px;

    font-weight: 700;

    letter-spacing: 3px;

    text-transform: uppercase;

    margin-bottom: 8px;
}

.section-title {

    font-family: 'Playfair Display', serif;

    font-size: 36px;

    color: var(--black);

    margin-bottom: 10px;
}

.section-title span {

    color: var(--brown-light);
}

.section-subtitle {

    color: var(--muted);

    font-size: 13px;

    line-height: 1.7;
}


.categories-section {

    background: var(--cream);
}

.category-card {

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 8px;

    overflow: hidden;

    height: 100%;

    transition: .3s;
}

.category-card:hover {

    transform: translateY(-7px);

    box-shadow: 0 15px 35px rgba(63,39,23,.11);

    border-color: var(--gold);
}

.category-image {

    height: 180px;

    background: #f3eee7;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 18px;
}

.category-image img {

    width: 100%;

    height: 100%;

    object-fit: contain;

    transition: .35s;
}

.category-card:hover .category-image img {

    transform: scale(1.08);
}

.category-content {

    padding: 17px 18px 19px;
}

.category-content h3 {

    font-family: 'Playfair Display', serif;

    font-size: 20px;

    margin-bottom: 7px;

    color: var(--black);
}

.category-content p {

    color: var(--muted);

    font-size: 11px;

    line-height: 1.6;

    min-height: 35px;

    margin-bottom: 13px;
}

.category-link {

    color: var(--brown);

    font-size: 11px;

    font-weight: 700;
}

.category-link:hover {

    color: var(--gold);
}


.products-section {

    background: #fff;
}

.product-card {

    height: 100%;

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 8px;

    overflow: hidden;

    transition: .3s;

    position: relative;
}

.product-card:hover {

    transform: translateY(-6px);

    box-shadow: 0 15px 35px rgba(0,0,0,.08);

    border-color: #d7c29b;
}

.product-image-area {

    height: 270px;

    background: #f6f3ef;

    position: relative;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;
}

.product-image {

    width: 100%;

    height: 100%;

    object-fit: contain;

    padding: 20px;

    transition: .4s;
}

.product-card:hover .product-image {

    transform: scale(1.06);
}

.product-label {

    position: absolute;

    top: 13px;

    left: 13px;

    background: var(--brown);

    color: #fff;

    padding: 5px 9px;

    border-radius: 3px;

    font-size: 8px;

    font-weight: 700;

    letter-spacing: .5px;

    z-index: 2;
}

.product-label.sale {

    background: #9b3c28;
}

.product-label.new {

    background: #47704f;
}

.product-heart {

    position: absolute;

    top: 12px;

    right: 12px;

    width: 34px;

    height: 34px;

    border-radius: 50%;

    background: rgba(255,255,255,.95);

    border: 1px solid var(--border);

    display: flex;

    align-items: center;

    justify-content: center;

    color: #555;

    z-index: 3;

    transition: .25s;
}

.product-heart:hover {

    color: #9b3c28;

    border-color: #d6aaa0;
}

.product-body {

    padding: 17px;
}

.product-category {

    color: var(--gold);

    font-size: 9px;

    font-weight: 700;

    letter-spacing: 1px;

    text-transform: uppercase;

    margin-bottom: 5px;
}

.product-name {

    font-size: 15px;

    font-weight: 700;

    color: var(--black);

    margin-bottom: 7px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}

.rating {

    display: flex;

    align-items: center;

    gap: 2px;

    margin-bottom: 10px;
}

.rating i {

    color: var(--gold);

    font-size: 10px;
}

.rating span {

    color: #999;

    font-size: 10px;

    margin-left: 4px;
}

.product-price {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 12px;
}

.current-price {

    font-size: 16px;

    font-weight: 700;

    color: var(--brown);
}

.old-price {

    color: #aaa;

    text-decoration: line-through;

    font-size: 11px;
}


.variant-area {

    border-top: 1px solid #eee7df;

    padding-top: 11px;

    margin-bottom: 13px;
}

.variant-row {

    display: flex;

    align-items: center;

    gap: 6px;

    margin-bottom: 7px;
}

.variant-title {

    font-size: 9px;

    font-weight: 700;

    color: #555;

    min-width: 38px;
}

.variant-list {

    display: flex;

    gap: 4px;

    flex-wrap: wrap;
}

.size-tag,

.color-tag {

    border: 1px solid #ddd4ca;

    background: #fff;

    color: #555;

    padding: 3px 6px;

    border-radius: 3px;

    font-size: 8px;
}

.color-tag {

    padding: 3px 7px;

    text-transform: capitalize;
}


/* PRODUCT BUTTON */

.product-actions {

    display: flex;

    gap: 7px;
}

.view-product {

    flex: 1;

    text-align: center;

    border: 1px solid var(--brown);

    color: var(--brown);

    padding: 9px 7px;

    border-radius: 4px;

    font-size: 10px;

    font-weight: 700;

    transition: .25s;
}

.view-product:hover {

    background: var(--brown);

    color: #fff;
}

.cart-product-btn {

    width: 42px;

    border: none;

    background: var(--brown);

    color: #fff;

    border-radius: 4px;

    transition: .25s;
}

.cart-product-btn:hover {

    background: var(--gold);

}


.view-all {

    text-align: center;

    margin-top: 38px;
}

.view-all a {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    border: 1px solid var(--brown);

    color: var(--brown);

    padding: 11px 24px;

    border-radius: 4px;

    font-size: 11px;

    font-weight: 700;

    transition: .25s;
}

.view-all a:hover {

    background: var(--brown);

    color: #fff;
}


.about-section {

    background: var(--cream);
}

.about-box {

    max-width: 1100px;

    margin: auto;

    display: grid;

    grid-template-columns: 1fr 1fr;

    background: #fff;

    border-radius: 10px;

    overflow: hidden;

    border: 1px solid var(--border);
}

.about-image {
    width: 50%;
    height: 100%;
    overflow: hidden;
  background:

        linear-gradient(
            rgba(40,24,15,.25),
            rgba(40,24,15,.25)
        ),

        url("images/hero-shoes.jpg");

    background-size: cover;

    background-position: center;
}

.about-image img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    object-position: center;
}

  

.about-content {

    padding: 55px;
}

.about-content h2 {

    font-family: 'Playfair Display', serif;

    font-size: 34px;

    margin-bottom: 15px;
}

.about-content h2 span {

    color: var(--brown-light);
}

.about-content p {

    color: var(--muted);

    font-size: 13px;

    line-height: 1.9;

    margin-bottom: 22px;
}

.about-points {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 12px;
}

.about-point {

    display: flex;

    gap: 9px;

    align-items: center;

    font-size: 11px;

    font-weight: 600;
}

.about-point i {

    color: var(--gold);
}

.footer {

    background: var(--black);

    color: #fff;

    padding: 60px 6% 20px;
}

.footer-inner {

    max-width: 1300px;

    margin: auto;

    display: grid;

    grid-template-columns: 2fr 1fr 1fr 1.3fr;

    gap: 45px;
}

.footer-brand {

    font-family: 'Playfair Display', serif;

    font-size: 27px;

    font-weight: 700;

    margin-bottom: 12px;
}

.footer-brand span {

    color: var(--gold-light);
}

.footer-description {

    color: #aaa;

    font-size: 11px;

    line-height: 1.8;

    max-width: 320px;
}

.footer-column h4 {

    color: var(--gold-light);

    font-size: 12px;

    margin-bottom: 17px;
}

.footer-column a {

    display: block;

    color: #aaa;

    font-size: 11px;

    margin-bottom: 10px;

    transition: .2s;
}

.footer-column a:hover {

    color: #fff;
}

.footer-bottom {

    max-width: 1300px;

    margin: 35px auto 0;

    padding-top: 17px;

    border-top: 1px solid #302b27;

    text-align: center;

    color: #777;

    font-size: 10px;
}


@media(max-width:1100px) {

    .nav-links {

        gap: 18px;
    }

    .nav-links a {

        font-size: 12px;
    }

    .trust-item {

        padding: 18px;
    }

}

@media(max-width:991px) {

    .topbar-right {

        display: none;
    }

    .nav-inner {

        min-height: 70px;
    }

    .nav-links {

        display: none;
    }

    .logo {

        min-width: auto;
    }

    .hero {

        min-height: 520px;
    }

    .trust-strip {

        grid-template-columns: 1fr 1fr;
    }

    .trust-item:nth-child(2) {

        border-right: none;
    }

    .trust-item:nth-child(-n+2) {

        border-bottom: 1px solid var(--border);
    }

    .about-box {

        grid-template-columns: 1fr;
    }

    .about-image {

        min-height: 300px;
    }

    .footer-inner {

        grid-template-columns: 1fr 1fr;
    }

}

@media(max-width:600px) {

    .topbar {

        text-align: center;
    }

    .topbar-left {

        width: 100%;
    }

    .logo-name {

        font-size: 22px;
    }

    .logo-icon {

        width: 37px;

        height: 37px;
    }

    .nav-actions {

        gap: 12px;
    }

    .login-btn {

        display: none;
    }

    .hero {

        min-height: 500px;

        background-position: 65% center;
    }

    .hero h1 {

        font-size: 40px;
    }

    .hero-text {

        font-size: 13px;
    }

    .trust-strip {

        grid-template-columns: 1fr;

        width: 92%;
    }

    .trust-item {

        border-right: none !important;

        border-bottom: 1px solid var(--border);
    }

    .trust-item:last-child {

        border-bottom: none;
    }

    .section {

        padding: 60px 5%;
    }

    .section-title {

        font-size: 30px;
    }

    .category-image {

        height: 150px;
    }

    .product-image-area {

        height: 240px;
    }

    .about-content {

        padding: 35px 25px;
    }

    .about-points {

        grid-template-columns: 1fr;
    }

    .footer-inner {

        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>


<div class="topbar">

    <div class="topbar-inner">

        <div class="topbar-left">

            <i class="fa-solid fa-truck-fast"></i>

            Free shipping on orders above Rs. 3,000

        </div>

        <div class="topbar-right">

            <span>
                Premium Leather Shoes
            </span>

            <span>|</span>

            <span>
                Kathmandu, Nepal
            </span>

        </div>

    </div>

</div>


<nav class="navbar-custom">

    <div class="nav-inner">


        <!-- LOGO -->

        <a href="index.php" class="logo">

            <div class="logo-icon">

                <i class="fa-solid fa-shoe-prints"></i>

            </div>

            <div>

                <div class="logo-name">

                    Leather<span>Craft</span>

                </div>

                <div class="logo-small">

                    STEP INTO QUALITY

                </div>

            </div>

        </a>


        <!-- NAVIGATION -->

        <div class="nav-links">

            <a
                href="index.php"
                class="active"
            >
                Home
            </a>

            <a href="products.php">
                Shop
            </a>

            <a href="#categories">
                Categories
            </a>

            <a href="#about">
                About
            </a>

            <a href="#footer">
                Contact
            </a>

        </div>


      

        <div class="nav-actions">

            <a
                href="products.php"
                class="nav-icon"
                title="Search"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

            </a>


            <?php if ($is_logged_in): ?>

                <a
                    href="profile.php"
                    class="nav-icon"
                    title="My Account"
                >

                    <i class="fa-regular fa-user"></i>

                </a>


                <a
                    href="notifications.php"
                    class="nav-icon"
                    title="Notifications"
                >

                    <i class="fa-regular fa-bell"></i>

                    <?php if ($notification_count > 0): ?>

                        <span class="nav-badge">

                            <?= $notification_count ?>

                        </span>

                    <?php endif; ?>

                </a>

            <?php endif; ?>


            <a
                href="cart.php"
                class="nav-icon"
                title="Shopping Cart"
            >

                <i class="fa-solid fa-bag-shopping"></i>

                <?php if ($cart_count > 0): ?>

                    <span class="nav-badge">

                        <?= $cart_count ?>

                    </span>

                <?php endif; ?>

            </a>


            <?php if ($is_logged_in): ?>

                <a
                    href="logout.php"
                    class="login-btn"
                >
                    Logout
                </a>

            <?php else: ?>

                <a
                    href="login.php"
                    class="login-btn"
                >
                    Login / Register
                </a>

            <?php endif; ?>

        </div>

    </div>

</nav>


<section class="hero">

    <div class="hero-inner">

        <div class="hero-content">

            <div class="hero-label">

                PREMIUM LEATHER COLLECTION

            </div>

            <h1>

                Walk With
                <span>Confidence.</span>

                <br>

                Crafted For You.

            </h1>

            <p class="hero-text">

                Discover carefully selected leather shoes designed
                for timeless style, comfort and everyday confidence.

            </p>

            <div class="hero-buttons">

                <a
                    href="products.php"
                    class="btn-primary-custom"
                >

                    SHOP COLLECTION

                    <i class="fa-solid fa-arrow-right ms-2"></i>

                </a>

                <a
                    href="#categories"
                    class="btn-outline-custom"
                >

                    EXPLORE CATEGORIES

                </a>

            </div>

        </div>

    </div>

</section>


<div class="trust-strip">

    <div class="trust-item">

        <div class="trust-icon">

            <i class="fa-solid fa-gem"></i>

        </div>

        <div>

            <strong>
                Premium Leather
            </strong>

            <span>
                Quality footwear
            </span>

        </div>

    </div>


    <div class="trust-item">

        <div class="trust-icon">

            <i class="fa-solid fa-truck-fast"></i>

        </div>

        <div>

            <strong>
                Fast Delivery
            </strong>

            <span>
                To your doorstep
            </span>

        </div>

    </div>


    <div class="trust-item">

        <div class="trust-icon">

            <i class="fa-solid fa-shield-halved"></i>

        </div>

        <div>

            <strong>
                Secure Checkout
            </strong>

            <span>
                Safe ordering process
            </span>

        </div>

    </div>


    <div class="trust-item">

        <div class="trust-icon">

            <i class="fa-solid fa-headset"></i>

        </div>

        <div>

            <strong>
                Customer Support
            </strong>

            <span>
                We're here to help
            </span>

        </div>

    </div>

</div>


<section
    class="section categories-section"
    id="categories"
>

    <div class="section-header">

        <div class="section-label">
            FIND YOUR STYLE
        </div>

        <h2 class="section-title">

            Shop By
            <span>Category</span>

        </h2>

        <p class="section-subtitle">

            Explore our collection of leather footwear
            designed for different occasions and styles.

        </p>

    </div>


    <div class="container-fluid">

        <div class="row g-4">

            <?php if (!empty($categories)): ?>

                <?php foreach ($categories as $category): ?>

                    <?php

                    $categoryName =
                        $category['name'] ?? '';

                    $categoryKey =
                        strtolower(
                            trim($categoryName)
                        );

                    $description =
                        $categoryDescriptions[$categoryKey]
                        ??
                        'Premium leather shoes for men.';

                    ?>

                    <div class="col-xl-2 col-lg-4 col-md-4 col-6">

                        <div class="category-card">

                            <div class="category-image">

                                <img
                                    src="<?= htmlspecialchars(
                                        getCategoryImage(
                                            $categoryName
                                        )
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $categoryName
                                    ) ?>"
                                    onerror="
                                        this.onerror=null;
                                        this.src='images/default-shoe.jpg';
                                    "
                                >

                            </div>

                            <div class="category-content">

                                <h3>

                                    <?= htmlspecialchars(
                                        $categoryName
                                    ) ?>

                                </h3>

                                <p>

                                    <?= htmlspecialchars(
                                        $description
                                    ) ?>

                                </p>

                                <a
                                    href="products.php?category=<?= urlencode(
                                        $categoryName
                                    ) ?>"
                                    class="category-link"
                                >

                                    Explore Collection

                                    <i class="fa-solid fa-arrow-right ms-1"></i>

                                </a>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="col-12 text-center">

                    <p class="text-muted">

                        No categories available.

                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>


<section
    class="section products-section"
>

    <div class="section-header">

        <div class="section-label">
            OUR COLLECTION
        </div>

        <h2 class="section-title">

            Featured
            <span>Products</span>

        </h2>

        <p class="section-subtitle">

            Discover some of our latest and most popular
            leather shoes.

        </p>

    </div>


    <div class="container-fluid">

        <div class="row g-4">

            <?php if (!empty($products)): ?>

                <?php foreach (
                    $products as $index => $product
                ): ?>

                    <?php

                    $average =
                        (float)(
                            $product['average_rating'] ?? 0
                        );

                    $reviews =
                        (int)(
                            $product['review_count'] ?? 0
                        );

                    $rounded =
                        (int)round($average);

                    $productImage =
                        getProductImage(
                            $product['image'] ?? ''
                        );

                    $sizes =
                        array_filter(
                            array_map(
                                'trim',
                                explode(
                                    ',',
                                    $product['size'] ?? ''
                                )
                            )
                        );

                    $colors =
                        array_filter(
                            array_map(
                                'trim',
                                explode(
                                    ',',
                                    $product['color'] ?? ''
                                )
                            )
                        );

                    ?>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">

                        <div class="product-card">


                            <!-- IMAGE -->

                            <div class="product-image-area">


                                <?php if ($index === 0): ?>

                                    <span class="product-label new">

                                        NEW

                                    </span>

                                <?php elseif ($index === 1): ?>

                                    <span class="product-label">

                                        POPULAR

                                    </span>

                                <?php elseif ($index === 4): ?>

                                    <span class="product-label sale">

                                        SALE

                                    </span>

                                <?php endif; ?>


                                <a
                                    href="product_detail.php?id=<?= (int)$product['id'] ?>"
                                    class="product-heart"
                                    title="View Product"
                                >

                                    <i class="fa-regular fa-heart"></i>

                                </a>


                                <img
                                    src="<?= htmlspecialchars(
                                        $productImage
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $product['name'] ?? 'Leather Shoe'
                                    ) ?>"
                                    class="product-image"
                                    onerror="
                                        this.onerror=null;
                                        this.src='images/default-shoe.jpg';
                                    "
                                >

                            </div>


               

                            <div class="product-body">


                                <div class="product-category">

                                    <?= htmlspecialchars(
                                        $product['category_name']
                                        ??
                                        'Leather Shoes'
                                    ) ?>

                                </div>


                                <div class="product-name">

                                    <?= htmlspecialchars(
                                        $product['name'] ?? ''
                                    ) ?>

                                </div>


                               

                                <div class="rating">

                                    <?php for (
                                        $i = 1;
                                        $i <= 5;
                                        $i++
                                    ): ?>

                                        <?php if (
                                            $i <= $rounded
                                        ): ?>

                                            <i
                                                class="fa-solid fa-star"
                                            ></i>

                                        <?php else: ?>

                                            <i
                                                class="fa-regular fa-star"
                                            ></i>

                                        <?php endif; ?>

                                    <?php endfor; ?>

                                    <span>

                                        (<?= $reviews ?>)

                                    </span>

                                </div>


                                <!-- PRICE -->

                                <div class="product-price">

                                    <span class="current-price">

                                        Rs.
                                        <?= number_format(
                                            (float)$product['price'],
                                            0
                                        ) ?>

                                    </span>

                                    <?php if (
                                        !empty(
                                            $product['discount_price']
                                        )
                                        &&
                                        (float)$product['discount_price']
                                        <
                                        (float)$product['price']
                                    ): ?>

                                        <span class="old-price">

                                            Rs.
                                            <?= number_format(
                                                (float)$product['price'],
                                                0
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <!-- SIZE + COLOR -->

                                <div class="variant-area">


                                    <?php if (!empty($sizes)): ?>

                                        <div class="variant-row">

                                            <span class="variant-title">

                                                SIZE

                                            </span>

                                            <div class="variant-list">

                                                <?php foreach (
                                                    array_slice(
                                                        $sizes,
                                                        0,
                                                        6
                                                    )
                                                    as $size
                                                ): ?>

                                                    <span class="size-tag">

                                                        <?= htmlspecialchars(
                                                            $size
                                                        ) ?>

                                                    </span>

                                                <?php endforeach; ?>

                                            </div>

                                        </div>

                                    <?php endif; ?>


                                    <?php if (!empty($colors)): ?>

                                        <div class="variant-row">

                                            <span class="variant-title">

                                                COLOR

                                            </span>

                                            <div class="variant-list">

                                                <?php foreach (
                                                    array_slice(
                                                        $colors,
                                                        0,
                                                        3
                                                    )
                                                    as $color
                                                ): ?>

                                                    <span class="color-tag">

                                                        <?= htmlspecialchars(
                                                            $color
                                                        ) ?>

                                                    </span>

                                                <?php endforeach; ?>

                                            </div>

                                        </div>

                                    <?php endif; ?>


                                </div>


  
                                <div class="product-actions">

                                    <a
                                        href="product_details.php?id=<?= (int)$product['id'] ?>"
                                        class="view-product"
                                    >

                                        View Details

                                    </a>

                                    <a
                                        href="product_details.php?id=<?= (int)$product['id'] ?>"
                                        class="cart-product-btn"
                                        title="Choose Size & Color"
                                    >

                                        <i class="fa-solid fa-bag-shopping"></i>

                                    </a>

                                </div>


                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="col-12 text-center">

                    <p class="text-muted">

                        No products available yet.

                    </p>

                </div>

            <?php endif; ?>

        </div>


        <div class="view-all">

            <a href="products.php">

                View All Products

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>

    </div>

</section>

<section
    class="section about-section"
    id="about"
>

    <div class="about-box">


       <div class="about-image">
    <img src="images/about-shoes.jpg" alt="Premium Leather Shoes">
</div>


        <div class="about-content">

            <div class="section-label">

                ABOUT LEATHERCRAFT

            </div>

            <h2>

                Made For
                <span>Your Journey.</span>

            </h2>

            <p>

                LeatherCraft is an online leather shoe shopping
                platform designed to make finding the right pair
                simple, convenient and enjoyable.

            </p>

            <p>

                Browse different categories, explore product
                details, select your preferred size and color,
                add products to your cart and complete your order
                through the online checkout process.

            </p>


            <div class="about-points">

                <div class="about-point">

                    <i class="fa-solid fa-check"></i>

                    Premium Leather

                </div>

                <div class="about-point">

                    <i class="fa-solid fa-check"></i>

                    Multiple Sizes

                </div>

                <div class="about-point">

                    <i class="fa-solid fa-check"></i>

                    Color Selection

                </div>

                <div class="about-point">

                    <i class="fa-solid fa-check"></i>

                    Secure Ordering

                </div>

            </div>

        </div>

    </div>

</section>


<footer
    class="footer"
    id="footer"
>

    <div class="footer-inner">


        <!-- BRAND -->

        <div>

            <div class="footer-brand">

                Leather<span>Craft</span>

            </div>

            <p class="footer-description">

                Premium men's leather shoes crafted for
                timeless style, comfort and durability.

            </p>

        </div>




        <div class="footer-column">

            <h4>
                QUICK LINKS
            </h4>

            <a href="index.php">
                Home
            </a>

            <a href="products.php">
                Shop
            </a>

            <a href="#categories">
                Categories
            </a>

            <a href="#about">
                About Us
            </a>

        </div>



        <div class="footer-column">

            <h4>
                CUSTOMER
            </h4>

            <a href="cart.php">
                Shopping Cart
            </a>

            <?php if ($is_logged_in): ?>

                <a href="orders.php">
                    My Orders
                </a>

                <a href="profile.php">
                    My Account
                </a>

                <a href="notifications.php">
                    Notifications
                </a>

            <?php else: ?>

                <a href="login.php">
                    Login / Register
                </a>

            <?php endif; ?>

        </div>



        <div class="footer-column">

            <h4>
                CONTACT
            </h4>

            <a href="#">

                <i class="fa-solid fa-location-dot me-2"></i>

                Kathmandu, Nepal

            </a>

            <a href="#">

                <i class="fa-solid fa-phone me-2"></i>

                +977 98XXXXXXXX

            </a>

            <a href="#">

                <i class="fa-solid fa-envelope me-2"></i>

                info@leathercraft.com

            </a>

        </div>


    </div>


    <div class="footer-bottom">

        © <?= date('Y') ?>
        LeatherCraft.
        All Rights Reserved.

    </div>

</footer>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>