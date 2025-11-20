<?php
session_start();
require 'db.php';

// Allow access to tutors, trainees, staff, and admins
if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff', 'trainee', 'tutor'])) {
  die("Access denied.");
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role === 'trainee') {
  // Trainee: show assignments explicitly assigned to them via trainee_assignments.assignment_id
  $stmt = $pdo->prepare("
    SELECT 
      a.assignment_id, a.title, a.description, a.due_date, c.course_name,
      s.status AS submission_status, s.score_percent, s.feedback_file
    FROM assignments a
    JOIN courses c ON a.course_id = c.course_id
    JOIN trainee_assignments ta ON ta.assignment_id = a.assignment_id
    LEFT JOIN assignment_submissions s ON s.assignment_id = a.assignment_id AND s.trainee_id = ta.trainee_id
    WHERE ta.trainee_id = ?
    ORDER BY a.due_date ASC
  ");
  $stmt->execute([$user_id]);
} elseif ($role === 'tutor') {
  // Tutor: show assignments they created or are responsible for
  $stmt = $pdo->prepare("
    SELECT a.assignment_id, a.title, a.description, a.due_date, c.course_name
    FROM assignments a
    JOIN courses c ON a.course_id = c.course_id
    WHERE a.tutor_id = ?
    ORDER BY a.due_date ASC
  ");
  $stmt->execute([$user_id]);
} else {
  // Admin/staff: show all assignments
  $stmt = $pdo->query("
    SELECT a.assignment_id, a.title, a.description, a.due_date, c.course_name
    FROM assignments a
    JOIN courses c ON a.course_id = c.course_id
    ORDER BY a.due_date ASC
  ");
}

$assignments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Assignments</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: #F4F0F8; margin: 0; }
    .dashboard-wrapper { display: flex; }
    .main-content { flex: 1; padding: 40px; }
    h2 { color: #6a1b9a; margin-bottom: 20px; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      padding: 12px;
      border-bottom: 1px solid #ccc;
      text-align: left;
      vertical-align: top;
    }
    th {
      background-color: #6a1b9a;
      color: white;
    }
    .no-data {
      text-align: center;
      color: #999;
      padding: 20px;
    }
    a.assignment-link {
      color: #6a1b9a;
      text-decoration: none;
      font-weight: bold;
    }
    a.assignment-link:hover {
      text-decoration: underline;
    }
    .graded { color: green; font-weight: bold; }
    .not-graded { color: #999; font-style: italic; }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Assignments</h2>
    <?php if (count($assignments) === 0): ?>
      <div class="no-data">No assignments found.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Course</th>
            <th>Title</th>
            <th>Description</th>
            <th>Due Date</th>
            <?php if ($role === 'trainee'): ?>
              <th>Status</th>
              <th>Score</th>
              <th>Feedback</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($assignments as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['course_name']) ?></td>
              <td>
                <?php if ($role === 'trainee'): ?>
                  <a class="assignment-link" href="assignment_detail.php?id=<?= $a['assignment_id'] ?>">
                    <?= htmlspecialchars($a['title']) ?>
                  </a>
                <?php else: ?>
                  <?= htmlspecialchars($a['title']) ?>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($a['description']) ?></td>
              <td><?= htmlspecialchars(date('j M Y', strtotime($a['due_date']))) ?></td>
              <?php if ($role === 'trainee'): ?>
                <td>
                  <?php if (!empty($a['submission_status'])): ?>
                    <span class="<?= $a['submission_status'] === 'graded' ? 'graded' : 'not-graded' ?>">
                      <?= ucfirst($a['submission_status']) ?>
                    </span>
                  <?php else: ?>
                    <span class="not-graded">Not submitted</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?= is_numeric($a['score_percent']) ? htmlspecialchars($a['score_percent']) . '%' : '<em>—</em>' ?>
                </td>
                <td>
                  <?php if (!empty($a['feedback_file'])): ?>
                    <a href="<?= htmlspecialchars($a['feedback_file']) ?>" target="_blank">Download</a>
                  <?php else: ?>
                    <em>—</em>
                  <?php endif; ?>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>