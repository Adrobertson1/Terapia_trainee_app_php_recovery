<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$supervisor_id = $_GET['id'] ?? null;

if (!$supervisor_id) {
  die("Invalid access.");
}

// Fetch supervisor details
$stmt = $pdo->prepare("
  SELECT supervisor_id, first_name, surname, email, telephone, job_title, start_date, profile_image
  FROM supervisors
  WHERE supervisor_id = ?
");
$stmt->execute([$supervisor_id]);
$supervisor = $stmt->fetch();

if (!$supervisor) {
  die("Supervisor not found.");
}

// Fetch assigned trainees
$tStmt = $pdo->prepare("
  SELECT trainee_id, first_name, surname
  FROM trainees
  WHERE supervisor_id = ?
  ORDER BY surname
");
$tStmt->execute([$supervisor_id]);
$trainees = $tStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Supervisor Profile</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; }
    .profile-card {
      background-color: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      border: 1px solid #ddd;
      max-width: 700px;
    }
    .profile-card img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid #ccc;
      margin-bottom: 10px;
    }
    .profile-card p {
      margin: 8px 0;
      font-size: 16px;
    }
    .profile-card strong {
      color: #6a1b9a;
    }
    .trainee-list {
      margin-top: 20px;
    }
    .trainee-list ul {
      padding-left: 20px;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Supervisor Profile</h2>

    <div class="profile-card">
      <?php
      $imagePath = $supervisor['profile_image'];
      if (!empty($imagePath) && file_exists($imagePath)) {
        echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Supervisor Photo">';
      } else {
        echo '<span style="color:#999;">No photo available</span>';
      }
      ?>
      <p><strong>Name:</strong> <?= htmlspecialchars($supervisor['first_name'] . ' ' . $supervisor['surname']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($supervisor['email'] ?? '—') ?></p>
      <p><strong>Telephone:</strong> <?= htmlspecialchars($supervisor['telephone'] ?? '—') ?></p>
      <p><strong>Job Title:</strong> <?= htmlspecialchars($supervisor['job_title'] ?? '—') ?></p>
      <p><strong>Start Date:</strong> <?= htmlspecialchars($supervisor['start_date'] ?? '—') ?></p>
    </div>

    <div class="trainee-list">
      <h3>Assigned Trainees</h3>
      <?php if (empty($trainees)): ?>
        <p><em>No trainees assigned.</em></p>
      <?php else: ?>
        <ul>
          <?php foreach ($trainees as $t): ?>
            <li><?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>