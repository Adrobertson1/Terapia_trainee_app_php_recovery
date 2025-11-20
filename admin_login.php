<?php
session_start();
require 'db.php'; // Your PDO connection

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password']) && strtolower($user['role']) === 'admin') {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['name'] = $user['full_name'] ?? $user['username'];

            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = "Invalid admin credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #E6D6EC;
      color: #000;
    }
    .login-container {
      max-width: 400px;
      margin: 80px auto;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      text-align: center;
    }
    .login-container img.logo {
      display: block;
      margin: 0 auto 20px auto;
      max-width: 160px;
      height: auto;
    }
    .login-container h2 {
      margin-bottom: 20px;
      color: #850069;
      font-family: 'Josefin Sans', sans-serif;
    }
    .login-container label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
      font-family: 'Inter', sans-serif;
      text-align: left;
    }
    .login-container input {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 16px;
    }
    .login-container button {
      margin-top: 20px;
      padding: 12px;
      background-color: #850069;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      width: 100%;
      font-family: 'Josefin Sans', serif;
      font-size: 18px;
    }
    .login-container button:hover {
      background-color: #BB9DC6;
    }
    .message.error {
      margin-top: 15px;
      color: #d32f2f;
      font-weight: bold;
    }
    .reset-link {
      margin-top: 20px;
    }
    .reset-link a {
      color: #850069;
      font-weight: 500;
      text-decoration: none;
      font-family: 'Inter', sans-serif;
    }
    .reset-link a:hover {
      text-decoration: underline;
      color: #BB9DC6;
    }
  </style>
</head>
<body>
<div class="login-container">
  <img src="Assets/logo.png" alt="Terapia Logo" class="logo">
  <h2>Admin Login</h2>

  <?php if ($error): ?>
    <div class="message error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <label>Email Address:</label>
    <input type="email" name="username" required>

    <label>Password:</label>
    <input type="password" name="password" required>

    <button type="submit">Login</button>
  </form>

  <div class="reset-link">
    <a href="request_reset.php">Forgot your password?</a>
  </div>
</div>
</body>
</html>