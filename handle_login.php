<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $college_id = trim($_POST['college_id']);
    $password = trim($_POST['password']);

    // This query is now updated to also select the user's email
    $stmt = $conn->prepare("SELECT id, password_hash, full_name, role, department, program_type, email FROM users WHERE college_id = ? OR email = ?");
    $stmt->bind_param("ss", $college_id, $college_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['program_type'] = $user['program_type'];
            $_SESSION['email'] = $user['email']; // This line is the most important

            header("Location: " . $user['role'] . "_dashboard.php");
            exit();
        }
    }
    header("Location: login.php?error=invalid");
    exit();
}