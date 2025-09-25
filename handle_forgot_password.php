<?php
require 'db_connect.php';

// Use the PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- CORRECTED PART ---
// This one line replaces the three old require_once lines and works perfectly.
require 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    
    $token = bin2hex(random_bytes(16));
    $token_hash = hash("sha256", $token);
    $expiry = date("Y-m-d H:i:s", time() + 60 * 30); // 30 minutes from now

    $sql = "UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $token_hash, $expiry, $email);
    $stmt->execute();

    if ($stmt->affected_rows) {
        $mail = new PHPMailer(true);
        try {
            // --- SERVER SETTINGS ---
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            // IMPORTANT: REPLACE WITH YOUR GMAIL AND APP PASSWORD
            $mail->Username = 'hajarabasheer26@gmail.com';
            $mail->Password = 'obdz xuhh droy rqpj'; // Use a Google App Password, NOT your regular password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // --- RECIPIENTS & CONTENT ---
            $mail->setFrom('your_email@gmail.com', 'CET Internship Tracker');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $reset_link = "http://localhost/internship_tracker/reset_password.php?token=$token";
            $mail->Body    = "Click <a href='{$reset_link}'>here</a> to reset your password. This link is valid for 30 minutes.";
            
            $mail->send();

        } catch (Exception $e) {
            // Error logging can be added here. For now, we fail silently to the user.
        }
    }
    // IMPORTANT: Always show a generic success message, even if the email doesn't exist.
    // This prevents attackers from figuring out which emails are registered.
    header("Location: forgot_password.php?success=true");
    exit();
}
?>