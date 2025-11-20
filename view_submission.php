<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'tutor'])) {
  die("Access denied.");
}

$assignment_id = $_GET['id'] ?? null;
if (!$assignment_id) die("No assignment specified");

// Get submissions for this assignment
$stmt = $pdo->prepare("
    SELECT s.submission_id, s.file_path, s.submitted_at,
           t.first_name, t.surname, g.grade, g.feedback
    FROM submissions s
    JOIN trainees t ON s.trainee_id = t.trainee_id
    LEFT JOIN grades g ON s.submission_id = g.submission_id
    WHERE s.assignment_id = ?");
$stmt->execute([$assignment_id]);
$submissions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Submissions</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <div class="page-container">
      <div class="page-header">
        <h2>Submissions</h2>
        <a href="assignments.php" class="btn">Back to Assignments</a>
      </div>

      <?php if (empty($submissions)): ?>
        <p>No submissions found for this assignment.</p>
      <?php else: ?>
        <table class="calendar-grid">
          <thead>
            <tr>
              <th>Trainee</th>
              <th>File</th>
              <th>Submitted</th>
              <th>Grade</th>
              <th>Feedback</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($submissions as $s): ?>
              <tr>
                <td><?= htmlspecialchars($s['first_name'] . " " . $s['surname']); ?></td>
                <td><a href="<?= htmlspecialchars($s['file_path']); ?>" target="_blank">Download</a></td>
                <td><?= htmlspecialchars($s['submitted_at']); ?></td>
                <td><?= htmlspecialchars($s['grade']); ?></td>
                <td><?= htmlspecialchars($s['feedback']); ?></td>
                <td>
                  <a href="grade_submission.php?submission_id=<?= $s['submission_id']; ?>" class="btn">Grade</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>