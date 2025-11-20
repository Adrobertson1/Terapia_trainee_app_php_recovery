<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff', 'tutor', 'supervisor'])) {
  die("Access denied.");
}

// Fetch filter options
$trainees = $pdo->query("SELECT trainee_id, first_name, surname FROM trainees WHERE is_archived = 0 ORDER BY surname")->fetchAll();
$assignmentTypes = $pdo->query("SELECT type_id, type_name FROM assignment_types WHERE is_active = 1 ORDER BY type_name")->fetchAll();
$assignableUsers = $pdo->query("SELECT user_id, username, email FROM users WHERE is_active = 1 ORDER BY username")->fetchAll();

// Handle filters
$filters = [];
$params = [];

if (!empty($_GET['trainee_id'])) {
  $filters[] = 'ta.trainee_id = ?';
  $params[] = $_GET['trainee_id'];
}
if (!empty($_GET['type_id'])) {
  $filters[] = 'ta.type_id = ?';
  $params[] = $_GET['type_id'];
}
if (!empty($_GET['assigned_by'])) {
  $filters[] = 'ta.assigned_by = ?';
  $params[] = $_GET['assigned_by'];
}
if (!empty($_GET['start_date'])) {
  $filters[] = 'ta.assigned_date >= ?';
  $params[] = $_GET['start_date'];
}
if (!empty($_GET['end_date'])) {
  $filters[] = 'ta.assigned_date <= ?';
  $params[] = $_GET['end_date'];
}

$whereClause = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

// Fetch assignments with submission info
$stmt = $pdo->prepare("
  SELECT 
    ta.id AS assignment_id,
    ta.assignment_id AS true_assignment_id,
    ta.trainee_id AS assignment_trainee_id,
    ta.type_id,
    ta.assigned_date,
    ta.due_date,
    ta.status,
    ta.assigned_by,
    u.username AS assigned_by_name,
    u.email AS assigned_by_email,
    t.first_name AS trainee_first_name,
    t.surname AS trainee_surname,
    at.type_name AS assignment_type,
    s.submitted_date,
    s.file_path,
    s.status AS submission_status,
    s.grade,
    s.feedback
  FROM trainee_assignments ta
  LEFT JOIN trainees t ON ta.trainee_id = t.trainee_id
  LEFT JOIN users u ON ta.assigned_by = u.user_id
  LEFT JOIN assignment_types at ON ta.type_id = at.type_id
  LEFT JOIN assignment_submissions s ON ta.assignment_id = s.assignment_id AND ta.trainee_id = s.trainee_id
  $whereClause
  ORDER BY ta.assigned_date DESC
");
$stmt->execute($params);
$assignments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Assignments</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; }
    form.filter-form { margin-bottom: 20px; }
    select, input[type="date"] {
      padding: 8px;
      margin-right: 10px;
      margin-bottom: 10px;
    }
    button {
      padding: 8px 16px;
      background-color: #6a1b9a;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    button:hover { background-color: #8e24aa; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      padding: 10px;
      border: 1px solid #ccc;
      text-align: left;
      vertical-align: top;
    }
    th {
      background-color: #f0f0f0;
    }
    .status-pending { color: orange; font-weight: bold; }
    .status-submitted { color: blue; font-weight: bold; }
    .status-graded { color: green; font-weight: bold; }
    a.profile-link {
      color: #6a1b9a;
      text-decoration: none;
      font-weight: bold;
    }
    a.profile-link:hover {
      text-decoration: underline;
    }
    .btn-sm {
      padding: 6px 12px;
      font-size: 0.85em;
      border-radius: 4px;
      text-decoration: none;
      display: inline-block;
      border: none;
      cursor: pointer;
    }
    .btn-view {
      background-color: #4CAF50;
      color: white;
    }
    .btn-view:hover {
      background-color: #388E3C;
    }
    .btn-download {
      background-color: #6a1b9a;
      color: white;
      padding: 6px 12px;
      border-radius: 4px;
      text-decoration: none;
      font-size: 0.85em;
    }
    .btn-download:hover {
      background-color: #8e24aa;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>All Trainee Assignments</h2>

    <form method="get" class="filter-form">
      <select name="trainee_id">
        <option value="">Filter by Trainee</option>
        <?php foreach ($trainees as $t): ?>
          <option value="<?= $t['trainee_id'] ?>" <?= ($_GET['trainee_id'] ?? '') == $t['trainee_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="type_id">
        <option value="">Filter by Type</option>
        <?php foreach ($assignmentTypes as $type): ?>
          <option value="<?= $type['type_id'] ?>" <?= ($_GET['type_id'] ?? '') == $type['type_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($type['type_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="assigned_by">
        <option value="">Filter by Assignee</option>
        <?php foreach ($assignableUsers as $user): ?>
          <option value="<?= $user['user_id'] ?>" <?= ($_GET['assigned_by'] ?? '') == $user['user_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)
          </option>
        <?php endforeach; ?>
      </select>

      <input type="date" name="start_date" value="<?= $_GET['start_date'] ?? '' ?>">
      <input type="date" name="end_date" value="<?= $_GET['end_date'] ?? '' ?>">

      <button type="submit">Apply Filters</button>
    </form>

    <form method="get" action="export_assignments.php">
      <input type="hidden" name="trainee_id" value="<?= $_GET['trainee_id'] ?? '' ?>">
      <input type="hidden" name="type_id" value="<?= $_GET['type_id'] ?? '' ?>">
      <input type="hidden" name="assigned_by" value="<?= $_GET['assigned_by'] ?? '' ?>">
      <input type="hidden" name="start_date" value="<?= $_GET['start_date'] ?? '' ?>">
      <input type="hidden" name="end_date" value="<?= $_GET['end_date'] ?? '' ?>">
      <button type="submit">📤 Export Filtered Assignments to CSV</button>
    </form>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Trainee</th>
          <th>Type</th>
          <th>Assigned Date</th>
          <th>Due Date</th>
          <th>Status</th>
          <th>Download</th>
          <th>Grade</th>
          <th>Submission Notes</th>
          <th>Assigned By</th>
          <th>View</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($assignments as $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['assignment_id']) ?></td>
            <td>
              <?php if (!empty($a['assignment_trainee_id']) && $a['assignment_trainee_id'] !== '0'): ?>
                <a href="view_trainee.php?id=<?= urlencode($a['assignment_trainee_id']) ?>" class="profile-link">
                 <?= htmlspecialchars($a['trainee_first_name'] . ' ' . $a['trainee_surname']) ?>
</a>
<?php else: ?>
  <em>Unknown Trainee</em>
<?php endif; ?>
</td>
<td><?= htmlspecialchars($a['assignment_type']) ?></td>
<td><?= htmlspecialchars($a['assigned_date']) ?></td>
<td><?= htmlspecialchars($a['due_date']) ?></td>
<td>
  <?php if (!empty($a['submitted_date'])): ?>
    <span class="status-submitted">Submitted</span><br>
    <small><?= htmlspecialchars(date('j M Y, H:i', strtotime($a['submitted_date']))) ?></small>
  <?php else: ?>
    <span class="status-pending">Pending</span>
  <?php endif; ?>
</td>
<td>
  <?php if (!empty($a['file_path']) && file_exists($a['file_path'])): ?>
    <a class="btn-download" href="<?= htmlspecialchars($a['file_path']) ?>" download>Download</a>
  <?php else: ?>
    —
  <?php endif; ?>
</td>
<td><?= !empty($a['grade']) ? htmlspecialchars($a['grade']) : '—' ?></td>
<td><?= !empty($a['feedback']) ? nl2br(htmlspecialchars($a['feedback'])) : '—' ?></td>
<td>
  <?php if (!empty($a['assigned_by_name'])): ?>
    <?= htmlspecialchars($a['assigned_by_name']) ?> (<?= htmlspecialchars($a['assigned_by_email']) ?>)
  <?php else: ?>
    <em>Unknown (ID: <?= htmlspecialchars($a['assigned_by']) ?>)</em>
  <?php endif; ?>
</td>
<td>
  <a href="view_assignment.php?assignment_id=<?= urlencode($a['assignment_id']) ?>" class="btn-sm btn-view">
    View Details
  </a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</body>
</html>