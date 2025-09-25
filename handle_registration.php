<?php
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $college_id = trim($_POST['college_id']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $department = $_POST['department'] ?? NULL;
    $program_type = $_POST['program_type'] ?? NULL;

    if (strlen($password) < 6) {
        header("Location: register.php?error=Password must be at least 6 characters long.");
        exit();
    }
    if ($password !== $confirm_password) {
        header("Location: register.php?error=Passwords do not match.");
        exit();
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR college_id = ?");
    $stmt->bind_param("ss", $email, $college_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: register.php?error=User with this ID or Email already exists.");
        exit();
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (full_name, college_id, email, password_hash, role, department, program_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $full_name, $college_id, $email, $hashed_password, $role, $department, $program_type);

    if ($stmt->execute()) {
        header("Location: login.php?success=registered");
    } else {
        header("Location: register.php?error=Registration failed. Please try again.");
    }
    $stmt->close();
    $conn->close();
}
?>