<?php
session_start();
require 'db.php';
require_once 'functions.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied");
}

// Handle resolution submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_id'])) {
  $resolve_id = $_POST['resolve_id'];
  $resolution_notes = $_POST['resolution_notes'] ?? '';
  $resolution_outcome = $_POST['resolution_outcome'] ?? '';
  $review_date = date('Y-m-d');

  $updateStmt = $pdo->prepare("
    UPDATE safeguarding_TerapiaPersonnel_records
    SET reviewer_name = ?, review_date = ?, dsl_action = ?
    WHERE record_id = ?
  ");
  $updateStmt->execute([
    $_SESSION['name'] ?? 'System User',
    $review_date,
    $resolution_outcome . ' — ' . $resolution_notes,
    $resolve_id
  ]);

  logAction($pdo, $_SESSION['user_id'], $_SESSION['role'], 'resolve_safeguarding', "Reviewed safeguarding record ID $resolve_id");

  header("Location: alerts_dashboard.php");
  exit;
}

// Fetch unresolved safeguarding records
$stmt = $pdo->prepare("
  SELECT record_id, record_date, individual_name, individual_role,
         concern_categories, observation, dsl_action
  FROM safeguarding_TerapiaPersonnel_records
  WHERE review_date IS NULL OR review_date = ''
  ORDER BY record_date DESC
");
$stmt->execute();
$alerts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Unresolved Safeguarding Alerts</title>
  <link rel="stylesheet" href="style.css">
  <style>
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      padding: 10px;
      border-bottom: 1px solid #ccc;
      vertical-align: top;
    }
    th {
      background-color: #6a1b9a;
      color: white;
    }
    .flag-box {
      padding: 6px;
      border-left: 4px solid #d32f2f;
      background-color: #f9f9f9;
    }
    .resolution-form textarea {
      width: 100%;
      height: 60px;
      margin-top: 5px;
    }
    .resolution-form select {
      width: 100%;
      margin-top: 5px;
    }
    .btn {
      margin-top: 10px;
      padding: 8px 16px;
      background-color: #6a1b9a;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
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
    <div class="page-container">
      <h2>Unresolved Safeguarding Alerts</h2>

      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Individual</th>
            <th>Role</th>
            <th>Concern Categories</th>
            <th>Observation</th>
            <th>View Details</th>
            <th>Resolve</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($alerts) === 0): ?>
            <tr><td colspan="7"><em>No unresolved safeguarding alerts found.</em></td></tr>
          <?php else: ?>
            <?php foreach ($alerts as $entry): ?>
              <tr>
                <td><?= htmlspecialchars($entry['record_date']) ?></td>
                <td><?= htmlspecialchars($entry['individual_name']) ?></td>
                <td><?= htmlspecialchars($entry['individual_role']) ?></td>
                <td><?= htmlspecialchars($entry['concern_categories']) ?></td>
                <td><div class="flag-box"><?= nl2br(htmlspecialchars($entry['observation'])) ?></div></td>
                <td>
                  <a href="view_safeguarding_record.php?record_id=<?= urlencode($entry['record_id']) ?>" class="btn">View Details</a>
                </td>
                <td>
                  <form method="post" class="resolution-form">
                    <input type="hidden" name="resolve_id" value="<?= $entry['record_id'] ?>">
                    <label>Resolution Outcome:</label>
                    <select name="resolution_outcome" required>
                      <option value="">-- Select Outcome --</option>
                      <option value="Monitored">Monitored</option>
                      <option value="Spoke with individual concerned">Spoke with individual concerned</option>
                      <option value="Referral to HR/management">Referral to HR/management</option>
                      <option value="Referral to external safeguarding authority">Referral to external safeguarding authority</option>
                      <option value="Other">Other (details)</option>
                    </select>

                    <label>Resolution Notes:</label>
                    <textarea name="resolution_notes" placeholder="Describe outcome or action taken..." required></textarea>
                    <button type="submit" class="btn">Mark Reviewed</button>
                  </form>
                </td>
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