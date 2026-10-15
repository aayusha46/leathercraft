<?php

session_start();

require_once 'database.php';

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


$userId = (int)$_SESSION['user_id'];

$orderId = (int)(
    $_POST['order_id']
    ?? $_GET['order_id']
    ?? 0
);


if ($orderId <= 0) {

    $_SESSION['order_error'] =
        "Invalid order.";

    header("Location: orders.php");
    exit;
}


try {


    $stmt = $pdo->prepare("
        SELECT
            id,
            user_id,
            status
        FROM orders
        WHERE id = ?
          AND user_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $orderId,
        $userId
    ]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$order) {

        $_SESSION['order_error'] =
            "Order not found.";

        header("Location: orders.php");
        exit;
    }

    if (
        !in_array(
            $order['status'],
            ['Pending', 'Confirmed'],
            true
        )
    ) {

        $_SESSION['order_error'] =
            "This order cannot be cancelled.";

        header("Location: orders.php");
        exit;
    }


    $stmt = $pdo->prepare("
        SELECT
            p.name
        FROM order_items oi
        INNER JOIN products p
            ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");

    $stmt->execute([
        $orderId
    ]);

    $products =
        $stmt->fetchAll(PDO::FETCH_COLUMN);


    if (!empty($products)) {

        $quotedProducts = [];

        foreach ($products as $productName) {

            $quotedProducts[] =
                '"' . trim($productName) . '"';
        }

        $shoeText =
            implode(', ', $quotedProducts);

    } else {

        $shoeText =
            '"Unknown Shoe"';
    }

    $pdo->beginTransaction();


    $stmt = $pdo->prepare("
        UPDATE orders
        SET status = 'Cancelled'
        WHERE id = ?
          AND user_id = ?
          AND status IN ('Pending', 'Confirmed')
    ");

    $stmt->execute([
        $orderId,
        $userId
    ]);

    if ($stmt->rowCount() === 0) {

        throw new Exception(
            "The order could not be cancelled."
        );
    }

    $customerMessage =
        "Your order #{$orderId} has been cancelled.";


    $stmt = $pdo->prepare("
        INSERT INTO notifications
        (
            user_id,
            order_id,
            message,
            type,
            is_read
        )
        VALUES
        (
            ?,
            ?,
            ?,
            'danger',
            0
        )
    ");

    $stmt->execute([
        $userId,
        $orderId,
        $customerMessage
    ]);

    $adminMessage =
        'Customer has canceled this ' . $shoeText;


    $stmt = $pdo->prepare("
        INSERT INTO admin_notifications
        (
            order_id,
            message,
            type,
            is_read
        )
        VALUES
        (
            ?,
            ?,
            'cancelled',
            0
        )
    ");

    $stmt->execute([
        $orderId,
        $adminMessage
    ]);


    $pdo->commit();


    $_SESSION['order_success'] =
        "Order #{$orderId} has been cancelled successfully.";


    header("Location: orders.php");
    exit;


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }


    $_SESSION['order_error'] =
        "Could not cancel order: "
        . $e->getMessage();


    header("Location: orders.php");
    exit;
}
?>