<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied");
}

$record_id = $_GET['id'] ?? null;
if (!$record_id || !is_numeric($record_id)) {
  die("Invalid record ID.");
}

// Handle update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_record'])) {
  $stmt = $pdo->prepare("
    UPDATE safeguarding_TerapiaPersonnel_records SET
      dsl_informed = ?, dsl_name = ?, dsl_action = ?, referral_datetime = ?, external_outcome = ?,
      ongoing_monitoring = ?, support_required = ?, review_date = ?,
      signoff_name = ?, signoff_signature = ?, signoff_datetime = ?,
      reviewer_name = ?, reviewer_signature = ?, review_date_reviewed = ?
    WHERE record_id = ?
  ");
  $stmt->execute([
    $_POST['dsl_informed'] ?? null,
    $_POST['dsl_name'] ?? null,
    isset($_POST['dsl_action']) ? implode(', ', $_POST['dsl_action']) : null,
    $_POST['referral_datetime'] ?? null,
    $_POST['external_outcome'] ?? null,
    $_POST['ongoing_monitoring'] ?? null,
    $_POST['support_required'] ?? null,
    $_POST['review_date'] ?? null,
    $_POST['signoff_name'] ?? null,
    $_POST['signoff_signature'] ?? null,
    $_POST['signoff_datetime'] ?? null,
    $_POST['reviewer_name'] ?? null,
    $_POST['reviewer_signature'] ?? null,
    $_POST['review_date_reviewed'] ?? null,
    $record_id
  ]);
  header("Location: view_safeguarding.php?id=$record_id&updated=1");
  exit;
}

// Fetch record
$stmt = $pdo->prepare("SELECT * FROM safeguarding_TerapiaPersonnel_records WHERE record_id = ?");
$stmt->execute([$record_id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
  die("Safeguarding record not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Safeguarding Record</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; max-width: 900px; margin: auto; background: #fff; }
    h2 { color: #6a1b9a; margin-bottom: 20px; }
    fieldset { border: 1px solid #ccc; padding: 20px; margin-bottom: 30px; }
    legend { font-weight: bold; color: #333; }
    label { display: block; margin-top: 10px; font-weight: bold; }
    input[type="text"], input[type="date"], textarea, select {
      width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ccc; border-radius: 4px;
    }
    textarea { height: 100px; }
    .checkbox-group label { font-weight: normal; display: block; margin-left: 20px; }
    .btn-submit {
      background-color: #6a1b9a; color: white; padding: 10px 20px;
      border: none; border-radius: 4px; cursor: pointer; font-size: 16px;
    }
    .btn-submit:hover { background-color: #4a148c; }
    .readonly { background-color: #f9f9f9; padding: 8px; border-radius: 4px; margin-bottom: 10px; }
    .message-success {
      background-color: #e0f7e9;
      color: #2e7d32;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
      font-weight: bold;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Safeguarding Record: <?= htmlspecialchars($record['individual_name']) ?></h2>

    <?php if (isset($_GET['updated'])): ?>
      <div class="message-success">✅ Record successfully updated.</div>
    <?php endif; ?>

    <fieldset>
      <legend>Record Summary (Sections 1–5)</legend>
      <div class="readonly"><strong>Date:</strong> <?= htmlspecialchars($record['record_date']) ?></div>
      <div class="readonly"><strong>Completed by:</strong> <?= htmlspecialchars($record['completed_by']) ?> (<?= htmlspecialchars($record['job_title']) ?>)</div>
      <div class="readonly"><strong>Individual Concerned:</strong> <?= htmlspecialchars($record['individual_name']) ?> (<?= htmlspecialchars($record['individual_role']) ?>)</div>
      <div class="readonly"><strong>Concern:</strong> <?= htmlspecialchars($record['concern_categories']) ?></div>
      <div class="readonly"><strong>Observation:</strong><br><?= nl2br(htmlspecialchars($record['observation'])) ?></div>
    </fieldset>

    <form method="post">
      <fieldset>
        <legend>6. Referral/Reporting</legend>
        <label>Was the DSL informed?</label>
        <input type="text" name="dsl_informed" value="<?= htmlspecialchars($record['dsl_informed']) ?>">
        <label>DSL name:</label>
        <input type="text" name="dsl_name" value="<?= htmlspecialchars($record['dsl_name']) ?>">
        <label>Action taken by DSL:</label>
        <div class="checkbox-group">
          <?php
          $actions = [
            "Monitored", "Spoke with individual concerned",
            "Referral to HR/management", "Referral to external safeguarding authority", "Other"
          ];
          $existing = explode(',', $record['dsl_action'] ?? '');
          foreach ($actions as $action):
          ?>
            <label><input type="checkbox" name="dsl_action[]" value="<?= $action ?>" <?= in_array(trim($action), $existing) ? 'checked' : '' ?>> <?= $action ?></label>
          <?php endforeach; ?>
        </div>
        <label>Date/time of referral:</label>
        <input type="text" name="referral_datetime" value="<?= htmlspecialchars($record['referral_datetime']) ?>">
        <label>Outcome/response from external agency:</label>
        <textarea name="external_outcome"><?= htmlspecialchars($record['external_outcome']) ?></textarea>
      </fieldset>

      <fieldset>
        <legend>7. Follow-Up</legend>
        <label>Ongoing monitoring or restrictions:</label>
        <textarea name="ongoing_monitoring"><?= htmlspecialchars($record['ongoing_monitoring']) ?></textarea>
        <label>Support required for person raising concern:</label>
        <textarea name="support_required"><?= htmlspecialchars($record['support_required']) ?></textarea>
        <label>Review date:</label>
        <input type="date" name="review_date" value="<?= htmlspecialchars($record['review_date']) ?>">
      </fieldset>

      <fieldset>
        <legend>8. Sign-Off</legend>
        <label>Name of person completing form:</label>
        <input type="text" name="signoff_name" value="<?= htmlspecialchars($record['signoff_name']) ?>">
        <label>Signature:</label>
        <input type="text" name="signoff_signature" value="<?= htmlspecialchars($record['signoff_signature']) ?>">
        <label>Date/time completed:</label>
        <input type="text" name="signoff_datetime" value="<?= htmlspecialchars($record['signoff_datetime']) ?>">
        <label>Name of DSL/Manager reviewing:</label>
        <input type="text" name="reviewer_name" value="<?= htmlspecialchars($record['reviewer_name']) ?>">
        <label>Signature:</label>
        <input type="text" name="reviewer_signature" value="<?= htmlspecialchars($record['reviewer_signature']) ?>">
        <label>Date reviewed:</label>
        <input type="text" name="review_date_reviewed" value="<?= htmlspecialchars($record['review_date_reviewed']) ?>">
      </fieldset>

      <button type="submit" name="update_record" class="btn-submit">Update Record</button>
    </form>
  </div>
</div>
</body>
</html>