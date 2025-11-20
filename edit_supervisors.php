<?php
session_start();
require 'db.php';
include 'header.php';

$supervisor_id = $_GET['supervisor_id'] ?? null;
if (!$supervisor_id || !is_numeric($supervisor_id)) {
  die("Invalid supervisor ID.");
}

$stmt = $pdo->prepare("SELECT * FROM supervisors WHERE supervisor_id = ?");
$stmt->execute([$supervisor_id]);
$supervisor = $stmt->fetch();

if (!$supervisor) {
  die("Supervisor not found.");
}

$success = '';
$error = '';

function isValidPassword($password) {
  return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first = trim($_POST['first_name'] ?? '');
  $surname = trim($_POST['surname'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $role = strtolower(trim($_POST['role'] ?? ''));
  $job_title = trim($_POST['job_title'] ?? '');
  $telephone = trim($_POST['telephone'] ?? '');
  $start_date = $_POST['start_date'] ?? $supervisor['start_date'];
  $new_password = $_POST['password'] ?? '';
  $profile_image = $supervisor['profile_image'];

  // Handle image upload
  if (!empty($_FILES['profile_image']['name'])) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
      mkdir($target_dir, 0755, true);
    }
    $filename = basename($_FILES["profile_image"]["name"]);
    $target_file = $target_dir . time() . "_" . $filename;
    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
      $profile_image = basename($target_file);
    }
  }

  if ($first && $surname && $email && $role) {
    try {
      if ($new_password) {
        if (!isValidPassword($new_password)) {
          $error = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
        } else {
          $hashed = password_hash($new_password, PASSWORD_DEFAULT);
          $stmt = $pdo->prepare("UPDATE supervisors SET first_name=?, surname=?, email=?, password=?, role=?, job_title=?, start_date=?, telephone=?, profile_image=? WHERE supervisor_id=?");
          $stmt->execute([$first, $surname, $email, $hashed, $role, $job_title, $start_date, $telephone, $profile_image, $supervisor_id]);
          $success = "Supervisor updated successfully.";
        }
      } else {
        $stmt = $pdo->prepare("UPDATE supervisors SET first_name=?, surname=?, email=?, role=?, job_title=?, start_date=?, telephone=?, profile_image=? WHERE supervisor_id=?");
        $stmt->execute([$first, $surname, $email, $role, $job_title, $start_date, $telephone, $profile_image, $supervisor_id]);
        $success = "Supervisor updated successfully.";
      }
    } catch (PDOException $e) {
      $error = "Error updating supervisor: " . $e->getMessage();
    }
  } else {
    $error = "Please fill in all required fields.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Supervisor</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: #E6D6EC; margin: 0; }
    .dashboard-wrapper { display: flex; }
    .main-content { flex: 1; padding: 40px; }
    .form-card {
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      max-width: 700px;
      margin-bottom: 40px;
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
    }
    .form-card input, .form-card select {
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
      font-family: 'Josefin Sans', serif;
      font-size: 18px;
    }
    .form-card button:hover {
      background-color: #BB9DC6;
    }
    .message {
      margin-bottom: 20px;
      font-weight: bold;
      text-align: center;
    }
    .message.success { color: #2e7d32; }
    .message.error { color: #d32f2f; }
    small { color: #555; display: block; margin-top: 5px; }
    .preview-img {
      margin-top: 10px;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      object-fit: cover;
      border: 1px solid #ccc;
    }
  </style>
</head>
<body>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="form-card">
      <h2>Edit Supervisor</h2>
      <?php if ($success): ?><div class="message success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="message error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <label>First Name:</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($supervisor['first_name']) ?>" required>

        <label>Surname:</label>
        <input type="text" name="surname" value="<?= htmlspecialchars($supervisor['surname']) ?>" required>

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($supervisor['email']) ?>" required>

        <label>New Password (leave blank to keep current):</label>
        <input type="password" name="password">
        <small>Password must be at least 8 characters and include uppercase, lowercase, number, and special character.</small>

        <label>Role:</label>
        <select name="role" required>
          <option value="supervisor" <?= $supervisor['role'] === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
          <option value="staff" <?= $supervisor['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
          <option value="admin" <?= $supervisor['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>

        <label>Job Title:</label>
        <input type="text" name="job_title" value="<?= htmlspecialchars($supervisor['job_title']) ?>">

        <label>Start Date:</label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($supervisor['start_date']) ?>">

        <label>Telephone:</label>
        <input type="text" name="telephone" value="<?= htmlspecialchars($supervisor['telephone']) ?>">

        <label>Profile Image:</label>
        <input type="file" name="profile_image" accept="image/*">
        <?php if ($supervisor['profile_image']): ?>
          <img src="uploads/<?= htmlspecialchars($supervisor['profile_image']) ?>" alt="Current Image" class="preview-img">
        <?php endif; ?>

        <button type="submit">Update Supervisor</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>