<?php

session_start();

require_once '../database.php';


if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}



$uploadDir = "../images/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}



$success = '';
$error = '';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {

    $name = trim($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);

    $imageName = '';


    if ($name === '') {

        $error = "Please enter product name.";

    } elseif ($category_id <= 0) {

        $error = "Please select a category.";

    } elseif ($price < 0) {

        $error = "Price cannot be negative.";

    } elseif ($stock < 0) {

        $error = "Stock cannot be negative.";

    } else {


        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {

                $error = "There was an error uploading the image.";

            } else {

                $originalName = $_FILES['image']['name'];

                $tmpName = $_FILES['image']['tmp_name'];

                $fileSize = $_FILES['image']['size'];

                $extension = strtolower(
                    pathinfo($originalName, PATHINFO_EXTENSION)
                );


                $allowedExtensions = [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];


                if (!in_array($extension, $allowedExtensions)) {

                    $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

                } elseif ($fileSize > 5 * 1024 * 1024) {

                    $error = "Image size must be less than 5MB.";

                } else {


                    $imageName =
                        strtolower(
                            preg_replace(
                                '/[^A-Za-z0-9\-]/',
                                '-',
                                pathinfo($originalName, PATHINFO_FILENAME)
                            )
                        )
                        . '-'
                        . time()
                        . '.'
                        . $extension;


                    $destination = $uploadDir . $imageName;


                    if (!move_uploaded_file($tmpName, $destination)) {

                        $error = "Could not save the uploaded image.";

                    }
                }
            }
        }


        if ($error === '') {

            try {

                $stmt = $pdo->prepare("
                    INSERT INTO products
                    (
                        name,
                        image,
                        price,
                        category_id,
                        description,
                        stock,
                        created_at
                    )
                    VALUES
                    (
                        :name,
                        :image,
                        :price,
                        :category_id,
                        :description,
                        :stock,
                        NOW()
                    )
                ");


                $stmt->execute([
                    ':name' => $name,
                    ':image' => $imageName,
                    ':price' => $price,
                    ':category_id' => $category_id,
                    ':description' => $description,
                    ':stock' => $stock
                ]);


                $success = "Product added successfully.";

            } catch (PDOException $e) {


                if (
                    $imageName !== '' &&
                    file_exists($uploadDir . $imageName)
                ) {
                    unlink($uploadDir . $imageName);
                }


                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}


if (isset($_GET['delete'])) {

    $deleteId = (int)$_GET['delete'];


    if ($deleteId > 0) {

        try {


            $stmt = $pdo->prepare("
                SELECT image
                FROM products
                WHERE id = ?
            ");

            $stmt->execute([$deleteId]);

            $productToDelete = $stmt->fetch();



            $stmt = $pdo->prepare("
                DELETE FROM products
                WHERE id = ?
            ");

            $stmt->execute([$deleteId]);


            if (
                $productToDelete &&
                !empty($productToDelete['image'])
            ) {

                $imageFile = basename(
                    $productToDelete['image']
                );

                $imagePath = $uploadDir . $imageFile;


                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }


            header("Location: products.php?deleted=1");
            exit;


        } catch (PDOException $e) {

            $error = "Could not delete product: " . $e->getMessage();
        }
    }
}

if (isset($_GET['deleted'])) {

    $success = "Product deleted successfully.";
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_stock'])
) {

    $productId = (int)($_POST['product_id'] ?? 0);

    $newStock = (int)($_POST['stock'] ?? 0);


    if ($productId > 0 && $newStock >= 0) {

        try {

            $stmt = $pdo->prepare("
                UPDATE products
                SET stock = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $newStock,
                $productId
            ]);


            $success = "Stock updated successfully.";

        } catch (PDOException $e) {

            $error = "Could not update stock: " . $e->getMessage();
        }
    }
}


try {

    $categoryStmt = $pdo->query("
        SELECT
            id,
            name
        FROM categories
        ORDER BY name ASC
    ");

    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $categories = [];

    $error = "Could not load categories: " . $e->getMessage();
}


try {

    $productStmt = $pdo->query("
        SELECT
            p.id,
            p.name,
            p.image,
            p.price,
            p.description,
            p.stock,
            p.created_at,
            p.category_id,
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c
            ON p.category_id = c.id
        ORDER BY p.id DESC
    ");

    $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $products = [];

    $error = "Could not load products: " . $e->getMessage();
}

$adminName = $_SESSION['admin_name'] ?? 'Administrator';

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Products | LeatherCraft Admin</title>


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

:root { --ink:#171411; --paper:#f7f5f0; --panel:#fff; --sidebar:#211a15; --gold:#c09968; --gold-bright:#d8b486; --muted:#91887d; --line:#e6e0d7; --green:#4f9663; --red:#b9554b; --yellow:#b7892d; --serif:'Playfair Display',Georgia,serif; --sans:'DM Sans',Arial,sans-serif; }
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

.admin-card { margin-bottom:30px; padding:0; overflow:hidden; border:1px solid var(--line); border-radius:6px; background:var(--panel); box-shadow:0 8px 25px rgba(33,26,21,.035); }
.card-title { margin:0; padding:21px 22px; color:var(--ink); border-bottom:1px solid var(--line); font-family:var(--serif); font-size:22px; font-weight:500; }
.card-title::before { display:block; margin-bottom:8px; color:var(--gold); content:'PRODUCT CATALOG'; font-family:var(--sans); font-size:9px; font-weight:700; letter-spacing:.24em; }
.admin-card > .row, .admin-card > form, .admin-card > .form-row { padding:22px; }
.form-label { margin-bottom:8px; color:var(--ink); font-size:11px; font-weight:700; }
.form-control, .form-select { min-height:43px; padding:10px 12px; color:var(--ink); border:1px solid var(--line); border-radius:4px; background:#fff; font-size:12px; }
.form-control:focus, .form-select:focus { color:var(--ink); border-color:var(--gold); box-shadow:0 0 0 3px rgba(192,153,104,.13); background:#fff; }
.form-select option { color:var(--ink); background:#fff; }
.form-control::placeholder { color:#aaa39a; }
textarea.form-control { min-height:92px; resize:vertical; }
.add-btn { padding:12px 25px; color:#fff; border:1px solid var(--sidebar); border-radius:4px; background:var(--sidebar); font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
.add-btn:hover { color:#fff; border-color:#926b42; background:#926b42; }

.table-wrapper { overflow-x:auto; }
.product-table { width:100%; min-width:900px; border-collapse:collapse; }
.product-table th { padding:14px 18px; color:var(--muted); border-bottom:1px solid var(--line); background:#fbfaf7; font-size:9px; font-weight:700; letter-spacing:.13em; text-align:left; text-transform:uppercase; white-space:nowrap; }
.product-table td { padding:15px 18px; color:#4f4841; border-bottom:1px solid #f0ece6; font-size:11px; vertical-align:middle; }
.product-table tr:last-child td { border-bottom:0; }
.product-table tr:hover td { background:#fdfcf9; }
.product-thumb { display:block; width:90px; height:75px; padding:5px; border:1px solid var(--line); border-radius:4px; background:#fff; object-fit:contain; object-position:center; }
.price { color:#926b42; font-weight:700; white-space:nowrap; }
.stock-form { display:flex; align-items:center; gap:6px; }
.stock-input { width:75px; padding:8px; color:var(--ink); border:1px solid var(--line); border-radius:4px; background:#fff; font-size:11px; }
.stock-input:focus { outline:none; border-color:var(--gold); box-shadow:0 0 0 3px rgba(192,153,104,.13); }
.stock-btn { display:grid; place-items:center; width:34px; height:34px; color:#fff; border:0; border-radius:4px; background:var(--sidebar); cursor:pointer; }
.stock-btn:hover { background:#926b42; }
.stock-good { color:var(--green); font-weight:700; }
.stock-low { color:var(--yellow); font-weight:700; }
.stock-out { color:var(--red); font-weight:700; }
.category-badge { display:inline-block; padding:5px 10px; color:#926b42; border:1px solid #dfccb4; border-radius:20px; background:#f5ede4; font-size:10px; }
.delete-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 11px; color:#fff; border-radius:4px; background:var(--red); font-size:10px; text-decoration:none; }
.delete-btn:hover { color:#fff; background:#8e342d; }
.empty { padding:60px!important; color:var(--muted)!important; text-align:center; }
.empty i { display:block; margin-bottom:12px; color:var(--gold); font-size:40px; }

@media (max-width:900px) { .sidebar { width:210px; } .main { margin-left:210px; padding:28px 24px 55px; } }
@media (max-width:700px) { .sidebar { position:relative; width:100%; height:auto; } .logout-link { margin-top:20px!important; } .main { margin-left:0; padding:25px 18px 50px; } .topbar { flex-direction:column; align-items:flex-start; gap:18px; } .back-btn { align-self:stretch; justify-content:center; } }
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

            <a
                href="products.php"
                class="active"
            >

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

            <a href="reviews.php">

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

                Manage Products

            </h1>

            <p>

                Add products and manage your shoe stock.

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


    <div class="admin-card">


        <div class="card-title">

            <i class="fa-solid fa-plus"></i>

            Add New Product

        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <div class="row g-3">

                <div class="col-md-6">

                    <label class="form-label">

                        Product Name

                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        placeholder="Enter product name"
                        required
                    >

                </div>


                <div class="col-md-6">

                    <label class="form-label">

                        Category

                    </label>


                    <select
                        name="category_id"
                        class="form-select"
                        required
                    >

                        <option value="">

                            Select Category

                        </option>


                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= (int)$category['id'] ?>"
                            >

                                <?= htmlspecialchars(
                                    $category['name']
                                ) ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </div>


                <!-- PRICE -->

                <div class="col-md-4">

                    <label class="form-label">

                        Price (Rs.)

                    </label>

                    <input
                        type="number"
                        name="price"
                        class="form-control"
                        placeholder="0.00"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>


                <div class="col-md-4">

                    <label class="form-label">

                        Stock Quantity

                    </label>

                    <input
                        type="number"
                        name="stock"
                        class="form-control"
                        placeholder="10"
                        min="0"
                        required
                    >

                </div>


                <div class="col-md-4">

                    <label class="form-label">

                        Product Image

                    </label>

                    <input
                        type="file"
                        name="image"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small class="text-secondary">

                        JPG, JPEG, PNG or WEBP — max 5MB

                    </small>

                </div>


                <div class="col-12">

                    <label class="form-label">

                        Description

                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                        rows="4"
                        placeholder="Enter product description..."
                    ></textarea>

                </div>


                <div class="col-12">

                    <button
                        type="submit"
                        name="add_product"
                        class="add-btn"
                    >

                        <i class="fa-solid fa-plus"></i>

                        Add Product

                    </button>

                </div>


            </div>


        </form>

    </div>


    <div class="admin-card">


        <div class="card-title">

            <i class="fa-solid fa-boxes-stacked"></i>

            All Products

        </div>


        <div class="table-wrapper">


            <table class="product-table">


                <thead>

                    <tr>

                        <th>
                            Image
                        </th>

                        <th>
                            Product
                        </th>

                        <th>
                            Category
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Stock
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (!empty($products)): ?>


                        <?php foreach ($products as $product): ?>


                            <tr>


                                <!-- IMAGE -->

                                <td>


                                    <?php

                                    $image = trim(
                                        $product['image'] ?? ''
                                    );


                                    if ($image === '') {

                                        $image =
                                            '../images/no-image.jpg';

                                    }

                                    elseif (
                                        strpos(
                                            $image,
                                            'http://'
                                        ) === 0 ||

                                        strpos(
                                            $image,
                                            'https://'
                                        ) === 0
                                    ) {

                                        // External URL

                                    }

                                    elseif (
                                        strpos(
                                            $image,
                                            'images/'
                                        ) === 0
                                    ) {

                                        $image =
                                            '../' . $image;

                                    }

                                    else {

                                        $image =
                                            '../images/' .
                                            ltrim(
                                                $image,
                                                '/'
                                            );
                                    }

                                    ?>


                                    <img
                                        src="<?= htmlspecialchars($image) ?>"
                                        alt="<?= htmlspecialchars($product['name']) ?>"
                                        class="product-thumb"
                                        onerror="this.onerror=null; this.src='../images/no-image.jpg';"
                                    >

                                </td>



                                <td>


                                    <strong>

                                        <?= htmlspecialchars(
                                            $product['name']
                                        ) ?>

                                    </strong>


                                    <?php if (!empty($product['description'])): ?>

                                        <br>

                                        <small
                                            style="color:#777;"
                                        >

                                            <?= htmlspecialchars(
                                                substr(
                                                    $product['description'],
                                                    0,
                                                    70
                                                )
                                            ) ?>

                                            <?php if (
                                                strlen(
                                                    $product['description']
                                                ) > 70
                                            ): ?>

                                                ...

                                            <?php endif; ?>

                                        </small>

                                    <?php endif; ?>


                                </td>

                                <td>

                                    <span class="category-badge">

                                        <?= htmlspecialchars(
                                            $product['category_name']
                                            ?? 'Uncategorized'
                                        ) ?>

                                    </span>

              </td>

                                <td>

                                    <span class="price">

                                        Rs.

                                        <?= number_format(
                                            (float)$product['price'],
                                            2
                                        ) ?>

                                    </span>

                                </td>

                                <td>


                                    <form
                                        method="POST"
                                        class="stock-form"
                                    >


                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= (int)$product['id'] ?>"
                                        >


                                        <input
                                            type="number"
                                            name="stock"
                                            class="stock-input"
                                            min="0"
                                            value="<?= (int)$product['stock'] ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="update_stock"
                                            class="stock-btn"
                                            title="Update stock"
                                        >

                                            <i class="fa-solid fa-check"></i>

                                        </button>


                                    </form>


                                </td>

                                <td>


                                    <?php

                                    $stock =
                                        (int)$product['stock'];


                                    if ($stock <= 0) {

                                        echo '
                                            <span class="stock-out">
                                                <i class="fa-solid fa-circle-xmark"></i>
                                                Out of Stock
                                            </span>
                                        ';

                                    }

                                    elseif ($stock <= 5) {

                                        echo '
                                            <span class="stock-low">
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                                Low Stock
                                            </span>
                                        ';

                                    }

                                    else {

                                        echo '
                                            <span class="stock-good">
                                                <i class="fa-solid fa-circle-check"></i>
                                                In Stock
                                            </span>
                                        ';
                                    }

                                    ?>


                                </td>
                                <td>


                                    <a
                                        href="products.php?delete=<?= (int)$product['id'] ?>"
                                        class="delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this product?');"
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
                                colspan="7"
                                class="empty"
                            >

                                <i class="fa-solid fa-box-open"></i>

                                <br><br>

                                No products found.

                                <br><br>

                                Add your first product using the form above.

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