<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit();
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$customer_name = trim($_POST['customer_name'] ?? '');
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$review = trim($_POST['review'] ?? '');

if ($product_id <= 0) {
    die("Invalid product.");
}

if ($customer_name === '') {
    die("Please enter your name.");
}

if ($rating < 1 || $rating > 5) {
    die("Please select a rating between 1 and 5.");
}

if ($review === '') {
    die("Please write a review.");
}

try {

    $stmt = $pdo->prepare("
        INSERT INTO reviews
        (product_id, customer_name, rating, review)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $product_id,
        $customer_name,
        $rating,
        $review
    ]);

    header("Location: product_details.php?id=" . $product_id . "&review=success");
    exit();

} catch (PDOException $e) {

    die("Error submitting review: " . $e->getMessage());
}
?>