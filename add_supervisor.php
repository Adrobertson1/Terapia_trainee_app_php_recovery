<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin'])) {
  die("Access denied.");
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Sanitize inputs
  $first_name = trim($_POST['first_name']);
  $surname = trim($_POST['surname']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $job_title = trim($_POST['job_title']);
  $start_date = $_POST['start_date'] ?? null;
  $telephone = trim($_POST['telephone']);
  $address_line1 = trim($_POST['address_line1']);
  $town_city = trim($_POST['town_city']);
  $postcode = trim($_POST['postcode']);
  $disability_status = trim($_POST['disability_status']);
  $disability_type = trim($_POST['disability_type']);
  $is_archived = isset($_POST['is_archived']) ? 1 : 0;

  // Validate required fields
  if (!$first_name || !$surname || !$email || !$password) {
    $errors[] = "First name, surname, email, and password are required.";
  }

  // Handle profile image upload
  $profile_image = null;
  if (!empty($_FILES['profile_image']['name'])) {
    $target_dir = "uploads/";
    $filename = uniqid() . "_" . basename($_FILES["profile_image"]["name"]);
    $target_file = $target_dir . $filename;

    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
      $profile_image = $target_file;
    } else {
      $errors[] = "Failed to upload profile image.";
    }
  }

  // Insert if no errors
  if (empty($errors)) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
      INSERT INTO supervisors (
        first_name, surname, email, password, role, job_title, start_date,
        telephone, address_line1, town_city, postcode,
        disability_status, disability_type, profile_image, is_archived
      ) VALUES (
        ?, ?, ?, ?, 'supervisor', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
      )
    ");

    $stmt->execute([
      $first_name, $surname, $email, $hashed_password, $job_title, $start_date,
      $telephone, $address_line1, $town_city, $postcode,
      $disability_status, $disability_type, $profile_image, $is_archived
    ]);

    header("Location: supervisors.php");
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Supervisor</title>
  <link rel="stylesheet" href="style.css">
  <style>
    form {
      max-width: 700px;
      margin: 40px auto;
      background: #fff;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
    }
    input, select {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
    }
    button {
      margin-top: 20px;
      padding: 10px 16px;
      background-color: #1976d2;
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 1em;
    }
    button:hover {
      background-color: #0d47a1;
    }
    .error {
      color: red;
      margin-top: 10px;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Add New Supervisor</h2>

    <?php if (!empty($errors)): ?>
      <div class="error">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <label>First Name</label>
      <input type="text" name="first_name" required>

      <label>Surname</label>
      <input type="text" name="surname" required>

      <label>Email</label>
      <input type="email" name="email" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <label>Job Title</label>
      <input type="text" name="job_title">

      <label>Start Date</label>
      <input type="date" name="start_date">

      <label>Telephone</label>
      <input type="text" name="telephone">

      <label>Address Line 1</label>
      <input type="text" name="address_line1">

      <label>Town/City</label>
      <input type="text" name="town_city">

      <label>Postcode</label>
      <input type="text" name="postcode">

      <label>Disability Status</label>
      <input type="text" name="disability_status">

      <label>Disability Type</label>
      <input type="text" name="disability_type">

      <label>Profile Image</label>
      <input type="file" name="profile_image">

      <label>
        <input type="checkbox" name="is_archived"> Archive this supervisor
      </label>

      <button type="submit">Add Supervisor</button>
    </form>
  </div>
</div>
</body>
</html>