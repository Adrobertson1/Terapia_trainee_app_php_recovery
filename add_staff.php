<?php
session_start();
require 'db.php';
include 'header.php';

$success = '';
$error = '';

// Generate unique staff ID
function generateStaffId($pdo) {
  do {
    $id = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    $stmt = $pdo->prepare("SELECT staff_id FROM staff WHERE staff_id = ?");
    $stmt->execute([$id]);
  } while ($stmt->fetch());

  return $id;
}

$generated_id = generateStaffId($pdo);

function isValidPassword($password) {
  return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $staff_id = $generated_id;
  $first = trim($_POST['first_name'] ?? '');
  $surname = trim($_POST['surname'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $role = strtolower(trim($_POST['role'] ?? ''));
  $job_title = trim($_POST['job_title'] ?? '');
  $telephone = trim($_POST['telephone'] ?? '');
  $start_date = $_POST['start_date'] ?? date('Y-m-d');

  $dbs_status = $_POST['dbs_status'] ?? '';
  $dbs_date = $_POST['dbs_date'] ?? null;
  $dbs_reference = trim($_POST['dbs_reference'] ?? '');
  $dbs_update_service = isset($_POST['dbs_update_service']) ? 1 : 0;

  $profile_image = '';
  if (!empty($_FILES['profile_image']['name'])) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
      mkdir($target_dir, 0755, true);
    }
    $filename = basename($_FILES["profile_image"]["name"]);
    $safeName = preg_replace("/[^a-zA-Z0-9.\-_]/", "", $filename);
    $target_file = $target_dir . time() . "_" . $safeName;
    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
      $profile_image = basename($target_file);
    }
  }

  if ($first && $surname && $email && $password && $role) {
    if (!isValidPassword($password)) {
      $error = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
    } else {
      $username = explode('@', $email)[0];
      $hashed = password_hash($password, PASSWORD_DEFAULT);
      try {
        $stmt = $pdo->prepare("
          INSERT INTO staff (
            staff_id, first_name, surname, email, username, password, role,
            job_title, start_date, telephone, profile_image,
            dbs_status, dbs_date, dbs_reference, dbs_update_service
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
          $staff_id, $first, $surname, $email, $username, $hashed, $role,
          $job_title, $start_date, $telephone, $profile_image,
          $dbs_status, $dbs_date, $dbs_reference, $dbs_update_service
        ]);
        $success = "Staff member added successfully. Username: " . htmlspecialchars($username);
      } catch (PDOException $e) {
        $error = "Error adding staff: " . $e->getMessage();
      }
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
  <title>Add Staff</title>
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
    .form-card input[readonly] {
      background-color: #eee;
      color: #555;
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
  </style>
</head>
<body>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="form-card">
      <h2>Add New Staff Member</h2>
      <?php if ($success): ?><div class="message success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="message error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <label>Staff ID:</label>
        <input type="text" value="<?= htmlspecialchars($generated_id) ?>" readonly>
        <input type="hidden" name="staff_id" value="<?= htmlspecialchars($generated_id) ?>">

        <label>First Name:</label>
        <input type="text" name="first_name" required>

        <label>Surname:</label>
        <input type="text" name="surname" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>
        <small>Password must be at least 8 characters and include uppercase, lowercase, number, and special character.</small>

        <label>Role:</label>
        <select name="role" required>
          <option value="">-- Select Role --</option>
          <option value="superuser">Superuser</option>
          <option value="admin">Admin</option>
          <option value="staff">Staff</option>
          <option value="tutor">Tutor</option>
          <option value="supervisor">Supervisor</option>
        </select>

        <label>Job Title:</label>
        <input type="text" name="job_title">

        <label>Start Date:</label>
        <input type="date" name="start_date" value="<?= date('Y-m-d') ?>">

        <label>Telephone:</label>
        <input type="text" name="telephone">

        <label>Profile Image:</label>
        <input type="file" name="profile_image" accept="image/*">

        <label>DBS Status:</label>
        <select name="dbs_status">
          <option value="">-- Select Status --</option>
          <option value="Pending">Pending</option>
          <option value="Approved">Approved</option>
          <option value="Expired">Expired</option>
        </select>

        <label>DBS Verified Date:</label>
        <input type="date" name="dbs_date">

        <label>DBS Reference Number:</label>
        <input type="text" name="dbs_reference">

        <label>
          <input type="checkbox" name="dbs_update_service" value="1">
          Registered with DBS Update Service
        </label>

        <button type="submit">Add Staff</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>