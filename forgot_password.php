<?php
session_start();
require 'db.php';
require_once 'functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires]);

        $resetLink = "http://yourdomain.com/reset_password.php?token=$token";
        mail($email, "Password Reset", "Click to reset your password: $resetLink");

        $success = "A reset link has been sent to your email.";
    } else {
        $error = "Email not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .reset-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background: linear-gradient(to right, #6a1b9a, #8e24aa);
    }
    .reset-card {
      background: white;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 400px;
      text-align: center;
    }
    .reset-card h2 {
      color: #6a1b9a;
      margin-bottom: 20px;
    }
    .reset-card input {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 16px;
    }
    .reset-card button {
      width: 100%;
      padding: 12px;
      background-color: #6a1b9a;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
    }
    .reset-card button:hover {
      background-color: #4a148c;
    }
    .message {
      margin-top: 15px;
      font-weight: bold;
    }
    .message.success {
      color: #388e3c;
    }
    .message.error {
      color: #d32f2f;
    }
  </style>
</head>
<body>
<div class="reset-wrapper">
  <div class="reset-card">
    <h2>Forgot Password</h2>
    <p>Enter your email to receive a reset link</p>

    <?php if ($success): ?>
      <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="email" name="email" placeholder="Email address" required>
      <button type="submit">Send Reset Link</button>
    </form>
  </div>
</div>
</body>
</html>