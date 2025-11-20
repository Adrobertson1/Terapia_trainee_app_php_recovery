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
$userType = $_POST['user_type'] ?? '';

$requiredFields = [
  'trainee' => ['first_name', 'surname', 'email', 'cohort', 'enrolment_date'],
  'tutor'   => ['first_name', 'surname', 'email', 'subject_area', 'start_date'],
  'staff'   => ['first_name', 'surname', 'email', 'role', 'start_date']
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
            while (($row = fgetcsv($handle)) !== false && count($previewRows) < 10) {
                $previewRows[] = array_combine($header, $row);
            }
            fclose($handle);

            // Store file temporarily
            $tempPath = 'uploads/temp_' . time() . '.csv';
            move_uploaded_file($_FILES['csv_file']['tmp_name'], $tempPath);
            $_SESSION['csv_temp_path'] = $tempPath;
            $_SESSION['csv_user_type'] = $userType;
            $_SESSION['csv_header'] = $header;
        }
    }

    // Step 2: Confirm and Insert
    elseif (isset($_POST['confirm_upload']) && isset($_SESSION['csv_temp_path'])) {
        $file = $_SESSION['csv_temp_path'];
        $userType = $_SESSION['csv_user_type'];
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
                foreach ($requiredFields[$userType] as $field) {
                    if (empty($data[$field])) {
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
                $dateField = $userType === 'trainee' ? 'enrolment_date' : 'start_date';
                if (!isValidDate($data[$dateField])) {
                    $uploadSummary[] = "Row $rowCount skipped: invalid date format (" . htmlspecialchars($data[$dateField]) . ")";
                    continue;
                }

                try {
                    if ($userType === 'trainee') {
                        $stmt = $pdo->prepare("
                            INSERT INTO trainees (first_name, surname, email, cohort, enrolment_date)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $data['first_name'],
                            $data['surname'],
                            $data['email'],
                            $data['cohort'],
                            $data['enrolment_date']
                        ]);
                    } elseif ($userType === 'tutor') {
                        $stmt = $pdo->prepare("
                            INSERT INTO tutors (first_name, surname, email, subject_area, start_date)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $data['first_name'],
                            $data['surname'],
                            $data['email'],
                            $data['subject_area'],
                            $data['start_date']
                        ]);
                    } elseif ($userType === 'staff') {
                        $stmt = $pdo->prepare("
                            INSERT INTO staff (first_name, surname, email, role, start_date)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $data['first_name'],
                            $data['surname'],
                            $data['email'],
                            $data['role'],
                            $data['start_date']
                        ]);
                    }

                    $successCount++;
                } catch (Exception $e) {
                    $uploadSummary[] = "Row $rowCount failed: " . $e->getMessage();
                }
            }

            fclose($handle);
            unlink($file);
            unset($_SESSION['csv_temp_path'], $_SESSION['csv_user_type'], $_SESSION['csv_header']);
            $uploadSummary[] = "$successCount of $rowCount records uploaded successfully.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bulk Upload Users</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .upload-form {
      margin-top: 30px;
      padding: 20px;
      background-color: #f9f9f9;
      border-radius: 8px;
      max-width: 600px;
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
      <h2>Bulk Upload Users</h2>

      <?php if (empty($previewRows) && empty($_POST['confirm_upload'])): ?>
        <form method="post" enctype="multipart/form-data" class="upload-form">
          <label>Select User Type:</label>
          <select name="user_type" required>
            <option value="">-- Choose Type --</option>
            <option value="trainee">Trainee</option>
            <option value="tutor">Tutor</option>
            <option value="staff">Staff</option>
          </select>

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