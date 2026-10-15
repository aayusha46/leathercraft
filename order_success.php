<?php

session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$order_id = (int)($_SESSION['last_order_id'] ?? 0);


if ($order_id <= 0) {
    header("Location: products.php");
    exit;
}


try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            user_id,
            total_amount,
            payment_method,
            payment_status,
            status,
            order_date
        FROM orders
        WHERE id = ?
          AND user_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $order_id,
        $_SESSION['user_id']
    ]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    die(
        "Could not load order: "
        . htmlspecialchars($e->getMessage())
    );
}



if (!$order) {

    unset($_SESSION['last_order_id']);

    header("Location: products.php");
    exit;
}
try {

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

    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    die(
        "Could not load order items: "
        . htmlspecialchars($e->getMessage())
    );
}

function orderImage($image)
{
    $image = trim($image ?? '');

   
    if ($image === '') {
        return 'images/no-image.jpg';
    }



    $image = str_replace('\\', '/', $image);

    $image = ltrim($image, '/');

    if (
        strpos($image, 'http://') === 0 ||
        strpos($image, 'https://') === 0
    ) {
        return $image;
    }



    if (strpos($image, 'images/') === 0) {
        return $image;
    }


   
    return 'images/' . $image;
}


function getStatusClass($status)
{
    $status = strtolower(trim($status));

    switch ($status) {

        case 'pending':
            return 'pending';

        case 'confirmed':
            return 'processing';

        case 'shipped':
            return 'shipped';

        case 'delivered':
            return 'completed';

        case 'cancelled':
            return 'cancelled';

        default:
            return 'pending';
    }
}


function getPaymentStatusClass($status)
{
    $status = strtolower(trim($status));

    if ($status === 'paid') {
        return 'paid';
    }

    return 'pending';
}

$order_date = '';

if (!empty($order['order_date'])) {

    $timestamp = strtotime(
        $order['order_date']
    );

    if ($timestamp !== false) {

        $order_date = date(
            'M d, Y',
            $timestamp
        );
    }
}

unset($_SESSION['last_order_id']);

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
        Order Successful | LeatherCraft
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
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap');
:root { --ink:#171411; --paper:#f7f5f0; --panel:#fff; --espresso:#211a15; --gold:#c09968; --gold-dark:#926b42; --muted:#91887d; --line:#e6e0d7; --green:#4f9663; --blue:#4d8ea8; --purple:#7663b0; --red:#b9554b; }
* { box-sizing:border-box; }
body { margin:0; min-height:100vh; color:var(--ink); background:var(--paper); font-family:'DM Sans',Arial,sans-serif; }
.navbar { padding:20px clamp(20px,6vw,86px); border-bottom:1px solid var(--line); background:#fff; }
.brand { color:var(--espresso); font-family:'Playfair Display',Georgia,serif; font-size:23px; font-weight:600; letter-spacing:-.04em; text-decoration:none; }
.brand i { margin-right:8px; color:var(--gold-dark); }
.brand:hover { color:var(--gold-dark); }
.nav-link { margin-left:22px; color:var(--muted)!important; font-size:11px; text-decoration:none; }
.nav-link:hover { color:var(--gold-dark)!important; }
.success-container { max-width:960px; margin:0 auto; padding:72px 24px 96px; }
.success-card { overflow:hidden; border:1px solid var(--line); border-radius:6px; background:var(--panel); box-shadow:0 15px 38px rgba(33,26,21,.05); }
.success-header { padding:56px 28px 48px; border-bottom:1px solid var(--line); text-align:center; }
.success-icon { display:grid; place-items:center; width:82px; height:82px; margin:0 auto 22px; color:var(--green); border:1px solid #bbddc2; border-radius:50%; background:#e8f4ea; font-size:36px; }
.success-header h1 { margin:0 0 12px; color:var(--ink); font-family:'Playfair Display',Georgia,serif; font-size:clamp(32px,5vw,48px); font-weight:500; letter-spacing:-.05em; line-height:1.02; }
.success-header h1 span { color:var(--gold); font-style:italic; }
.success-header p { max-width:500px; margin:0 auto; color:var(--muted); font-size:13px; line-height:1.7; }
.order-number { margin-top:17px; color:var(--gold-dark); font-size:13px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
.details { padding:30px; }
.details-title { margin-bottom:20px; color:var(--ink); font-family:'Playfair Display',Georgia,serif; font-size:23px; font-weight:500; }
.details-title i { margin-right:9px; color:var(--gold-dark); font-size:17px; }
.info-box { height:100%; padding:17px; border:1px solid var(--line); border-radius:4px; background:#fcfbf8; }
.info-label { margin-bottom:7px; color:var(--muted); font-size:9px; font-weight:700; letter-spacing:.13em; text-transform:uppercase; }
.info-value { color:var(--ink); font-size:12px; font-weight:700; }
.status { display:inline-block; padding:6px 11px; border-radius:20px; font-size:9px; font-weight:700; letter-spacing:.03em; }
.pending { color:#9a7821; background:#f6eed7; }
.processing { color:var(--blue); background:#e3f0f5; }
.shipped { color:var(--purple); background:#eeeaf8; }
.completed,.paid { color:var(--green); background:#e5f2e8; }
.cancelled { color:var(--red); background:#f8e6e3; }
.products { margin-top:30px; padding-top:27px; border-top:1px solid var(--line); }
.product { display:flex; align-items:center; gap:16px; padding:16px 0; border-bottom:1px solid var(--line); }
.product:last-child { border-bottom:0; }
.product-image { display:block; flex:0 0 auto; width:78px; height:78px; padding:6px; border:1px solid var(--line); border-radius:4px; background:#fff; object-fit:contain; }
.product-name { color:var(--ink); font-size:12px; font-weight:700; }
.product-quantity { margin-top:5px; color:var(--muted); font-size:10px; }
.product-price { margin-left:auto; color:var(--gold-dark); font-size:13px; font-weight:700; white-space:nowrap; }
.total-box { display:flex; justify-content:space-between; margin-top:13px; padding-top:20px; border-top:1px solid var(--line); font-size:21px; font-weight:700; }
.total-box span:last-child { color:var(--gold-dark); }
.actions { display:flex; justify-content:center; gap:12px; margin-top:32px; }
.btn-gold,.btn-dark { display:inline-flex; align-items:center; justify-content:center; padding:13px 23px; border-radius:4px; font-size:10px; font-weight:700; letter-spacing:.07em; text-decoration:none; text-transform:uppercase; transition:.2s ease; }
.btn-gold { color:#fff; border:1px solid var(--espresso); background:var(--espresso); }
.btn-gold:hover { color:#fff; background:var(--gold-dark); }
.btn-dark { color:var(--gold-dark); border:1px solid #d6c4ac; background:#fff; }
.btn-dark:hover { color:#fff; border-color:var(--espresso); background:var(--espresso); }
.no-products { padding:20px; color:var(--muted); text-align:center; }
@media (max-width:600px) { .navbar { padding:16px 20px; } .nav-link { margin-left:10px; } .success-container { padding:42px 15px 65px; } .success-header { padding:40px 20px 35px; } .success-header h1 { font-size:34px; } .details { padding:21px; } .actions { flex-direction:column; } .actions a { width:100%; text-align:center; } .product-price { font-size:12px; } .product-image { width:65px; height:65px; } }
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
                href="orders.php"
                class="nav-link d-inline-block"
            >

                My Orders

            </a>

        </div>

    </div>

</nav>

<div class="success-container">

    <div class="success-card">
        <div class="success-header">

            <div class="success-icon">

                <i class="fa-solid fa-check"></i>

            </div>


            <h1>

                Order
                <span>Placed Successfully!</span>

            </h1>


            <p>

                Thank you for shopping with LeatherCraft.

                Your order has been received successfully.

            </p>


            <div class="order-number">

                Order #
                <?= (int)$order['id'] ?>

            </div>

        </div>

        <div class="details">


            <div class="details-title">

                <i class="fa-solid fa-receipt"></i>

                Order Information

            </div>


            <div class="row g-3">


                <!-- DATE -->

                <div class="col-md-4">

                    <div class="info-box">

                        <div class="info-label">

                            Order Date

                        </div>


                        <div class="info-value">

                            <?= htmlspecialchars(
                                $order_date
                            ) ?>

                        </div>

                    </div>

                </div>


             

                <div class="col-md-4">

                    <div class="info-box">

                        <div class="info-label">

                            Payment Method

                        </div>


                        <div class="info-value">

                            <?= htmlspecialchars(
                                $order['payment_method']
                                ?? 'Not specified'
                            ) ?>

                        </div>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="info-box">

                        <div class="info-label">

                            Order Status

                        </div>


                        <div class="info-value">

                            <?php

                            $status =
                                $order['status']
                                ?? 'Pending';

                            ?>

                            <span
                                class="
                                    status
                                    <?= htmlspecialchars(
                                        getStatusClass(
                                            $status
                                        )
                                    ) ?>
                                "
                            >

                                <?= htmlspecialchars(
                                    $status
                                ) ?>

                            </span>

                        </div>

                    </div>

                </div>


            </div>
            <div
                class="row g-3"
                style="margin-top: 1px;"
            >

                <div class="col-md-4">

                    <div class="info-box">

                        <div class="info-label">

                            Payment Status

                        </div>


                        <div class="info-value">

                            <?php

                            $payment_status =
                                $order['payment_status']
                                ?? 'Pending';

                            ?>

                            <span
                                class="
                                    status
                                    <?= htmlspecialchars(
                                        getPaymentStatusClass(
                                            $payment_status
                                        )
                                    ) ?>
                                "
                            >

                                <?= htmlspecialchars(
                                    $payment_status
                                ) ?>

                            </span>

                        </div>

                    </div>

                </div>

            </div>
            <div class="products">


                <div class="details-title">

                    <i class="fa-solid fa-shoe-prints"></i>

                    Ordered Shoes

                </div>


                <?php if (count($order_items) > 0): ?>


                    <?php foreach ($order_items as $item): ?>


                        <?php

                        $item_total =
                            (float)$item['price']
                            *
                            (int)$item['quantity'];

                        ?>


                        <div class="product">


                            <!-- PRODUCT IMAGE -->

                            <img
                                src="<?= htmlspecialchars(
                                    orderImage(
                                        $item['image']
                                        ?? ''
                                    )
                                ) ?>"
                                class="product-image"
                                alt="<?= htmlspecialchars(
                                    $item['name']
                                    ?? 'Product'
                                ) ?>"
                                onerror="
                                    this.onerror=null;
                                    this.src='images/no-image.jpg';
                                "
                            >
                            <div>

                                <div class="product-name">

                                    <?= htmlspecialchars(
                                        $item['name']
                                        ?? 'Product'
                                    ) ?>

                                </div>


                                <div class="product-quantity">

                                    Quantity:

                                    <?= (int)(
                                        $item['quantity']
                                    ) ?>

                                </div>

                            </div>

                            <div class="product-price">

                                Rs.

                                <?= number_format(
                                    $item_total,
                                    2
                                ) ?>

                            </div>


                        </div>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="no-products">

                        No order items found.

                    </div>


                <?php endif; ?>


                <div class="total-box">

                    <span>

                        Total Amount

                    </span>


                    <span>

                        Rs.

                        <?= number_format(
                            (float)$order['total_amount'],
                            2
                        ) ?>

                    </span>

                </div>


            </div>
            <div class="actions">


                <a
                    href="orders.php"
                    class="btn-gold"
                >

                    <i class="fa-solid fa-box"></i>

                    View My Orders

                </a>


                <a
                    href="products.php"
                    class="btn-dark"
                >

                    <i class="fa-solid fa-bag-shopping"></i>

                    Continue Shopping

                </a>


            </div>


        </div>

    </div>

</div>


</body>

</html>