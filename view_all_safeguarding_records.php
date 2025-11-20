<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$where = "WHERE 1=1";
$params = [];

if (!empty($_GET['name'])) {
  $where .= " AND individual_name LIKE ?";
  $params[] = '%' . $_GET['name'] . '%';
}
if (!empty($_GET['from'])) {
  $where .= " AND record_date >= ?";
  $params[] = $_GET['from'];
}
if (!empty($_GET['to'])) {
  $where .= " AND record_date <= ?";
  $params[] = $_GET['to'];
}
if (isset($_GET['unreviewed']) && $_GET['unreviewed'] === '1') {
  $where .= " AND review_date IS NULL";
}

$stmt = $pdo->prepare("
  SELECT record_id, record_date, individual_name, concern_categories, review_date, reviewer_name
  FROM safeguarding_TerapiaPersonnel_records
  $where
  ORDER BY record_date DESC
");
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename=safeguarding_records.csv');
  header('Pragma: no-cache');
  header('Expires: 0');

  $output = fopen('php://output', 'w');
  if (!empty($records)) {
    fputcsv($output, array_keys($records[0]));
    foreach ($records as $row) {
      fputcsv($output, $row);
    }
  } else {
    fputcsv($output, ['No records found']);
  }
  fclose($output);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Safeguarding Records</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; max-width: 1200px; margin: auto; }
    .filter-form .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }
    .record-table {
      width: 100%;
      border-collapse: collapse;
    }
    .record-table th, .record-table td {
      padding: 10px;
      border-bottom: 1px solid #ccc;
    }
    .record-table th {
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
      margin-right: 10px;
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
    <h2>Safeguarding Records</h2>

    <form method="get" class="filter-form">
      <div class="grid">
        <input type="text" name="name" placeholder="Individual Name" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>">
        <input type="date" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">
        <input type="date" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>">
        <label><input type="checkbox" name="unreviewed" value="1" <?= isset($_GET['unreviewed']) ? 'checked' : '' ?>> Unreviewed Only</label>
        <button type="submit" class="btn">Search</button>
        <button type="submit" name="export" value="csv" class="btn">Export CSV</button>
        <a href="view_all_safeguarding_records.php" class="btn btn-reset">Reset</a>
      </div>
    </form>

    <?php if (count($records) === 0): ?>
      <p class="no-results">No safeguarding records found.</p>
    <?php else: ?>
      <table class="record-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Date</th>
            <th>Individual</th>
            <th>Concern Categories</th>
            <th>Review Date</th>
            <th>Reviewer</th>
            <th>View</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['record_id']) ?></td>
              <td><?= htmlspecialchars($r['record_date']) ?></td>
              <td><?= htmlspecialchars($r['individual_name']) ?></td>
              <td><?= htmlspecialchars($r['concern_categories']) ?></td>
              <td><?= htmlspecialchars($r['review_date'] ?? '—') ?></td>
              <td><?= htmlspecialchars($r['reviewer_name'] ?? '—') ?></td>
              <td>
                <a href="view_safeguarding_record.php?record_id=<?= $r['record_id'] ?>" class="btn" target="_blank">View</a>
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