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
            WHERE user_id = ?
            AND is_read = 0
        ");

        $stmt->execute([
            (int)$_SESSION['user_id']
        ]);

        $notification_count = (int)$stmt->fetchColumn();

    } catch (PDOException $e) {

        $notification_count = 0;

    }

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

$selected_category = trim(
    $_GET['category'] ?? ''
);

$search = trim(
    $_GET['search'] ?? ''
);

$products = [];

try {

    $sql = "
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

        WHERE 1 = 1
    ";

    $params = [];

    if ($selected_category !== '') {

        $sql .= "
            AND c.name = :category
        ";

        $params[':category'] =
            $selected_category;

    }

    if ($search !== '') {

        $sql .= "
            AND (
                p.name LIKE :search
                OR p.description LIKE :search
                OR c.name LIKE :search
            )
        ";

        $params[':search'] =
            '%' . $search . '%';

    }

    $sql .= "
        GROUP BY p.id
        ORDER BY p.id DESC
    ";


    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $products =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    $products = [];

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

        return str_replace(
            '\\',
            '/',
            $image
        );

    }

    return 'images/' . $image;

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Shop Men's Leather Shoes | LeatherCraft
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
.shop-hero {

    background:

        linear-gradient(
            90deg,
            rgba(28,17,11,.88),
            rgba(28,17,11,.63),
            rgba(28,17,11,.25)
        ),

        url("images/shop-banner.jpg");

    background-size: cover;

    background-position: center;

    min-height: 330px;

    display: flex;

    align-items: center;

}

.shop-hero-inner {

    width: 88%;

    max-width: 1400px;

    margin: auto;

}

.shop-hero-content {

    max-width: 650px;

    color: #fff;

}

.shop-label {

    color: var(--gold-light);

    font-size: 11px;

    font-weight: 700;

    letter-spacing: 3px;

    margin-bottom: 12px;

}

.shop-hero h1 {

    font-family: 'Playfair Display', serif;

    font-size: clamp(42px, 5vw, 58px);

    line-height: 1.1;

    margin-bottom: 13px;

}

.shop-hero h1 span {

    color: var(--gold-light);

}

.shop-hero p {

    color: #e8e0d8;

    font-size: 14px;

    line-height: 1.7;

    max-width: 540px;

}
.shop-section {

    padding: 65px 5% 90px;

    background: #fff;

}

.filter-box {

    max-width: 1300px;

    margin: 0 auto 45px;

    background: var(--cream);

    border: 1px solid var(--border);

    border-radius: 9px;

    padding: 24px;

}

.search-wrapper {

    position: relative;

}

.search-icon {

    position: absolute;

    left: 17px;

    top: 50%;

    transform: translateY(-50%);

    color: #93897f;

    font-size: 14px;

    pointer-events: none;

}

.search-input {

    width: 100%;

    height: 48px;

    border: 1px solid #ddd4ca;

    background: #fff;

    border-radius: 5px;

    padding: 0 16px 0 45px;

    color: var(--black);

    font-size: 12px;

    outline: none;

}

.search-input:focus {

    border-color: var(--gold);

    box-shadow: 0 0 0 3px rgba(197,155,69,.10);

}

.search-input::placeholder {

    color: #9a928b;

}

.search-btn {

    height: 48px;

    width: 100%;

    background: var(--brown);

    color: #fff;

    border: 1px solid var(--brown);

    border-radius: 5px;

    font-size: 11px;

    font-weight: 700;

    transition: .25s;

}

.search-btn:hover {

    background: var(--gold);

    border-color: var(--gold);

    color: #fff;

}

.filter-heading {

    margin-top: 23px;

    margin-bottom: 12px;

    color: var(--black);

    font-size: 12px;

    font-weight: 700;

}

.filter-heading i {

    color: var(--gold);

    margin-right: 7px;

}

.category-buttons {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

}

.category-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    border: 1px solid #dcd3c9;

    background: #fff;

    color: #5d554e;

    padding: 8px 15px;

    border-radius: 4px;

    font-size: 10px;

    font-weight: 600;

    transition: .25s;

}

.category-btn:hover {

    color: var(--brown);

    border-color: var(--gold);

}

.category-btn.active {

    background: var(--brown);

    color: #fff;

    border-color: var(--brown);

}

.results-header {

    max-width: 1300px;

    margin: 0 auto 25px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

}

.results-title {

    font-family: 'Playfair Display', serif;

    font-size: 25px;

    color: var(--black);

}

.results-title span {

    color: var(--brown-light);

}

.results-info {

    color: var(--muted);

    font-size: 11px;

}

.selected-result {

    color: var(--brown);

    font-weight: 700;

}

.products-container {

    max-width: 1300px;

    margin: auto;

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

    transform: translateY(-7px);

    box-shadow:
        0 15px 35px rgba(0,0,0,.08);

    border-color: #d7c29b;
}

.product-image-area {

    height: 285px;

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

    z-index: 3;

    padding: 5px 9px;

    border-radius: 3px;

    background: var(--brown);

    color: #fff;

    font-size: 8px;

    font-weight: 700;

    letter-spacing: 1px;

}

.product-label.out {

    background: #8c3d30;

}
.product-heart {

    position: absolute;

    top: 12px;

    right: 12px;

    width: 35px;

    height: 35px;

    border-radius: 50%;

    background: rgba(255,255,255,.95);

    border: 1px solid var(--border);

    display: flex;

    align-items: center;

    justify-content: center;

    color: #5c5752;

    z-index: 3;

    transition: .25s;

}

.product-heart:hover {

    color: #9b3c28;

    border-color: #d7b7ad;

}
.product-body {

    padding: 18px;

}

.product-category {

    color: var(--gold);

    font-size: 9px;

    font-weight: 700;

    letter-spacing: 1.2px;

    text-transform: uppercase;

    margin-bottom: 5px;

}

.product-name {

    color: var(--black);

    font-size: 16px;

    font-weight: 700;

    margin-bottom: 7px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;

}

.product-description {

    color: var(--muted);

    font-size: 10px;

    line-height: 1.6;

    min-height: 32px;

    margin-bottom: 9px;

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

.rating-count {

    color: #999;

    font-size: 10px;

    margin-left: 5px;

}

.price-row {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 12px;

}

.current-price {

    color: var(--brown);

    font-size: 17px;

    font-weight: 700;

}

.old-price {

    color: #aaa;

    font-size: 10px;

    text-decoration: line-through;

}
.stock-status {

    margin-bottom: 12px;

    font-size: 9px;

    font-weight: 600;

}

.stock-status.available {

    color: #55714d;

}

.stock-status.low {

    color: #a16a27;

}

.stock-status.out {

    color: #9b3c30;

}

.stock-status i {

    margin-right: 4px;

}

.variant-box {

    border-top: 1px solid #eee7df;

    padding-top: 11px;

    margin-bottom: 13px;

}

.variant-row {

    display: flex;

    align-items: flex-start;

    gap: 6px;

    margin-bottom: 7px;

}

.variant-row:last-child {

    margin-bottom: 0;

}

.variant-title {

    min-width: 38px;

    color: #625b54;

    font-size: 8px;

    font-weight: 700;

    padding-top: 3px;

}

.variant-list {

    display: flex;

    flex-wrap: wrap;

    gap: 4px;

}

.size-tag,

.color-tag {

    border: 1px solid #ddd4ca;

    background: #fff;

    color: #5e5750;

    border-radius: 3px;

    padding: 3px 6px;

    font-size: 8px;

}

.color-tag {

    text-transform: capitalize;

}

.product-actions {

    display: flex;

    gap: 7px;

}

.view-btn {

    flex: 1;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    border: 1px solid var(--brown);

    color: var(--brown);

    background: #fff;

    padding: 9px 7px;

    border-radius: 4px;

    font-size: 10px;

    font-weight: 700;

    transition: .25s;

}

.view-btn:hover {

    background: var(--brown);

    color: #fff;

}

.cart-btn {

    width: 42px;

    border: none;

    background: var(--brown);

    color: #fff;

    border-radius: 4px;

    display: flex;

    align-items: center;

    justify-content: center;

    transition: .25s;

}

.cart-btn:hover {

    background: var(--gold);

}
.out-stock-btn {

    width: 100%;

    padding: 9px;

    background: #eeeae5;

    color: #918980;

    border: 1px solid #ddd6ce;

    border-radius: 4px;

    font-size: 10px;

    font-weight: 700;

    text-align: center;

}

.empty-box {

    max-width: 800px;

    margin: 30px auto;

    padding: 75px 25px;

    text-align: center;

    background: var(--cream);

    border: 1px solid var(--border);

    border-radius: 9px;

}

.empty-icon {

    width: 70px;

    height: 70px;

    margin: 0 auto 18px;

    border-radius: 50%;

    background: #fff;

    color: var(--gold);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 27px;

}

.empty-box h3 {

    font-family: 'Playfair Display', serif;

    color: var(--black);

    font-size: 25px;

    margin-bottom: 8px;

}

.empty-box p {

    color: var(--muted);

    font-size: 12px;

    margin-bottom: 20px;

}

.reset-btn {

    display: inline-block;

    background: var(--brown);

    color: #fff;

    padding: 10px 20px;

    border-radius: 4px;

    font-size: 10px;

    font-weight: 700;

    transition: .25s;

}

.reset-btn:hover {

    background: var(--gold);

    color: #fff;

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

    .shop-hero {

        min-height: 300px;

    }

    .footer-inner {

        grid-template-columns: 1fr 1fr;

    }

}


@media(max-width:767px) {

    .shop-section {

        padding: 50px 5% 70px;

    }

    .results-header {

        flex-direction: column;

        align-items: flex-start;

    }

    .product-image-area {

        height: 260px;

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

    .shop-hero {

        min-height: 360px;

    }

    .shop-hero h1 {

        font-size: 40px;

    }

    .shop-hero p {

        font-size: 12px;

    }

    .filter-box {

        padding: 17px;

    }

    .category-buttons {

        gap: 6px;

    }

    .category-btn {

        padding: 7px 10px;

    }

    .product-image-area {

        height: 235px;

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

            <a href="index.php">

                Home

            </a>

            <a
                href="products.php"
                class="active"
            >

                Shop

            </a>

            <a href="index.php#categories">

                Categories

            </a>

            <a href="index.php#about">

                About

            </a>

            <a href="index.php#footer">

                Contact

            </a>

        </div>


        <!-- ACTIONS -->

        <div class="nav-actions">


            
            <a
                href="#shop"
                class="nav-icon"
                title="Search"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

            </a>


            <?php if ($is_logged_in): ?>


                <!-- PROFILE -->

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



<section class="shop-hero">

    <div class="shop-hero-inner">

        <div class="shop-hero-content">

            <div class="shop-label">

                LEATHERCRAFT COLLECTION

            </div>

            <h1>

                Find Your
                <span>Perfect Pair.</span>

            </h1>

            <p>

                Explore our complete collection of premium
                men's leather shoes. Choose your style,
                size and color and find the pair made for you.

            </p>

        </div>

    </div>

</section>

<section
    class="shop-section"
    id="shop"
>
    <div class="filter-box">


        <!-- SEARCH -->

        <form
            action="products.php"
            method="GET"
        >

            <?php if ($selected_category !== ''): ?>

                <input
                    type="hidden"
                    name="category"
                    value="<?= htmlspecialchars(
                        $selected_category
                    ) ?>"
                >

            <?php endif; ?>


            <div class="row g-2">

                <div class="col-md-10">

                    <div class="search-wrapper">

                        <i
                            class="fa-solid fa-magnifying-glass search-icon"
                        ></i>

                        <input
                            type="text"
                            name="search"
                            class="search-input"
                            placeholder="Search by shoe name, category or description..."
                            value="<?= htmlspecialchars(
                                $search
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="col-md-2">

                    <button
                        type="submit"
                        class="search-btn"
                    >

                        <i
                            class="fa-solid fa-magnifying-glass me-1"
                        ></i>

                        Search

                    </button>

                </div>

            </div>

        </form>


        <!-- CATEGORY -->

        <div class="filter-heading">

            <i class="fa-solid fa-sliders"></i>

            Shop By Category

        </div>


        <div class="category-buttons">

            <a
                href="products.php<?= $search !== ''
                    ? '?search=' . urlencode($search)
                    : '' ?>"
                class="category-btn
                <?= $selected_category === ''
                    ? 'active'
                    : '' ?>"
            >

                <i class="fa-solid fa-layer-group me-1"></i>

                All Shoes

            </a>


            <?php foreach (
                $categories as $category
            ): ?>

                <?php

                $categoryName =
                    $category['name'] ?? '';

                $categoryUrl =
                    'products.php?category=' .
                    urlencode($categoryName);

                if ($search !== '') {

                    $categoryUrl .=
                        '&search=' .
                        urlencode($search);

                }

                ?>

                <a
                    href="<?= htmlspecialchars(
                        $categoryUrl
                    ) ?>"
                    class="category-btn
                    <?= strcasecmp(
                        $selected_category,
                        $categoryName
                    ) === 0
                        ? 'active'
                        : '' ?>"
                >

                    <?= htmlspecialchars(
                        $categoryName
                    ) ?>

                </a>

            <?php endforeach; ?>


        </div>

    </div>


    <div class="results-header">


        <div>

            <div class="results-title">

                <?php if ($selected_category !== ''): ?>

                    <?= htmlspecialchars(
                        $selected_category
                    ) ?>

                    <span>
                        Collection
                    </span>

                <?php elseif ($search !== ''): ?>

                    Search
                    <span>Results</span>

                <?php else: ?>

                    All
                    <span>Shoes</span>

                <?php endif; ?>

            </div>


            <?php if ($search !== ''): ?>

                <div class="results-info">

                    Searching for:

                    <span class="selected-result">

                        "<?= htmlspecialchars(
                            $search
                        ) ?>"

                    </span>

                </div>

            <?php endif; ?>

        </div>


        <div class="results-info">

            <?= count($products) ?>

            product<?= count($products) !== 1
                ? 's'
                : '' ?>

        </div>


    </div>


    <div class="products-container">


        <?php if (!empty($products)): ?>


            <div class="row g-4">


                <?php foreach (
                    $products as $index => $product
                ): ?>


                    <?php


                    $productId =
                        (int)$product['id'];

                    $productName =
                        $product['name'] ?? '';

                    $categoryName =
                        $product['category_name']
                        ??
                        'Leather Shoes';

                    $description =
                        $product['description']
                        ??
                        'Premium leather shoes designed for comfort and style.';



                    $price =
                        (float)(
                            $product['price']
                            ?? 0
                        );

                    $image =
                        getProductImage(
                            $product['image']
                            ?? ''
                        );

                    $stock =
                        (int)(
                            $product['stock']
                            ?? 0
                        );

                    $averageRating =
                        (float)(
                            $product['average_rating']
                            ?? 0
                        );

                    $reviewCount =
                        (int)(
                            $product['review_count']
                            ?? 0
                        );

                    $roundedRating =
                        (int)round(
                            $averageRating
                        );


                    $sizes = [];

                    if (
                        isset($product['size'])
                        &&
                        trim(
                            (string)$product['size']
                        ) !== ''
                    ) {

                        $sizes =
                            array_filter(
                                array_map(
                                    'trim',
                                    explode(
                                        ',',
                                        $product['size']
                                    )
                                )
                            );

                    }


                    $colors = [];

                    if (
                        isset($product['color'])
                        &&
                        trim(
                            (string)$product['color']
                        ) !== ''
                    ) {

                        $colors =
                            array_filter(
                                array_map(
                                    'trim',
                                    explode(
                                        ',',
                                        $product['color']
                                    )
                                )
                            );

                    }


                    $shortDescription =
                        strlen(
                            $description
                        ) > 85
                        ?
                        substr(
                            $description,
                            0,
                            85
                        ) . '...'
                        :
                        $description;


                    ?>



                    <!-- PRODUCT -->

                    <div
                        class="col-xl-3 col-lg-4 col-md-6 col-sm-6"
                    >

                        <div class="product-card">


                            <div class="product-image-area">


                                <!-- LABEL -->

                                <?php if (
                                    $stock <= 0
                                ): ?>

                                    <span class="product-label out">

                                        OUT OF STOCK

                                    </span>

                                <?php elseif (
                                    $index < 2
                                ): ?>

                                    <span class="product-label">

                                        FEATURED

                                    </span>

                                <?php endif; ?>


                                <a
                                    href="product_detail.php?id=<?= $productId ?>"
                                    class="product-heart"
                                    title="View Product"
                                >

                                    <i
                                        class="fa-regular fa-heart"
                                    ></i>

                                </a>

                                <img
                                    src="<?= htmlspecialchars(
                                        $image
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $productName
                                    ) ?>"
                                    class="product-image"
                                    onerror="
                                        this.onerror=null;
                                        this.src='images/default-shoe.jpg';
                                    "
                                >

                            </div>


                            <div class="product-body">


                                <!-- CATEGORY -->

                                <div class="product-category">

                                    <?= htmlspecialchars(
                                        $categoryName
                                    ) ?>

                                </div>



                                <div class="product-name">

                                    <?= htmlspecialchars(
                                        $productName
                                    ) ?>

                                </div>


                                <div class="product-description">

                                    <?= htmlspecialchars(
                                        $shortDescription
                                    ) ?>

                                </div>


                                <div class="rating">


                                    <?php for (
                                        $star = 1;
                                        $star <= 5;
                                        $star++
                                    ): ?>

                                        <?php if (
                                            $star <=
                                            $roundedRating
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


                                    <span
                                        class="rating-count"
                                    >

                                        <?=
                                            $reviewCount
                                        ?>
                                        review<?= $reviewCount !== 1
                                            ? 's'
                                            : '' ?>

                                    </span>


                                </div>


                                <div class="price-row">

                                    <span class="current-price">

                                        Rs.
                                        <?= number_format(
                                            $price,
                                            0
                                        ) ?>

                                    </span>

                                </div>


                                <?php if (
                                    $stock <= 0
                                ): ?>

                                    <div
                                        class="stock-status out"
                                    >

                                        <i
                                            class="fa-solid fa-circle-xmark"
                                        ></i>

                                        Out of stock

                                    </div>

                                <?php elseif (
                                    $stock <= 5
                                ): ?>

                                    <div
                                        class="stock-status low"
                                    >

                                        <i
                                            class="fa-solid fa-triangle-exclamation"
                                        ></i>

                                        Only
                                        <?= $stock ?>
                                        left in stock

                                    </div>

                                <?php else: ?>

                                    <div
                                        class="stock-status available"
                                    >

                                        <i
                                            class="fa-solid fa-circle-check"
                                        ></i>

                                        <?= $stock ?>
                                        available

                                    </div>

                                <?php endif; ?>


                                <?php if (
                                    !empty($sizes)
                                    ||
                                    !empty($colors)
                                ): ?>

                                    <div class="variant-box">


                                        <!-- SIZE -->

                                        <?php if (
                                            !empty($sizes)
                                        ): ?>

                                            <div
                                                class="variant-row"
                                            >

                                                <span
                                                    class="variant-title"
                                                >

                                                    SIZE

                                                </span>


                                                <div
                                                    class="variant-list"
                                                >

                                                    <?php foreach (
                                                        array_slice(
                                                            $sizes,
                                                            0,
                                                            6
                                                        )
                                                        as $size
                                                    ): ?>

                                                        <span
                                                            class="size-tag"
                                                        >

                                                            <?= htmlspecialchars(
                                                                $size
                                                            ) ?>

                                                        </span>

                                                    <?php endforeach; ?>


                                                    <?php if (
                                                        count($sizes)
                                                        > 6
                                                    ): ?>

                                                        <span
                                                            class="size-tag"
                                                        >

                                                            +

                                                            <?= count(
                                                                $sizes
                                                            ) - 6 ?>

                                                        </span>

                                                    <?php endif; ?>

                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($colors)
                                        ): ?>

                                            <div
                                                class="variant-row"
                                            >

                                                <span
                                                    class="variant-title"
                                                >

                                                    COLOR

                                                </span>


                                                <div
                                                    class="variant-list"
                                                >

                                                    <?php foreach (
                                                        array_slice(
                                                            $colors,
                                                            0,
                                                            3
                                                        )
                                                        as $color
                                                    ): ?>

                                                        <span
                                                            class="color-tag"
                                                        >

                                                            <?= htmlspecialchars(
                                                                $color
                                                            ) ?>

                                                        </span>

                                                    <?php endforeach; ?>


                                                    <?php if (
                                                        count($colors)
                                                        > 3
                                                    ): ?>

                                                        <span
                                                            class="color-tag"
                                                        >

                                                            +

                                                            <?= count(
                                                                $colors
                                                            ) - 3 ?>

                                                        </span>

                                                    <?php endif; ?>

                                                </div>

                                            </div>

                                        <?php endif; ?>


                                    </div>

                                <?php endif; ?>

                                <?php if (
                                    $stock > 0
                                ): ?>

                                    <div
                                        class="product-actions"
                                    >


                                        <!-- VIEW DETAILS -->

                                        <a
                                            href="product_details.php?id=<?= $productId ?>"
                                            class="view-btn"
                                        >

                                            <i
                                                class="fa-regular fa-eye"
                                            ></i>

                                            View Details

                                        </a>

                                        <a
                                            href="product_details.php?id=<?= $productId ?>"
                                            class="cart-btn"
                                            title="Choose Size & Color"
                                        >

                                            <i
                                                class="fa-solid fa-bag-shopping"
                                            ></i>

                                        </a>


                                    </div>

                                <?php else: ?>


                                    <div
                                        class="out-stock-btn"
                                    >

                                        <i
                                            class="fa-solid fa-ban me-1"
                                        ></i>

                                        Currently Unavailable

                                    </div>

                                <?php endif; ?>


                            </div>

                        </div>

                    </div>


                <?php endforeach; ?>


            </div>


        <?php else: ?>

            <div class="empty-box">


                <div class="empty-icon">

                    <i
                        class="fa-solid fa-shoe-prints"
                    ></i>

                </div>


                <h3>

                    No Shoes Found

                </h3>


                <p>

                    We couldn't find any leather shoes
                    matching your search or selected category.

                </p>


                <a
                    href="products.php"
                    class="reset-btn"
                >

                    <i
                        class="fa-solid fa-rotate-left me-1"
                    ></i>

                    View All Shoes

                </a>


            </div>


        <?php endif; ?>


    </div>


</section>

<footer class="footer">

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

            <a href="index.php#categories">

                Categories

            </a>

            <a href="index.php#about">

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

                <i
                    class="fa-solid fa-location-dot me-2"
                ></i>

                Kathmandu, Nepal

            </a>

            <a href="#">

                <i
                    class="fa-solid fa-phone me-2"
                ></i>

                +977 98XXXXXXXX

            </a>

            <a href="#">

                <i
                    class="fa-solid fa-envelope me-2"
                ></i>

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
