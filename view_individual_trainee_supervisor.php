<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff', 'tutor', 'trainee'])) {
  die("Access denied.");
}

// Use GET param if present, fallback to session
$trainee_id = $_GET['trainee_id'] ?? ($_SESSION['trainee_id'] ?? null);

if (!$trainee_id) {
  $error = "Invalid or missing trainee ID.";
} else {
  // Fetch trainee and supervisor details
  $stmt = $pdo->prepare("
    SELECT 
      t.trainee_id,
      t.first_name,
      t.surname,
      t.email,
      t.telephone,
      t.start_date,
      s.supervisor_id,
      s.first_name AS supervisor_first_name,
      s.surname AS supervisor_surname,
      s.email AS supervisor_email
    FROM trainees t
    LEFT JOIN supervisors s ON CAST(t.individual_supervisor AS UNSIGNED) = CAST(s.supervisor_id AS UNSIGNED)
    WHERE t.trainee_id = ?
  ");
  $stmt->execute([$trainee_id]);
  $trainee = $stmt->fetch();

  if (!$trainee) {
    $error = "Trainee not found.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Individual Supervisor</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; }
    .info-block {
      background-color: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      border: 1px solid #ddd;
      max-width: 600px;
    }
    .info-block p {
      margin: 10px 0;
      font-size: 16px;
    }
    .info-block strong {
      color: #6a1b9a;
    }
    .error-message {
      background-color: #ffe6e6;
      border-left: 6px solid #c62828;
      padding: 20px;
      border-radius: 8px;
      max-width: 600px;
      margin-bottom: 20px;
      font-weight: bold;
      color: #c62828;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Individual Supervisor</h2>

    <?php if (!empty($error)): ?>
      <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php else: ?>
      <div class="info-block">
        <p><strong>Trainee:</strong> <?= htmlspecialchars($trainee['first_name'] . ' ' . $trainee['surname']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($trainee['email'] ?? '—') ?></p>
        <p><strong>Telephone:</strong> <?= htmlspecialchars($trainee['telephone'] ?? '—') ?></p>
        <p><strong>Start Date:</strong> <?= htmlspecialchars($trainee['start_date'] ?? '—') ?></p>
        <hr>
        <p><strong>Assigned Supervisor:</strong></p>
        <?php if (!empty($trainee['supervisor_id'])): ?>
          <p><strong>Name:</strong> <?= htmlspecialchars($trainee['supervisor_first_name'] . ' ' . $trainee['supervisor_surname']) ?></p>
          <p><strong>Email:</strong> <?= htmlspecialchars($trainee['supervisor_email'] ?? '—') ?></p>
        <?php else: ?>
          <p><em>No supervisor assigned.</em></p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>