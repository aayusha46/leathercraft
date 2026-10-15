<?php

session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;

}


$user_id = (int) $_SESSION['user_id'];


$orders = [];

$error = '';

$success_message = '';

$total_orders = 0;

$pending_orders = 0;

$completed_orders = 0;

$cancelled_orders = 0;


if (isset($_SESSION['order_success'])) {

    $success_message =
        $_SESSION['order_success'];

    unset(
        $_SESSION['order_success']
    );

}

if (isset($_SESSION['order_error'])) {

    $error =
        $_SESSION['order_error'];

    unset(
        $_SESSION['order_error']
    );

}

function getOrderImage($image)
{

    $image =
        trim(
            $image ?? ''
        );


    if ($image === '') {

        return 'images/no-image.jpg';

    }


    $image =
        str_replace(
            '\\',
            '/',
            $image
        );


    $image =
        ltrim(
            $image,
            '/'
        );


    if (
        strpos(
            $image,
            'http://'
        ) === 0
        ||
        strpos(
            $image,
            'https://'
        ) === 0
    ) {

        return $image;

    }


    if (
        strpos(
            $image,
            'images/'
        ) === 0
    ) {

        return $image;

    }

    return 'images/' . $image;

}

function getOrderStatusClass($status)
{

    $status =
        strtolower(
            trim($status)
        );


    switch ($status) {

        case 'pending':

            return 'status-pending';


        case 'confirmed':

            return 'status-confirmed';


        case 'shipped':

            return 'status-shipped';


        case 'delivered':

            return 'status-delivered';


        case 'cancelled':

            return 'status-cancelled';


        default:

            return 'status-pending';

    }

}


function getPaymentStatusClass($status)
{

    $status =
        strtolower(
            trim($status)
        );


    if ($status === 'paid') {

        return 'payment-paid';

    }


    return 'payment-pending';

}

try {

    $stmt = $pdo->prepare("

        SELECT

            o.id AS order_id,

            o.total_amount,

            o.payment_method,

            o.payment_status,

            o.status,

            o.order_date,


            oi.id AS item_id,

            oi.product_id,

            oi.quantity,

            oi.price,


            p.name AS product_name,

            p.image AS product_image


        FROM orders o


        LEFT JOIN order_items oi

            ON o.id = oi.order_id


        LEFT JOIN products p

            ON oi.product_id = p.id


        WHERE o.user_id = ?


        ORDER BY
            o.id DESC,
            oi.id ASC

    ");


    $stmt->execute([
        $user_id
    ]);


    $rows =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

    foreach (
        $rows
        as $row
    ) {


        $order_id =
            (int)$row['order_id'];

        if (
            !isset(
                $orders[$order_id]
            )
        ) {

            $orders[$order_id] = [

                'order_id' =>
                    $order_id,

                'total_amount' =>
                    (float)
                    $row[
                        'total_amount'
                    ],

                'payment_method' =>
                    $row[
                        'payment_method'
                    ]
                    ??
                    'Not specified',

                'payment_status' =>
                    $row[
                        'payment_status'
                    ]
                    ??
                    'Pending',

                'status' =>
                    $row[
                        'status'
                    ]
                    ??
                    'Pending',

                'order_date' =>
                    $row[
                        'order_date'
                    ]
                    ??
                    '',

                'items' => []

            ];

        }


        if (
            !empty(
                $row['item_id']
            )
        ) {


            $quantity =
                (int)
                $row['quantity'];


            $price =
                (float)
                $row['price'];


            $orders[$order_id]['items'][] = [

                'item_id' =>
                    (int)
                    $row['item_id'],

                'product_id' =>
                    (int)
                    $row['product_id'],

                'product_name' =>
                    $row[
                        'product_name'
                    ]
                    ??
                    'Product',

                'product_image' =>
                    $row[
                        'product_image'
                    ]
                    ??
                    '',

                'quantity' =>
                    $quantity,

                'price' =>
                    $price,

                'item_total' =>
                    $price *
                    $quantity

            ];

        }

    }


    $orders =
        array_values(
            $orders
        );



    $total_orders =
        count(
            $orders
        );


    foreach (
        $orders
        as $order
    ) {


        $status =
            strtolower(
                trim(
                    $order[
                        'status'
                    ]
                    ??
                    ''
                )
            );


        if (
            $status === 'pending'
        ) {

            $pending_orders++;

        }

        elseif (
            $status === 'delivered'
        ) {

            $completed_orders++;

        }

        elseif (
            $status === 'cancelled'
        ) {

            $cancelled_orders++;

        }

    }


} catch (
    PDOException $e
) {

    $error =
        "Could not load orders: "
        .
        $e->getMessage();

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
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<style>

@import url(
'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap'
);


:root {

    --ink:#171411;

    --paper:#f7f5f0;

    --panel:#fff;

    --espresso:#211a15;

    --gold:#c09968;

    --gold-dark:#926b42;

    --muted:#91887d;

    --line:#e6e0d7;

    --green:#4f9663;

    --blue:#4d8ea8;

    --purple:#7663b0;

    --red:#b9554b;

}


* {

    box-sizing:border-box;

}


body {

    margin:0;

    min-height:100vh;

    color:var(--ink);

    background:var(--paper);

    font-family:
        'DM Sans',
        Arial,
        sans-serif;

}

.navbar {

    position:sticky;

    top:0;

    z-index:10;

    display:flex;

    align-items:center;

    justify-content:space-between;

    height:72px;

    padding:
        0 clamp(
            20px,
            6vw,
            86px
        );

    border-bottom:
        1px solid var(--line);

    background:
        rgba(
            255,
            255,
            255,
            .96
        );

    backdrop-filter:
        blur(12px);

}


.logo {

    display:flex;

    align-items:center;

    gap:9px;

    color:var(--espresso);

    font-family:
        'Playfair Display',
        Georgia,
        serif;

    font-size:23px;

    font-weight:600;

    letter-spacing:-.04em;

    text-decoration:none;

}


.logo i {

    color:var(--gold-dark);

}


.logo:hover {

    color:var(--gold-dark);

}


.navbar-links {

    display:flex;

    align-items:center;

    gap:25px;

}


.navbar-links a {

    color:var(--muted);

    font-size:11px;

    text-decoration:none;

    transition:.2s ease;

}


.navbar-links a:hover,
.navbar-links .active {

    color:var(--gold-dark);

}

.container {

    max-width:1220px;

    margin:0 auto;

    padding:
        76px 28px 88px;

}


.page-header {

    margin-bottom:42px;

}


.page-header::before {

    display:block;

    margin-bottom:15px;

    color:var(--gold-dark);

    content:
        'LEATHERCRAFT ACCOUNT';

    font-size:9px;

    font-weight:700;

    letter-spacing:.28em;

}


.page-header h1 {

    margin:
        0 0 13px;

    color:var(--ink);

    font-family:
        'Playfair Display',
        Georgia,
        serif;

    font-size:
        clamp(
            44px,
            6vw,
            70px
        );

    font-weight:500;

    letter-spacing:-.06em;

    line-height:.98;

}


.page-header h1 span {

    color:var(--gold);

    font-style:italic;

}


.page-header p {

    margin:0;

    color:var(--muted);

    font-size:13px;

}

.error-box {

    margin-bottom:24px;

    padding:
        14px 16px;

    color:#8b4437;

    border:
        1px solid #e4c4ba;

    border-radius:5px;

    background:#fff6f3;

    font-size:12px;

}


.success-box {

    margin-bottom:24px;

    padding:
        14px 16px;

    color:#39734a;

    border:
        1px solid #b9d8c1;

    border-radius:5px;

    background:#f1faf3;

    font-size:12px;

}


.success-box i,
.error-box i {

    margin-right:7px;

}
.stats {

    display:grid;

    grid-template-columns:
        repeat(
            4,
            minmax(
                0,
                1fr
            )
        );

    gap:16px;

    margin-bottom:35px;

}


.stat-card {

    display:flex;

    align-items:center;

    gap:15px;

    height:100%;

    padding:20px;

    border:
        1px solid var(--line);

    border-radius:6px;

    background:var(--panel);

    box-shadow:
        0 8px 25px
        rgba(
            33,
            26,
            21,
            .035
        );

    transition:.2s ease;

}


.stat-card:hover {

    border-color:#c6a77e;

    transform:
        translateY(-3px);

}


.stat-icon {

    display:grid;

    place-items:center;

    flex:
        0 0 auto;

    width:44px;

    height:44px;

    border-radius:4px;

    color:var(--gold-dark);

    background:#f3ebe1;

    font-size:17px;

}


.stat-info span {

    display:block;

    margin-bottom:5px;

    color:var(--muted);

    font-size:9px;

    font-weight:700;

    letter-spacing:.12em;

    text-transform:uppercase;

}


.stat-info strong {

    color:var(--ink);

    font-family:
        'Playfair Display',
        Georgia,
        serif;

    font-size:28px;

    font-weight:500;

}
.empty-orders {

    padding:
        75px 20px;

    border:
        1px solid var(--line);

    border-radius:6px;

    background:var(--panel);

    text-align:center;

    box-shadow:
        0 10px 28px
        rgba(
            33,
            26,
            21,
            .035
        );

}


.empty-icon {

    display:grid;

    place-items:center;

    width:76px;

    height:76px;

    margin:
        0 auto 20px;

    color:var(--gold-dark);

    border-radius:50%;

    background:#f3ebe1;

    font-size:29px;

}


.empty-orders h2 {

    margin:
        0 0 10px;

    color:var(--ink);

    font-family:
        'Playfair Display',
        Georgia,
        serif;

    font-size:30px;

    font-weight:500;

}


.empty-orders p {

    margin-bottom:25px;

    color:var(--muted);

    font-size:13px;

}


.shop-btn {

    display:inline-flex;

    align-items:center;

    gap:8px;

    padding:
        13px 21px;

    color:#fff;

    border:
        1px solid var(--espresso);

    border-radius:4px;

    background:var(--espresso);

    font-size:10px;

    font-weight:700;

    letter-spacing:.08em;

    text-decoration:none;

    text-transform:uppercase;

}


.shop-btn:hover {

    color:#fff;

    background:var(--gold-dark);

}
.order-card {

    margin-bottom:22px;

    overflow:hidden;

    border:
        1px solid var(--line);

    border-radius:6px;

    background:var(--panel);

    box-shadow:
        0 10px 28px
        rgba(
            33,
            26,
            21,
            .035
        );

}


.order-header {

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

    padding:
        19px 22px;

    border-bottom:
        1px solid var(--line);

    background:#fbfaf7;

}


.order-info {

    display:flex;

    align-items:center;

    gap:25px;

    flex-wrap:wrap;

}


.order-id {

    color:var(--gold-dark);

    font-size:14px;

    font-weight:700;

}


.order-date {

    color:var(--muted);

    font-size:11px;

}


.order-date i {

    margin-right:5px;

    color:var(--gold-dark);

}
.status {

    display:inline-flex;

    align-items:center;

    gap:6px;

    padding:
        6px 11px;

    border-radius:20px;

    font-size:9px;

    font-weight:700;

    text-transform:capitalize;

}


.status-pending {

    color:#9a7821;

    background:#f6eed7;

}


.status-confirmed {

    color:var(--blue);

    background:#e3f0f5;

}


.status-shipped {

    color:var(--purple);

    background:#eeeaf8;

}


.status-delivered {

    color:var(--green);

    background:#e5f2e8;

}


.status-cancelled {

    color:var(--red);

    background:#f8e6e3;

}
.order-body {

    padding:
        20px 22px;

}


.order-item {

    display:flex;

    align-items:center;

    gap:16px;

    padding:
        15px 0;

    border-bottom:
        1px solid var(--line);

}


.order-item:last-child {

    border-bottom:0;

}


.product-image {

    display:block;

    flex:
        0 0 auto;

    width:88px;

    height:88px;

    padding:7px;

    border:
        1px solid var(--line);

    border-radius:4px;

    background:#fff;

    object-fit:contain;

}


.product-details {

    flex:1;

}


.product-name {

    margin-bottom:7px;

    color:var(--ink);

    font-size:13px;

    font-weight:700;

}


.product-meta {

    color:var(--muted);

    font-size:11px;

    line-height:1.8;

}


.product-meta strong {

    color:#625a51;

}


.item-total {

    color:var(--gold-dark);

    font-size:14px;

    font-weight:700;

    white-space:nowrap;

}

.order-footer {

    display:flex;

    align-items:flex-end;

    justify-content:space-between;

    gap:25px;

    padding:
        20px 22px;

    border-top:
        1px solid var(--line);

    flex-wrap:wrap;

}


.payment-info {

    display:flex;

    flex-direction:column;

    gap:7px;

}


.payment-label,
.total-label {

    color:var(--muted);

    font-size:9px;

    font-weight:700;

    letter-spacing:.13em;

    text-transform:uppercase;

}


.payment-method {

    color:var(--ink);

    font-size:12px;

    font-weight:700;

}


.payment-status {

    display:inline-block;

    width:fit-content;

    padding:
        5px 10px;

    border-radius:20px;

    font-size:9px;

    font-weight:700;

}


.payment-paid {

    color:var(--green);

    background:#e5f2e8;

}


.payment-pending {

    color:#9a7821;

    background:#f6eed7;

}


.total-section {

    text-align:right;

}


.total-amount {

    color:var(--gold-dark);

    font-family:
        'Playfair Display',
        Georgia,
        serif;

    font-size:28px;

    font-weight:500;

}
.order-actions {

    display:flex;

    align-items:center;

    gap:10px;

    margin-top:18px;

}


.cancel-btn {

    display:inline-flex;

    align-items:center;

    gap:8px;

    padding:
        10px 16px;

    color:var(--red);

    border:
        1px solid #e4b8b2;

    border-radius:4px;

    background:#fff7f5;

    font-size:10px;

    font-weight:700;

    letter-spacing:.06em;

    text-decoration:none;

    text-transform:uppercase;

    transition:.2s ease;

}


.cancel-btn:hover {

    color:#fff;

    border-color:var(--red);

    background:var(--red);

}


.cancelled-label {

    display:inline-flex;

    align-items:center;

    gap:8px;

    padding:
        10px 16px;

    color:var(--red);

    border-radius:4px;

    background:#f8e6e3;

    font-size:10px;

    font-weight:700;

    text-transform:uppercase;

}


.cannot-cancel {

    display:inline-flex;

    align-items:center;

    gap:8px;

    color:var(--muted);

    font-size:10px;

    font-weight:600;

}
.back-shop {

    margin-top:30px;

    text-align:center;

}


.back-shop a {

    color:var(--gold-dark);

    font-size:12px;

    text-decoration:none;

}


.back-shop a:hover {

    text-decoration:underline;

}
.footer {

    padding:
        25px 20px;

    color:var(--muted);

    border-top:
        1px solid var(--line);

    background:#fff;

    font-size:11px;

    text-align:center;

}


.footer span {

    color:var(--gold-dark);

}

@media (max-width:900px) {

    .stats {

        grid-template-columns:
            repeat(
                2,
                minmax(
                    0,
                    1fr
                )
            );

    }

}


@media (max-width:650px) {

    .navbar {

        height:auto;

        padding:
            16px 20px;

        flex-direction:column;

        gap:12px;

    }


    .navbar-links {

        gap:17px;

    }


    .container {

        padding:
            48px 17px 65px;

    }


    .page-header h1 {

        font-size:48px;

    }


    .stats {

        grid-template-columns:1fr;

    }


    .order-header {

        align-items:flex-start;

        flex-direction:column;

    }


    .order-info {

        gap:12px;

        flex-direction:column;

        align-items:flex-start;

    }


    .order-item {

        align-items:flex-start;

    }


    .product-image {

        width:70px;

        height:70px;

    }


    .item-total {

        font-size:12px;

    }


    .order-footer {

        align-items:flex-start;

        flex-direction:column;

    }


    .total-section {

        text-align:left;

    }

}

</style>

</head>


<body>

<nav class="navbar">


<a
    href="index.php"
    class="logo"
>

<i class="fa-solid fa-shoe-prints"></i>

LeatherCraft

</a>


<div class="navbar-links">


<a href="index.php">

<i class="fa-solid fa-house"></i>

Home

</a>


<a href="products.php">

<i class="fa-solid fa-bag-shopping"></i>

Shop

</a>


<a href="cart.php">

<i class="fa-solid fa-cart-shopping"></i>

Cart

</a>


<a
    href="orders.php"
    class="active"
>

<i class="fa-solid fa-box"></i>

My Orders

</a>


<a href="profile.php">

<i class="fa-solid fa-user"></i>

Profile

</a>


</div>

</nav>


<main class="container">


<div class="page-header">

<h1>

My
<span>Orders</span>

</h1>


<p>

View your LeatherCraft order history and track your orders.

</p>

</div>

<?php if ($success_message !== ''): ?>

<div class="success-box">

<i class="fa-solid fa-circle-check"></i>

<?= htmlspecialchars(
    $success_message
) ?>

</div>

<?php endif; ?>

<?php if ($error !== ''): ?>

<div class="error-box">

<i class="fa-solid fa-circle-exclamation"></i>

<?= htmlspecialchars(
    $error
) ?>

</div>

<?php endif; ?>



<?php if ($error === ''): ?>

<div class="stats">


<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-box"></i>

</div>


<div class="stat-info">

<span>
Total Orders
</span>

<strong>
<?= $total_orders ?>
</strong>

</div>

</div>



<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-clock"></i>

</div>


<div class="stat-info">

<span>
Pending
</span>

<strong>
<?= $pending_orders ?>
</strong>

</div>

</div>



<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-circle-check"></i>

</div>


<div class="stat-info">

<span>
Delivered
</span>

<strong>
<?= $completed_orders ?>
</strong>

</div>

</div>



<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-circle-xmark"></i>

</div>


<div class="stat-info">

<span>
Cancelled
</span>

<strong>
<?= $cancelled_orders ?>
</strong>

</div>

</div>


</div>

<?php if (empty($orders)): ?>


<div class="empty-orders">


<div class="empty-icon">

<i class="fa-solid fa-box-open"></i>

</div>


<h2>

No Orders Yet

</h2>


<p>

You haven't placed any orders yet.

Start shopping for your perfect pair of leather shoes.

</p>


<a
    href="products.php"
    class="shop-btn"
>

<i class="fa-solid fa-bag-shopping"></i>

Start Shopping

</a>


</div>


<?php else: ?>

<?php foreach (
    $orders
    as $order
): ?>


<?php

$status =
    trim(
        $order['status']
        ??
        'Pending'
    );


$status_lower =
    strtolower(
        $status
    );


$status_class =
    getOrderStatusClass(
        $status
    );


$payment_status =
    trim(
        $order['payment_status']
        ??
        'Pending'
    );


$payment_class =
    getPaymentStatusClass(
        $payment_status
    );

?>


<div class="order-card">

<div class="order-header">


<div class="order-info">


<div class="order-id">

Order #

<?= (int)
    $order['order_id']
?>

</div>


<div class="order-date">

<i class="fa-regular fa-calendar"></i>

<?php

if (
    !empty(
        $order['order_date']
    )
) {

    $timestamp =
        strtotime(
            $order['order_date']
        );


    if (
        $timestamp !== false
    ) {

        echo htmlspecialchars(
            date(
                'M d, Y h:i A',
                $timestamp
            )
        );

    } else {

        echo htmlspecialchars(
            $order['order_date']
        );

    }

} else {

    echo 'Date unavailable';

}

?>

</div>


</div>

<span
    class="
        status
        <?= htmlspecialchars(
            $status_class
        ) ?>
    "
>


<?php

if (
    $status_lower ===
    'pending'
) {

    echo
        '<i class="fa-solid fa-clock"></i>';

}

elseif (
    $status_lower ===
    'confirmed'
) {

    echo
        '<i class="fa-solid fa-check"></i>';

}

elseif (
    $status_lower ===
    'shipped'
) {

    echo
        '<i class="fa-solid fa-truck"></i>';

}

elseif (
    $status_lower ===
    'delivered'
) {

    echo
        '<i class="fa-solid fa-circle-check"></i>';

}

elseif (
    $status_lower ===
    'cancelled'
) {

    echo
        '<i class="fa-solid fa-circle-xmark"></i>';

}

?>


<?= htmlspecialchars(
    $status
) ?>


</span>


</div>


<div class="order-body">


<?php if (
    !empty(
        $order['items']
    )
): ?>


<?php foreach (
    $order['items']
    as $item
): ?>


<div class="order-item">


<img
    src="<?= htmlspecialchars(
        getOrderImage(
            $item[
                'product_image'
            ]
        )
    ) ?>"
    alt="<?= htmlspecialchars(
        $item[
            'product_name'
        ]
    ) ?>"
    class="product-image"
    onerror="
        this.onerror=null;
        this.src='images/no-image.jpg';
    "
>

<div class="product-details">


<div class="product-name">

<?= htmlspecialchars(
    $item[
        'product_name'
    ]
) ?>

</div>


<div class="product-meta">

<strong>
Quantity:
</strong>

<?= (int)
    $item[
        'quantity'
    ]
?>

<br>


<strong>
Price:
</strong>

Rs.

<?= number_format(
    (float)
    $item[
        'price'
    ],
    2
) ?>


</div>


</div>


<div class="item-total">

Rs.

<?= number_format(
    (float)
    $item[
        'item_total'
    ],
    2
) ?>

</div>


</div>


<?php endforeach; ?>


<?php else: ?>


<div
    style="
        color:#777;
        padding:15px 0;
    "
>

No product items found for this order.

</div>


<?php endif; ?>


</div>

<div class="order-footer">


<div class="payment-info">


<div class="payment-label">

Payment Method

</div>


<div class="payment-method">

<i
    class="fa-solid fa-credit-card"
    style="color:#d4af37;"
></i>


<?= htmlspecialchars(
    $order[
        'payment_method'
    ]
    ??
    'Not specified'
) ?>

</div>


<div>


<span
    class="
        payment-status
        <?= htmlspecialchars(
            $payment_class
        ) ?>
    "
>

Payment:

<?= htmlspecialchars(
    $payment_status
) ?>

</span>


</div>

<div class="order-actions">


<?php if (
    $status_lower ===
        'pending'
    ||
    $status_lower ===
        'confirmed'
): ?>


<a
    href="cancel_order.php?id=<?= (int)$order['order_id'] ?>"
    class="cancel-btn"
    onclick="
        return confirm(
            'Are you sure you want to cancel Order #<?= (int)$order['order_id'] ?>?'
        );
    "
>

<i class="fa-solid fa-ban"></i>

Cancel Order

</a>


<?php elseif (
    $status_lower ===
    'cancelled'
): ?>


<span class="cancelled-label">

<i class="fa-solid fa-circle-xmark"></i>

Order Cancelled

</span>


<?php else: ?>


<span class="cannot-cancel">

<i class="fa-solid fa-lock"></i>

Cancellation unavailable

</span>


<?php endif; ?>


</div>


</div>

<div class="total-section">


<div class="total-label">

Order Total

</div>


<div class="total-amount">

Rs.

<?= number_format(
    (float)
    $order[
        'total_amount'
    ],
    2
) ?>

</div>


</div>


</div>


</div>


<?php endforeach; ?>


<?php endif; ?>


<?php endif; ?>

<div class="back-shop">

<a
    href="products.php"
>

<i class="fa-solid fa-arrow-left"></i>

Continue Shopping

</a>

</div>


</main>

<footer class="footer">

© <?= date('Y') ?>

<span>
LeatherCraft
</span>

— Crafted for Style. Made for Comfort.

</footer>


</body>

</html>