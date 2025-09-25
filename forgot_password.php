<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CET Internship Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root { --navy-blue: #0a2342; --light-grey: #f0f2f5; --card-bg: #ffffff; --text-primary: #1e293b; --text-secondary: #64748b; --error-color: #ef4444; --success-color: #22c55e; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: var(--light-grey); display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .container { background-color: var(--card-bg); padding: 50px 40px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        h1 { font-size: 28px; font-weight: 600; color: var(--text-primary); margin-bottom: 10px; }
        p { color: var(--text-secondary); margin-bottom: 30px; }
        .input-group { position: relative; margin-bottom: 20px; text-align: left; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .input-group input { width: 100%; padding: 12px 15px 12px 45px; background-color: #f9fafb; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; box-sizing: border-box; }
        button { width: 100%; padding: 14px; background-color: var(--navy-blue); border: none; border-radius: 6px; color: #fff; font-size: 16px; font-weight: 500; cursor: pointer; }
        a { color: var(--navy-blue); font-weight: 500; text-decoration: none; }
        .message { padding: 10px 15px; margin-bottom: 20px; border-radius: 6px; border-left: 5px solid; text-align: left; }
        .error { background-color: #fee2e2; border-color: var(--error-color); color: #b91c1c; }
        .success { background-color: #dcfce7; border-color: var(--success-color); color: #15803d; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Forgot Password</h1>
        <p>Enter your email address and we will send you a link to reset your password.</p>
        <?php if(isset($_GET['success'])) echo '<div class="message success">If an account with that email exists, a password reset link has been sent.</div>'; ?>
        <?php if(isset($_GET['error'])) echo '<div class="message error">'.htmlspecialchars($_GET['error']).'</div>'; ?>
        <form action="handle_forgot_password.php" method="POST">
            <div class="input-group">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="Your Email Address" required>
            </div>
            <button type="submit">Send Reset Link</button>
            <p style="margin-top: 20px;"><a href="login.php">Back to Login</a></p>
        </form>
    </div>
</body>
</html>
