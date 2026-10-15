<?php

session_start();
include 'database.php';


if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}
$stmt = $pdo->prepare("
    SELECT
        id,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([
    $_SESSION['user_id']
]);

$orders = $stmt->fetchAll();


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
        My Orders | LeatherCraft
    </title>


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

        .orders-container {

            max-width: 1200px;

            margin: 50px auto;

            padding: 0 20px;

        }


        .page-title {

            margin-bottom: 30px;

        }


        .page-title h1 {

            font-size: 34px;

            font-weight: 700;

        }


        .page-title h1 span {

            color: #d4af37;

        }


        .page-title p {

            color: #777;

            margin-top: 8px;

        }

        .order-card {

            background: #151515;

            border:
                1px solid #292929;

            border-radius: 8px;

            margin-bottom: 20px;

            overflow: hidden;

        }


        .order-header {

            padding: 18px 22px;

            background: #111;

            border-bottom:
                1px solid #292929;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

        }


        .order-number {

            color: #d4af37;

            font-size: 17px;

            font-weight: 700;

        }


        .order-date {

            color: #777;

            font-size: 12px;

            margin-top: 5px;

        }

        .order-body {

            padding: 22px;

        }


        .order-info {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 15px;

        }


        .info-box {

            background: #0e0e0e;

            border:
                1px solid #292929;

            border-radius: 6px;

            padding: 15px;

        }


        .info-label {

            color: #666;

            font-size: 10px;

            text-transform: uppercase;

            margin-bottom: 7px;

        }


        .info-value {

            color: #eee;

            font-weight: 600;

            font-size: 14px;
        }

        .status {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 10px;

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


        .order-footer {

            margin-top: 20px;

            padding-top: 18px;

            border-top:
                1px solid #292929;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }


        .total {

            font-size: 18px;

            font-weight: 700;

        }


        .total span {

            color: #d4af37;

        }


        .view-btn {

            background: #d4af37;

            color: #000;

            text-decoration: none;

            padding: 10px 18px;

            border-radius: 5px;

            font-size: 13px;

            font-weight: 600;

        }


        .view-btn:hover {

            background: #e5c354;

            color: #000;

        }

        .empty-orders {

            background: #151515;

            border:
                1px solid #292929;

            border-radius: 8px;

            text-align: center;

            padding: 70px 20px;

        }


        .empty-icon {

            font-size: 50px;

            color: #444;

            margin-bottom: 20px;

        }


        .empty-orders h3 {

            font-size: 22px;

            margin-bottom: 10px;

        }


        .empty-orders p {

            color: #777;

            margin-bottom: 25px;

        }


        .shop-btn {

            display: inline-block;

            background: #d4af37;

            color: #000;

            padding: 12px 22px;

            border-radius: 5px;

            text-decoration: none;

            font-weight: 600;

        }


        .shop-btn:hover {

            background: #e5c354;

            color: #000;

        }


        @media (max-width: 850px) {

            .order-info {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 600px) {

            .orders-container {

                margin-top: 30px;

            }


            .order-header {

                flex-direction: column;

                align-items: flex-start;

            }


            .order-info {

                grid-template-columns: 1fr;

            }


            .order-footer {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;

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
                href="cart.php"
                class="nav-link d-inline-block"
            >

                <i class="fa-solid fa-cart-shopping"></i>

                Cart

            </a>

        </div>


    </div>

</nav>


<div class="orders-container">


    <div class="page-title">

        <h1>

            My <span>Orders</span>

        </h1>


        <p>

            Track and manage your LeatherCraft orders.

        </p>

    </div>


    <?php if (count($orders) > 0): ?>


        <?php foreach ($orders as $order): ?>


            <div class="order-card">


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

                <div class="order-body">


                    <div class="order-info">


                        <!-- PAYMENT METHOD -->

                        <div class="info-box">


                            <div class="info-label">

                                Payment Method

                            </div>


                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $order['payment_method']
                                ) ?>

                            </div>


                        </div>


                        <!-- PAYMENT STATUS -->

                        <div class="info-box">


                            <div class="info-label">

                                Payment Status

                            </div>


                            <div class="info-value">


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


                        <div class="info-box">


                            <div class="info-label">

                                Delivery Status

                            </div>


                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $order['order_status']
                                ) ?>

                            </div>


                        </div>

                        <div class="info-box">


                            <div class="info-label">

                                Order Total

                            </div>


                            <div
                                class="info-value"
                                style="
                                    color:#d4af37;
                                "
                            >

                                Rs.

                                <?= number_format(
                                    $order['total_amount'],
                                    2
                                ) ?>

                            </div>


                        </div>


                    </div>


                    <div class="order-footer">


                        <div class="total">

                            Total:

                            <span>

                                Rs.

                                <?= number_format(
                                    $order['total_amount'],
                                    2
                                ) ?>

                            </span>

                        </div>


                        <a
                            href="order_details.php?id=<?= (int)$order['id'] ?>"
                            class="view-btn"
                        >

                            <i
                                class="
                                    fa-solid
                                    fa-eye
                                "
                            ></i>

                            View Details

                        </a>


                    </div>


                </div>


            </div>


        <?php endforeach; ?>


    <?php else: ?>

        <div class="empty-orders">


            <div class="empty-icon">

                <i
                    class="
                        fa-solid
                        fa-box-open
                    "
                ></i>

            </div>


            <h3>

                No Orders Yet

            </h3>


            <p>

                You haven't placed any orders yet.

                Start shopping for your favorite leather shoes.

            </p>


            <a
                href="products.php"
                class="shop-btn"
            >

                <i
                    class="
                        fa-solid
                        fa-bag-shopping
                    "
                ></i>

                Start Shopping

            </a>


        </div>


    <?php endif; ?>


</div>


</body>

</html>