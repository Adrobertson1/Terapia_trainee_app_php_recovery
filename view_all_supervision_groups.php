<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

// Fetch all supervision groups with supervisor details
$groupStmt = $pdo->prepare("
  SELECT g.group_id,
         CONCAT_WS(' ', 'Module', g.module_number, '-', g.module_title, '(', g.group_type, g.group_option, ')') AS group_name,
         CONCAT_WS(' ', s.first_name, s.surname_prefix, s.surname) AS supervisor_name,
         s.email AS supervisor_email, s.role AS supervisor_role
  FROM supervision_groups g
  LEFT JOIN supervisors s ON g.supervisor_id = s.supervisor_id
  ORDER BY g.module_number, g.group_type, g.group_option
");
$groupStmt->execute();
$groups = $groupStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all group members using correct mapping table
$membersStmt = $pdo->prepare("
  SELECT sgt.group_id, t.trainee_id, t.first_name, t.surname, t.email, t.start_date
  FROM supervision_group_trainees sgt
  JOIN trainees t ON sgt.trainee_id = t.trainee_id
  ORDER BY sgt.group_id, t.surname
");
$membersStmt->execute();
$members = $membersStmt->fetchAll(PDO::FETCH_ASSOC);

// Organize members by group using normalized keys
$groupMembers = [];
foreach ($members as $m) {
  $groupMembers[(string)$m['group_id']][] = $m;
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename=supervision_groups.csv');
  header('Pragma: no-cache');
  header('Expires: 0');

  $output = fopen('php://output', 'w');
  fputcsv($output, ['Group Name', 'Supervisor', 'Trainee Name', 'Email', 'Start Date']);

  foreach ($groups as $g) {
    $group_id = (string)$g['group_id'];
    $group_name = $g['group_name'];
    $supervisor = $g['supervisor_name'] ?? 'Unassigned';

    if (!empty($groupMembers[$group_id])) {
      foreach ($groupMembers[$group_id] as $t) {
        fputcsv($output, [
          $group_name,
          $supervisor,
          $t['first_name'] . ' ' . $t['surname'],
          $t['email'],
          $t['start_date']
        ]);
      }
    } else {
      fputcsv($output, [$group_name, $supervisor, '—', '—', '—']);
    }
  }

  fclose($output);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Supervision Groups</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; max-width: 1200px; margin: auto; }
    .group-section { margin-bottom: 40px; }
    .group-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    .group-table th, .group-table td {
      padding: 10px;
      border-bottom: 1px solid #ccc;
    }
    .group-table th {
      background-color: #6a1b9a;
      color: white;
    }
    .btn {
      display: inline-block;
      padding: 8px 16px;
      background-color: #6a1b9a;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      font-weight: bold;
      margin-bottom: 20px;
    }
    .btn:hover {
      background-color: #4a148c;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Supervision Groups</h2>

    <a href="view_all_supervision_groups.php?export=csv" class="btn">Export CSV</a>

    <?php if (count($groups) === 0): ?>
      <p class="no-results">No supervision groups found.</p>
    <?php else: ?>
      <?php foreach ($groups as $g): ?>
        <?php $group_id = (string)$g['group_id']; ?>
        <div class="group-section">
          <h3>🧑‍🏫 <?= htmlspecialchars($g['group_name']) ?> — <?= htmlspecialchars($g['supervisor_name'] ?? 'Unassigned') ?> (<?= htmlspecialchars($g['supervisor_role'] ?? '—') ?>)</h3>
          <p>Contact: <?= htmlspecialchars($g['supervisor_email'] ?? '—') ?></p>

          <?php if (empty($groupMembers[$group_id])): ?>
            <p>No trainees assigned to this group.</p>
          <?php else: ?>
            <table class="group-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Start Date</th>
                  <th>View</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($groupMembers[$group_id] as $t): ?>
                  <tr>
                    <td><?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?></td>
                    <td><?= htmlspecialchars($t['email']) ?></td>
                    <td><?= htmlspecialchars($t['start_date']) ?></td>
                    <td>
                      <a href="view_trainee.php?id=<?= $t['trainee_id'] ?>" class="btn" target="_blank">View</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
</body>
</html>