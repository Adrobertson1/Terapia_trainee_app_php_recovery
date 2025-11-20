<?php
session_start();
require 'db.php';
require_once 'functions.php';

if (!in_array($_SESSION['role'], ['admin', 'staff', 'tutor'])) {
    die("Access denied");
}

// Handle filters
$filter_alert = $_GET['alert_flag'] ?? '';
$filter_tutor = $_GET['tutor_id'] ?? '';
$filter_start = $_GET['start_date'] ?? '';
$filter_end = $_GET['end_date'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "
  SELECT tf.feedback_date, tf.notes, tf.alert_flag,
         t.trainee_id, t.first_name AS trainee_first, t.surname AS trainee_surname,
         tu.first_name AS tutor_first, tu.surname AS tutor_surname
  FROM tutor_feedback tf
  JOIN trainees t ON tf.trainee_id = t.trainee_id
  JOIN tutors tu ON tf.tutor_id = tu.tutor_id
  WHERE 1=1
";

$params = [];

if ($filter_alert !== '') {
    $sql .= " AND tf.alert_flag = ?";
    $params[] = $filter_alert;
}

if ($filter_tutor !== '') {
    $sql .= " AND tf.tutor_id = ?";
    $params[] = $filter_tutor;
}

if ($filter_start !== '') {
    $sql .= " AND tf.feedback_date >= ?";
    $params[] = $filter_start;
}

if ($filter_end !== '') {
    $sql .= " AND tf.feedback_date <= ?";
    $params[] = $filter_end;
}

if ($search !== '') {
    $sql .= " AND (
        t.first_name LIKE ? OR
        t.surname LIKE ? OR
        tf.notes LIKE ?
    )";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY tf.feedback_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll();

// Fetch tutors for filter dropdown
$tutorList = $pdo->query("SELECT tutor_id, first_name, surname FROM tutors ORDER BY surname ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>All Tutor Feedback</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .filter-form {
      margin-bottom: 20px;
    }
    .filter-form input, .filter-form select {
      padding: 6px;
      margin-right: 10px;
    }
    .filter-form input[type="text"] {
      width: 200px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      padding: 10px;
      border-bottom: 1px solid #ccc;
      vertical-align: top;
    }
    .flag-box {
      padding: 6px;
      border-left: 4px solid #888;
      background-color: #f9f9f9;
    }
    .flag-safeguarding { border-color: #d32f2f; }
    .flag-performance { border-color: #fbc02d; }
    .flag-attendance { border-color: #1976d2; }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="page-container">
      <h2>All Tutor Feedback</h2>

      <form method="get" class="filter-form">
        <label>Search:</label>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Name or keyword">

        <label>Alert Type:</label>
        <select name="alert_flag">
          <option value="">All Alerts</option>
          <option value="safeguarding" <?= $filter_alert === 'safeguarding' ? 'selected' : '' ?>>Safeguarding</option>
          <option value="performance" <?= $filter_alert === 'performance' ? 'selected' : '' ?>>Performance</option>
          <option value="attendance" <?= $filter_alert === 'attendance' ? 'selected' : '' ?>>Attendance</option>
          <option value="none" <?= $filter_alert === 'none' ? 'selected' : '' ?>>No Alert</option>
        </select>

        <label>Tutor:</label>
        <select name="tutor_id">
          <option value="">All Tutors</option>
          <?php foreach ($tutorList as $tutor): ?>
            <option value="<?= $tutor['tutor_id'] ?>" <?= $filter_tutor == $tutor['tutor_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($tutor['surname'] . ', ' . $tutor['first_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label>Start Date:</label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($filter_start) ?>">

        <label>End Date:</label>
        <input type="date" name="end_date" value="<?= htmlspecialchars($filter_end) ?>">

        <button type="submit" class="btn">Apply Filters</button>
      </form>

      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Trainee</th>
            <th>Tutor</th>
            <th>Alert</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($entries) === 0): ?>
            <tr><td colspan="5"><em>No feedback found.</em></td></tr>
          <?php else: ?>
            <?php foreach ($entries as $entry): ?>
              <?php
                $flagClass = 'flag-box';
                if ($entry['alert_flag'] === 'safeguarding') $flagClass .= ' flag-safeguarding';
                elseif ($entry['alert_flag'] === 'performance') $flagClass .= ' flag-performance';
                elseif ($entry['alert_flag'] === 'attendance') $flagClass .= ' flag-attendance';
              ?>
              <tr>
                <td><?= htmlspecialchars($entry['feedback_date']) ?></td>
                <td>
                  <a href="view_trainee.php?id=<?= $entry['trainee_id'] ?>">
                    <?= htmlspecialchars($entry['trainee_surname'] . ', ' . $entry['trainee_first']) ?>
                  </a>
                </td>
                <td><?= htmlspecialchars($entry['tutor_surname'] . ', ' . $entry['tutor_first']) ?></td>
                <td><?= ucfirst($entry['alert_flag']) ?></td>
                <td><div class="<?= $flagClass ?>"><?= nl2br(htmlspecialchars($entry['notes'])) ?></div></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>