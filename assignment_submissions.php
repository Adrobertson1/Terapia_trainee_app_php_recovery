<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff', 'tutor'])) {
  die("Access denied.");
}

// Collect filters
$filters = [];
$params = [];

if (!empty($_GET['trainee_id'])) {
  $filters[] = 's.trainee_id = ?';
  $params[] = $_GET['trainee_id'];
}
if (!empty($_GET['type_id'])) {
  $filters[] = 'a.type_id = ?';
  $params[] = $_GET['type_id'];
}
if (!empty($_GET['start_date'])) {
  $filters[] = 's.submitted_date >= ?';
  $params[] = $_GET['start_date'];
}
if (!empty($_GET['end_date'])) {
  $filters[] = 's.submitted_date <= ?';
  $params[] = $_GET['end_date'];
}
if (!empty($_GET['status'])) {
  $filters[] = 's.status = ?';
  $params[] = $_GET['status'];
}

$whereClause = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

// Fetch filter options
$traineeOptions = $pdo->query("SELECT trainee_id, first_name, surname FROM trainees ORDER BY surname")->fetchAll();
$typeOptions = $pdo->query("SELECT type_id, type_name FROM assignment_types ORDER BY type_name")->fetchAll();

// Fetch submissions
$stmt = $pdo->prepare("
  SELECT 
    s.submission_id,
    s.assignment_id,
    s.trainee_id,
    s.submitted_date,
    s.status,
    s.score_percent,
    s.feedback_text,
    s.feedback_file,
    t.first_name,
    t.surname,
    at.type_name,
    a.due_date
  FROM assignment_submissions s
  LEFT JOIN trainees t ON s.trainee_id = t.trainee_id
  LEFT JOIN trainee_assignments a ON s.assignment_id = a.id
  LEFT JOIN assignment_types at ON a.type_id = at.type_id
  $whereClause
  ORDER BY s.submitted_date DESC
");
$stmt->execute($params);
$submissions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Assignment Submissions</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      padding: 10px;
      border: 1px solid #ccc;
      text-align: left;
    }
    th {
      background-color: #f0f0f0;
    }
    .status-submitted { color: blue; font-weight: bold; }
    .status-graded { color: green; font-weight: bold; }
    .status-late { color: red; font-weight: bold; }
    .export-form, .filter-form {
      margin-bottom: 20px;
    }
    .export-form button, .filter-form button {
      background-color: #850069;
      color: white;
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .export-form button:hover, .filter-form button:hover {
      background-color: #a0007a;
    }
    .filter-form select, .filter-form input {
      padding: 6px;
      margin-right: 10px;
      margin-bottom: 10px;
    }
    .btn-sm.btn-grade {
      background-color: #4CAF50;
      color: white;
      padding: 6px 12px;
      font-size: 14px;
      border-radius: 4px;
      text-decoration: none;
      display: inline-block;
    }
    .btn-sm.btn-grade:hover {
      background-color: #388E3C;
    }
    .locked-label {
      color: #999;
      font-style: italic;
    }
    .trainee-link {
      color: #6a1b9a;
      font-weight: bold;
      text-decoration: none;
    }
    .trainee-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Assignment Submissions</h2>

    <form method="get" class="filter-form">
      <select name="trainee_id">
        <option value="">Filter by Trainee</option>
        <?php foreach ($traineeOptions as $t): ?>
          <option value="<?= $t['trainee_id'] ?>" <?= ($_GET['trainee_id'] ?? '') == $t['trainee_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="type_id">
        <option value="">Filter by Assignment Type</option>
        <?php foreach ($typeOptions as $type): ?>
          <option value="<?= $type['type_id'] ?>" <?= ($_GET['type_id'] ?? '') == $type['type_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($type['type_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input type="date" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>" placeholder="Start Date">
      <input type="date" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>" placeholder="End Date">

      <select name="status">
        <option value="">Filter by Status</option>
        <option value="submitted" <?= ($_GET['status'] ?? '') === 'submitted' ? 'selected' : '' ?>>Submitted</option>
        <option value="graded" <?= ($_GET['status'] ?? '') === 'graded' ? 'selected' : '' ?>>Graded</option>
        <option value="late" <?= ($_GET['status'] ?? '') === 'late' ? 'selected' : '' ?>>Late</option>
      </select>

      <button type="submit">Apply Filters</button>
    </form>

    <form method="get" action="export_submissions.php" class="export-form">
      <input type="hidden" name="trainee_id" value="<?= $_GET['trainee_id'] ?? '' ?>">
      <input type="hidden" name="type_id" value="<?= $_GET['type_id'] ?? '' ?>">
      <input type="hidden" name="start_date" value="<?= $_GET['start_date'] ?? '' ?>">
      <input type="hidden" name="end_date" value="<?= $_GET['end_date'] ?? '' ?>">
      <input type="hidden" name="status" value="<?= $_GET['status'] ?? '' ?>">
      <button type="submit">📤 Export Filtered Submissions to CSV</button>
    </form>

    <?php if (empty($submissions)): ?>
      <p>No submissions found.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Trainee</th>
            <th>Assignment Type</th>
            <th>Due Date</th>
            <th>Submitted Date</th>
            <th>Status</th>
            <th>Score (%)</th>
            <th>Feedback</th>
            <th>File</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($submissions as $s): ?>
            <tr>
              <td>
                <a href="view_trainee.php?id=<?= $s['trainee_id'] ?>" class="trainee-link">
                  <?= htmlspecialchars($s['first_name'] . ' ' . $s['surname']) ?>
                </a>
              </td>
              <td><?= htmlspecialchars($s['type_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($s['due_date'] ?? '—') ?></td>
              <td>
                <?php
                $submitted = $s['submitted_date'];
                $due = $s['due_date'];
                $isLate = ($submitted > $due);
                echo '<span style="' . ($isLate ? 'color:red;font-weight:bold;' : '') . '">'
                     . htmlspecialchars($submitted)
                     . ($isLate ? ' <strong>Late</strong>' : '')
                     . '</span>';
                ?>
              </td>
              <td class="status-<?= htmlspecialchars($s['status']) ?>">
                <?= ucfirst(htmlspecialchars($s['status'])) ?>
              </td>
              <td>
                <?= is_numeric($s['score_percent']) ? htmlspecialchars($s['score_percent']) . '%' : '<em>Not graded</em>' ?>
              </td>
              <td>
          <?= !empty($s['feedback_text']) ? nl2br(htmlspecialchars($s['feedback_text'])) : '<em>No feedback</em>' ?>
</td>
<td>
  <?php if (!empty($s['feedback_file'])): ?>
    <a href="<?= htmlspecialchars($s['feedback_file']) ?>" target="_blank">Download</a>
  <?php else: ?>
    <em>No file</em>
  <?php endif; ?>
</td>
<td>
  <?php
  $isGraded = is_numeric($s['score_percent']);
  $canOverride = in_array($_SESSION['role'], ['admin', 'superuser']);
  if (!$isGraded || $canOverride): ?>
    <a href="/trainee_app/grade_submission.php?id=<?= $s['submission_id'] ?>" class="btn-sm btn-grade">
      <?= $isGraded ? 'Regrade Submission' : 'Grade Submission' ?>
    </a>
  <?php else: ?>
    <span class="locked-label">Locked</span>
  <?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
</div>
</body>
</html>