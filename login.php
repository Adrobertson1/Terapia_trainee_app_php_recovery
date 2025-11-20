<?php
session_start();
require 'db.php';

$error = '';

function logAudit($pdo, $user_id, $role, $type, $detail) {
    $stmt = $pdo->prepare("
        INSERT INTO audit_log (user_id, role, action_type, action_detail, ip_address, timestamp)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $user_id,
        $role,
        $type,
        $detail,
        $_SERVER['REMOTE_ADDR']
    ]);
}

function logLoginActivity($pdo, $user_id, $role, $email) {
    $stmt = $pdo->prepare("
        INSERT INTO login_activity (user_id, role, email, ip_address, user_agent, login_time)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $user_id,
        $role,
        $email,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}

function logFailedLogin($pdo, $username, $reason) {
    $stmt = $pdo->prepare("
        INSERT INTO login_attempts (username, ip_address, user_agent, success, reason, attempt_time)
        VALUES (?, ?, ?, 0, ?, NOW())
    ");
    $stmt->execute([
        $username,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'],
        $reason
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = "Please enter both email and password.";
    } else {
        // Staff login
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE email = ? AND is_archived = 0");
        $stmt->execute([$email]);
        $staff = $stmt->fetch();

        if ($staff && password_verify($password, $staff['password'])) {
            $_SESSION['user_id'] = $staff['staff_id'];
            $_SESSION['role'] = strtolower(trim($staff['role']));
            $_SESSION['name'] = $staff['first_name'] . ' ' . $staff['surname'];
            $_SESSION['email'] = $staff['email'];
            $_SESSION['initiated'] = 1;
            $_SESSION['last_activity'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            logAudit($pdo, $staff['staff_id'], $_SESSION['role'], 'login', 'Successful login');
            logLoginActivity($pdo, $staff['staff_id'], $_SESSION['role'], $staff['email']);
            header("Location: dashboard.php");
            exit;
        }

        // Trainee login
        $stmt = $pdo->prepare("
            SELECT t.*, u.user_id
            FROM trainees t
            JOIN users u ON t.user_id = u.user_id
            WHERE t.email = ? AND t.is_archived = 0
        ");
        $stmt->execute([$email]);
        $trainee = $stmt->fetch();

        if ($trainee && password_verify($password, $trainee['password'])) {
            $_SESSION['user_id'] = $trainee['user_id'];       // ✅ used for calendar queries
            $_SESSION['trainee_id'] = $trainee['trainee_id']; // optional for trainee-specific logic
            $_SESSION['role'] = 'trainee';
            $_SESSION['name'] = $trainee['first_name'] . ' ' . $trainee['surname'];
            $_SESSION['email'] = $trainee['email'];
            $_SESSION['initiated'] = 1;
            $_SESSION['last_activity'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            logAudit($pdo, $trainee['trainee_id'], 'trainee', 'login', 'Successful login');
            logLoginActivity($pdo, $trainee['trainee_id'], 'trainee', $trainee['email']);
            header("Location: dashboard.php");
            exit;
        }

        // Tutor login
        $stmt = $pdo->prepare("SELECT * FROM tutors WHERE email = ? AND is_archived = 0");
        $stmt->execute([$email]);
        $tutor = $stmt->fetch();

        if ($tutor && password_verify($password, $tutor['password'])) {
            $_SESSION['user_id'] = $tutor['tutor_id'];
            $_SESSION['role'] = 'tutor';
            $_SESSION['name'] = $tutor['first_name'] . ' ' . $tutor['surname'];
            $_SESSION['email'] = $tutor['email'];
            $_SESSION['initiated'] = 1;
            $_SESSION['last_activity'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            logAudit($pdo, $tutor['tutor_id'], 'tutor', 'login', 'Successful login');
            logLoginActivity($pdo, $tutor['tutor_id'], 'tutor', $tutor['email']);
            header("Location: dashboard.php");
            exit;
        }

        // Failed login
        logAudit($pdo, null, null, 'login_failed', "Failed login attempt for $email");
        logFailedLogin($pdo, $email, 'Invalid credentials or archived account');
        $error = "Invalid login credentials or account is archived.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>System Login</title>
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
    .login-container input[disabled] {
      background-color: #f0f0f0;
      color: #999;
      cursor: not-allowed;
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
  <h2>Login</h2>

  <?php if ($error): ?>
    <div class="message error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <label>Email Address:</label>
    <input type="email" name="email" required>

    <label>Password:</label>
    <input type="password" name="password" required>

    <label>MFA CODE:</label>
    <input type="text" placeholder="Disabled in Sandbox" disabled>

        <button type="submit">Login</button>
  </form>

  <div class="reset-link">
    <a href="request_reset.php">Forgot your password?</a>
  </div>
</div>
</body>
</html>