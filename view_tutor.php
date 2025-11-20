<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$tutor_id = $_GET['id'] ?? null;

if (!$tutor_id) {
  die("No tutor ID provided.");
}

$stmt = $pdo->prepare("SELECT * FROM tutors WHERE tutor_id = ?");
$stmt->execute([$tutor_id]);
$tutor = $stmt->fetch();

if (!$tutor) {
  die("Tutor not found.");
}

$photoPath = '';
if (!empty($tutor['profile_image'])) {
  $photoPath = 'uploads/' . basename($tutor['profile_image']);
  if (!file_exists($photoPath)) {
    $photoPath = '';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Tutor</title>
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
    .btn-back {
      margin-top: 20px;
      display: inline-block;
      padding: 8px 16px;
      background-color: #850069;
      color: #fff;
      text-decoration: none;
      border-radius: 4px;
    }
    .btn-back:hover {
      background-color: #BB9DC6;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>View Tutor</h2>

    <div class="profile-box">
      <h3><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['surname']) ?></h3>

      <div class="profile-photo">
        <?php if ($photoPath): ?>
          <img src="<?= $photoPath ?>" alt="Tutor Photo">
        <?php else: ?>
          <em>No photo available.</em>
        <?php endif; ?>
      </div>

      <div class="profile-field">
        <span class="profile-label">Email:</span>
        <?= htmlspecialchars($tutor['email'] ?? '-') ?>
      </div>

      <div class="profile-field">
        <span class="profile-label">Telephone:</span>
        <?= htmlspecialchars($tutor['telephone'] ?? '-') ?>
      </div>

      <div class="profile-field">
        <span class="profile-label">Start Date:</span>
        <?= htmlspecialchars($tutor['start_date'] ?? '-') ?>
      </div>

      <div class="profile-field">
        <span class="profile-label">Role:</span>
        <?= htmlspecialchars($tutor['role'] ?? 'Tutor') ?>
      </div>

      <a href="tutors.php?view=<?= $tutor['is_archived'] ? 'archived' : 'active' ?>" class="btn-back">← Back to List</a>
    </div>
  </div>
</div>
</body>
</html>