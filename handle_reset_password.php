<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST["token"];
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

    $password = $_POST["password"];
    $password_confirmation = $_POST["password_confirmation"];

    if (strlen($password) < 6) {
        header("Location: reset_password.php?token=$token&error=Password must be at least 6 characters.");
        exit();
    }
    if ($password !== $password_confirmation) {
        header("Location: reset_password.php?token=$token&error=Passwords must match.");
        exit();
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password_hash = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $password_hash, $user["id"]);
    $stmt->execute();

    header("Location: login.php?success=reset");
    exit();
}
