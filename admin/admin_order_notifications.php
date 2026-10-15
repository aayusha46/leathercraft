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

function createCustomerNotification(PDO $pdo, int $userId, int $orderId, string $message, string $type = 'info'): void
{
    if ($userId <= 0) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO notifications
        (user_id, order_id, message, type, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");

    $stmt->execute([
        $userId,
        $orderId,
        $message,
        $type
    ]);
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

    if ($orderId <= 0) {

        $error = "Invalid order ID.";

    } elseif (!in_array($newStatus, $allowedStatuses, true)) {

        $error = "Invalid order status.";

    } else {

        try {

          
            $stmt = $pdo->prepare("
                SELECT
                    id,
                    user_id,
                    status,
                    total_amount
                FROM orders
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {

                $error = "Order #{$orderId} was not found.";

            } else {

                $oldStatus = $order['status'] ?? 'Pending';
                $userId = (int)($order['user_id'] ?? 0);

               
                if ($oldStatus === $newStatus) {

                    $success = "Order #{$orderId} is already {$newStatus}.";

                } else {

                    
                    switch ($newStatus) {

                        case 'Confirmed':
                            $notificationMessage =
                                "Your order #{$orderId} has been confirmed.";
                            $notificationType = 'success';
                            break;

                        case 'Shipped':
                            $notificationMessage =
                                "Your order #{$orderId} has been shipped and is on the way.";
                            $notificationType = 'info';
                            break;

                        case 'Delivered':
                            $notificationMessage =
                                "Your order #{$orderId} has been delivered successfully. Thank you for shopping with LeatherCraft!";
                            $notificationType = 'success';
                            break;

                        case 'Cancelled':
                            $notificationMessage =
                                "Your order #{$orderId} has been cancelled.";
                            $notificationType = 'danger';
                            break;

                        case 'Pending':
                            $notificationMessage =
                                "Your order #{$orderId} is now pending.";
                            $notificationType = 'warning';
                            break;

                        default:
                            $notificationMessage =
                                "Your order #{$orderId} status has been changed to {$newStatus}.";
                            $notificationType = 'info';
                            break;
                    }

                  
                    $pdo->beginTransaction();

                    try {

                        $updateStmt = $pdo->prepare("
                            UPDATE orders
                            SET status = ?
                            WHERE id = ?
                        ");

                        $updateStmt->execute([
                            $newStatus,
                            $orderId
                        ]);

                     
                        createCustomerNotification(
                            $pdo,
                            $userId,
                            $orderId,
                            $notificationMessage,
                            $notificationType
                        );

                        $pdo->commit();

                       
                        $success =
                            "Order #{$orderId} changed from {$oldStatus} to {$newStatus}. "
                            . "Customer notification sent successfully.";

                    } catch (Throwable $e) {

                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }

                        throw $e;
                    }
                }
            }

        } catch (Throwable $e) {

            $error =
                "Could not update order status. "
                . $e->getMessage();
        }
    }
}

if (isset($_GET['delete'])) {

    $deleteId = (int)$_GET['delete'];

    if ($deleteId > 0) {

        try {

           
            $stmt = $pdo->prepare("
                SELECT user_id
                FROM orders
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$deleteId]);
            $deleteOrder = $stmt->fetch(PDO::FETCH_ASSOC);

            $pdo->beginTransaction();

           
            try {

                $stmt = $pdo->prepare("
                    DELETE FROM notifications
                    WHERE order_id = ?
                ");

                $stmt->execute([$deleteId]);

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $e;
            }

           
            $stmt = $pdo->prepare("
                DELETE FROM order_items
                WHERE order_id = ?
            ");

            $stmt->execute([$deleteId]);

            $stmt = $pdo->prepare("
                DELETE FROM orders
                WHERE id = ?
            ");

            $stmt->execute([$deleteId]);

            $pdo->commit();

            header("Location: orders.php?deleted=1");
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

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


$adminNotificationCount = $pendingOrders;

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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #0b0b0b;
            color: #fff;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #111;
            border-right: 1px solid #292929;
            padding: 25px 15px;
            z-index: 1000;
        }

        .logo {
            display: block;
            text-align: center;
            text-decoration: none;
            color: #d4af37;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 35px;
        }

        .logo i {
            margin-right: 7px;
        }

        .admin-info {
            background: #181818;
            border: 1px solid #292929;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin-bottom: 25px;
        }

        .admin-info i {
            color: #d4af37;
            font-size: 30px;
            margin-bottom: 8px;
        }

        .admin-info p {
            color: #777;
            font-size: 13px;
            margin: 0;
        }

        .admin-info strong {
            display: block;
            margin-top: 3px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 8px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #bbb;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 6px;
            transition: 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #d4af37;
            color: #000;
        }

        .sidebar-menu i {
            width: 20px;
            text-align: center;
        }

        .logout-link {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #292929;
        }

        .notification-badge {
            margin-left: auto;
            background: #dc3545;
            color: #fff;
            min-width: 22px;
            height: 22px;
            padding: 2px 6px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        .main {
            margin-left: 250px;
            padding: 30px 35px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .topbar h1 {
            margin: 0;
            font-size: 30px;
        }

        .topbar p {
            color: #888;
            margin: 5px 0 0;
        }

        .admin-alert {
            background: #171717;
            border: 1px solid #d4af37;
            border-radius: 8px;
            padding: 12px 15px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .admin-alert i {
            color: #d4af37;
            margin-right: 7px;
        }

        .back-btn {
            color: #d4af37;
            text-decoration: none;
            border: 1px solid #d4af37;
            padding: 9px 16px;
            border-radius: 6px;
        }

        .back-btn:hover {
            background: #d4af37;
            color: #000;
        }

        .alert {
            border-radius: 7px;
        }

        .stat-card {
            background: #151515;
            border: 1px solid #292929;
            border-radius: 9px;
            padding: 20px;
            height: 100%;
            transition: 0.3s;
        }

        .stat-card:hover {
            border-color: #d4af37;
            transform: translateY(-3px);
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            background: #222;
            color: #d4af37;
            font-size: 20px;
            margin-bottom: 13px;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 28px;
        }

        .stat-card p {
            margin: 5px 0 0;
            color: #888;
            font-size: 13px;
        }

        .admin-card {
            background: #151515;
            border: 1px solid #292929;
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
        }

        .card-title {
            color: #d4af37;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 22px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .orders-table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
        }

        .orders-table th {
            background: #202020;
            color: #d4af37;
            padding: 15px;
            font-size: 12px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #292929;
            vertical-align: middle;
            color: #ddd;
        }

        .orders-table tr:hover td {
            background: #181818;
        }

        .order-number {
            color: #d4af37;
            font-weight: 700;
        }

        .customer-name {
            font-weight: 600;
        }

        .customer-email {
            display: block;
            color: #777;
            font-size: 12px;
            margin-top: 3px;
        }

        .order-price {
            color: #d4af37;
            font-weight: 700;
            white-space: nowrap;
        }

        .order-date {
            color: #999;
            white-space: nowrap;
            font-size: 13px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-pending {
            background: rgba(255,193,7,0.12);
            color: #ffc107;
        }

        .status-confirmed {
            background: rgba(40,167,69,0.12);
            color: #28a745;
        }

        .status-shipped {
            background: rgba(13,110,253,0.12);
            color: #4d9cff;
        }

        .status-delivered {
            background: rgba(25,135,84,0.12);
            color: #20c997;
        }

        .status-cancelled {
            background: rgba(220,53,69,0.12);
            color: #dc3545;
        }

        .status-default {
            background: #252525;
            color: #aaa;
        }

        .status-form {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .status-select {
            min-width: 125px;
            background: #0d0d0d;
            color: #fff;
            border: 1px solid #333;
            border-radius: 5px;
            padding: 7px 8px;
            font-size: 12px;
        }

        .status-select:focus {
            outline: none;
            border-color: #d4af37;
        }

        .status-select option {
            background: #111;
            color: #fff;
        }

        .update-btn {
            background: #d4af37;
            color: #000;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 5px;
            cursor: pointer;
        }

        .update-btn:hover {
            background: #f0ce55;
        }

        .delete-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #dc3545;
            text-decoration: none;
            font-size: 13px;
            margin-top: 8px;
        }

        .delete-btn:hover {
            color: #ff5969;
        }

        .empty {
            text-align: center;
            padding: 70px 20px !important;
            color: #777 !important;
        }

        .empty i {
            color: #d4af37;
            font-size: 45px;
            margin-bottom: 15px;
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                padding: 25px;
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

    </style>

</head>

<body>

<aside class="sidebar">

    <a href="dashboard.php" class="logo">
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
            <a href="orders.php" class="active">
                <i class="fa-solid fa-cart-shopping"></i>
                Orders

                <?php if ($adminNotificationCount > 0): ?>
                    <span
                        class="notification-badge"
                        title="Pending orders"
                    >
                        <?= $adminNotificationCount ?>
                    </span>
                <?php endif; ?>

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

    <?php if ($adminNotificationCount > 0): ?>

        <div class="admin-alert">

            <i class="fa-solid fa-bell"></i>

            You have
            <strong><?= $adminNotificationCount ?></strong>
            pending order<?= $adminNotificationCount === 1 ? '' : 's' ?>
            waiting for action.

        </div>

    <?php endif; ?>

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

    <div class="row g-3">

        <div class="col-xl-2 col-md-4 col-6">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>

                <h3><?= $totalOrders ?></h3>

                <p>Total Orders</p>

            </div>

        </div>

        <div class="col-xl-2 col-md-4 col-6">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-clock"></i>
                </div>

                <h3><?= $pendingOrders ?></h3>

                <p>Pending</p>

            </div>

        </div>

        <div class="col-xl-2 col-md-4 col-6">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <h3><?= $confirmedOrders ?></h3>

                <p>Confirmed</p>

            </div>

        </div>

        <div class="col-xl-2 col-md-4 col-6">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-truck"></i>
                </div>

                <h3><?= $shippedOrders ?></h3>

                <p>Shipped</p>

            </div>

        </div>

        <div class="col-xl-2 col-md-4 col-6">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-box-open"></i>
                </div>

                <h3><?= $deliveredOrders ?></h3>

                <p>Delivered</p>

            </div>

        </div>

        <div class="col-xl-2 col-md-4 col-6">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>

                <h3><?= $cancelledOrders ?></h3>

                <p>Cancelled</p>

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

                        <th>Order</th>

                        <th>Customer</th>

                        <th>Total</th>

                        <th>Date</th>

                        <th>Status</th>

                        <th>Change Status</th>

                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (!empty($orders)): ?>

                        <?php foreach ($orders as $order): ?>

                            <?php

                            $status = $order['status'] ?? 'Pending';

                            $statusClass = getStatusClass($status);

                            $statusIcon = getStatusIcon($status);

                            ?>

                            <tr>

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
                                            title="Update status and notify customer"
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
