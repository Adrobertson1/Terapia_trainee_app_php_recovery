<?php
session_start();
require 'db.php';
require 'vendor/autoload.php'; // PHPMailer via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');

  if ($username) {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
      // Generate secure token
      $token = bin2hex(random_bytes(32));
      $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

      // Store token
      $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
      $stmt->execute([$user['user_id'], $token, $expires]);

      // Send email — replace with actual email logic if you have an email field
      $reset_link = "http://localhost/trainee_app/reset_password.php?token=$token";

      $mail = new PHPMailer(true);
      try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'yourgmail@gmail.com'; // Replace with your sender address
        $mail->Password = 'your-app-password';   // Use Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('yourgmail@gmail.com', 'Terapia Support');
        $mail->addAddress('fallback@example.com'); // Replace with actual email if available
        $mail->Subject = '🔐 Terapia Password Reset Request';
        $mail->Body = <<<EOT
Hello,

We received a request to reset your password for your Terapia account.

Click the secure link below to proceed:
$reset_link

This link will expire in 1 hour for your protection.

If you didn’t request this, you can safely ignore this message.

Warm regards,  
Terapia Support Team
EOT;

        $mail->send();
        $success = "If your username is registered, a password reset link has been sent.";
      } catch (Exception $e) {
        $error = "Failed to send email. Please try again later.";
      }
    } else {
      // Don't reveal whether the username exists
      $success = "If your username is registered, a password reset link has been sent.";
    }
  } else {
    $error = "Please enter your username.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Terapia | Request Password Reset</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #F4F0F8;
      margin: 0;
    }
    .dashboard-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .main-content {
      flex: 1;
      padding: 40px;
    }
    .form-card {
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      max-width: 500px;
      margin: auto;
      text-align: center;
    }
    .form-card img.logo {
      max-width: 140px;
      margin-bottom: 20px;
    }
    .form-card h2 {
      color: #850069;
      font-family: 'Josefin Sans', sans-serif;
      margin-bottom: 20px;
    }
    .form-card label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
      text-align: left;
    }
    .form-card input {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 16px;
    }
    .form-card button {
      margin-top: 20px;
      padding: 12px;
      background-color: #850069;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      font-size: 18px;
      width: 100%;
    }
    .form-card button:hover {
      background-color: #BB9DC6;
    }
    .message {
      margin-top: 20px;
      font-weight: bold;
      text-align: center;
    }
    .message.success { color: #2e7d32; }
    .message.error { color: #d32f2f; }
  </style>
</head>
<body>
<div class="dashboard-wrapper">
  <div class="main-content">
    <div class="form-card">
      <img src="Assets/logo.png" alt="Terapia Logo" class="logo">
      <h2>Reset Your Password</h2>
      <?php if ($success): ?><div class="message success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="message error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post">
        <label>Username:</label>
        <input type="text" name="username" required>
        <button type="submit">Send Reset Link</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>