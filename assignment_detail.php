<?php
session_start();
require 'db.php';

$assignment_id = $_GET['id'] ?? null;
$trainee_id = $_SESSION['user_id'] ?? null;

if (!$assignment_id || !$trainee_id) {
  die("Invalid access.");
}

// Fetch assignment details
$stmt = $pdo->prepare("
  SELECT a.title, a.description AS core_description, a.due_date, c.course_name,
         ta.assignment_description, ta.assignment_instructions, ta.assignment_notes,
         ta.assigned_date, ta.status, u.username AS assigned_by
  FROM trainee_assignments ta
  JOIN assignments a ON ta.assignment_id = a.assignment_id
  JOIN courses c ON a.course_id = c.course_id
  JOIN users u ON ta.assigned_by = u.user_id
  WHERE ta.trainee_id = ? AND ta.assignment_id = ?
");
$stmt->execute([$trainee_id, $assignment_id]);
$details = $stmt->fetch();

if (!$details) {
  die("Assignment not found.");
}

// Fetch submission status
$submissionStmt = $pdo->prepare("
  SELECT status, score_percent, feedback_file
  FROM assignment_submissions
  WHERE trainee_id = ? AND assignment_id = ?
  ORDER BY submitted_date DESC LIMIT 1
");
$submissionStmt->execute([$trainee_id, $assignment_id]);
$submission = $submissionStmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Assignment Details</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; }
    .submit-button {
      display: inline-block;
      margin-top: 30px;
      padding: 12px 20px;
      background-color: #6a1b9a;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
    }
    .submit-button:hover {
      background-color: #8e24aa;
    }
    .graded { color: green; font-weight: bold; }
    .not-graded { color: #999; font-style: italic; }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="main-content">
  <h2><?= htmlspecialchars($details['title']) ?></h2>
  <p><strong>Course:</strong> <?= htmlspecialchars($details['course_name']) ?></p>
  <p><strong>Due Date:</strong> <?= htmlspecialchars(date('j M Y', strtotime($details['due_date']))) ?></p>
  <p><strong>Status:</strong>
    <?php if ($submission): ?>
      <span class="<?= $submission['status'] === 'graded' ? 'graded' : 'not-graded' ?>">
        <?= ucfirst($submission['status']) ?>
      </span>
    <?php else: ?>
      <span class="not-graded">Awaiting Submission</span>
    <?php endif; ?>
  </p>

  <?php if ($submission): ?>
    <p><strong>Score:</strong>
      <?= is_numeric($submission['score_percent']) ? htmlspecialchars($submission['score_percent']) . '%' : '<em>—</em>' ?>
    </p>
    <p><strong>Feedback File:</strong>
      <?php if (!empty($submission['feedback_file'])): ?>
        <a href="<?= htmlspecialchars($submission['feedback_file']) ?>" target="_blank">Download</a>
      <?php else: ?>
        <em>—</em>
      <?php endif; ?>
    </p>
  <?php endif; ?>

  <hr>
  <p><strong>Core Description:</strong><br><?= nl2br(htmlspecialchars($details['core_description'])) ?></p>
  <p><strong>Custom Description:</strong><br><?= nl2br(htmlspecialchars($details['assignment_description'])) ?></p>
  <p><strong>Instructions:</strong><br><?= nl2br(htmlspecialchars($details['assignment_instructions'])) ?></p>
  <p><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($details['assignment_notes'])) ?></p>
  <p><strong>Assigned By:</strong> <?= htmlspecialchars($details['assigned_by']) ?></p>
  <p><strong>Assigned Date:</strong> <?= htmlspecialchars($details['assigned_date']) ?></p>

  <?php if (!$submission || !in_array($submission['status'], ['submitted', 'graded', 'late'])): ?>
    <a class="submit-button" href="submit_assignment.php?assignment_id=<?= urlencode($assignment_id) ?>">Submit Assignment</a>
  <?php endif; ?>
</div>
</body>
</html>