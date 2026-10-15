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


if (isset($_GET['delete'])) {

    $reviewId = (int)($_GET['delete'] ?? 0);

    if ($reviewId > 0) {

        try {

            $stmt = $pdo->prepare("
                DELETE FROM reviews
                WHERE id = ?
            ");

            $stmt->execute([$reviewId]);


            if ($stmt->rowCount() > 0) {

                header("Location: reviews.php?deleted=1");
                exit;

            } else {

                $error = "Review not found.";
            }


        } catch (PDOException $e) {

            $error =
                "Could not delete review: "
                . $e->getMessage();
        }
    }
}

if (isset($_GET['deleted'])) {

    $success = "Review deleted successfully.";
}


$search = trim($_GET['search'] ?? '');




$totalReviews = 0;
$averageRating = 0;
$fiveStarReviews = 0;
$oneStarReviews = 0;


try {

    $totalReviews = (int)$pdo->query("
        SELECT COUNT(*)
        FROM reviews
    ")->fetchColumn();


    $averageRating = (float)$pdo->query("
        SELECT COALESCE(AVG(rating), 0)
        FROM reviews
    ")->fetchColumn();


    $fiveStarReviews = (int)$pdo->query("
        SELECT COUNT(*)
        FROM reviews
        WHERE rating = 5
    ")->fetchColumn();


    $oneStarReviews = (int)$pdo->query("
        SELECT COUNT(*)
        FROM reviews
        WHERE rating = 1
    ")->fetchColumn();


} catch (PDOException $e) {

    $error =
        "Could not load review statistics: "
        . $e->getMessage();
}


$reviews = [];


try {

    $sql = "
        SELECT
            r.id,
            r.user_id,
            r.product_id,
            r.rating,
            r.comment,
            r.created_at,

            u.name AS customer_name,
            u.email AS customer_email,

            p.name AS product_name,
            p.image AS product_image

        FROM reviews r

        LEFT JOIN users u
            ON r.user_id = u.id

        LEFT JOIN products p
            ON r.product_id = p.id

        WHERE 1 = 1
    ";


    $params = [];

    if ($search !== '') {

        $sql .= "
            AND (
                u.name LIKE :search
                OR u.email LIKE :search
                OR p.name LIKE :search
                OR r.comment LIKE :search
            )
        ";

        $params[':search'] = '%' . $search . '%';
    }


    $sql .= "
        ORDER BY r.id DESC
    ";


    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    $error =
        "Could not load reviews: "
        . $e->getMessage();
}
function displayStars($rating)
{
    $rating = (int)$rating;

    $html = '';

    for ($i = 1; $i <= 5; $i++) {

        if ($i <= $rating) {

            $html .= '<i class="fa-solid fa-star star-filled"></i>';

        } else {

            $html .= '<i class="fa-regular fa-star star-empty"></i>';
        }
    }

    return $html;
}


function getProductImage($image)
{
    $image = trim($image ?? '');

    if ($image === '') {
        return '';
    }


    if (
        strpos($image, 'http://') === 0 ||
        strpos($image, 'https://') === 0
    ) {
        return $image;
    }


    if (strpos($image, 'images/') === 0) {

        return '../' . $image;
    }


    return '../images/' . ltrim($image, '/');
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

    <title>Reviews | LeatherCraft Admin</title>


    <!-- Bootstrap -->

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
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap');

:root { --ink:#171411; --paper:#f7f5f0; --panel:#fff; --sidebar:#211a15; --gold:#c09968; --gold-bright:#d8b486; --muted:#91887d; --line:#e6e0d7; --green:#4f9663; --red:#b9554b; --serif:'Playfair Display',Georgia,serif; --sans:'DM Sans',Arial,sans-serif; }
* { box-sizing:border-box; }
body { margin:0; min-height:100vh; color:var(--ink); background:var(--paper); font-family:var(--sans); }
.sidebar { position:fixed; inset:0 auto 0 0; z-index:5; width:256px; height:100vh; padding:32px 18px 24px; border-right:1px solid rgba(255,255,255,.08); background:linear-gradient(160deg,#35271d,var(--sidebar) 48%,#15110e); box-shadow:12px 0 35px rgba(26,18,12,.08); }
.logo { display:flex; align-items:center; gap:10px; padding:0 16px 38px; color:#fff; font-family:var(--serif); font-size:23px; font-weight:600; letter-spacing:-.04em; text-decoration:none; }
.logo i { display:grid; place-items:center; width:32px; height:32px; border-radius:50%; color:var(--sidebar); background:var(--gold-bright); font-size:14px; }
.admin-info { margin-bottom:27px; padding:17px 12px; border:1px solid rgba(255,255,255,.1); border-radius:6px; background:rgba(255,255,255,.06); text-align:center; }
.admin-info i { margin-bottom:8px; color:var(--gold-bright); font-size:26px; }
.admin-info p { margin:0; color:rgba(255,255,255,.45); font-size:9px; letter-spacing:.14em; text-transform:uppercase; }
.admin-info strong { display:block; margin-top:5px; color:#fff; font-size:12px; }
.sidebar-menu { margin:0; padding:0; list-style:none; }
.sidebar-menu li { margin-bottom:5px; }
.sidebar-menu a { display:flex; align-items:center; gap:14px; padding:13px 16px; color:rgba(255,255,255,.62); border:1px solid transparent; border-radius:5px; font-size:12px; text-decoration:none; transition:.2s ease; }
.sidebar-menu a:hover,.sidebar-menu a.active { color:#fff; border-color:rgba(192,153,104,.25); background:rgba(192,153,104,.14); }
.sidebar-menu a.active { box-shadow:inset 3px 0 var(--gold-bright); }
.sidebar-menu i { width:18px; color:var(--gold-bright); text-align:center; }
.logout-link { margin-top:25px!important; padding-top:18px; border-top:1px solid rgba(255,255,255,.08); }
.logout-link a { color:#d88b83; }
.logout-link a i { color:#d88b83; }
.logout-link a:hover { border-color:rgba(216,139,131,.22); background:rgba(185,85,75,.12); }

.main { min-height:100vh; margin-left:256px; padding:38px clamp(24px,4vw,64px) 70px; }
.topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:30px; margin-bottom:35px; }
.topbar h1 { margin:0; color:var(--ink); font-family:var(--serif); font-size:clamp(38px,4vw,56px); font-weight:500; letter-spacing:-.06em; line-height:.98; }
.topbar h1::before { display:block; margin-bottom:12px; color:var(--gold); content:'LEATHERCRAFT ADMIN'; font-family:var(--sans); font-size:9px; font-weight:700; letter-spacing:.28em; }
.topbar p { margin:17px 0 0; color:var(--muted); font-size:13px; }
.back-btn { display:inline-flex; align-items:center; gap:9px; padding:11px 15px; color:#926b42; border:1px solid #d6c4ac; border-radius:4px; background:transparent; font-size:10px; font-weight:700; letter-spacing:.05em; text-decoration:none; text-transform:uppercase; }
.back-btn:hover { color:#fff; border-color:var(--sidebar); background:var(--sidebar); }
.alert { margin-bottom:20px; border-radius:5px; font-size:12px; }

.stats-grid { margin-bottom:30px; }
.stat-card { height:100%; padding:20px; border:1px solid var(--line); border-radius:6px; background:var(--panel); box-shadow:0 8px 25px rgba(33,26,21,.035); transition:.2s ease; }
.stat-card:hover { border-color:#c6a77e; transform:translateY(-3px); }
.stat-icon { display:grid; place-items:center; width:42px; height:42px; margin-bottom:18px; border-radius:4px; color:#926b42; background:#f3ebe1; font-size:17px; }
.stat-card h3 { margin:0; color:var(--ink); font-family:var(--serif); font-size:31px; font-weight:500; }
.stat-card p { margin:7px 0 0; color:var(--muted); font-size:11px; }
.admin-card { margin-top:35px; padding:0; overflow:hidden; border:1px solid var(--line); border-radius:6px; background:var(--panel); box-shadow:0 8px 25px rgba(33,26,21,.035); }
.card-title { margin:0; padding:21px 22px; color:var(--ink); border-bottom:1px solid var(--line); font-family:var(--serif); font-size:22px; font-weight:500; }
.card-title::before { display:block; margin-bottom:8px; color:var(--gold); content:'CUSTOMER FEEDBACK'; font-family:var(--sans); font-size:9px; font-weight:700; letter-spacing:.24em; }
.search-form { display:flex; gap:10px; margin:0; padding:20px 22px; border-bottom:1px solid var(--line); }
.search-input { flex:1; min-height:42px; padding:10px 13px; color:var(--ink); border:1px solid var(--line); border-radius:4px; outline:none; background:#fff; font-size:12px; }
.search-input:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(192,153,104,.13); }
.search-input::placeholder { color:#aaa39a; }
.search-btn { min-height:42px; padding:0 20px; color:#fff; border:1px solid var(--sidebar); border-radius:4px; background:var(--sidebar); font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
.search-btn:hover { background:#926b42; }
.clear-btn { display:flex; align-items:center; padding:0 15px; color:var(--muted); border:1px solid var(--line); border-radius:4px; font-size:11px; text-decoration:none; }
.clear-btn:hover { color:var(--ink); border-color:#c6a77e; }
.table-wrapper { overflow-x:auto; }
.reviews-table { width:100%; min-width:1050px; border-collapse:collapse; }
.reviews-table th { padding:14px 18px; color:var(--muted); border-bottom:1px solid var(--line); background:#fbfaf7; font-size:9px; font-weight:700; letter-spacing:.13em; text-align:left; text-transform:uppercase; white-space:nowrap; }
.reviews-table td { padding:15px 18px; color:#4f4841; border-bottom:1px solid #f0ece6; font-size:11px; vertical-align:middle; }
.reviews-table tr:last-child td { border-bottom:0; }
.reviews-table tr:hover td { background:#fdfcf9; }
.product-cell,.customer-cell { display:flex; align-items:center; gap:12px; }
.product-cell { min-width:220px; }
.product-image,.no-image { flex:0 0 auto; width:65px; height:60px; border-radius:4px; }
.product-image { padding:4px; border:1px solid var(--line); background:#fff; object-fit:contain; }
.no-image { display:grid; place-items:center; color:var(--muted); background:#f0ece6; }
.product-name,.customer-name { color:var(--ink); font-weight:700; }
.product-id { margin-top:3px; color:var(--muted); font-size:10px; }
.customer-cell { min-width:200px; }
.customer-avatar { display:grid; place-items:center; flex:0 0 auto; width:40px; height:40px; color:#926b42; border:1px solid #dfccb4; border-radius:50%; background:#f5ede4; font-weight:700; }
.customer-email { margin-top:3px; color:var(--muted); font-size:10px; }
.stars { margin-bottom:4px; white-space:nowrap; }
.star-filled { color:var(--gold); font-size:13px; }
.star-empty { color:#d8d1c8; font-size:13px; }
.rating-number { color:var(--muted); font-size:10px; }
.comment { max-width:320px; color:#625a51; font-size:11px; line-height:1.6; }
.review-date { color:var(--muted); font-size:10px; white-space:nowrap; }
.delete-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 11px; color:#fff; border-radius:4px; background:var(--red); font-size:10px; text-decoration:none; }
.delete-btn:hover { color:#fff; background:#8e342d; }
.empty { padding:70px 20px!important; color:var(--muted)!important; text-align:center; }
.empty i { display:block; margin-bottom:15px; color:var(--gold); font-size:42px; }

@media (max-width:900px) { .sidebar { width:210px; } .main { margin-left:210px; padding:28px 24px 55px; } }
@media (max-width:700px) { .sidebar { position:relative; width:100%; height:auto; } .logout-link { margin-top:20px!important; } .main { margin-left:0; padding:25px 18px 50px; } .topbar { flex-direction:column; align-items:flex-start; gap:18px; } .back-btn { align-self:stretch; justify-content:center; } .search-form { flex-direction:column; } .search-btn,.clear-btn { min-height:42px; justify-content:center; } }
</style>

</head>


<body>

<aside class="sidebar">


    <a
        href="dashboard.php"
        class="logo"
    >

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

            <a href="orders.php">

                <i class="fa-solid fa-cart-shopping"></i>

                Orders

            </a>

        </li>


        <li>

            <a href="customers.php">

                <i class="fa-solid fa-users"></i>

                Customers

            </a>

        </li>


        <li>

            <a
                href="reviews.php"
                class="active"
            >

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


    <!-- TOPBAR -->

    <div class="topbar">


        <div>

            <h1>

                Shoe Reviews

            </h1>


            <p>

                View and manage customer reviews.

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


        <!-- TOTAL -->

        <div class="col-md-3 col-6">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-comments"></i>

                </div>


                <h3>

                    <?= $totalReviews ?>

                </h3>


                <p>

                    Total Reviews

                </p>

            </div>

        </div>


        <div class="col-md-3 col-6">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-star"></i>

                </div>


                <h3>

                    <?= number_format(
                        $averageRating,
                        1
                    ) ?>

                    / 5

                </h3>


                <p>

                    Average Rating

                </p>

            </div>

        </div>

        <div class="col-md-3 col-6">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-face-smile"></i>

                </div>


                <h3>

                    <?= $fiveStarReviews ?>

                </h3>


                <p>

                    5-Star Reviews

                </p>

            </div>

        </div>


        <div class="col-md-3 col-6">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-face-frown"></i>

                </div>


                <h3>

                    <?= $oneStarReviews ?>

                </h3>


                <p>

                    1-Star Reviews

                </p>

            </div>

        </div>


    </div>

    <div class="admin-card">


        <div class="card-title">

            <i class="fa-solid fa-star"></i>

            Customer Shoe Reviews

        </div>

        <form
            method="GET"
            class="search-form"
        >


            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Search by customer, product or review..."
                value="<?= htmlspecialchars($search) ?>"
            >


            <button
                type="submit"
                class="search-btn"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

                Search

            </button>


            <?php if ($search !== ''): ?>

                <a
                    href="reviews.php"
                    class="clear-btn"
                >

                    <i class="fa-solid fa-xmark"></i>

                    Clear

                </a>

            <?php endif; ?>


        </form>

        <div class="table-wrapper">


            <table class="reviews-table">


                <thead>

                    <tr>

                        <th>
                            Product
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Rating
                        </th>

                        <th>
                            Review
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (!empty($reviews)): ?>


                        <?php foreach ($reviews as $review): ?>


                            <?php

                            $customerName =
                                $review['customer_name']
                                ?? 'Unknown Customer';


                            $productName =
                                $review['product_name']
                                ?? 'Unknown Product';


                            $rating =
                                (int)(
                                    $review['rating']
                                    ?? 0
                                );


                            $productImage =
                                getProductImage(
                                    $review['product_image']
                                    ?? ''
                                );


                            $firstLetter =
                                strtoupper(
                                    substr(
                                        $customerName,
                                        0,
                                        1
                                    )
                                );

                            ?>


                            <tr>

                                <td>


                                    <div class="product-cell">


                                        <?php if ($productImage !== ''): ?>


                                            <img
                                                src="<?= htmlspecialchars($productImage) ?>"
                                                alt="<?= htmlspecialchars($productName) ?>"
                                                class="product-image"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                            >


                                            <div
                                                class="no-image"
                                                style="display:none;"
                                            >

                                                <i class="fa-solid fa-shoe-prints"></i>

                                            </div>


                                        <?php else: ?>


                                            <div class="no-image">

                                                <i class="fa-solid fa-shoe-prints"></i>

                                            </div>


                                        <?php endif; ?>


                                        <div>


                                            <div class="product-name">

                                                <?= htmlspecialchars(
                                                    $productName
                                                ) ?>

                                            </div>


                                            <div class="product-id">

                                                Product ID:
                                                #<?= (int)$review['product_id'] ?>

                                            </div>


                                        </div>


                                    </div>


                                </td>
                                <td>


                                    <div class="customer-cell">


                                        <div class="customer-avatar">

                                            <?= htmlspecialchars(
                                                $firstLetter
                                            ) ?>

                                        </div>


                                        <div>


                                            <div class="customer-name">

                                                <?= htmlspecialchars(
                                                    $customerName
                                                ) ?>

                                            </div>


                                            <div class="customer-email">

                                                <?= htmlspecialchars(
                                                    $review['customer_email']
                                                    ?? 'No email'
                                                ) ?>

                                            </div>


                                        </div>


                                    </div>


 </td>

                                <td>


                                    <div class="stars">

                                        <?= displayStars($rating) ?>

                                    </div>


                                    <span class="rating-number">

                                        <?= $rating ?>/5

                                    </span>


                                </td>

                                <td>


                                    <div class="comment">

                                        <?= nl2br(
                                            htmlspecialchars(
                                                $review['comment']
                                                ?? ''
                                            )
                                        ) ?>

                                    </div>


                                </td>

                                <td>


                                    <span class="review-date">


                                        <?php

                                        if (!empty($review['created_at'])) {

                                            echo htmlspecialchars(
                                                date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $review['created_at']
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


                                    <a
                                        href="reviews.php?delete=<?= (int)$review['id'] ?>"
                                        class="delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this review?');"
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
                                colspan="6"
                                class="empty"
                            >

                                <i class="fa-solid fa-star"></i>

                                <br>

                                <?php if ($search !== ''): ?>

                                    No reviews found for:

                                    <strong>

                                        <?= htmlspecialchars($search) ?>

                                    </strong>

                                <?php else: ?>

                                    No customer reviews found.

                                <?php endif; ?>


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