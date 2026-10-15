<?php

session_start();

include 'database.php';


if (!isset($_SESSION['user_id'])) {

    $_SESSION['review_error'] =
        "Please login to write a review.";

    header("Location: login.php");

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: products.php");

    exit;
}

$product_id =
    isset($_POST['product_id'])
        ? (int)$_POST['product_id']
        : 0;

$rating =
    isset($_POST['rating'])
        ? (int)$_POST['rating']
        : 0;

$review =
    trim($_POST['review'] ?? '');

$user_id =
    (int)$_SESSION['user_id'];


if ($product_id <= 0) {

    $_SESSION['review_error'] =
        "Invalid product.";

    header("Location: products.php");

    exit;
}


if ($rating < 1 || $rating > 5) {

    $_SESSION['review_error'] =
        "Please select a rating from 1 to 5 stars.";

    header(
        "Location: product_detail.php?id="
        . $product_id
    );

    exit;
}


if ($review === '') {

    $_SESSION['review_error'] =
        "Please enter your review.";

    header(
        "Location: product_detail.php?id="
        . $product_id
    );

    exit;
}


if (strlen($review) < 5) {

    $_SESSION['review_error'] =
        "Review must contain at least 5 characters.";

    header(
        "Location: product_detail.php?id="
        . $product_id
    );

    exit;
}
$stmt = $pdo->prepare("
    SELECT id
    FROM products
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $product_id
]);

$product = $stmt->fetch();


if (!$product) {

    $_SESSION['review_error'] =
        "Product not found.";

    header("Location: products.php");

    exit;
}

$stmt = $pdo->prepare("
    SELECT id
    FROM reviews
    WHERE user_id = ?
      AND product_id = ?
    LIMIT 1
");

$stmt->execute([
    $user_id,
    $product_id
]);


$existing_review = $stmt->fetch();


if ($existing_review) {

    $_SESSION['review_error'] =
        "You have already reviewed this product.";

    header(
        "Location: product_detail.php?id="
        . $product_id
    );

    exit;
}


$stmt = $pdo->prepare("
    INSERT INTO reviews
    (
        product_id,
        user_id,
        rating,
        review
    )

    VALUES
    (
        ?,
        ?,
        ?,
        ?
    )
");


$stmt->execute([
    $product_id,
    $user_id,
    $rating,
    $review
]);



$_SESSION['review_success'] =
    "Your review has been added successfully!";



header(
    "Location: product_detail.php?id="
    . $product_id
);

exit;

?>