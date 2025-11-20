<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Generate Reports</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .report-filter-form .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 15px;
      margin-bottom: 10px;
    }
    .report-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 30px;
      margin-bottom: 20px;
    }
    .btn {
      display: inline-block;
      padding: 8px 16px;
      background-color: #6a1b9a;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      font-weight: bold;
    }
    .btn:hover {
      background-color: #4a148c;
    }
    .btn-reset {
      background-color: #ccc;
      color: #333;
    }
    .btn-reset:hover {
      background-color: #bbb;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Generate Reports</h2>

    <form method="get" class="report-filter-form">
      <div class="grid">
        <input type="date" name="start_from" value="<?= htmlspecialchars($_GET['start_from'] ?? '') ?>">
        <input type="date" name="start_to" value="<?= htmlspecialchars($_GET['start_to'] ?? '') ?>">
        <label><input type="checkbox" name="submitted" value="1" <?= isset($_GET['submitted']) ? 'checked' : '' ?>> Has Submitted Assignments</label>
        <button type="submit" class="btn">Search</button>
        <button type="submit" name="export" value="csv" class="btn">Export CSV</button>
        <a href="generate_reports.php" class="btn btn-reset">Reset</a>
      </div>
    </form>

    <div class="report-buttons">
      <a href="view_all_safeguarding_records.php" class="btn" target="_blank">Safeguarding Reports</a>
      <a href="view_all_trainee_records.php" class="btn" target="_blank">All Trainee Records</a>
      <a href="view_course_assignments.php" class="btn" target="_blank">Course Assignments</a>
      <a href="view_all_assignment_records.php" class="btn" target="_blank">Assignment Records</a>
      <a href="view_all_supervisor_allocations.php" class="btn" target="_blank">Supervisor Allocations</a>
      <a href="view_all_supervision_groups.php" class="btn" target="_blank">Supervision Groups</a>
    </div>
  </div>
</div>
</body>
</html>