<?php

session_start();

require_once "../database.php";


if (
    isset($_SESSION["admin_id"]) &&
    isset($_SESSION["admin_role"]) &&
    $_SESSION["admin_role"] === "admin"
) {
    header("Location: dashboard.php");
    exit;
}



$email = "";
$error = "";



if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";



    if ($email === "") {

        $error = "Please enter your email address.";

    }



    elseif ($password === "") {

        $error = "Please enter your password.";

    }



    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    }


    else {

        try {

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    name,
                    email,
                    password,
                    role
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([$email]);

            $user = $stmt->fetch();


            if (!$user) {

                $error = "No account found with this email.";

            }


            elseif (!password_verify($password, $user["password"])) {

                $error = "Incorrect password. Please try again.";

            }



            elseif ($user["role"] !== "admin") {

                $error = "This account does not have administrator access.";

            }


            else {

                session_regenerate_id(true);


                $_SESSION["admin_id"] = $user["id"];

                $_SESSION["admin_name"] = $user["name"];

                $_SESSION["admin_email"] = $user["email"];

                $_SESSION["admin_role"] = $user["role"];


                header("Location: dashboard.php");

                exit;

            }

        } catch (PDOException $e) {

            $error = "Database error. Please try again.";

        }

    }

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
        Admin Login | LeatherCraft
    </title>


  

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap');

        :root {
            --ink: #191511;
            --muted: #81786e;
            --line: #ded8cf;
            --paper: #fbfaf7;
            --espresso: #251d17;
            --gold: #b99161;
            --gold-dark: #926b42;
            --error-bg: #fff5f2;
            --error-border: #e6c2b8;
            --error-text: #8b4437;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            color: var(--ink);
            background: var(--paper);
            font-family: 'DM Sans', Arial, sans-serif;
        }

        body::before {
            position: fixed;
            inset: 0 auto 0 0;
            width: 42%;
            content: '';
            pointer-events: none;
            background: linear-gradient(145deg, rgba(55, 39, 26, .86), rgba(17, 12, 8, .98));
            z-index: -1;
        }

        .login-container {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            width: 100%;
            min-height: 100vh;
            margin: 0;
            padding: 70px clamp(48px, 9vw, 150px) 70px calc(42% + clamp(48px, 8vw, 125px));
            background: linear-gradient(to right, #2b2118 0%, #17110d 42%, var(--paper) 42%, var(--paper) 100%);
        }

        .login-container::before {
            position: fixed;
            top: 42px;
            left: clamp(28px, 5vw, 70px);
            color: #fff;
            content: 'LeatherCraft';
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 22px;
            font-weight: 600;
            letter-spacing: -.04em;
        }

        .login-container::after {
            position: fixed;
            bottom: 70px;
            left: clamp(28px, 5vw, 70px);
            max-width: 250px;
            color: rgba(255,255,255,.7);
            content: 'Premium leather footwear, thoughtfully made for the moments that matter.';
            font-size: 12px;
            line-height: 1.7;
        }

        .login-header { margin-bottom: 34px; text-align: left; }
        .login-header::before {
            display: block;
            margin-bottom: 18px;
            color: var(--gold-dark);
            content: 'SECURE ADMIN ACCESS';
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .27em;
        }

        .login-header h1 {
            margin: 0;
            color: var(--ink);
            font-family: 'Playfair Display', Georgia, serif;
            font-size: clamp(42px, 5vw, 64px);
            font-weight: 500;
            letter-spacing: -.06em;
            line-height: .98;
        }

        .login-header h1::first-line { }
        .login-header p { margin: 20px 0 0; color: var(--muted); font-size: 13px; line-height: 1.7; }

        .error-message {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            padding: 13px 14px;
            color: var(--error-text);
            border: 1px solid var(--error-border);
            border-radius: 3px;
            background: var(--error-bg);
            font-size: 12px;
        }

        .error-message i { color: var(--gold-dark); }
        form { width: 100%; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--ink); font-size: 11px; font-weight: 700; }
        .input-wrapper { position: relative; }
        .input-wrapper > .input-icon { position: absolute; top: 50%; left: 0; color: var(--muted); pointer-events: none; transform: translateY(-50%); }
        .input-wrapper input {
            width: 100%;
            height: 42px;
            padding: 0 30px 0 23px;
            color: var(--ink);
            border: 0;
            border-bottom: 1px solid var(--line);
            border-radius: 0;
            outline: none;
            background: transparent;
            font-size: 12px;
        }
        .input-wrapper input:focus { border-bottom-color: var(--gold-dark); box-shadow: none; }
        .input-wrapper input::placeholder { color: #b3aca4; }
        .password-toggle { position: absolute; top: 50%; right: 0; color: var(--gold-dark); cursor: pointer; transform: translateY(-50%); }
        .password-toggle:hover { color: var(--ink); }

        .login-button {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            min-height: 48px;
            padding: 0 17px;
            color: #fff;
            border: 1px solid var(--espresso);
            border-radius: 0;
            background: var(--espresso);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .12em;
            cursor: pointer;
            transition: background .2s ease, transform .2s ease;
        }
        .login-button:hover { background: var(--gold-dark); transform: translateY(-1px); }
        .login-button i { order: 2; margin: 0; color: #d9b486; }

        .back-store { margin-top: 28px; text-align: center; }
        .back-store a { color: var(--muted); font-size: 11px; text-decoration: none; }
        .back-store a:hover { color: var(--gold-dark); }
        .back-store i { margin-right: 7px; }

        @media (max-width: 700px) {
            body::before { position: absolute; width: 100%; height: 185px; }
            .login-container { width: 100%; min-height: 100vh; padding: 205px 28px 55px; background: var(--paper); }
            .login-container::before { top: 38px; left: 28px; }
            .login-container::after { top: 91px; right: 28px; bottom: auto; left: auto; max-width: 175px; text-align: right; }
        }

        @media (max-width: 420px) {
            .login-container { padding-right: 22px; padding-left: 22px; }
            .login-header h1 { font-size: 42px; }
        }
    </style>

</head>


<body>


<div class="login-container">



    <div class="login-header">

        <h1>
            Admin Login
        </h1>

        <p>
            Sign in to manage your store
        </p>

    </div>


    <?php if ($error !== ""): ?>

        <div class="error-message">

            <i class="fa-solid fa-circle-exclamation"></i>

            <span>
                <?= htmlspecialchars($error) ?>
            </span>

        </div>

    <?php endif; ?>



    <form
        method="POST"
        action=""
    >




        <div class="form-group">

            <label for="email">
                Email Address
            </label>


            <div class="input-wrapper">

                <i
                    class="fa-solid fa-envelope input-icon"
                ></i>


                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    placeholder="Enter your email"
                    autocomplete="email"
                >

            </div>

        </div>



        <div class="form-group">

            <label for="password">
                Password
            </label>


            <div class="input-wrapper">

                <i
                    class="fa-solid fa-lock input-icon"
                ></i>


                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                >


                <span
                    class="password-toggle"
                    onclick="togglePassword()"
                >

                    <i
                        class="fa-solid fa-eye"
                        id="eyeIcon"
                    ></i>

                </span>

            </div>

        </div>


        <button
            type="submit"
            class="login-button"
        >

            <i
                class="fa-solid fa-right-to-bracket"
            ></i>

            Login to Dashboard

        </button>


    </form>




    <div class="back-store">

        <a href="../index.php">

            <i
                class="fa-solid fa-arrow-left"
            ></i>

            Back to Store

        </a>

    </div>


</div>


<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const eye =
        document.getElementById("eyeIcon");


    if (password.type === "password") {

        password.type = "text";

        eye.classList.remove("fa-eye");

        eye.classList.add("fa-eye-slash");

    } else {

        password.type = "password";

        eye.classList.remove("fa-eye-slash");

        eye.classList.add("fa-eye");

    }

}

</script>


</body>

</html>