<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $dashboard_page = $_SESSION['role'] . '_dashboard.php';
    header('Location: ' . $dashboard_page);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CET Internship Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --navy-blue: #0a2342;
            --light-grey: #f0f2f5;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --error-color: #ef4444;
            --success-color: #22c55e;
        }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--light-grey);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-container {
            background-color: var(--card-bg);
            padding: 50px 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .main-heading {
            font-size: 28px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        .sub-heading {
            color: var(--text-secondary);
            margin-bottom: 30px;
        }
        .input-group {
            position: relative;
            margin-bottom: 20px;
            text-align: left;
        }
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        .input-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .input-group input:focus {
            outline: none;
            border-color: var(--navy-blue);
            box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.1);
        }
        .primary-button {
            width: 100%;
            padding: 14px;
            background-color: var(--navy-blue);
            border: none;
            border-radius: 6px;
            color: #fff;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .links-container {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-top: 20px;
        }
        .links-container a {
            color: var(--navy-blue);
            text-decoration: none;
            font-weight: 500;
        }
        .separator {
            display: flex;
            align-items: center;
            text-align: center;
            color: var(--text-secondary);
            margin: 30px 0;
        }
        .separator::before, .separator::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e5e7eb;
        }
        .separator:not(:empty)::before {
            margin-right: .5em;
        }
        .separator:not(:empty)::after {
            margin-left: .5em;
        }
        .secondary-button {
            display: block;
            width: 100%;
            padding: 14px;
            box-sizing: border-box;
            border: 1px solid var(--navy-blue);
            border-radius: 6px;
            color: var(--navy-blue);
            background-color: transparent;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.2s, color 0.2s;
        }
        .secondary-button:hover {
            background-color: var(--navy-blue);
            color: #fff;
        }
        .message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            border-left: 5px solid;
            text-align: left;
        }
        .error {
            background-color: #fee2e2;
            border-color: var(--error-color);
            color: #b91c1c;
        }
        .success {
            background-color: #dcfce7;
            border-color: var(--success-color);
            color: #15803d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="main-heading">Internship Approval Tracker</h1>
        <p class="sub-heading">College of Engineering, Trivandrum</p>

        <?php if (isset($_GET['error'])) echo '<div class="message error">Invalid credentials. Please try again.</div>'; ?>
        <?php if (isset($_GET['success']) && $_GET['success'] == 'registered') echo '<div class="message success">Registration successful! Please sign in.</div>'; ?>
        <?php if (isset($_GET['success']) && $_GET['success'] == 'reset') echo '<div class="message success">Password has been reset! Please sign in.</div>'; ?>

        <form action="handle_login.php" method="POST">
            <div class="input-group">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="college_id" placeholder="College ID / Email" required>
            </div>
            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <div class="links-container">
                <div></div>
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
            <button type="submit" class="primary-button" style="margin-top: 20px;">Sign In</button>
        </form>
        <div class="separator">OR</div>
        <a href="register.php" class="secondary-button">Don't have an account? Register</a>
    </div>
</body>
</html>