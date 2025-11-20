<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff', 'tutor', 'trainee'])) {
  die("Access denied.");
}

// Get trainee_id from session user
$stmt = $pdo->prepare("SELECT trainee_id FROM trainees WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$trainee = $stmt->fetch();

// Fetch grades for this trainee
$stmt = $pdo->prepare("
    SELECT a.title AS assignment_title, c.course_name, s.file_path, g.grade, g.feedback, g.graded_at
    FROM grades g
    JOIN submissions s ON g.submission_id = s.submission_id
    JOIN assignments a ON s.assignment_id = a.assignment_id
    JOIN courses c ON a.course_id = c.course_id
    WHERE s.trainee_id = ?
    ORDER BY g.graded_at DESC
");
$stmt->execute([$trainee['trainee_id']]);
$grades = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Grades</title>
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
        <h2>My Grades</h2>
      </div>

      <?php if (empty($grades)): ?>
        <p>No grades available yet.</p>
      <?php else: ?>
        <table class="calendar-grid">
          <thead>
            <tr>
              <th>Course</th>
              <th>Assignment</th>
              <th>Submission</th>
              <th>Grade</th>
              <th>Feedback</th>
              <th>Graded At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($grades as $g): ?>
              <tr>
                <td><?= htmlspecialchars($g['course_name']); ?></td>
                <td><?= htmlspecialchars($g['assignment_title']); ?></td>
                <td>
                  <?php if ($g['file_path']): ?>
                    <a href="<?= htmlspecialchars($g['file_path']); ?>" target="_blank">Download</a>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($g['grade']); ?></td>
                <td><?= nl2br(htmlspecialchars($g['feedback'])); ?></td>
                <td><?= htmlspecialchars($g['graded_at']); ?></td>
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