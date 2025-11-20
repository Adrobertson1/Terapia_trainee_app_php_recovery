<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin'])) {
  die("Access denied.");
}

$staff_id = $_GET['id'] ?? null;
if (!$staff_id) {
  die("No staff ID provided.");
}

$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

if (!$staff) {
  die("Staff member not found.");
}

$photoPath = '';
if (!empty($staff['profile_image'])) {
  $photoPath = 'uploads/' . basename($staff['profile_image']);
  if (!file_exists($photoPath)) {
    $photoPath = '';
  }
}

$statusLabel = $staff['is_archived'] ? 'Archived' : 'Active';
$statusColor = $staff['is_archived'] ? '#d32f2f' : '#4CAF50';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Staff Member</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .profile-box {
      background: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      max-width: 600px;
      margin-bottom: 30px;
      border: 1px solid #ccc;
    }
    .profile-label {
      font-weight: bold;
      display: inline-block;
      width: 150px;
    }
    .profile-field {
      margin-bottom: 10px;
    }
    .profile-photo img {
      max-width: 120px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }
    .btn-back, .btn-edit {
      margin-top: 20px;
      display: inline-block;
      padding: 8px 16px;
      background-color: #850069;
      color: #fff;
      text-decoration: none;
      border-radius: 4px;
      margin-right: 10px;
    }
    .btn-back:hover, .btn-edit:hover {
      background-color: #BB9DC6;
    }
    .status-tag {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 4px;
      font-weight: bold;
      color: white;
      background-color: <?= $statusColor ?>;
      margin-left: 10px;
      font-size: 0.9em;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>View Staff Member</h2>

    <div class="profile-box">
      <h3>
        <?= htmlspecialchars($staff['first_name'] . ' ' . $staff['surname']) ?>
        <span class="status-tag"><?= $statusLabel ?></span>
      </h3>

      <div class="profile-photo">
        <?php if ($photoPath): ?>
          <img src="<?= $photoPath ?>" alt="Staff Photo">
        <?php else: ?>
          <em>No photo available.</em>
        <?php endif; ?>
      </div>

      <div class="profile-field">
        <span class="profile-label">Email:</span>
        <?= htmlspecialchars($staff['email'] ?? '-') ?>
      </div>

      <div class="profile-field">
        <span class="profile-label">Telephone:</span>
        <?= htmlspecialchars($staff['telephone'] ?? '-') ?>
      </div>

      <div class="profile-field">
        <span class="profile-label">Job Title:</span>
        <?= htmlspecialchars($staff['job_title'] ?? '-') ?>
      </div>

      <div class="profile-field">
        <span class="profile-label">Start Date:</span>
        <?= htmlspecialchars($staff['start_date'] ?? '-') ?>
      </div>

      <div class="profile-field">
        <span class="profile-label">DBS Status:</span>
        <?= htmlspecialchars($staff['dbs_status'] ?? '-') ?>
      </div>

      <div class="profile-field">
        <span class="profile-label">Role:</span>
        <?= htmlspecialchars($staff['role'] ?? 'Staff') ?>
      </div>

      <a href="staff.php?view=<?= $staff['is_archived'] ? 'archived' : 'active' ?>" class="btn-back">← Back to List</a>
      <a href="edit_staff.php?staff_id=<?= urlencode($staff['staff_id']) ?>" class="btn-edit">✏️ Edit Staff</a>
    </div>
  </div>
</div>
</body>
</html>