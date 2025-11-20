<?php
session_start();
require 'db.php';
require_once 'functions.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff', 'tutor'])) {
  die("Access denied.");
}

$submission_id = $_GET['id'] ?? null;
if (!$submission_id || !is_numeric($submission_id)) {
  die("Invalid submission ID.");
}

// Fetch submission details
$stmt = $pdo->prepare("
  SELECT 
    s.submission_id, s.assignment_id, s.trainee_id, s.submitted_date, s.status,
    s.score_percent, s.feedback_text, s.feedback_file,
    t.first_name, t.surname,
    at.type_name
  FROM assignment_submissions s
  LEFT JOIN trainees t ON s.trainee_id = t.trainee_id
  LEFT JOIN trainee_assignments a ON s.assignment_id = a.id
  LEFT JOIN assignment_types at ON a.type_id = at.type_id
  WHERE s.submission_id = ?
");
$stmt->execute([$submission_id]);
$submission = $stmt->fetch();

if (!$submission) {
  die("Submission not found.");
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $score = $_POST['score_percent'] ?? null;
  $status = $_POST['pass_status'] ?? null;
  $text = trim($_POST['feedback_text'] ?? '');
  $grader = $_SESSION['user_id'];
  $timestamp = date('Y-m-d H:i:s');

  // Validate inputs
  if (!is_numeric($score) || $score < 0 || $score > 100) {
    $errors[] = "Score must be between 0 and 100.";
  }
  if (!in_array($status, ['Pass', 'Fail'])) {
    $errors[] = "Invalid pass status.";
  }

  // Handle file upload
  $filePath = $submission['feedback_file'];
  if (!empty($_FILES['feedback_file']['name'])) {
    $targetDir = "feedback/";
    if (!is_dir($targetDir)) {
      mkdir($targetDir, 0755, true);
    }
    $safeName = preg_replace("/[^a-zA-Z0-9.\-_]/", "", basename($_FILES['feedback_file']['name']));
    $filePath = $targetDir . time() . "_" . $safeName;

    if (!move_uploaded_file($_FILES['feedback_file']['tmp_name'], $filePath)) {
      $errors[] = "Failed to upload feedback file.";
    }
  }

  if (empty($errors)) {
    // Update submission
    $stmt = $pdo->prepare("
      UPDATE assignment_submissions SET
        score_percent = ?, pass_status = ?, feedback_text = ?, feedback_file = ?,
        graded_by = ?, graded_at = ?, status = 'graded'
      WHERE submission_id = ?
    ");
    $stmt->execute([
      $score, $status, $text, $filePath,
      $grader, $timestamp, $submission_id
    ]);

    // Insert audit log
    $logStmt = $pdo->prepare("
      INSERT INTO submission_audit_log (
        submission_id, graded_by, score_percent, pass_status, feedback_text, feedback_file, timestamp
      ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $logStmt->execute([
      $submission_id, $grader, $score, $status, $text, $filePath, $timestamp
    ]);

    logAction($pdo, $_SESSION['user_id'], $_SESSION['role'], 'grade_submission', "Graded submission ID $submission_id with $score% ($status)");
    header("Location: assignment_submissions.php?graded=1");
    exit;
  }
}

// Fetch audit log
$logQuery = $pdo->prepare("
  SELECT l.*, s.first_name, s.surname
  FROM submission_audit_log l
  LEFT JOIN staff s ON l.graded_by = s.staff_id
  WHERE l.submission_id = ?
  ORDER BY l.timestamp DESC
");
$logQuery->execute([$submission_id]);
$logs = $logQuery->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Grade Submission</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; max-width: 800px; margin: auto; }
    .form-box {
      background: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      border: 1px solid #ccc;
      margin-bottom: 40px;
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
    .form-box button {
      margin-top: 20px;
      padding: 12px;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      font-size: 18px;
    }
    .form-box button:hover {
      background-color: #388E3C;
    }
    .error-list {
      background-color: #ffe0e0;
      color: #b71c1c;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 20px;
    }
    table.audit-log {
      width: 100%;
      border-collapse: collapse;
    }
    table.audit-log th, table.audit-log td {
      padding: 10px;
      border: 1px solid #ccc;
      text-align: left;
    }
    table.audit-log th {
      background-color: #f0f0f0;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Grade Submission for <?= htmlspecialchars($submission['first_name'] . ' ' . $submission['surname']) ?></h2>
    <p><strong>Assignment Type:</strong> <?= htmlspecialchars($submission['type_name']) ?></p>
    <p><strong>Submitted Date:</strong> <?= htmlspecialchars($submission['submitted_date']) ?></p>

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
        <label>Score (%):</label>
        <input type="number" name="score_percent" min="0" max="100" step="0.1" required>

        <label>Pass Status:</label>
        <select name="pass_status" required>
          <option value="Pass">Pass – Permitted to Proceed</option>
          <option value="Fail">Fail</option>
        </select>

        <label>Feedback (Text):</label>
        <textarea name="feedback_text" rows="4" placeholder="Write feedback for the trainee..."></textarea>

        <label>Upload Feedback File:</label>
        <input type="file" name="feedback_file" accept=".pdf,.doc,.docx,.txt">

        <button type="submit">Submit Grade & Feedback</button>
      </form>
    </div>

    <h3>Grade Change History</h3>
    <?php if (empty($logs)): ?>
      <p>No grading history found.</p>
    <?php else: ?>
      <table class="audit-log">
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>Graded By</th>
            <th>Score (%)</th>
            <th>Status</th>
            <th>Feedback</th>
            <th>File</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td><?= date('j M Y, H:i', strtotime($log['timestamp'])) ?></td>
              <td><?= htmlspecialchars($log['first_name'] . ' ' . $log['surname']) ?></td>
              <td><?= htmlspecialchars($log['score_percent']) ?>%</td>
              <td><?= htmlspecialchars($log['pass_status']) ?></td>
              <td><?= nl2br(htmlspecialchars($log['feedback_text'])) ?></td>
              <td>
                <?php if (!empty($log['feedback_file'])): ?>
  <a href="<?= htmlspecialchars($log['feedback_file']) ?>" target="_blank">Download</a>
<?php else: ?>
  <em>None</em>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>