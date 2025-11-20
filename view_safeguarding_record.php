<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$record_id = $_GET['record_id'] ?? null;

if (!$record_id) {
  die("No record specified.");
}

$stmt = $pdo->prepare("SELECT * FROM safeguarding_TerapiaPersonnel_records WHERE record_id = ?");
$stmt->execute([$record_id]);
$record = $stmt->fetch();

if (!$record) {
  die("Record not found.");
}

// Fetch change log entries
$logStmt = $pdo->prepare("
  SELECT changed_by, change_summary, changed_at
  FROM safeguarding_change_log
  WHERE record_id = ?
  ORDER BY changed_at DESC
");
$logStmt->execute([$record_id]);
$changeLog = $logStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Safeguarding Record Details</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; max-width: 1000px; margin: auto; }
    .record-box { background: #fff; padding: 20px; border-left: 4px solid #6a1b9a; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .record-box h3 { margin-top: 0; color: #6a1b9a; }
    .section { margin-bottom: 30px; }
    .label { font-weight: bold; color: #6a1b9a; display: block; margin-top: 10px; }
    .value { margin-bottom: 10px; }
    .back-link, .edit-link {
      display: inline-block;
      margin-top: 30px;
      padding: 8px 16px;
      background-color: #6a1b9a;
      color: white;
      text-decoration: none;
      border-radius: 4px;
    }
    .back-link:hover, .edit-link:hover {
      background-color: #4a148c;
    }
    table.change-log {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    table.change-log th, table.change-log td {
      border: 1px solid #ccc;
      padding: 8px;
      text-align: left;
    }
    table.change-log th {
      background-color: #f0f0f0;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <a href="edit_safeguarding_record.php?record_id=<?= urlencode($record['record_id']) ?>" class="edit-link" style="float:right;">✏️ Edit Record</a>
    <h2>Safeguarding Record #<?= htmlspecialchars($record['record_id']) ?></h2>
    <div class="record-box">

      <?php foreach ([
        'Record Details' => [
          'record_date', 'record_time', 'location', 'completed_by', 'job_title'
        ],
        'Person Concern Relates To' => [
          'individual_name', 'individual_role', 'department', 'contact_details'
        ],
        'Details of Concern' => [
          'incident_datetime', 'incident_location', 'concern_categories', 'concern_other_detail',
          'observation', 'evidence'
        ],
        'Immediate Actions Taken' => [
          'spoken_to', 'children_involved', 'escalated', 'urgent_steps'
        ],
        'Person Raising the Concern' => [
          'raiser_name', 'raiser_role', 'raiser_contact'
        ],
        'DSL and Review Information' => [
          'dsl_informed', 'dsl_name', 'dsl_action', 'referral_datetime', 'external_outcome',
          'ongoing_monitoring', 'support_required', 'review_date', 'reviewer_name',
          'reviewer_signature', 'review_date_reviewed'
        ],
        'Sign-Off' => [
          'signoff_name', 'signoff_signature', 'signoff_datetime'
        ],
        'Metadata' => [
          'created_at', 'updated_at', 'created_by', 'updated_by'
        ]
      ] as $sectionTitle => $fields): ?>
        <div class="section">
          <h3><?= $sectionTitle ?></h3>
          <?php foreach ($fields as $field): ?>
            <div class="value">
              <span class="label"><?= ucwords(str_replace('_', ' ', $field)) ?>:</span>
              <?= nl2br(htmlspecialchars($record[$field] ?? '')) ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>

      <div class="section">
        <h3>Change Log</h3>
        <?php if (count($changeLog) === 0): ?>
          <p><em>No changes recorded.</em></p>
        <?php else: ?>
          <table class="change-log">
            <thead>
              <tr>
                <th>Changed By</th>
                <th>Summary</th>
                <th>Date/Time</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($changeLog as $log): ?>
                <tr>
                  <td><?= htmlspecialchars($log['changed_by']) ?></td>
                  <td><?= nl2br(htmlspecialchars($log['change_summary'])) ?></td>
                  <td><?= htmlspecialchars(date('j M Y, H:i', strtotime($log['changed_at']))) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <a href="alerts_dashboard.php" class="back-link">← Back to Alerts Dashboard</a>
    </div>
  </div>
</div>
</body>
</html>