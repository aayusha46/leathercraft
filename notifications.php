<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$success_message = '';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {

    $notification_id = (int) $_POST['notification_id'];

    try {
        $stmt = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ? AND user_id = ?
        ");

        $stmt->execute([
            $notification_id,
            $user_id
        ]);

        $success_message = "Notification marked as read.";

    } catch (PDOException $e) {
        $error_message = "Unable to update notification.";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {

    try {
        $stmt = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
        ");

        $stmt->execute([$user_id]);

        $success_message = "All notifications marked as read.";

    } catch (PDOException $e) {
        $error_message = "Unable to update notifications.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notification'])) {

    $notification_id = (int) $_POST['notification_id'];

    try {
        $stmt = $pdo->prepare("
            DELETE FROM notifications
            WHERE id = ? AND user_id = ?
        ");

        $stmt->execute([
            $notification_id,
            $user_id
        ]);

        $success_message = "Notification deleted.";

    } catch (PDOException $e) {
        $error_message = "Unable to delete notification.";
    }
}
$notifications = [];
$unread_count = 0;

try {

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM notifications
        WHERE user_id = ?
        AND is_read = 0
    ");

    $stmt->execute([$user_id]);

    $unread_count = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("
        SELECT
            id,
            order_id,
            message,
            type,
            is_read,
            created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY id DESC
    ");

    $stmt->execute([$user_id]);

    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $error_message = "Notifications are currently unavailable.";
}
function getNotificationIcon($type)
{
    switch (strtolower($type)) {

        case 'success':
            return 'fa-circle-check';

        case 'warning':
            return 'fa-triangle-exclamation';

        case 'error':
            return 'fa-circle-xmark';

        case 'order':
            return 'fa-box';

        default:
            return 'fa-bell';
    }
}
function notificationTime($date)
{
    $timestamp = strtotime($date);

    if (!$timestamp) {
        return '';
    }

    $difference = time() - $timestamp;

    if ($difference < 60) {
        return 'Just now';
    }

    if ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    }

    if ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    }

    if ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }

    return date('M d, Y • h:i A', $timestamp);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications | LeatherCraft</title>

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
:root { --ink:#171411; --paper:#f7f5f0; --panel:#fff; --espresso:#211a15; --gold:#c09968; --gold-dark:#926b42; --muted:#91887d; --line:#e6e0d7; --green:#4f9663; --red:#b9554b; }
* { box-sizing:border-box; }
body { margin:0; min-height:100vh; color:var(--ink); background:var(--paper); font-family:'DM Sans',Arial,sans-serif; }
.navbar-custom { padding:20px 0; border-bottom:1px solid var(--line); background:#fff; }
.navbar-brand { color:var(--espresso); font-family:'Playfair Display',Georgia,serif; font-size:23px; font-weight:600; letter-spacing:-.04em; text-decoration:none; }
.navbar-brand i { margin-right:8px; color:var(--gold-dark); }
.navbar-brand:hover { color:var(--gold-dark); }
.nav-link-custom { position:relative; display:inline-flex; align-items:center; gap:7px; margin-left:23px; color:var(--muted); font-size:11px; text-decoration:none; }
.nav-link-custom:hover,.nav-link-custom.active { color:var(--gold-dark); }
.unread-badge { display:inline-flex; align-items:center; justify-content:center; min-width:20px; height:20px; padding:0 6px; color:#fff; border-radius:20px; background:var(--gold-dark); font-size:9px; font-weight:700; }
.notification-page { min-height:calc(100vh - 145px); padding:76px 18px 90px; }
.notification-container { max-width:900px; margin:0 auto; }
.page-heading { margin-bottom:37px; }
.page-heading h1 { margin:0 0 13px; color:var(--ink); font-family:'Playfair Display',Georgia,serif; font-size:clamp(44px,6vw,68px); font-weight:500; letter-spacing:-.06em; line-height:.98; }
.page-heading h1 i { margin-right:9px; color:var(--gold-dark); font-size:.65em; }
.page-heading p { margin:0; color:var(--muted); font-size:13px; }
.custom-alert { display:flex; align-items:center; gap:10px; margin-bottom:18px; padding:13px 16px; border-radius:5px; font-size:12px; }
.custom-alert.success { color:var(--green); border:1px solid #bfddc5; background:#eff8f0; }
.custom-alert.error { color:var(--red); border:1px solid #e6c3bd; background:#fff5f3; }
.notification-topbar { display:flex; align-items:center; justify-content:space-between; gap:18px; margin-bottom:16px; padding:17px 20px; border:1px solid var(--line); border-radius:5px; background:var(--panel); box-shadow:0 8px 24px rgba(33,26,21,.03); }
.notification-summary { color:var(--muted); font-size:12px; }
.notification-summary strong { color:var(--ink); font-family:'Playfair Display',Georgia,serif; font-size:24px; font-weight:500; }
.notification-summary .unread-badge { margin-left:9px; color:var(--gold-dark); background:#f5ede4; }
.mark-all-btn { padding:10px 14px; color:#fff; border:1px solid var(--espresso); border-radius:4px; background:var(--espresso); font-size:10px; font-weight:700; letter-spacing:.04em; cursor:pointer; }
.mark-all-btn:hover { background:var(--gold-dark); }
.notification-card { display:flex; align-items:flex-start; gap:16px; margin-bottom:12px; padding:20px; border:1px solid var(--line); border-radius:5px; background:var(--panel); box-shadow:0 8px 22px rgba(33,26,21,.025); transition:.2s ease; }
.notification-card:hover { border-color:#c6a77e; transform:translateY(-2px); }
.notification-card.unread { border-left:3px solid var(--gold); background:#fffdf9; }
.notification-icon-box { display:grid; place-items:center; flex:0 0 auto; width:43px; height:43px; color:var(--gold-dark); border-radius:50%; background:#f3ebe1; font-size:16px; }
.notification-content { flex:1; min-width:0; }
.notification-message { color:var(--ink); font-size:13px; font-weight:700; line-height:1.55; }
.notification-time { margin-top:7px; color:var(--muted); font-size:10px; }
.notification-time i { margin-right:4px; color:var(--gold-dark); }
.order-link { display:inline-flex; align-items:center; gap:6px; margin-top:11px; color:var(--gold-dark); font-size:10px; font-weight:700; text-decoration:none; }
.order-link:hover { color:var(--ink); }
.notification-actions { display:flex; align-items:center; gap:7px; }
.notification-action-btn { display:grid; place-items:center; width:33px; height:33px; color:var(--gold-dark); border:1px solid #d6c4ac; border-radius:4px; background:#fff; cursor:pointer; }
.notification-action-btn:hover { color:#fff; border-color:var(--espresso); background:var(--espresso); }
.notification-action-btn.delete { color:var(--red); border-color:#e2c0bb; }
.notification-action-btn.delete:hover { color:#fff; border-color:var(--red); background:var(--red); }
.empty-notifications { padding:78px 24px; border:1px solid var(--line); border-radius:6px; background:var(--panel); text-align:center; box-shadow:0 10px 28px rgba(33,26,21,.035); }
.empty-notifications > i { display:block; margin-bottom:19px; color:var(--gold); font-size:52px; }
.empty-notifications h3 { margin:0 0 10px; color:var(--ink); font-family:'Playfair Display',Georgia,serif; font-size:29px; font-weight:500; }
.empty-notifications p { max-width:360px; margin:0 auto 24px; color:var(--muted); font-size:13px; line-height:1.6; }
.shop-btn { display:inline-flex; align-items:center; gap:8px; padding:13px 21px; color:#fff; border:1px solid var(--espresso); border-radius:4px; background:var(--espresso); font-size:10px; font-weight:700; letter-spacing:.08em; text-decoration:none; text-transform:uppercase; }
.shop-btn:hover { color:#fff; background:var(--gold-dark); }
footer { padding:25px 20px; color:var(--muted); border-top:1px solid var(--line); background:#fff; font-size:11px; text-align:center; }
footer span { color:var(--gold-dark); }
@media (max-width:700px) { .navbar-custom { padding:16px 18px; } .navbar-custom .d-flex.align-items-center.justify-content-between { align-items:flex-start!important; flex-direction:column; gap:15px; } .navbar-custom .d-flex.align-items-center.justify-content-between > .d-flex { width:100%; justify-content:space-between; } .nav-link-custom { margin-left:0; font-size:10px; } .notification-page { padding:50px 15px 65px; } .page-heading h1 { font-size:48px; } .notification-topbar { align-items:flex-start; flex-direction:column; } .notification-card { gap:11px; padding:16px; } .notification-actions { flex-direction:column; } }
@media (max-width:480px) { .notification-card { flex-wrap:wrap; } .notification-content { flex-basis:calc(100% - 58px); } .notification-actions { width:100%; flex-direction:row; justify-content:flex-end; } }
</style></head>

<body>


<nav class="navbar-custom">

    <div class="container">

        <div class="d-flex align-items-center justify-content-between">

            <a href="index.php" class="navbar-brand">
                <i class="fa-solid fa-shoe-prints"></i>
                LeatherCraft
            </a>

            <div class="d-flex align-items-center">

                <a href="index.php" class="nav-link-custom">
                    <i class="fa-solid fa-house"></i>
                    Home
                </a>

                <a href="products.php" class="nav-link-custom">
                    <i class="fa-solid fa-shop"></i>
                    Products
                </a>

                <a href="orders.php" class="nav-link-custom">
                    <i class="fa-solid fa-box"></i>
                    My Orders
                </a>

                <a href="notifications.php"
                   class="nav-link-custom active"
                   title="Notifications">

                    <i class="fa-solid fa-bell"></i>

                    <?php if ($unread_count > 0): ?>
                        <span class="unread-badge">
                            <?= $unread_count ?>
                        </span>
                    <?php endif; ?>

                </a>

            </div>

        </div>

    </div>

</nav>

<main class="notification-page">

    <div class="notification-container">

        <div class="page-heading">

            <h1>
                <i class="fa-regular fa-bell"></i>
                Notifications
            </h1>

            <p>
                Stay updated with your LeatherCraft orders.
            </p>

        </div>


        <?php if (!empty($success_message)): ?>

            <div class="custom-alert success">
                <i class="fa-solid fa-circle-check"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>

        <?php endif; ?>


        <!-- ERROR MESSAGE -->

        <?php if (!empty($error_message)): ?>

            <div class="custom-alert error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>

        <?php endif; ?>
        <?php if (!empty($notifications)): ?>

            <div class="notification-topbar">

                <div class="notification-summary">

                    <strong>
                        <?= count($notifications) ?>
                    </strong>

                    notification<?= count($notifications) != 1 ? 's' : '' ?>

                    <?php if ($unread_count > 0): ?>

                        <span class="unread-badge">
                            <?= $unread_count ?> unread
                        </span>

                    <?php endif; ?>

                </div>


                <?php if ($unread_count > 0): ?>

                    <form method="POST">

                        <button
                            type="submit"
                            name="mark_all_read"
                            class="mark-all-btn"
                        >
                            <i class="fa-solid fa-check-double"></i>
                            Mark All as Read
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        <?php endif; ?>

        <?php if (!empty($notifications)): ?>

            <?php foreach ($notifications as $notification): ?>

                <?php

                $is_unread = ((int)$notification['is_read'] === 0);

                $icon = getNotificationIcon(
                    $notification['type'] ?? 'info'
                );

                ?>

                <div class="notification-card <?= $is_unread ? 'unread' : '' ?>">

                    <!-- ICON -->

                    <div class="notification-icon-box">

                        <i class="fa-solid <?= htmlspecialchars($icon) ?>"></i>

                    </div>


                    <!-- CONTENT -->

                    <div class="notification-content">

                        <div class="notification-message">

                            <?= htmlspecialchars($notification['message']) ?>

                        </div>


                        <div class="notification-time">

                            <i class="fa-regular fa-clock"></i>

                            <?= notificationTime($notification['created_at']) ?>

                        </div>


                        <?php if (!empty($notification['order_id'])): ?>

                            <a
                                href="orders.php?order_id=<?= (int)$notification['order_id'] ?>"
                                class="order-link"
                            >
                                <i class="fa-solid fa-arrow-right"></i>
                                View Order #<?= (int)$notification['order_id'] ?>
                            </a>

                        <?php endif; ?>

                    </div>


                    <div class="notification-actions">

                        <?php if ($is_unread): ?>

                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="notification_id"
                                    value="<?= (int)$notification['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="mark_read"
                                    class="notification-action-btn"
                                    title="Mark as read"
                                >
                                    <i class="fa-solid fa-check"></i>
                                </button>

                            </form>

                        <?php endif; ?>


                        <form method="POST">

                            <input
                                type="hidden"
                                name="notification_id"
                                value="<?= (int)$notification['id'] ?>"
                            >

                            <button
                                type="submit"
                                name="delete_notification"
                                class="notification-action-btn delete"
                                title="Delete notification"
                                onclick="return confirm('Delete this notification?');"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>

                        </form>

                    </div>

                </div>

            <?php endforeach; ?>


        <?php else: ?>

            <div class="empty-notifications">

                <i class="fa-regular fa-bell-slash"></i>

                <h3>No Notifications Yet</h3>

                <p>
                    You don't have any notifications at the moment.
                    Order updates will appear here.
                </p>

                <a href="products.php" class="shop-btn">

                    <i class="fa-solid fa-bag-shopping"></i>
                    Shop Now

                </a>

            </div>

        <?php endif; ?>

    </div>

</main>

<footer>

    © <?= date('Y') ?>
    <span>LeatherCraft</span>.
    All Rights Reserved.

</footer>
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>