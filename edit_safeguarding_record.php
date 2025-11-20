<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['admin', 'staff', 'superuser'])) {
  die("Access denied.");
}

$record_id = $_GET['record_id'] ?? null;
if (!$record_id) {
  die("No record specified.");
}

// Fetch existing record
$stmt = $pdo->prepare("SELECT * FROM safeguarding_TerapiaPersonnel_records WHERE record_id = ?");
$stmt->execute([$record_id]);
$record = $stmt->fetch();
if (!$record) {
  die("Record not found.");
}

$updated = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $pdo->beginTransaction();

    $fields = [
      'record_date', 'record_time', 'location', 'completed_by', 'job_title',
      'individual_name', 'individual_role', 'department', 'contact_details',
      'incident_datetime', 'incident_location', 'concern_categories', 'concern_other_detail',
      'observation', 'evidence', 'spoken_to', 'children_involved', 'escalated', 'urgent_steps',
      'raiser_name', 'raiser_role', 'raiser_contact', 'dsl_informed', 'dsl_name', 'dsl_action',
      'referral_datetime', 'external_outcome', 'ongoing_monitoring', 'support_required',
      'review_date', 'signoff_name', 'signoff_signature', 'signoff_datetime',
      'reviewer_name', 'reviewer_signature', 'review_date_reviewed'
    ];

    $updates = [];
    $values = [];

    foreach ($fields as $field) {
      $updates[] = "$field = ?";
      $values[] = $_POST[$field] ?? null;
    }

    $values[] = $record_id;

    $updateStmt = $pdo->prepare("
      UPDATE safeguarding_TerapiaPersonnel_records
      SET " . implode(', ', $updates) . "
      WHERE record_id = ?
    ");
    $updateStmt->execute($values);

    // Log the change
    $logStmt = $pdo->prepare("
      INSERT INTO safeguarding_change_log (record_id, changed_by, change_summary)
      VALUES (?, ?, ?)
    ");
    $summary = $_POST['change_summary'] ?? 'Edited record';
    $logStmt->execute([$record_id, $_SESSION['name'] ?? 'System User', $summary]);

    $pdo->commit();
    $updated = true;
  } catch (Exception $e) {
    $pdo->rollBack();
    $error = "Update failed: " . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Safeguarding Record</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; max-width: 1000px; margin: auto; }
    fieldset { border: 1px solid #ccc; padding: 20px; margin-bottom: 30px; }
    legend { font-weight: bold; color: #333; }
    label { display: block; margin-top: 10px; font-weight: bold; }
    input, textarea, select {
      width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ccc; border-radius: 4px;
    }
    textarea { height: 100px; }
    .btn-submit {
      background-color: #6a1b9a; color: white; padding: 10px 20px;
      border: none; border-radius: 4px; cursor: pointer; font-size: 16px;
    }
    .btn-submit:hover { background-color: #4a148c; }
    .message-success { background-color: #e0f7e9; color: #2e7d32; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
    .error-message { background-color: #fdecea; color: #c62828; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Edit Safeguarding Record #<?= htmlspecialchars($record_id) ?></h2>

    <?php if ($updated): ?>
      <div class="message-success">✅ Record successfully updated.</div>
    <?php elseif ($error): ?>
      <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <?php
      function field($label, $name, $type = 'text') {
        global $record;
        echo "<label>$label:</label>";
        if ($type === 'textarea') {
          echo "<textarea name=\"$name\">" . htmlspecialchars($record[$name] ?? '') . "</textarea>";
        } elseif ($type === 'select' && $name === 'individual_role') {
          $options = ['Staff Member', 'Tutor', 'Supervisor', 'Trainee', 'Other Terapia Personnel', 'Other (please specify)'];
          echo "<select name=\"$name\">";
          foreach ($options as $opt) {
            $selected = ($record[$name] ?? '') === $opt ? 'selected' : '';
            echo "<option $selected>$opt</option>";
          }
          echo "</select>";
        } else {
          echo "<input type=\"$type\" name=\"$name\" value=\"" . htmlspecialchars($record[$name] ?? '') . "\">";
        }
      }
      ?>

      <fieldset><legend>1. Record Details</legend>
        <?php field('Date of record', 'record_date', 'date'); ?>
        <?php field('Time of record', 'record_time', 'time'); ?>
        <?php field('Location', 'location'); ?>
        <?php field('Completed by', 'completed_by'); ?>
        <?php field('Job title', 'job_title'); ?>
      </fieldset>

      <fieldset><legend>2. Person Concern Relates To</legend>
        <?php field('Individual name', 'individual_name'); ?>
        <?php field('Role', 'individual_role', 'select'); ?>
        <?php field('Department', 'department'); ?>
        <?php field('Contact details', 'contact_details'); ?>
      </fieldset>

      <fieldset><legend>3. Details of Concern</legend>
        <?php field('Incident date/time', 'incident_datetime'); ?>
        <?php field('Incident location', 'incident_location'); ?>
        <?php field('Concern categories', 'concern_categories'); ?>
        <?php field('Other concern detail', 'concern_other_detail'); ?>
        <?php field('Observation', 'observation', 'textarea'); ?>
        <?php field('Evidence', 'evidence', 'textarea'); ?>
      </fieldset>

      <fieldset><legend>4. Immediate Actions Taken</legend>
        <?php field('Spoken to', 'spoken_to', 'textarea'); ?>
        <?php field('Children involved', 'children_involved'); ?>
        <?php field('Escalated', 'escalated'); ?>
        <?php field('Urgent steps', 'urgent_steps', 'textarea'); ?>
      </fieldset>

      <fieldset><legend>5. Person Raising the Concern</legend>
        <?php field('Raiser name', 'raiser_name'); ?>
        <?php field('Raiser role', 'raiser_role'); ?>
        <?php field('Raiser contact', 'raiser_contact'); ?>
      </fieldset>

      <fieldset><legend>6. DSL and Review Information</legend>
        <?php field('DSL informed', 'dsl_informed', 'textarea'); ?>
        <?php field('DSL name', 'dsl_name'); ?>
        <?php field('DSL action', 'dsl_action', 'textarea'); ?>
        <?php field('Referral datetime', 'referral_datetime'); ?>
        <?php field('External outcome', 'external_outcome', 'textarea'); ?>
        <?php field('Ongoing monitoring', 'ongoing_monitoring', 'textarea'); ?>
        <?php field('Support required', 'support_required', 'textarea'); ?>
        <?php field('Review date', 'review_date', 'date'); ?>
        <?php field('Reviewer name', 'reviewer_name'); ?>
        <?php field('Reviewer signature', 'reviewer_signature'); ?>
        <?php field('Review date reviewed', 'review_date_reviewed'); ?>
      </fieldset>

      <fieldset><legend>7. Sign-Off</legend>
        <?php field('Sign-off name', 'signoff_name'); ?>
        <?php field('Sign-off signature', 'signoff_signature'); ?>
        <?php field('Sign-off datetime', 'signoff_datetime'); ?>
      </fieldset>

      <fieldset><legend>8. Change Summary</legend>
        <label>Brief summary of changes made:</label>
<textarea name="change_summary" placeholder="E.g. Updated contact details and DSL action..."></textarea>
</fieldset>

<button type="submit" class="btn-submit">Update Record</button>
</form>
</div>
</div>
</body>
</html>