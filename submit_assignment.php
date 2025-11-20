<?php
session_start();
require 'db.php';

$assignment_id = $_GET['assignment_id'] ?? null;
$trainee_id = $_SESSION['user_id'] ?? null;

if (!$assignment_id || !$trainee_id) {
  die("Invalid access.");
}

$success = false;
$error = '';

// Fetch assignment details for display
$stmt = $pdo->prepare("
  SELECT a.title, a.due_date, c.course_name,
         ta.assignment_description, ta.assignment_instructions, ta.assignment_notes
  FROM trainee_assignments ta
  JOIN assignments a ON ta.assignment_id = a.assignment_id
  JOIN courses c ON a.course_id = c.course_id
  WHERE ta.trainee_id = ? AND ta.assignment_id = ?
");
$stmt->execute([$trainee_id, $assignment_id]);
$details = $stmt->fetch();

if (!$details) {
  die("Assignment not found.");
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $filename = trim($_POST['filename'] ?? '');
  $notes = trim($_POST['notes'] ?? '');
  $submitted_date = date('Y-m-d H:i:s');
  $status = 'submitted';

  // Handle file upload
  $file_path = '';
  if (!empty($_FILES['attachment']['name'])) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
      mkdir($target_dir, 0775, true);
    }
    $file_path = $target_dir . basename($_FILES["attachment"]["name"]);
    if (!move_uploaded_file($_FILES["attachment"]["tmp_name"], $file_path)) {
      $error = "File upload failed.";
    }
  }

  if (!$filename || !$file_path) {
    $error = "Filename and file upload are required.";
  } else {
    try {
      $stmt = $pdo->prepare("
        INSERT INTO assignment_submissions (
          assignment_id, trainee_id, submitted_date, file_path, status, grade, feedback
        ) VALUES (?, ?, ?, ?, ?, NULL, ?)
      ");
      $stmt->execute([
        $assignment_id, $trainee_id, $submitted_date, $file_path, $status, $notes
      ]);
      $success = true;
    } catch (PDOException $e) {
      $error = "Submission failed: " . $e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Submit Assignment</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; }
    label { font-weight: bold; display: block; margin-top: 15px; }
    input, textarea, button {
      padding: 10px;
      margin-top: 5px;
      width: 100%;
      max-width: 600px;
    }
    textarea { resize: vertical; }
    button {
      background-color: #6a1b9a;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    button:hover { background-color: #8e24aa; }
    .success-message { color: green; font-weight: bold; margin-top: 20px; }
    .error-message { color: #d32f2f; font-weight: bold; margin-top: 20px; }
    .assignment-details {
      background: #f9f5ff;
      border-left: 4px solid #6a1b9a;
      padding: 20px;
      margin-bottom: 30px;
    }
    .assignment-details h3 {
      margin-top: 0;
      color: #6a1b9a;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Submit Assignment</h2>

    <div class="assignment-details">
      <h3><?= htmlspecialchars($details['title']) ?></h3>
      <p><strong>Course:</strong> <?= htmlspecialchars($details['course_name']) ?></p>
      <p><strong>Due Date:</strong> <?= htmlspecialchars(date('j M Y', strtotime($details['due_date']))) ?></p>
      <hr>
      <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($details['assignment_description'])) ?></p>
      <p><strong>Instructions:</strong><br><?= nl2br(htmlspecialchars($details['assignment_instructions'])) ?></p>
      <p><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($details['assignment_notes'])) ?></p>
    </div>

    <?php if ($success): ?>
      <p class="success-message">✅ Submission successful.</p>
    <?php elseif ($error): ?>
      <p class="error-message"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <label for="filename">Filename:</label>
      <input type="text" name="filename" id="filename" required>

      <label for="attachment">Upload File:</label>
      <input type="file" name="attachment" id="attachment" required>

      <label for="notes">Submission Notes:</label>
      <textarea name="notes" id="notes" rows="4" placeholder="Any comments or context..."></textarea>

      <button type="submit">Submit</button>
    </form>
  </div>
</div>
</body>
</html>