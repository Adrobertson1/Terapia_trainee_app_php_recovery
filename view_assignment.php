<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff', 'tutor', 'supervisor'])) {
  die("Access denied.");
}

$assignment_id = $_GET['id'] ?? null;
if (!$assignment_id || !is_numeric($assignment_id)) {
  die("Invalid assignment ID.");
}

// Fetch assignment details
$stmt = $pdo->prepare("
  SELECT 
    ta.id AS trainee_assignment_id,
    ta.assignment_id AS true_assignment_id,
    ta.trainee_id,
    ta.type_id,
    ta.assigned_date,
    ta.due_date,
    ta.status,
    ta.assigned_by,
    ta.assignment_description,
    ta.assignment_instructions,
    ta.assignment_notes,
    u.username AS assigned_by_name,
    u.email AS assigned_by_email,
    t.first_name AS trainee_first_name,
    t.surname AS trainee_surname,
    at.type_name AS assignment_type
  FROM trainee_assignments ta
  LEFT JOIN users u ON ta.assigned_by = u.user_id
  LEFT JOIN trainees t ON ta.trainee_id = t.trainee_id
  LEFT JOIN assignment_types at ON ta.type_id = at.type_id
  WHERE ta.id = ?
");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch();

if (!$assignment) {
  die("Assignment not found.");
}

// Fetch submission record
$submissionStmt = $pdo->prepare("
  SELECT submitted_date, file_path, status, score_percent, feedback_text
  FROM assignment_submissions
  WHERE assignment_id = ? AND trainee_id = ?
  ORDER BY submitted_date DESC
  LIMIT 1
");
$submissionStmt->execute([$assignment['true_assignment_id'], $assignment['trainee_id']]);
$submission = $submissionStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Assignment Details</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; }
    .detail-box {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      max-width: 800px;
    }
    .detail-box p { margin-bottom: 10px; }
    .label { font-weight: bold; color: #6a1b9a; }
    .btn {
      margin-top: 20px;
      padding: 8px 16px;
      background-color: #6a1b9a;
      color: white;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      display: inline-block;
    }
    .btn:hover { background-color: #8e24aa; }
    .status-pending { color: orange; font-weight: bold; }
    .status-submitted { color: blue; font-weight: bold; }
    .status-graded { color: green; font-weight: bold; }
    .profile-link {
      color: #6a1b9a;
      text-decoration: none;
      font-weight: bold;
    }
    .profile-link:hover {
      text-decoration: underline;
    }
    .submission-section {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #ccc;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Assignment Details</h2>
    <div class="detail-box">
      <p><span class="label">Assignment ID:</span> <?= htmlspecialchars($assignment['trainee_assignment_id']) ?></p>
      <p><span class="label">Trainee:</span> 
        <a href="view_trainee.php?id=<?= urlencode($assignment['trainee_id']) ?>" class="profile-link">
          <?= htmlspecialchars($assignment['trainee_first_name'] . ' ' . $assignment['trainee_surname']) ?>
        </a>
      </p>
      <p><span class="label">Assignment Type:</span> <?= htmlspecialchars($assignment['assignment_type']) ?></p>
      <p><span class="label">Assigned Date:</span> <?= htmlspecialchars($assignment['assigned_date']) ?></p>
      <p><span class="label">Due Date:</span> <?= htmlspecialchars($assignment['due_date']) ?></p>
      <p><span class="label">Status:</span> <span class="status-<?= htmlspecialchars($assignment['status']) ?>">
        <?= ucfirst(htmlspecialchars($assignment['status'])) ?>
      </span></p>
      <hr>
      <p><span class="label">Assignment Description:</span><br><?= nl2br(htmlspecialchars($assignment['assignment_description'] ?? '—')) ?></p>
      <p><span class="label">Instructions:</span><br><?= nl2br(htmlspecialchars($assignment['assignment_instructions'] ?? '—')) ?></p>
      <p><span class="label">Additional Notes:</span><br><?= nl2br(htmlspecialchars($assignment['assignment_notes'] ?? '—')) ?></p>
      <p><span class="label">Assignee:</span> <?= htmlspecialchars($assignment['assigned_by_name']) ?> (<?= htmlspecialchars($assignment['assigned_by_email']) ?>)</p>

      <div class="submission-section">
        <h3>Submission Record</h3>
        <?php if ($submission): ?>
          <p><span class="label">Submitted Date:</span> <?= htmlspecialchars(date('j M Y, H:i', strtotime($submission['submitted_date']))) ?></p>
          <p><span class="label">Status:</span> <?= htmlspecialchars($submission['status']) ?></p>
          <?php if (!empty($submission['feedback_text'])): ?>
            <p><span class="label">Feedback:</span><br><?= nl2br(htmlspecialchars($submission['feedback_text'])) ?></p>
          <?php endif; ?>
          <?php if (is_numeric($submission['score_percent'])): ?>
            <p><span class="label">Score:</span> <?= htmlspecialchars($submission['score_percent']) ?>%</p>
          <?php endif; ?>
          <?php if (!empty($submission['file_path']) && file_exists($submission['file_path'])): ?>
            <p><span class="label">Submitted File:</span><br>
              <a class="btn" href="<?= htmlspecialchars($submission['file_path']) ?>" download>
                ⬇ Download Submitted Assignment
              </a>
              <br><small><?= htmlspecialchars(basename($submission['file_path'])) ?></small>
            </p>
          <?php endif; ?>
        <?php else: ?>
          <p><em>No assignment file submitted.</em></p>
        <?php endif; ?>
      </div>
    </div>
    <a class="btn" href="view_all_assignments.php">← Back to All Assignments</a>
  </div>
</div>
</body>
</html>