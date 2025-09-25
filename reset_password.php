<?php
$token = $_GET["token"];
$token_hash = hash("sha256", $token);

require 'db_connect.php';
$sql = "SELECT * FROM users WHERE reset_token_hash = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user === null) { die("Token not found."); }
if (strtotime($user["reset_token_expires_at"]) <= time()) { die("Token has expired."); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CET Internship Tracker</title>
    <style>
        :root { --navy-blue: #0a2342; --light-grey: #f0f2f5; --card-bg: #ffffff; --text-primary: #1e293b; --text-secondary: #64748b; --error-color: #ef4444; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: var(--light-grey); display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .container { background-color: var(--card-bg); padding: 50px 40px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        h1 { font-size: 28px; font-weight: 600; color: var(--text-primary); margin-bottom: 10px; }
        .input-group { margin-bottom: 20px; text-align: left; }
        input { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; box-sizing: border-box; }
        button { width: 100%; padding: 14px; background-color: var(--navy-blue); border: none; border-radius: 6px; color: #fff; font-size: 16px; font-weight: 500; cursor: pointer; }
        .message { padding: 10px 15px; margin-bottom: 20px; border-radius: 6px; border-left: 5px solid; text-align: left; }
        .error { background-color: #fee2e2; border-color: var(--error-color); color: #b91c1c; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Password</h1>
        <?php if(isset($_GET['error'])) echo '<div class="message error">'.htmlspecialchars($_GET['error']).'</div>'; ?>
        <form action="handle_reset_password.php" method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="input-group"><input type="password" name="password" placeholder="New Password" required></div>
            <div class="input-group"><input type="password" name="password_confirmation" placeholder="Confirm New Password" required></div>
            <button type="submit">Reset Password</button>
        </form>
    </div>
</body>
</html>
