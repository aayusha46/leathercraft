<?php

session_start();
include 'database.php';

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}

$order_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($order_id <= 0) {

    header("Location: my_orders.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.total_amount,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.created_at,

        u.name,
        u.email,
        u.phone,
        u.address

    FROM orders o

    INNER JOIN users u
        ON o.user_id = u.id

    WHERE o.id = ?
      AND o.user_id = ?

    LIMIT 1
");


$stmt->execute([

    $order_id,

    $_SESSION['user_id']

]);


$order = $stmt->fetch();

if (!$order) {

    header("Location: my_orders.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        oi.id,
        oi.product_id,
        oi.quantity,
        oi.price,

        p.name,
        p.image

    FROM order_items oi

    LEFT JOIN products p
        ON oi.product_id = p.id

    WHERE oi.order_id = ?

    ORDER BY oi.id ASC
");


$stmt->execute([
    $order_id
]);


$order_items = $stmt->fetchAll();

function productImage($image)
{

    if (!$image) {

        return 'images/no-image.png';

    }


    if (
        strpos($image, '/') !== false ||
        strpos($image, '\\') !== false
    ) {

        return ltrim(
            $image,
            '/\\'
        );

    }


    return 'images/' . $image;
}

function statusClass($status)
{

    return strtolower(

        preg_replace(
            '/[^a-zA-Z0-9]+/',
            '-',
            $status
        )

    );

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
        Order #<?= (int)$order['id'] ?>
        | LeatherCraft
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

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

            font-family: "Segoe UI", sans-serif;

        }

        .navbar {

            background: #111;

            border-bottom:
                1px solid #292929;

            padding: 16px 5%;

        }


        .brand {

            color: #d4af37;

            font-size: 23px;

            font-weight: 700;

            text-decoration: none;

        }


        .brand:hover {

            color: #e5c354;

        }


        .nav-link {

            color: #aaa !important;

            margin-left: 20px;

            text-decoration: none;

        }


        .nav-link:hover {

            color: #d4af37 !important;

        }
        .details-container {

            max-width: 1100px;

            margin: 50px auto;

            padding: 0 20px;

        }


        .page-top {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 30px;

        }


        .page-title h1 {

            font-size: 30px;

            margin: 0;

        }


        .page-title h1 span {

            color: #d4af37;

        }


        .page-title p {

            color: #777;

            margin: 7px 0 0;

        }


        .back-btn {

            color: #aaa;

            border:
                1px solid #444;

            padding: 10px 16px;

            border-radius: 5px;

            text-decoration: none;

            font-size: 13px;

        }


        .back-btn:hover {

            color: #d4af37;

            border-color: #d4af37;

        }

        .card-box {

            background: #151515;

            border:
                1px solid #292929;

            border-radius: 8px;

            padding: 25px;

            margin-bottom: 20px;

        }


        .card-title {

            font-size: 18px;

            font-weight: 600;

            margin-bottom: 20px;

        }


        .card-title i {

            color: #d4af37;

            margin-right: 8px;

        }

        .order-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding-bottom: 20px;

            border-bottom:
                1px solid #292929;

            margin-bottom: 20px;

        }


        .order-number {

            color: #d4af37;

            font-size: 20px;

            font-weight: 700;

        }


        .order-date {

            color: #777;

            font-size: 12px;

            margin-top: 5px;

        }

        .status {

            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 600;

        }


        .pending {

            background: #3a3015;

            color: #e5c65b;

        }


        .processing {

            background: #172d3d;

            color: #62b9ed;

        }


        .shipped {

            background: #29204a;

            color: #b494ff;

        }


        .completed {

            background: #173421;

            color: #69d18a;

        }


        .cancelled {

            background: #351919;

            color: #ff7373;

        }


        .paid {

            background: #173421;

            color: #69d18a;

        }


        .failed {

            background: #351919;

            color: #ff7373;

        }
        .info-item {

            margin-bottom: 16px;

        }


        .info-item:last-child {

            margin-bottom: 0;

        }


        .info-label {

            color: #666;

            font-size: 10px;

            text-transform: uppercase;

            margin-bottom: 5px;

        }


        .info-value {

            color: #eee;

            font-size: 14px;

        }

        .product-row {

            display: flex;

            align-items: center;

            gap: 16px;

            padding: 16px 0;

            border-bottom:
                1px solid #292929;

        }


        .product-row:last-child {

            border-bottom: none;

        }


        .product-image {

            width: 80px;

            height: 80px;

            object-fit: cover;

            border-radius: 6px;

            border:
                1px solid #333;

        }


        .product-name {

            font-weight: 600;

            font-size: 15px;

        }


        .product-price {

            color: #888;

            font-size: 12px;

            margin-top: 5px;

        }


        .product-quantity {

            color: #777;

            font-size: 12px;

            margin-top: 3px;

        }


        .product-total {

            margin-left: auto;

            color: #d4af37;

            font-weight: 600;

        }

        .summary {

            margin-top: 20px;

            padding-top: 20px;

            border-top:
                1px solid #333;

        }


        .summary-row {

            display: flex;

            justify-content: space-between;

            padding: 8px 0;

            color: #aaa;

        }


        .summary-total {

            display: flex;

            justify-content: space-between;

            padding-top: 15px;

            margin-top: 10px;

            border-top:
                1px solid #333;

            font-size: 21px;

            font-weight: 700;

        }


        .summary-total span:last-child {

            color: #d4af37;

        }

        .payment-box {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 15px;

        }


        .payment-item {

            background: #0e0e0e;

            border:
                1px solid #292929;

            border-radius: 6px;

            padding: 15px;

        }


        .payment-label {

            color: #666;

            font-size: 10px;

            text-transform: uppercase;

            margin-bottom: 7px;

        }


        .payment-value {

            font-size: 14px;

            font-weight: 600;

        }

        @media (max-width: 700px) {

            .page-top {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;

            }


            .order-header {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;

            }


            .payment-box {

                grid-template-columns: 1fr;

            }


            .product-total {

                font-size: 13px;

            }

        }

    </style>

</head>


<body>

<nav class="navbar">


    <div
        class="
            container-fluid
            d-flex
            justify-content-between
            align-items-center
        "
    >


        <a
            href="index.php"
            class="brand"
        >

            <i class="fa-solid fa-shoe-prints"></i>

            LeatherCraft

        </a>


        <div>

            <a
                href="products.php"
                class="nav-link d-inline-block"
            >

                Shop

            </a>


            <a
                href="my_orders.php"
                class="nav-link d-inline-block"
            >

                My Orders

            </a>


        </div>


    </div>

</nav>

<div class="details-container">


    <!-- PAGE TOP -->

    <div class="page-top">


        <div class="page-title">

            <h1>

                Order <span>#<?= (int)$order['id'] ?></span>

            </h1>


            <p>

                Complete information about your order.

            </p>

        </div>


        <a
            href="my_orders.php"
            class="back-btn"
        >

            <i
                class="
                    fa-solid
                    fa-arrow-left
                "
            ></i>

            Back to My Orders

        </a>


    </div>
    <div class="card-box">


        <div class="order-header">


            <div>


                <div class="order-number">

                    Order #<?= (int)$order['id'] ?>

                </div>


                <div class="order-date">

                    <i
                        class="
                            fa-regular
                            fa-calendar
                        "
                    ></i>

                    <?= date(
                        'M d, Y h:i A',
                        strtotime(
                            $order['created_at']
                        )
                    ) ?>

                </div>


            </div>


            <div>


                <span
                    class="
                        status
                        <?= statusClass(
                            $order['order_status']
                        ) ?>
                    "
                >

                    <?= htmlspecialchars(
                        $order['order_status']
                    ) ?>

                </span>

            </div>


        </div>


        <div class="card-title">

            <i
                class="
                    fa-solid
                    fa-user
                "
            ></i>

            Customer Information

        </div>


        <div class="row">


            <div class="col-md-6">


                <div class="info-item">


                    <div class="info-label">

                        Full Name

                    </div>


                    <div class="info-value">

                        <?= htmlspecialchars(
                            $order['name']
                        ) ?>

                    </div>


                </div>


                <div class="info-item">


                    <div class="info-label">

                        Email

                    </div>


                    <div class="info-value">

                        <?= htmlspecialchars(
                            $order['email']
                        ) ?>

                    </div>


                </div>


            </div>


            <div class="col-md-6">


                <div class="info-item">


                    <div class="info-label">

                        Phone

                    </div>


                    <div class="info-value">

                        <?= htmlspecialchars(
                            $order['phone']
                        ) ?>

                    </div>


                </div>


                <div class="info-item">


                    <div class="info-label">

                        Delivery Address

                    </div>


                    <div class="info-value">

                        <?= nl2br(
                            htmlspecialchars(
                                $order['address']
                            )
                        ) ?>

                    </div>


                </div>


            </div>


        </div>


    </div>


    <div class="card-box">


        <div class="card-title">

            <i
                class="
                    fa-solid
                    fa-shoe-prints
                "
            ></i>

            Ordered Products

        </div>


        <?php if (count($order_items) > 0): ?>


            <?php foreach (
                $order_items
                as $item
            ): ?>


                <?php

                $item_total =
                    (float)$item['price']
                    *
                    (int)$item['quantity'];

                ?>


                <div class="product-row">


                    <img
                        src="<?= htmlspecialchars(
                            productImage(
                                $item['image']
                            )
                        ) ?>"
                        class="product-image"
                        alt="<?= htmlspecialchars(
                            $item['name']
                        ) ?>"
                        onerror="
                            this.src='images/no-image.png';
                        "
                    >


                    <div>


                        <div class="product-name">

                            <?= htmlspecialchars(
                                $item['name']
                            ) ?>

                        </div>


                        <div class="product-price">

                            Unit Price:

                            Rs.

                            <?= number_format(
                                $item['price'],
                                2
                            ) ?>

                        </div>


                        <div class="product-quantity">

                            Quantity:

                            <?= (int)
                                $item['quantity']
                            ?>

                        </div>


                    </div>


                    <div class="product-total">

                        Rs.

                        <?= number_format(
                            $item_total,
                            2
                        ) ?>

                    </div>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <p style="color:#777;">

                No products found for this order.

            </p>


        <?php endif; ?>


        <!-- SUMMARY -->

        <div class="summary">


            <div class="summary-row">

                <span>

                    Subtotal

                </span>


                <span>

                    Rs.

                    <?= number_format(
                        $order['total_amount'],
                        2
                    ) ?>

                </span>

            </div>


            <div class="summary-row">

                <span>

                    Delivery

                </span>


                <span>

                    Free

                </span>

            </div>


            <div class="summary-total">


                <span>

                    Total

                </span>


                <span>

                    Rs.

                    <?= number_format(
                        $order['total_amount'],
                        2
                    ) ?>

                </span>


            </div>


        </div>


    </div>
    <div class="card-box">


        <div class="card-title">

            <i
                class="
                    fa-solid
                    fa-credit-card
                "
            ></i>

            Payment Information

        </div>


        <div class="payment-box">


            <!-- METHOD -->

            <div class="payment-item">


                <div class="payment-label">

                    Payment Method

                </div>


                <div class="payment-value">

                    <?= htmlspecialchars(
                        $order['payment_method']
                    ) ?>

                </div>


            </div>


            <div class="payment-item">


                <div class="payment-label">

                    Payment Status

                </div>


                <div class="payment-value">


                    <span
                        class="
                            status
                            <?= statusClass(
                                $order['payment_status']
                            ) ?>
                        "
                    >

                        <?= htmlspecialchars(
                            $order['payment_status']
                        ) ?>

                    </span>


                </div>


            </div>



            <div class="payment-item">


                <div class="payment-label">

                    Delivery Status

                </div>


                <div class="payment-value">


                    <span
                        class="
                            status
                            <?= statusClass(
                                $order['order_status']
                            ) ?>
                        "
                    >

                        <?= htmlspecialchars(
                            $order['order_status']
                        ) ?>

                    </span>


                </div>


            </div>


        </div>


    </div>


</div>


</body>

</html>