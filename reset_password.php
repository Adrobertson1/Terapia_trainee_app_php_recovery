<?php
require 'db.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

function isValidPassword($password) {
  return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new_password = $_POST['new_password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';

  if (!$new_password || !$confirm_password) {
    $error = "Please fill in both password fields.";
  } elseif ($new_password !== $confirm_password) {
    $error = "Passwords do not match.";
  } elseif (!isValidPassword($new_password)) {
    $error = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
  } else {
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if ($row) {
      $hashed = password_hash($new_password, PASSWORD_DEFAULT);
      $pdo->prepare("UPDATE staff SET password = ? WHERE email = ?")->execute([$hashed, $row['email']]);
      $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$row['email']]);
      $success = "Password has been reset successfully.";
    } else {
      $error = "Invalid or expired token.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: #E6D6EC; margin: 0; }
    .reset-container { max-width: 500px; margin: 80px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    h2 { color: #850069; font-family: 'Josefin Sans', sans-serif; text-align: center; margin-bottom: 20px; }
    label { display: block; margin-top: 15px; font-weight: bold; }
    input { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; font-size: 16px; }
    button { margin-top: 20px; padding: 12px; background-color: #850069; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; font-family: 'Josefin Sans', serif; font-size: 18px; }
    button:hover { background-color: #BB9DC6; }
    .message { margin-top: 15px; text-align: center; font-weight: bold; }
    .message.success { color: #2