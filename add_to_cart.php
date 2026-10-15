<?php

session_start();
require_once 'database.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity   = (int)($_POST['quantity'] ?? 1);
$size       = trim($_POST['size'] ?? '');
$color      = trim($_POST['color'] ?? '');

if ($product_id <= 0) {
    header("Location: products.php");
    exit;
}


if ($quantity < 1) {
    $quantity = 1;
}


if ($size === '') {
    header(
        "Location: product_details.php?id=" .
        $product_id .
        "&error=size"
    );
    exit;
}


if ($color === '') {
    header(
        "Location: product_details.php?id=" .
        $product_id .
        "&error=color"
    );
    exit;
}


try {

    $stmt = $pdo->prepare("
        SELECT
            p.*,
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c
            ON p.category_id = c.id
        WHERE p.id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $product_id
    ]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Unable to add product to cart.");

}


if (!$product) {
    header("Location: products.php");
    exit;
}

$stock = (int)($product['stock'] ?? 0);

if ($stock <= 0) {

    header(
        "Location: product_details.php?id=" .
        $product_id .
        "&error=outofstock"
    );

    exit;
}

$product_sizes = [];

if (!empty($product['size'])) {

    $product_sizes = array_filter(
        array_map(
            'trim',
            explode(',', $product['size'])
        )
    );
}

if (!empty($product_sizes)) {

    $valid_size = false;

    foreach ($product_sizes as $available_size) {

        if (strcasecmp($available_size, $size) === 0) {

            $valid_size = true;

            // Use exact database value
            $size = $available_size;

            break;
        }
    }

    if (!$valid_size) {

        header(
            "Location: product_details.php?id=" .
            $product_id .
            "&error=size"
        );

        exit;
    }
}


$product_colors = [];

if (!empty($product['color'])) {

    $product_colors = array_filter(
        array_map(
            'trim',
            explode(',', $product['color'])
        )
    );
}

if (!empty($product_colors)) {

    $valid_color = false;

    foreach ($product_colors as $available_color) {

        if (strcasecmp($available_color, $color) === 0) {

            $valid_color = true;

            // Use exact database value
            $color = $available_color;

            break;
        }
    }

    if (!$valid_color) {

        header(
            "Location: product_details.php?id=" .
            $product_id .
            "&error=color"
        );

        exit;
    }
}


if ($quantity > $stock) {
    $quantity = $stock;
}


if (
    !isset($_SESSION['cart']) ||
    !is_array($_SESSION['cart'])
) {

    $_SESSION['cart'] = [];

}


$size_key = strtolower(
    preg_replace(
        '/[^a-zA-Z0-9_-]/',
        '-',
        $size
    )
);

$color_key = strtolower(
    preg_replace(
        '/[^a-zA-Z0-9_-]/',
        '-',
        $color
    )
);

$cart_key =
    $product_id .
    '_' .
    $size_key .
    '_' .
    $color_key;


if (isset($_SESSION['cart'][$cart_key])) {

    $current_quantity =
        (int)($_SESSION['cart'][$cart_key]['quantity'] ?? 0);

    $new_quantity =
        $current_quantity + $quantity;

    if ($new_quantity > $stock) {
        $new_quantity = $stock;
    }

    $_SESSION['cart'][$cart_key]['quantity'] =
        $new_quantity;

} else {

    $_SESSION['cart'][$cart_key] = [

        'product_id' => $product_id,

        'name' => $product['name'],

        'price' => (float)$product['price'],

        'image' => $product['image'] ?? '',

        'size' => $size,

        'color' => $color,

        'quantity' => $quantity,

        'category' =>
            $product['category_name'] ?? ''

    ];
}


$_SESSION['cart_message'] =
    $product['name'] . " has been added to your cart.";


header("Location: cart.php");
exit;

?>