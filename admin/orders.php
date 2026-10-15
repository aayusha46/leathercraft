<?php

session_start();

require_once '../database.php';

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}


$success = '';
$error = '';

$adminName = $_SESSION['admin_name'] ?? 'Administrator';

$adminNotifications = [];
$unreadAdminNotifications = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_admin_notification_read'])) {
    $notificationId = (int)($_POST['notification_id'] ?? 0);
    if ($notificationId > 0) {
        $stmt = $pdo->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$notificationId]);
    }
    header('Location: orders.php');
    exit;
}

try {
    $adminNotifications = $pdo->query("
        SELECT id, order_id, message, type, is_read, created_at
        FROM admin_notifications
        ORDER BY id DESC
        LIMIT 10
    " )->fetchAll(PDO::FETCH_ASSOC);

    $unreadAdminNotifications = (int)$pdo->query("
        SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0
    " )->fetchColumn();
} catch (PDOException $e) {
    $adminNotifications = [];
    $unreadAdminNotifications = 0;
}
if (!empty($_SESSION['order_status_flash'])) {
    $flash = $_SESSION['order_status_flash'];
    $success = $flash['type'] === 'success' ? ($flash['message'] ?? '') : '';
    $error = $flash['type'] === 'error' ? ($flash['message'] ?? '') : '';
    unset($_SESSION['order_status_flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {

    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');

    $allowedStatuses = [
        'Pending',
        'Confirmed',
        'Shipped',
        'Delivered',
        'Cancelled'
    ];

    $flashType = 'error';
    $flashMessage = '';

    if ($orderId <= 0) {
        $flashMessage = "Invalid order ID.";
    } elseif (!in_array($newStatus, $allowedStatuses, true)) {
        $flashMessage = "Invalid order status.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                SELECT user_id, status
                FROM orders
                WHERE id = ?
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([$orderId]);
            $orderOwner = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$orderOwner) {
                throw new RuntimeException('Order not found.');
            }

            $oldStatus = (string)($orderOwner['status'] ?? 'Pending');

            $stmt = $pdo->prepare("
                UPDATE orders
                SET status = ?
                WHERE id = ?
            ");
            $stmt->execute([$newStatus, $orderId]);

            if (strcasecmp($oldStatus, $newStatus) !== 0) {
                $message = "Order #{$orderId} status changed from {$oldStatus} to {$newStatus}.";

                $notification = $pdo->prepare("
                    INSERT INTO admin_notifications
                    (order_id, message, type, is_read)
                    VALUES (?, ?, 'order', 0)
                ");
                $notification->execute([
                    $orderId,
                    $message
                ]);
            }

            $pdo->commit();
            $flashType = 'success';
            $flashMessage = "Order #{$orderId} status updated to {$newStatus}.";

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $flashMessage = "Could not update order status or create notification: " . $e->getMessage();
        }
    }
    $_SESSION['order_status_flash'] = [
        'type' => $flashType,
        'message' => $flashMessage
    ];
    header('Location: orders.php');
    exit;
}


if (isset($_GET['delete'])) {

    $deleteId = (int)$_GET['delete'];


    if ($deleteId > 0) {

        try {


            try {

                $stmt = $pdo->prepare("
                    DELETE FROM order_items
                    WHERE order_id = ?
                ");

                $stmt->execute([$deleteId]);

            } catch (PDOException $e) {

                /*
                | Ignore this if order_items does not exist.
                */
            }


            $stmt = $pdo->prepare("
                DELETE FROM orders
                WHERE id = ?
            ");

            $stmt->execute([$deleteId]);


            header("Location: orders.php?deleted=1");
            exit;


        } catch (PDOException $e) {

            $error = "Could not delete order: " . $e->getMessage();
        }
    }
}


if (isset($_GET['deleted'])) {

    $success = "Order deleted successfully.";
}


try {

    $totalOrders = (int)$pdo->query("
        SELECT COUNT(*)
        FROM orders
    ")->fetchColumn();


    $pendingOrders = (int)$pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE status = 'Pending'
    ")->fetchColumn();


    $confirmedOrders = (int)$pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE status = 'Confirmed'
    ")->fetchColumn();


    $shippedOrders = (int)$pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE status = 'Shipped'
    ")->fetchColumn();


    $deliveredOrders = (int)$pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE status = 'Delivered'
    ")->fetchColumn();


    $cancelledOrders = (int)$pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE status = 'Cancelled'
    ")->fetchColumn();


} catch (PDOException $e) {

    $totalOrders = 0;
    $pendingOrders = 0;
    $confirmedOrders = 0;
    $shippedOrders = 0;
    $deliveredOrders = 0;
    $cancelledOrders = 0;

    $error = "Could not load order statistics: " . $e->getMessage();
}



try {

    $stmt = $pdo->query("
        SELECT
            o.id,
            o.user_id,
            o.total_amount,
            o.status,
            o.order_date,
            u.name AS customer_name,
            u.email AS customer_email
        FROM orders o
        LEFT JOIN users u
            ON o.user_id = u.id
        ORDER BY o.id DESC
    ");


    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    $orders = [];

    $error = "Could not load orders: " . $e->getMessage();
}


function getStatusClass($status)
{
    switch ($status) {

        case 'Pending':
            return 'status-pending';

        case 'Confirmed':
            return 'status-confirmed';

        case 'Shipped':
            return 'status-shipped';

        case 'Delivered':
            return 'status-delivered';

        case 'Cancelled':
            return 'status-cancelled';

        default:
            return 'status-default';
    }
}

function getStatusIcon($status)
{
    switch ($status) {

        case 'Pending':
            return 'fa-clock';

        case 'Confirmed':
            return 'fa-circle-check';

        case 'Shipped':
            return 'fa-truck';

        case 'Delivered':
            return 'fa-box-open';

        case 'Cancelled':
            return 'fa-circle-xmark';

        default:
            return 'fa-circle-question';
    }
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

    <title>Manage Orders | LeatherCraft Admin</title>


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

:root { --ink:#171411; --paper:#f7f5f0; --panel:#fff; --sidebar:#211a15; --gold:#c09968; --gold-bright:#d8b486; --muted:#91887d; --line:#e6e0d7; --green:#4f9663; --red:#b9554b; --blue:#4d8ea8; --purple:#7663b0; --serif:'Playfair Display',Georgia,serif; --sans:'DM Sans',Arial,sans-serif; }
* { box-sizing:border-box; }
body { margin:0; min-height:100vh; color:var(--ink); background:var(--paper); font-family:var(--sans); }

.sidebar { position:fixed; inset:0 auto 0 0; z-index:5; width:256px; height:100vh; padding:32px 18px 24px; border-right:1px solid rgba(255,255,255,.08); background:linear-gradient(160deg,#35271d,var(--sidebar) 48%,#15110e); box-shadow:12px 0 35px rgba(26,18,12,.08); }
.logo { display:flex; align-items:center; gap:10px; padding:0 16px 38px; color:#fff; font-family:var(--serif); font-size:23px; font-weight:600; letter-spacing:-.04em; text-decoration:none; }
.logo i { display:grid; place-items:center; width:32px; height:32px; border-radius:50%; color:var(--sidebar); background:var(--gold-bright); font-size:14px; }
.admin-info { margin-bottom:27px; padding:17px 12px; border:1px solid rgba(255,255,255,.1); border-radius:6px; background:rgba(255,255,255,.06); text-align:center; }
.admin-info i { margin-bottom:8px; color:var(--gold-bright); font-size:26px; }
.admin-info p { margin:0; color:rgba(255,255,255,.45); font-size:9px; letter-spacing:.14em; text-transform:uppercase; }
.admin-info strong { display:block; margin-top:5px; color:#fff; font-size:12px; }
.sidebar-menu { margin:0; padding:0; list-style:none; }
.sidebar-menu li { margin-bottom:5px; }
.sidebar-menu a { display:flex; align-items:center; gap:14px; padding:13px 16px; color:rgba(255,255,255,.62); border:1px solid transparent; border-radius:5px; font-size:12px; text-decoration:none; transition:.2s ease; }
.sidebar-menu a:hover,.sidebar-menu a.active { color:#fff; border-color:rgba(192,153,104,.25); background:rgba(192,153,104,.14); }
.sidebar-menu a.active { box-shadow:inset 3px 0 var(--gold-bright); }
.sidebar-menu i { width:18px; color:var(--gold-bright); text-align:center; }
.logout-link { margin-top:25px!important; padding-top:18px; border-top:1px solid rgba(255,255,255,.08); }
.logout-link a { color:#d88b83; }
.logout-link a i { color:#d88b83; }
.logout-link a:hover { border-color:rgba(216,139,131,.22); background:rgba(185,85,75,.12); }

.main { min-height:100vh; margin-left:256px; padding:38px clamp(24px,4vw,64px) 70px; }
.topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:30px; margin-bottom:35px; }
.topbar h1 { margin:0; color:var(--ink); font-family:var(--serif); font-size:clamp(38px,4vw,56px); font-weight:500; letter-spacing:-.06em; line-height:.98; }
.topbar h1::before { display:block; margin-bottom:12px; color:var(--gold); content:'LEATHERCRAFT ADMIN'; font-family:var(--sans); font-size:9px; font-weight:700; letter-spacing:.28em; }
.topbar p { margin:17px 0 0; color:var(--muted); font-size:13px; }
.back-btn { display:inline-flex; align-items:center; gap:9px; padding:11px 15px; color:var(--gold-dark,#926b42); border:1px solid #d6c4ac; border-radius:4px; background:transparent; font-size:10px; font-weight:700; letter-spacing:.05em; text-decoration:none; text-transform:uppercase; }
.back-btn:hover { color:#fff; background:var(--sidebar); border-color:var(--sidebar); }
.alert { margin-bottom:18px; border-radius:5px; font-size:12px; }
.notification-panel { margin-top:20px; } .notification-title { display:flex; align-items:center; gap:10px; } .notification-title i { color:var(--gold); } .notification-count { margin-left:auto; padding:5px 9px; color:var(--gold-dark); border-radius:20px; background:#f5ede4; font-family:var(--sans); font-size:9px; font-weight:700; } .admin-notification-list { padding:0 22px; } .admin-notification { display:flex; align-items:center; gap:12px; padding:14px 0; border-bottom:1px solid var(--line); } .admin-notification:last-child { border-bottom:0; } .admin-notification.unread { background:#fffdf9; } .admin-notification-icon { display:grid; place-items:center; flex:0 0 auto; width:34px; height:34px; color:var(--gold-dark); border-radius:50%; background:#f3ebe1; font-size:13px; } .admin-notification-content { flex:1; min-width:0; } .admin-notification-content strong { display:block; color:var(--ink); font-size:11px; line-height:1.5; } .admin-notification-content small { display:block; margin-top:4px; color:var(--muted); font-size:9px; } .notification-read-btn { display:grid; place-items:center; width:30px; height:30px; color:var(--green); border:1px solid #bfddc5; border-radius:4px; background:#fff; cursor:pointer; } .notification-read-btn:hover { color:#fff; background:var(--green); }

.stat-card { height:100%; padding:20px; border:1px solid var(--line); border-radius:6px; background:var(--panel); box-shadow:0 8px 25px rgba(33,26,21,.035); transition:.2s ease; }
.stat-card:hover { border-color:#c6a77e; transform:translateY(-3px); }
.stat-icon { display:grid; place-items:center; width:42px; height:42px; margin-bottom:18px; border-radius:4px; color:#926b42; background:#f3ebe1; font-size:17px; }
.stat-card h3 { margin:0; color:var(--ink); font-family:var(--serif); font-size:31px; font-weight:500; }
.stat-card p { margin:7px 0 0; color:var(--muted); font-size:11px; }
.admin-card { margin-top:35px; padding:0; overflow:hidden; border:1px solid var(--line); border-radius:6px; background:var(--panel); box-shadow:0 8px 25px rgba(33,26,21,.035); }
.card-title { margin:0; padding:21px 22px; color:var(--ink); border-bottom:1px solid var(--line); font-family:var(--serif); font-size:22px; font-weight:500; }
.card-title::before { display:block; margin-bottom:8px; color:var(--gold); content:'ORDER MANAGEMENT'; font-family:var(--sans); font-size:9px; font-weight:700; letter-spacing:.24em; }
.table-wrapper { overflow-x:auto; }
.orders-table { width:100%; min-width:1050px; border-collapse:collapse; }
.orders-table th { padding:14px 18px; color:var(--muted); border-bottom:1px solid var(--line); background:#fbfaf7; font-size:9px; font-weight:700; letter-spacing:.13em; text-align:left; text-transform:uppercase; white-space:nowrap; }
.orders-table td { padding:16px 18px; color:#4f4841; border-bottom:1px solid #f0ece6; font-size:11px; vertical-align:middle; }
.orders-table tr:last-child td { border-bottom:0; }
.orders-table tr:hover td { background:#fdfcf9; }
.order-number { color:#926b42; font-weight:700; }
.customer-name { color:var(--ink); font-weight:700; }
.customer-email { display:block; margin-top:4px; color:var(--muted); font-size:10px; }
.order-price { color:#926b42; font-weight:700; white-space:nowrap; }
.order-date { color:var(--muted); font-size:11px; white-space:nowrap; }
.status-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:20px; font-size:9px; font-weight:700; white-space:nowrap; }
.status-pending { color:#9a7821; background:#f6eed7; }
.status-confirmed { color:var(--blue); background:#e3f0f5; }
.status-shipped { color:var(--purple); background:#eeeaf8; }
.status-delivered { color:var(--green); background:#e5f2e8; }
.status-cancelled { color:var(--red); background:#f8e6e3; }
.status-default { color:var(--muted); background:#efede9; }
.status-form { display:flex; align-items:center; gap:6px; }
.status-select { min-width:125px; padding:8px; color:var(--ink); border:1px solid var(--line); border-radius:4px; outline:none; background:#fff; font-size:11px; }
.status-select:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(192,153,104,.13); }
.status-select option { color:var(--ink); background:#fff; }
.update-btn { display:grid; place-items:center; width:35px; height:35px; color:#fff; border:0; border-radius:4px; background:var(--sidebar); cursor:pointer; }
.update-btn:hover { background:#926b42; }
.delete-btn { display:inline-flex; align-items:center; gap:5px; margin-top:8px; color:var(--red); font-size:11px; text-decoration:none; }
.delete-btn:hover { color:#8b2f29; }
.empty { padding:70px 20px!important; color:var(--muted)!important; text-align:center; }
.empty i { display:block; margin-bottom:15px; color:var(--gold); font-size:42px; }

@media (max-width:900px) { .sidebar { width:210px; } .main { margin-left:210px; padding:28px 24px 55px; } }
@media (max-width:700px) { .sidebar { position:relative; width:100%; height:auto; } .logout-link { margin-top:20px!important; } .main { margin-left:0; padding:25px 18px 50px; } .topbar { flex-direction:column; align-items:flex-start; gap:18px; } .back-btn { align-self:stretch; justify-content:center; } }
</style>

</head>


<body>
<aside class="sidebar">


    <a
        href="dashboard.php"
        class="logo"
    >

        <i class="fa-solid fa-shoe-prints"></i>

        LeatherCraft

    </a>


    <div class="admin-info">

        <i class="fa-solid fa-user-shield"></i>

        <p>Logged in as</p>

        <strong>

            <?= htmlspecialchars($adminName) ?>

        </strong>

    </div>


    <ul class="sidebar-menu">


        <li>

            <a href="dashboard.php">

                <i class="fa-solid fa-gauge-high"></i>

                Dashboard

            </a>

        </li>


        <li>

            <a href="products.php">

                <i class="fa-solid fa-box"></i>

                Products

            </a>

        </li>


        <li>

            <a
                href="orders.php"
                class="active"
            >

                <i class="fa-solid fa-cart-shopping"></i>

                Orders

            </a>

        </li>


        <li>

            <a href="customers.php">

                <i class="fa-solid fa-users"></i>

                Customers

            </a>

        </li>


        <li>

            <a href="reviews.php">

                <i class="fa-solid fa-star"></i>

                Reviews

            </a>

        </li>


        <li class="logout-link">

            <a href="logout.php">

                <i class="fa-solid fa-right-from-bracket"></i>

                Logout

            </a>

        </li>


    </ul>

</aside>

<main class="main">


    <!-- TOPBAR -->

    <div class="topbar">


        <div>

            <h1>

                Manage Orders

            </h1>


            <p>

                View customer orders and update their status.

            </p>

        </div>


        <a
            href="dashboard.php"
            class="back-btn"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Dashboard

        </a>


    </div>


    <?php if ($success !== ''): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >

            <i class="fa-solid fa-circle-check"></i>

            <?= htmlspecialchars($success) ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <?php if ($error !== ''): ?>

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >

            <i class="fa-solid fa-circle-exclamation"></i>

            <?= htmlspecialchars($error) ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>



    <?php if (!empty($adminNotifications)): ?>

        <div class="admin-card notification-panel">
            <div class="card-title notification-title">
                <i class="fa-solid fa-bell"></i>
                Admin Notifications
                <?php if ($unreadAdminNotifications > 0): ?>
                    <span class="notification-count">
                        <?= $unreadAdminNotifications ?> unread
                    </span>
                <?php endif; ?>
            </div>

            <div class="admin-notification-list">
                <?php foreach ($adminNotifications as $adminNotification): ?>
                    <div class="admin-notification <?= (int)$adminNotification['is_read'] === 0 ? 'unread' : '' ?>">
                        <div class="admin-notification-icon">
                            <i class="fa-solid <?= ($adminNotification['type'] ?? 'info') === 'warning' ? 'fa-triangle-exclamation' : 'fa-bell' ?>"></i>
                        </div>
                        <div class="admin-notification-content">
                            <strong><?= htmlspecialchars($adminNotification['message']) ?></strong>
                            <small>
                                Order #<?= (int)$adminNotification['order_id'] ?> ·
                                <?= htmlspecialchars($adminNotification['created_at']) ?>
                            </small>
                        </div>
                        <?php if ((int)$adminNotification['is_read'] === 0): ?>
                            <form method="POST">
                                <input type="hidden" name="notification_id" value="<?= (int)$adminNotification['id'] ?>">
                                <button type="submit" name="mark_admin_notification_read" class="notification-read-btn" title="Mark as read">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php endif; ?>
    <div class="row g-3">


        <div class="col-xl-2 col-md-4 col-6">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-cart-shopping"></i>

                </div>

                <h3>

                    <?= $totalOrders ?>

                </h3>

                <p>

                    Total Orders

                </p>

            </div>

        </div>


        <div class="col-xl-2 col-md-4 col-6">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-clock"></i>

                </div>

                <h3>

                    <?= $pendingOrders ?>

                </h3>

                <p>

                    Pending

                </p>

            </div>

        </div>


        <div class="col-xl-2 col-md-4 col-6">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

                <h3>

                    <?= $confirmedOrders ?>

                </h3>

                <p>

                    Confirmed

                </p>

            </div>

        </div>


        <div class="col-xl-2 col-md-4 col-6">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-truck"></i>

                </div>

                <h3>

                    <?= $shippedOrders ?>

                </h3>

                <p>

                    Shipped

                </p>

            </div>

        </div>


        <div class="col-xl-2 col-md-4 col-6">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-box-open"></i>

                </div>

                <h3>

                    <?= $deliveredOrders ?>

                </h3>

                <p>

                    Delivered

                </p>

            </div>

        </div>


        <div class="col-xl-2 col-md-4 col-6">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-circle-xmark"></i>

                </div>

                <h3>

                    <?= $cancelledOrders ?>

                </h3>

                <p>

                    Cancelled

                </p>

            </div>

        </div>


    </div>

    <div class="admin-card">


        <div class="card-title">

            <i class="fa-solid fa-list"></i>

            All Customer Orders

        </div>


        <div class="table-wrapper">


            <table class="orders-table">


                <thead>

                    <tr>

                        <th>
                            Order
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Total
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Change Status
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (!empty($orders)): ?>


                        <?php foreach ($orders as $order): ?>


                            <?php

                            $status =
                                $order['status']
                                ?? 'Pending';


                            $statusClass =
                                getStatusClass($status);


                            $statusIcon =
                                getStatusIcon($status);

                            ?>


                            <tr>


                                <!-- ORDER ID -->

                                <td>

                                    <span class="order-number">

                                        #<?= (int)$order['id'] ?>

                                    </span>

                                </td>

                                <td>


                                    <span class="customer-name">

                                        <?= htmlspecialchars(
                                            $order['customer_name']
                                            ?? 'Unknown Customer'
                                        ) ?>

                                    </span>


                                    <?php if (!empty($order['customer_email'])): ?>

                                        <span class="customer-email">

                                            <?= htmlspecialchars(
                                                $order['customer_email']
                                            ) ?>

                                        </span>

                                    <?php endif; ?>


                                </td>

                                <td>

                                    <span class="order-price">

                                        Rs.

                                        <?= number_format(
                                            (float)$order['total_amount'],
                                            2
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <span class="order-date">

                                        <?php

                                        if (!empty($order['order_date'])) {

                                            echo htmlspecialchars(
                                                date(
                                                    'M d, Y h:i A',
                                                    strtotime(
                                                        $order['order_date']
                                                    )
                                                )
                                            );

                                        } else {

                                            echo 'N/A';
                                        }

                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <span
                                        class="status-badge <?= htmlspecialchars($statusClass) ?>"
                                    >

                                        <i
                                            class="fa-solid <?= htmlspecialchars($statusIcon) ?>"
                                        ></i>

                                        <?= htmlspecialchars($status) ?>

                                    </span>

                                </td>


                                <td>


                                    <form
                                        method="POST"
                                        class="status-form"
                                    >


                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?= (int)$order['id'] ?>"
                                        >


                                        <select
                                            name="status"
                                            class="status-select"
                                            required
                                        >

                                            <option
                                                value="Pending"
                                                <?= $status === 'Pending' ? 'selected' : '' ?>
                                            >
                                                Pending
                                            </option>


                                            <option
                                                value="Confirmed"
                                                <?= $status === 'Confirmed' ? 'selected' : '' ?>
                                            >
                                                Confirmed
                                            </option>


                                            <option
                                                value="Shipped"
                                                <?= $status === 'Shipped' ? 'selected' : '' ?>
                                            >
                                                Shipped
                                            </option>


                                            <option
                                                value="Delivered"
                                                <?= $status === 'Delivered' ? 'selected' : '' ?>
                                            >
                                                Delivered
                                            </option>


                                            <option
                                                value="Cancelled"
                                                <?= $status === 'Cancelled' ? 'selected' : '' ?>
                                            >
                                                Cancelled
                                            </option>

                                        </select>


                                        <button
                                            type="submit"
                                            name="update_status"
                                            class="update-btn"
                                            title="Update status"
                                        >

                                            <i class="fa-solid fa-check"></i>

                                        </button>


                                    </form>


                                </td>


                                <td>


                                    <a
                                        href="orders.php?delete=<?= (int)$order['id'] ?>"
                                        class="delete-btn"
                                        onclick="return confirm('Are you sure you want to delete order #<?= (int)$order['id'] ?>? This action cannot be undone.');"
                                    >

                                        <i class="fa-solid fa-trash"></i>

                                        Delete

                                    </a>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="7"
                                class="empty"
                            >

                                <i class="fa-solid fa-cart-shopping"></i>

                                <br>

                                No orders found.

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>


</main>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>