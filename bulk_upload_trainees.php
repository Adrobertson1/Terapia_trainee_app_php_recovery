<?php
session_start();
require 'db.php';
require_once 'functions.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$uploadSummary = [];
$previewRows = [];
$header = [];

$expectedFields = [
  'trainee_id', 'first_name', 'surname', 'individual_supervisor', 'email', 'is_archived',
  'date_of_birth', 'disability_status', 'disability_type', 'town_city', 'postcode',
  'password', 'profile_image', 'trainee_code', 'address_line1', 'telephone',
  'supervisor_id', 'start_date', 'progress_score', 'cohort', 'enrolment_date',
  'dbs_status', 'dbs_date', 'dbs_reference', 'dbs_update_service'
];

function isValidDate($date) {
  return (bool)strtotime($date);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Step 1: Preview CSV
  if (isset($_FILES['csv_file']) && !isset($_POST['confirm_upload'])) {
    $file = $_FILES['csv_file']['tmp_name'];

    if (($handle = fopen($file, 'r')) !== false) {
      $header = fgetcsv($handle);
      if ($header !== $expectedFields) {
        $uploadSummary[] = "❌ Invalid CSV format. Please use the official template.";
      } else {
        while (($row = fgetcsv($handle)) !== false && count($previewRows) < 10) {
          $previewRows[] = array_combine($header, $row);
        }
        fclose($handle);

        $tempPath = 'uploads/temp_' . time() . '.csv';
        move_uploaded_file($_FILES['csv_file']['tmp_name'], $tempPath);
        $_SESSION['csv_temp_path'] = $tempPath;
        $_SESSION['csv_header'] = $header;
      }
    }
  }

  // Step 2: Confirm and Insert
  elseif (isset($_POST['confirm_upload']) && isset($_SESSION['csv_temp_path'])) {
    $file = $_SESSION['csv_temp_path'];
    $header = $_SESSION['csv_header'];

    if (($handle = fopen($file, 'r')) !== false) {
      fgetcsv($handle); // skip header
      $rowCount = 0;
      $successCount = 0;

      while (($row = fgetcsv($handle)) !== false) {
        $rowCount++;
        $data = array_combine($header, array_map('trim', $row));

        // Validate required fields
        $missing = [];
        foreach ($expectedFields as $field) {
          if (!isset($data[$field]) || $data[$field] === '') {
            $missing[] = $field;
          }
        }
        if (!empty($missing)) {
          $uploadSummary[] = "Row $rowCount skipped: missing fields (" . implode(', ', $missing) . ")";
          continue;
        }

        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
          $uploadSummary[] = "Row $rowCount skipped: invalid email format (" . htmlspecialchars($data['email']) . ")";
          continue;
        }

        // Validate date fields
        foreach (['start_date', 'date_of_birth', 'dbs_date', 'enrolment_date'] as $field) {
          if (!isValidDate($data[$field])) {
            $uploadSummary[] = "Row $rowCount skipped: invalid date format ($field)";
            continue 2;
          }
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        try {
          $stmt = $pdo->prepare("
            INSERT INTO trainees (
              trainee_id, first_name, surname, individual_supervisor, email, is_archived,
              date_of_birth, disability_status, disability_type, town_city, postcode,
              password, profile_image, trainee_code, address_line1, telephone,
              supervisor_id, start_date, progress_score, cohort, enrolment_date,
              dbs_status, dbs_date, dbs_reference, dbs_update_service
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
          ");

          $stmt->execute([
            $data['trainee_id'],
            $data['first_name'],
            $data['surname'],
            $data['individual_supervisor'],
            $data['email'],
            $data['is_archived'],
            $data['date_of_birth'],
            $data['disability_status'],
            $data['disability_status'] === 'Yes' ? $data['disability_type'] : '',
            $data['town_city'],
            $data['postcode'],
            $hashedPassword,
            $data['profile_image'],
            $data['trainee_code'],
            $data['address_line1'],
            $data['telephone'],
            $data['supervisor_id'],
            $data['start_date'],
            $data['progress_score'],
            $data['cohort'],
            $data['enrolment_date'],
            $data['dbs_status'],
            $data['dbs_date'],
            $data['dbs_reference'],
            !empty($data['dbs_update_service']) ? 1 : 0
          ]);

          $successCount++;
        } catch (Exception $e) {
          $uploadSummary[] = "Row $rowCount failed: " . $e->getMessage();
        }
      }

      fclose($handle);
      unlink($file);
      unset($_SESSION['csv_temp_path'], $_SESSION['csv_header']);
      $uploadSummary[] = "$successCount of $rowCount records uploaded successfully.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bulk Upload Trainees</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .upload-form {
      margin-top: 30px;
      padding: 20px;
      background-color: #f9f9f9;
      border-radius: 8px;
      max-width: 700px;
    }
    .upload-form label {
      display: block;
      margin-bottom: 8px;
      font-weight: bold;
    }
    .upload-form input, .upload-form select {
      width: 100%;
      padding: 8px;
      margin-bottom: 15px;
    }
    .upload-summary {
      margin-top: 20px;
      background-color: #e0f7fa;
      padding: 15px;
      border-radius: 6px;
    }
    .summary-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .summary-table th, .summary-table td {
      padding: 10px;
      border-bottom: 1px solid #ccc;
      text-align: left;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="page-container">
      <h2>Bulk Upload Trainees</h2>

      <?php if (empty($previewRows) && empty($_POST['confirm_upload'])): ?>
        <form method="post" enctype="multipart/form-data" class="upload-form">
          <label>Upload CSV File:</label>
          <input type="file" name="csv_file" accept=".csv" required>
          <button type="submit" class="btn">Preview CSV</button>
        </form>
      <?php endif; ?>

      <?php if (!empty($previewRows)): ?>
        <h4>Preview First 10 Rows:</h4>
        <table class="summary-table">
          <thead>
            <tr>
              <?php foreach ($header as $col): ?>
                <th><?= htmlspecialchars($col) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($previewRows as $row): ?>
              <tr>
                <?php foreach ($row as $cell): ?>
                  <td><?= htmlspecialchars($cell) ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <form method="post">
          <input type="hidden" name="confirm_upload" value="1">
          <button type="submit" class="btn">Confirm & Upload</button>
        </form>
      <?php endif; ?>

      <?php if (!empty($uploadSummary)): ?>
        <div class="upload-summary">
          <h4>Upload Summary:</h4>
          <ul>
            <?php foreach ($uploadSummary as $msg): ?>
              <li><?= htmlspecialchars($msg) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>