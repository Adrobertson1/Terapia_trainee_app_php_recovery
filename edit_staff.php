<?php
session_start();
require 'db.php';

$staff_id = $_GET['staff_id'] ?? null;
if (!$staff_id || !is_numeric($staff_id)) {
  die("Invalid staff ID.");
}

$can_edit = in_array($_SESSION['role'], ['superuser', 'admin']);

$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

if (!$staff) {
  die("Staff member not found.");
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
  $first_name = trim($_POST['first_name']);
  $surname = trim($_POST['surname']);
  $email = trim($_POST['email']);
  $role = $_POST['role'];
  $job_title = trim($_POST['job_title']);
  $start_date = $_POST['start_date'];
  $telephone = trim($_POST['telephone']);
  $dbs_status = $_POST['dbs_status'];
  $dbs_date = $_POST['dbs_date'];
  $dbs_reference = trim($_POST['dbs_reference']);
  $dbs_update_service = isset($_POST['dbs_update_service']) ? 1 : 0;
  $change_reason = trim($_POST['change_reason'] ?? '');

  $profileImagePath = $staff['profile_image'];
  if (!empty($_FILES['profile_image']['name'])) {
    $targetDir = "uploads/";
    $fileName = basename($_FILES['profile_image']['name']);
    $safeName = preg_replace("/[^a-zA-Z0-9.\-_]/", "", $fileName);
    $profileImagePath = $targetDir . time() . "_" . $safeName;

    if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $profileImagePath)) {
      $errors[] = "Failed to upload profile image.";
    }
  }

  if ($first_name === '') $errors[] = "First name is required.";
  if ($surname === '') $errors[] = "Surname is required.";
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
  if (!strtotime($start_date)) $errors[] = "Valid start date is required.";

  if (empty($errors)) {
    // Audit role change if different
    if ($staff['role'] !== $role) {
      $stmt = $pdo->prepare("
        INSERT INTO role_audit_log (user_id, previous_role, new_role, changed_by, change_reason, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([
        $staff_id,
        $staff['role'],
        $role,
        $_SESSION['user_id'],
        $change_reason ?: 'Role updated via edit_staff',
        $_SERVER['REMOTE_ADDR']
      ]);
    }

    $stmt = $pdo->prepare("
      UPDATE staff SET
        first_name = ?, surname = ?, email = ?, role = ?, job_title = ?, start_date = ?,
        telephone = ?, profile_image = ?, dbs_status = ?, dbs_date = ?, dbs_reference = ?, dbs_update_service = ?
      WHERE staff_id = ?
    ");
    $stmt->execute([
      $first_name, $surname, $email, $role, $job_title, $start_date,
      $telephone, $profileImagePath, $dbs_status, $dbs_date, $dbs_reference, $dbs_update_service,
      $staff_id
    ]);
    header("Location: staff.php?updated=1");
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Staff Member</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .form-box {
      background: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      max-width: 700px;
      margin-bottom: 30px;
      border: 1px solid #ccc;
    }
    .form-box label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
    }
    .form-box input, .form-box select, .form-box textarea {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 16px;
    }
    .form-box input[readonly], .form-box select[disabled] {
      background-color: #eaeaea;
      color: #555;
    }
    .form-box button {
      margin-top: 20px;
      padding: 12px;
      background-color: #850069;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      font-size: 18px;
    }
    .form-box button:hover {
      background-color: #BB9DC6;
    }
    .error-list {
      background-color: #ffe0e0;
      color: #b71c1c;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 20px;
    }
    .thumbnail {
      max-width: 100px;
      height: auto;
      margin-bottom: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2><?= $can_edit ? 'Edit' : 'View' ?> Staff Member</h2>

    <div class="form-box">
      <?php if (!empty($errors)): ?>
        <div class="error-list">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <label>First Name:</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($staff['first_name']) ?>" <?= $can_edit ? 'required' : 'readonly' ?>>

        <label>Surname:</label>
        <input type="text" name="surname" value="<?= htmlspecialchars($staff['surname']) ?>" <?= $can_edit ? 'required' : 'readonly' ?>>

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($staff['email']) ?>" <?= $can_edit ? 'required' : 'readonly' ?>>

        <label>Username:</label>
        <input type="text" value="<?= htmlspecialchars($staff['username']) ?>" readonly>

        <label>Role:</label>
        <select name="role" <?= $can_edit ? 'required' : 'disabled' ?>>
          <?php
          $roles = ['superuser', 'admin', 'staff', 'tutor', 'supervisor'];
          foreach ($roles as $r) {
            $selected = ($staff['role'] === $r) ? 'selected' : '';
            echo "<option value=\"$r\" $selected>" . ucfirst($r) . "</option>";
          }
          ?>
        </select>

        <?php if ($can_edit): ?>
          <label>Reason for Role Change:</label>
          <textarea name="change_reason" rows="3" placeholder="Explain why the role is being changed..."></textarea>
        <?php endif; ?>

        <label>Job Title:</label>
        <input type="text" name="job_title" value="<?= htmlspecialchars($staff['job_title']) ?>" <?= $can_edit ? '' : 'readonly' ?>>

        <label>Start Date:</label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($staff['start_date']) ?>" <?= $can_edit ? 'required' : 'readonly' ?>>

        <label>Telephone:</label>
        <input type="text" name="telephone" value="<?= htmlspecialchars($staff['telephone']) ?>" <?= $can_edit ? '' : 'readonly' ?>>

        <label>DBS Status:</label>
        <select name="dbs_status" <?= $can_edit ? '' : 'disabled' ?>>
          <?php
          $statuses = ['Pending', 'Approved', 'Expired'];
          foreach ($statuses as $status) {
            $selected = ($staff['dbs_status'] === $status) ? 'selected' : '';
            echo "<option value=\"$status\" $selected>$status</option>";
          }
          ?>
        </select>

        <label>DBS Verified Date:</label>
        <input type="date" name="dbs_date" value="<?= htmlspecialchars($staff['dbs_date']) ?>" <?= $can_edit ? '' : 'readonly' ?>>

                <label>DBS Reference Number:</label>
        <input type="text" name="dbs_reference" value="<?= htmlspecialchars($staff['dbs_reference']) ?>" <?= $can_edit ? '' : 'readonly' ?>>

        <label>
          <input type="checkbox" name="dbs_update_service" value="1" <?= $staff['dbs_update_service'] ? 'checked' : '' ?> <?= $can_edit ? '' : 'disabled' ?>>
          Registered with DBS Update Service
        </label>

        <?php if (!empty($staff['profile_image']) && file_exists($staff['profile_image'])): ?>
          <label>Current Profile Image:</label>
          <img src="<?= htmlspecialchars($staff['profile_image']) ?>" alt="Profile Image" class="thumbnail">
        <?php endif; ?>

        <?php if ($can_edit): ?>
          <label>Upload New Profile Image:</label>
          <input type="file" name="profile_image" accept="image/*">

          <button type="submit">Update Staff Member</button>
        <?php else: ?>
          <p><em>You do not have permission to edit this record.</em></p>
        <?php endif; ?>
      </form>
    </div>

    <a href="staff.php" class="btn-sm btn-default">← Back to Staff List</a>
  </div>
</div>
</body>
</html>