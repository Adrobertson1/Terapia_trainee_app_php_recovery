<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role'])) {
  die("Access denied.");
}

// Helper to flatten checkbox arrays
function flattenCheckbox($field) {
  return isset($_POST[$field]) ? implode(', ', $_POST[$field]) : '';
}

$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $pdo->beginTransaction();

    $concerns = flattenCheckbox('concern');
    $other_detail = $_POST['concern_other_detail'] ?? null;

    $stmt = $pdo->prepare("
      INSERT INTO safeguarding_TerapiaPersonnel_records (
        record_date, record_time, location, completed_by, job_title,
        individual_name, individual_role, department, contact_details,
        incident_datetime, incident_location, concern_categories, concern_other_detail,
        observation, evidence, spoken_to, children_involved, escalated, urgent_steps,
        raiser_name, raiser_role, raiser_contact
      ) VALUES (
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?
      )
    ");

    $data = [
      $_POST['record_date'] ?? null,
      $_POST['record_time'] ?? null,
      $_POST['location'] ?? null,
      $_POST['completed_by'] ?? null,
      $_POST['job_title'] ?? null,

      $_POST['individual_name'] ?? null,
      $_POST['individual_role'] ?? null,
      $_POST['department'] ?? null,
      $_POST['contact_details'] ?? null,

      $_POST['incident_datetime'] ?? null,
      $_POST['incident_location'] ?? null,
      $concerns,
      $other_detail,

      $_POST['observation'] ?? null,
      $_POST['evidence'] ?? null,
      $_POST['spoken_to'] ?? null,
      $_POST['children_involved'] ?? null,
      $_POST['escalated'] ?? null,
      $_POST['urgent_steps'] ?? null,

      $_POST['raiser_name'] ?? null,
      $_POST['raiser_role'] ?? null,
      $_POST['raiser_contact'] ?? null
    ];

    $stmt->execute($data);
    $record_id = $pdo->lastInsertId();

    // Insert concern types into normalized table
    if (!empty($_POST['concern'])) {
      $insertConcern = $pdo->prepare("
        INSERT INTO safeguarding_concern_types (record_id, concern_type)
        VALUES (?, ?)
      ");
      foreach ($_POST['concern'] as $concern) {
        $insertConcern->execute([$record_id, $concern]);
      }

      if (in_array('Other', $_POST['concern']) && !empty($other_detail)) {
        $insertConcern->execute([$record_id, 'Other: ' . trim($other_detail)]);
      }
    }

    $pdo->commit();
    $submitted = true;

  } catch (Exception $e) {
    $pdo->rollBack();
    error_log("Safeguarding insert failed: " . $e->getMessage());
    die("An error occurred while saving the record.");
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Safeguarding Concern Logging Form</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { font-family: Arial, sans-serif; background-color: #f9f9f9; margin: 0; padding: 0; }
    .main-content { padding: 40px; max-width: 900px; margin: auto; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { color: #6a1b9a; margin-bottom: 20px; }
    fieldset { border: 1px solid #ccc; padding: 20px; margin-bottom: 30px; }
    legend { font-weight: bold; color: #333; }
    label { display: block; margin-top: 10px; font-weight: bold; }
    input[type="text"], input[type="email"], input[type="date"], input[type="time"], select, textarea {
      width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ccc; border-radius: 4px;
    }
    textarea { height: 100px; }
    .checkbox-group { margin-top: 10px; }
    .checkbox-group label { font-weight: normal; display: block; margin-left: 20px; }
    .btn-submit {
      background-color: #6a1b9a; color: white; padding: 10px 20px;
      border: none; border-radius: 4px; cursor: pointer; font-size: 16px;
    }
    .btn-submit:hover { background-color: #4a148c; }
    .message-success {
      background-color: #e0f7e9;
      color: #2e7d32;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
      font-weight: bold;
    }
  </style>
  <script>
    function toggleOtherDetail() {
      const otherCheckbox = document.querySelector('input[name="concern[]"][value="Other"]');
      const otherDetailBox = document.getElementById('other-detail-box');
      otherDetailBox.style.display = otherCheckbox.checked ? 'block' : 'none';
    }

    document.addEventListener('DOMContentLoaded', () => {
      const otherCheckbox = document.querySelector('input[name="concern[]"][value="Other"]');
      if (otherCheckbox) {
        otherCheckbox.addEventListener('change', toggleOtherDetail);
        toggleOtherDetail();
      }
    });
  </script>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Safeguarding Concern Logging Form</h2>

    <?php if ($submitted): ?>
      <div class="message-success">✅ Safeguarding record successfully submitted.</div>
    <?php endif; ?>

    <form method="post" action="">
      <fieldset>
        <legend>1. Record Details</legend>
        <label>Date of record:</label>
        <input type="date" name="record_date" required>
        <label>Time of record:</label>
        <input type="time" name="record_time" required>
        <label>Location (where disclosure/incident took place):</label>
        <input type="text" name="location">
        <label>Name of staff member completing form:</label>
        <input type="text" name="completed_by" required>
        <label>Job title/role:</label>
        <input type="text" name="job_title">
      </fieldset>

      <fieldset>
        <legend>2. Person Concern Relates To</legend>
        <label>Full name of individual concerned:</label>
        <input type="text" name="individual_name" required>
        <label>Role/relationship to organisation:</label>
        <select name="individual_role" required>
          <option value="">-- Select --</option>
          <option>Staff Member</option>
          <option>Tutor</option>
          <option>Supervisor</option>
          <option>Trainee</option>
          <option>Other Terapia Personnel</option>
          <option>Other (please specify)</option>
        </select>
        <label>Department/Programme (if applicable):</label>
        <input type="text" name="department">
        <label>Contact details (if known/relevant):</label>
        <input type="text" name="contact_details">
      </fieldset>

      <fieldset>
        <legend>3. Details of Concern</legend>
        <label>Date and time of incident/disclosure:</label>
        <input type="text" name="incident_datetime">
        <label>Where incident/disclosure took place:</label>
        <input type="text" name="incident_location">
        <label>Nature of concern:</label>
        <div class="checkbox-group">
          <label><input type="checkbox" name="concern[]" value="Behaviour towards child/young person"> Behaviour towards child/young person</label>
          <label><input type="checkbox" name="concern[]" value="Professional conduct/boundary issue"> Professional conduct/boundary issue</label>
          <label><input type="checkbox" name="concern[]" value="Safeguarding practice breach"> Safeguarding practice breach</label>
          <label><input type="checkbox" name="concern[]" value="Allegation of harm/abuse"> Allegation of harm/abuse</label>
          <label><input type="checkbox" name="concern[]" value="Neglect"> Neglect</label>
          <label><input type="checkbox" name="concern[]" value="Emotional abuse"> Emotional abuse</label>
          <label><input type="checkbox" name="concern[]" value="Physical abuse"> Physical abuse</label>
          <label><input type="checkbox" name="concern[]" value="Sexual abuse"> Sexual abuse</label>
          <label><input type="checkbox" name="concern[]" value="Radicalisation"> Radicalisation</label>
          <label><input type="checkbox" name="concern[]" value="Online safety"> Online safety</label>
          <label><input type="checkbox" name="concern[]" value="Bullying or harassment"> Bullying or harassment</label>
          <label><input type="checkbox" name="concern[]" value="Discriminatory behaviour"> Discriminatory behaviour</label>
          <label><input type="checkbox" name="concern[]" value="Mental health concern"> Mental health concern</label>
          <label><input type="checkbox" name="concern[]" value="Other"> Other (please specify)</label>
        </div>
        <div id="other-detail-box" style="display:none;">
          <label>Please specify other concern:</label>
          <input type="text" name="concern_other_detail">
        </div>
        <label>What was observed/said/done (use exact words where possible):</label>
        <textarea name="observation"></textarea>
        <label>Any evidence (documents, screenshots, witness accounts, physical signs):</label>
        <textarea name="evidence"></textarea>
      </fieldset>

      <fieldset>
        <legend>4. Immediate Actions Taken</legend>
        <label>Was the individual spoken to at the time?</label>
        <input type="text" name="spoken_to">
        <label>Were any children/young people involved?</label>
        <input type="text" name="children_involved">
        <label>Was the situation escalated immediately?</label>
        <input type="text" name="escalated">
        <label>Any urgent safeguarding steps taken:</label>
        <textarea name="urgent_steps"></textarea>
      </fieldset>

      <fieldset>
        <legend>5. Person Raising the Concern</legend>
        <label>Name:</label>
        <input type="text" name="raiser_name">
        <label>Role/relationship to organisation:</label>
        <input type="text" name="raiser_role">
        <label>Contact details (if external person raising concern):</label>
        <input type="text" name="raiser_contact">
      </fieldset>

      <button type="submit" class="btn-submit">Submit Safeguarding Record</button>
    </form>
  </div>
</div>
</body>
</html>
          