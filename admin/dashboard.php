<?php

session_start();


require_once __DIR__ . '/../database.php';


$isAdminLoggedIn =
    (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)
    ||
    isset($_SESSION['admin_id']);

if (!$isAdminLoggedIn) {
    header("Location: admin_login.php");
    exit;
}


$adminName = $_SESSION['admin_name']
    ?? $_SESSION['user_name']
    ?? 'LeatherCraft Admin';

$adminEmail = $_SESSION['admin_email']
    ?? $_SESSION['user_email']
    ?? 'admin@leathercraft.com';


$totalProducts = 0;
$totalStock = 0;
$lowStock = 0;
$outOfStock = 0;

$totalCustomers = 0;

$totalOrders = 0;
$pendingOrders = 0;
$confirmedOrders = 0;
$shippedOrders = 0;
$deliveredOrders = 0;
$cancelledOrders = 0;

$totalReviews = 0;

$recentProducts = [];
$recentOrders = [];

$errorMessage = "";


try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM products
    ");

    $totalProducts = (int)$stmt->fetchColumn();


    $stmt = $pdo->query("
        SELECT COALESCE(SUM(stock), 0)
        FROM products
    ");

    $totalStock = (int)$stmt->fetchColumn();


    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM products
        WHERE stock > 0
        AND stock <= 5
    ");

    $lowStock = (int)$stmt->fetchColumn();


    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM products
        WHERE stock = 0
    ");

    $outOfStock = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE role = 'customer'
    ");

    $totalCustomers = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
    ");

    $totalOrders = (int)$stmt->fetchColumn();


    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE status = 'Pending'
    ");

    $pendingOrders = (int)$stmt->fetchColumn();


    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE status = 'Confirmed'
    ");

    $confirmedOrders = (int)$stmt->fetchColumn();


    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE status = 'Shipped'
    ");

    $shippedOrders = (int)$stmt->fetchColumn();


    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE status = 'Delivered'
    ");

    $deliveredOrders = (int)$stmt->fetchColumn();


    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE status = 'Cancelled'
    ");

    $cancelledOrders = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM reviews
    ");

    $totalReviews = (int)$stmt->fetchColumn();


    $stmt = $pdo->query("
        SELECT
            id,
            name,
            price,
            stock
        FROM products
        ORDER BY id DESC
        LIMIT 5
    ");

    $recentProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("
        SELECT
            o.id,
            o.total_amount,
            o.status,
            o.order_date,
            u.name AS customer_name
        FROM orders o
        LEFT JOIN users u
            ON o.user_id = u.id
        ORDER BY o.id DESC
        LIMIT 5
    ");

    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    $errorMessage = $e->getMessage();

}

?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>LeatherCraft Admin Dashboard</title>

<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
rel="stylesheet">


<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap');

:root {
    --ink: #171411;
    --paper: #f7f5f0;
    --panel: #ffffff;
    --sidebar: #211a15;
    --sidebar-soft: #2d241d;
    --gold: #c09968;
    --gold-bright: #d8b486;
    --muted: #91887d;
    --line: #e6e0d7;
    --green: #4f9663;
    --red: #b9554b;
    --blue: #4d8ea8;
    --purple: #7663b0;
    --serif: 'Playfair Display', Georgia, serif;
    --sans: 'DM Sans', Arial, sans-serif;
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body { margin: 0; min-height: 100vh; color: var(--ink); background: var(--paper); font-family: var(--sans); }
a { color: inherit; }

.sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    z-index: 5;
    width: 256px;
    height: 100vh;
    padding: 32px 18px 24px;
    border-right: 1px solid rgba(255,255,255,.08);
    background: linear-gradient(160deg, #35271d, var(--sidebar) 48%, #15110e);
    box-shadow: 12px 0 35px rgba(26,18,12,.08);
}

.logo { display: flex; align-items: center; gap: 10px; padding: 0 16px 42px; color: #fff; font-family: var(--serif); font-size: 23px; font-weight: 600; letter-spacing: -.04em; }
.logo i { display: grid; place-items: center; width: 32px; height: 32px; border-radius: 50%; color: var(--sidebar); background: var(--gold-bright); font-size: 14px; }
.menu-title { margin: 0 16px 12px; color: rgba(255,255,255,.4); font-size: 9px; font-weight: 700; letter-spacing: .24em; text-transform: uppercase; }
.menu a, .logout a { display: flex; align-items: center; gap: 14px; margin-bottom: 5px; padding: 13px 16px; color: rgba(255,255,255,.62); border: 1px solid transparent; border-radius: 5px; font-size: 12px; text-decoration: none; transition: .2s ease; }
.menu a:hover, .menu a.active { color: #fff; border-color: rgba(192,153,104,.25); background: rgba(192,153,104,.14); }
.menu a.active { box-shadow: inset 3px 0 var(--gold-bright); }
.menu a i, .logout a i { width: 18px; color: var(--gold-bright); text-align: center; }
.logout { position: absolute; right: 18px; bottom: 24px; left: 18px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,.08); }
.logout a { margin: 0; color: #d88b83; }
.logout a i { color: #d88b83; }
.logout a:hover { border-color: rgba(216,139,131,.22); background: rgba(185,85,75,.12); }

.main { min-height: 100vh; margin-left: 256px; padding: 38px clamp(24px, 4vw, 64px) 70px; }
.topbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 30px; margin-bottom: 42px; }
.title h1 { margin: 0; font-family: var(--serif); font-size: clamp(38px, 4vw, 56px); font-weight: 500; letter-spacing: -.06em; line-height: .98; }
.title h1::before { display: block; margin-bottom: 12px; color: var(--gold); content: 'LEATHERCRAFT ADMIN'; font-family: var(--sans); font-size: 9px; font-weight: 700; letter-spacing: .28em; }
.title p { margin: 17px 0 0; color: var(--muted); font-size: 13px; }
.admin-box { display: flex; align-items: center; gap: 12px; min-width: 225px; padding: 10px 14px; border: 1px solid var(--line); border-radius: 6px; background: var(--panel); box-shadow: 0 10px 30px rgba(33,26,21,.04); }
.admin-icon { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 50%; color: #fff; background: var(--sidebar); }
.admin-icon i { color: var(--gold-bright); }
.admin-info strong { display: block; font-size: 12px; }
.admin-info small { display: block; max-width: 155px; margin-top: 3px; overflow: hidden; color: var(--muted); font-size: 10px; text-overflow: ellipsis; white-space: nowrap; }

.error-box { margin-bottom: 25px; padding: 16px 18px; color: #8b4437; border: 1px solid #e4c4ba; border-radius: 5px; background: #fff6f3; font-size: 12px; }
.stats, .order-stats, .quick-actions { display: grid; gap: 16px; }
.stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.stat-card, .order-card, .table-box, .quick-action { border: 1px solid var(--line); border-radius: 6px; background: var(--panel); box-shadow: 0 8px 25px rgba(33,26,21,.035); }
.stat-card { padding: 20px; }
.stat-icon { display: grid; place-items: center; width: 42px; height: 42px; margin-bottom: 18px; border-radius: 4px; color: var(--gold-dark, #926b42); background: #f3ebe1; }
.stat-card h2 { margin: 0; font-family: var(--serif); font-size: 31px; font-weight: 500; }
.stat-card p { margin: 7px 0 0; color: var(--muted); font-size: 11px; }
.section { margin-top: 38px; }
.section-title { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; color: var(--ink); font-family: var(--serif); font-size: 21px; }
.section-title::after { flex: 1; height: 1px; content: ''; background: var(--line); }
.order-stats { grid-template-columns: repeat(5, minmax(0, 1fr)); }
.order-card { padding: 19px; }
.order-card h3 { margin: 0 0 13px; color: var(--muted); font-size: 11px; font-weight: 500; }
.order-card strong { color: var(--gold-dark, #926b42); font-family: var(--serif); font-size: 27px; font-weight: 500; }
.tables { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
.table-box { overflow: hidden; }
.table-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px; border-bottom: 1px solid var(--line); }
.table-header h3 { margin: 0; font-family: var(--serif); font-size: 19px; font-weight: 500; }
.view-link { color: var(--gold-dark, #926b42); font-size: 10px; font-weight: 700; letter-spacing: .06em; text-decoration: none; text-transform: uppercase; }
.view-link:hover { color: var(--ink); }
table { width: 100%; border-collapse: collapse; }
th { padding: 13px 20px; color: var(--muted); border-bottom: 1px solid var(--line); font-size: 9px; font-weight: 700; letter-spacing: .12em; text-align: left; text-transform: uppercase; }
td { padding: 15px 20px; border-bottom: 1px solid #f0ece6; font-size: 11px; }
tr:last-child td { border-bottom: 0; }
.empty { padding: 28px; color: var(--muted); text-align: center; }
.stock { font-weight: 700; }
.stock.good { color: var(--green); }
.stock.low { color: #b7892d; }
.stock.out { color: var(--red); }
.status { display: inline-block; padding: 5px 9px; border-radius: 20px; font-size: 9px; font-weight: 700; letter-spacing: .03em; }
.status-pending { color: #9a7821; background: #f6eed7; }
.status-confirmed { color: var(--blue); background: #e3f0f5; }
.status-shipped { color: var(--purple); background: #eeeaf8; }
.status-delivered { color: var(--green); background: #e5f2e8; }
.status-cancelled { color: var(--red); background: #f8e6e3; }
.status-unknown { color: var(--muted); background: #efede9; }
.quick-actions { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.quick-action { display: block; padding: 20px; text-decoration: none; transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; }
.quick-action:hover { border-color: #c6a77e; box-shadow: 0 13px 28px rgba(33,26,21,.08); transform: translateY(-3px); }
.quick-action i { display: block; margin-bottom: 14px; color: var(--gold-dark, #926b42); font-size: 18px; }
.quick-action strong { display: block; margin-bottom: 5px; font-size: 12px; }
.quick-action span { color: var(--muted); font-size: 10px; }

@media (max-width: 1200px) { .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .order-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); } .quick-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 900px) { .sidebar { width: 210px; } .main { margin-left: 210px; } .tables { grid-template-columns: 1fr; } }
@media (max-width: 650px) { .sidebar { position: relative; width: 100%; height: auto; } .logout { position: static; margin-top: 22px; } .main { margin-left: 0; padding: 28px 18px 50px; } .topbar { flex-direction: column; gap: 20px; } .admin-box { width: 100%; } .stats, .order-stats, .quick-actions { grid-template-columns: 1fr; } .table-box { overflow-x: auto; } table { min-width: 490px; } }
</style>

</head>


<body>


<aside class="sidebar">

    <div class="logo">
        <i class="fa-solid fa-shoe-prints"></i>
        LeatherCraft
    </div>

    <div class="menu-title">
        Main Menu
    </div>

    <nav class="menu">

        <a href="dashboard.php" class="active">
            <i class="fa-solid fa-gauge-high"></i>
            Dashboard
        </a>

        <a href="products.php">
            <i class="fa-solid fa-shoe-prints"></i>
            Products
        </a>

        <a href="orders.php">
            <i class="fa-solid fa-cart-shopping"></i>
            Orders
        </a>

        <a href="customers.php">
            <i class="fa-solid fa-users"></i>
            Customers
        </a>

        <a href="reviews.php">
            <i class="fa-solid fa-star"></i>
            Reviews
        </a>

    </nav>


    <div class="logout">

        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>

    </div>

</aside>


<!-- MAIN -->

<main class="main">



    <div class="topbar">

        <div class="title">

            <h1>Dashboard</h1>

            <p>
                Welcome back,
                <?= htmlspecialchars($adminName) ?>
            </p>

        </div>


        <div class="admin-box">

            <div class="admin-icon">
                <i class="fa-solid fa-user"></i>
            </div>

            <div class="admin-info">

                <strong>
                    <?= htmlspecialchars($adminName) ?>
                </strong>

                <small>
                    <?= htmlspecialchars($adminEmail) ?>
                </small>

            </div>

        </div>

    </div>

    <?php if ($errorMessage !== ''): ?>

        <div class="error-box">

            <strong>
                Dashboard database error:
            </strong>

            <br><br>

            <?= htmlspecialchars($errorMessage) ?>

        </div>

    <?php endif; ?>

    <div class="stats">


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-shoe-prints"></i>
            </div>

            <h2>
                <?= $totalProducts ?>
            </h2>

            <p>
                Total Products
            </p>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>

            <h2>
                <?= $totalStock ?>
            </h2>

            <p>
                Total Stock
            </p>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>

            <h2>
                <?= $totalCustomers ?>
            </h2>

            <p>
                Registered Customers
            </p>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>

            <h2>
                <?= $totalOrders ?>
            </h2>

            <p>
                Total Orders
            </p>

        </div>

    </div>



    <div class="section">

        <div class="section-title">
            Stock Information
        </div>

        <div class="order-stats">

            <div class="order-card">
                <h3>Total Stock</h3>
                <strong><?= $totalStock ?></strong>
            </div>

            <div class="order-card">
                <h3>Low Stock</h3>
                <strong><?= $lowStock ?></strong>
            </div>

            <div class="order-card">
                <h3>Out of Stock</h3>
                <strong><?= $outOfStock ?></strong>
            </div>

            <div class="order-card">
                <h3>Total Reviews</h3>
                <strong><?= $totalReviews ?></strong>
            </div>

            <div class="order-card">
                <h3>Total Orders</h3>
                <strong><?= $totalOrders ?></strong>
            </div>

        </div>

    </div>



    <div class="section">

        <div class="section-title">
            Order Status
        </div>

        <div class="order-stats">

            <div class="order-card">
                <h3>Pending Orders</h3>
                <strong><?= $pendingOrders ?></strong>
            </div>

            <div class="order-card">
                <h3>Confirmed Orders</h3>
                <strong><?= $confirmedOrders ?></strong>
            </div>

            <div class="order-card">
                <h3>Shipped Orders</h3>
                <strong><?= $shippedOrders ?></strong>
            </div>

            <div class="order-card">
                <h3>Delivered Orders</h3>
                <strong><?= $deliveredOrders ?></strong>
            </div>

            <div class="order-card">
                <h3>Cancelled Orders</h3>
                <strong><?= $cancelledOrders ?></strong>
            </div>

        </div>

    </div>



    <div class="section">

        <div class="tables">


            <!-- PRODUCTS -->

            <div class="table-box">

                <div class="table-header">

                    <h3>
                        Recent Products
                    </h3>

                    <a href="products.php"
                       class="view-link">
                        View All
                    </a>

                </div>


                <table>

                    <thead>

                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Stock</th>
                    </tr>

                    </thead>


                    <tbody>

                    <?php if (!empty($recentProducts)): ?>

                        <?php foreach ($recentProducts as $product): ?>

                            <?php

                            $stock = (int)($product['stock'] ?? 0);

                            if ($stock <= 0) {
                                $stockClass = 'out';
                            } elseif ($stock <= 5) {
                                $stockClass = 'low';
                            } else {
                                $stockClass = 'good';
                            }

                            ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $product['name'] ?? 'Unknown'
                                    ) ?>
                                </td>

                                <td>
                                    $<?= number_format(
                                        (float)($product['price'] ?? 0),
                                        2
                                    ) ?>
                                </td>

                                <td>

                                    <span class="stock <?= $stockClass ?>">
                                        <?= $stock ?>
                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="3"
                                class="empty">
                                No products found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <!-- ORDERS -->

            <div class="table-box">

                <div class="table-header">

                    <h3>
                        Recent Orders
                    </h3>

                    <a href="orders.php"
                       class="view-link">
                        View All
                    </a>

                </div>


                <table>

                    <thead>

                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Status</th>
                    </tr>

                    </thead>


                    <tbody>

                    <?php if (!empty($recentOrders)): ?>

                        <?php foreach ($recentOrders as $order): ?>

                            <?php

                            $status = trim(
                                $order['status'] ?? ''
                            );

                            switch ($status) {

                                case 'Pending':
                                    $statusClass = 'status-pending';
                                    break;

                                case 'Confirmed':
                                    $statusClass = 'status-confirmed';
                                    break;

                                case 'Shipped':
                                    $statusClass = 'status-shipped';
                                    break;

                                case 'Delivered':
                                    $statusClass = 'status-delivered';
                                    break;

                                case 'Cancelled':
                                    $statusClass = 'status-cancelled';
                                    break;

                                default:
                                    $statusClass = 'status-unknown';
                            }

                            ?>

                            <tr>

                                <td>
                                    #<?= (int)$order['id'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $order['customer_name']
                                        ?? 'Unknown'
                                    ) ?>
                                </td>

                                <td>

                                    <span class="status <?= $statusClass ?>">

                                        <?= htmlspecialchars(
                                            $status !== ''
                                                ? $status
                                                : 'Unknown'
                                        ) ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="3"
                                class="empty">

                                No orders found.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <div class="section">

        <div class="section-title">
            Quick Actions
        </div>


        <div class="quick-actions">


            <a href="products.php"
               class="quick-action">

                <i class="fa-solid fa-shoe-prints"></i>

                <strong>
                    Manage Products
                </strong>

                <span>
                    Add, edit, delete and check product stock.
                </span>

            </a>


            <a href="orders.php"
               class="quick-action">

                <i class="fa-solid fa-cart-shopping"></i>

                <strong>
                    Manage Orders
                </strong>

                <span>
                    View and update customer orders.
                </span>

            </a>


            <a href="customers.php"
               class="quick-action">

                <i class="fa-solid fa-users"></i>

                <strong>
                    View Customers
                </strong>

                <span>
                    View registered customers.
                </span>

            </a>


            <a href="reviews.php"
               class="quick-action">

                <i class="fa-solid fa-star"></i>

                <strong>
                    View Reviews
                </strong>

                <span>
                    View customer reviews and ratings.
                </span>

            </a>


        </div>

    </div>


</main>

</body>

</html>