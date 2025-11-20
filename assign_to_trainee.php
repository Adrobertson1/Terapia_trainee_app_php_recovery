<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff', 'tutor', 'supervisor'])) {
  die("Access denied.");
}

// Fetch trainees
$trainees = $pdo->query("
  SELECT trainee_id, first_name, surname
  FROM trainees
  WHERE is_archived = 0
  ORDER BY surname
")->fetchAll();

// Fetch assignment types
$assignmentTypes = $pdo->query("
  SELECT type_id, type_name
  FROM assignment_types
  WHERE is_active = 1
  ORDER BY type_name
")->fetchAll();

// Fetch assignable users
$assignableUsers = [];
if (in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  $stmt = $pdo->prepare("
    SELECT user_id, username, email, role
    FROM users
    WHERE is_active = 1 AND role IN ('superuser', 'admin', 'staff')
    ORDER BY username
  ");
  $stmt->execute();
  $assignableUsers = $stmt->fetchAll();
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $trainee_id = $_POST['trainee_id'] ?? '';
  $type_id = $_POST['type_id'] ?? '';
  $assigned_date = $_POST['assigned_date'] ?? date('Y-m-d');
  $due_date = $_POST['due_date'] ?? '';
  $assigned_by = (!empty($_POST['assigned_by'])) ? $_POST['assigned_by'] : ($_SESSION['user_id'] ?? null);

  $assignment_notes = trim($_POST['assignment_notes'] ?? '');
  $assignment_instructions = trim($_POST['assignment_instructions'] ?? '');
  $assignment_description = trim($_POST['assignment_description'] ?? '');

  // Auto-select latest assignment matching type_id
  $assignment_id = null;
  if ($type_id) {
    $stmt = $pdo->prepare("
      SELECT assignment_id
      FROM assignments
      WHERE type_id = ?
      ORDER BY due_date DESC
      LIMIT 1
    ");
    $stmt->execute([$type_id]);
    $assignment_id = $stmt->fetchColumn();
  }

  if (!$trainee_id || !$type_id || !$due_date || !$assigned_by) {
    $error = 'All required fields must be completed.';
  } elseif (!$assignment_id) {
    $error = 'No assignment exists for the selected type. Please create one first.';
  } else {
    try {
      $stmt = $pdo->prepare("
        INSERT INTO trainee_assignments (
          trainee_id, assignment_id, type_id, assigned_date, due_date, status, assigned_by,
          assignment_notes, assignment_instructions, assignment_description
        ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)
      ");
      $stmt->execute([
        $trainee_id, $assignment_id, $type_id, $assigned_date, $due_date, $assigned_by,
        $assignment_notes, $assignment_instructions, $assignment_description
      ]);
      $success = true;
      header("Location: view_trainee.php?id=" . urlencode($trainee_id));
      exit;
    } catch (PDOException $e) {
      $error = 'Insert failed: ' . $e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Assign Assignment</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; }
    label { font-weight: bold; display: block; margin-top: 15px; }
    select, input, button, textarea {
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
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Assign Assignment to Trainee</h2>

    <?php if ($success): ?>
      <p class="success-message">✅ Assignment successfully assigned.</p>
    <?php elseif ($error): ?>
      <p class="error-message"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
      <label for="trainee_id">Select Trainee:</label>
      <select name="trainee_id" id="trainee_id" required>
        <option value="">-- Choose --</option>
        <?php foreach ($trainees as $t): ?>
          <option value="<?= $t['trainee_id'] ?>">
            <?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="type_id">Select Assignment Type:</label>
      <select name="type_id" id="type_id" required>
        <option value="">-- Choose --</option>
        <?php foreach ($assignmentTypes as $type): ?>
          <option value="<?= $type['type_id'] ?>">
            <?= htmlspecialchars($type['type_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="assigned_date">Assigned Date:</label>
      <input type="date" name="assigned_date" id="assigned_date" value="<?= date('Y-m-d') ?>" required>

      <label for="due_date">Due Date:</label>
      <input type="date" name="due_date" id="due_date" required>

      <label for="assignment_description">Assignment Description:</label>
      <textarea name="assignment_description" id="assignment_description" rows="4" placeholder="Brief overview of the assignment..."></textarea>

      <label for="assignment_instructions">Instructions:</label>
      <textarea name="assignment_instructions" id="assignment_instructions" rows="4" placeholder="Step-by-step instructions or expectations..."></textarea>

      <label for="assignment_notes">Additional Notes:</label>
      <textarea name="assignment_notes" id="assignment_notes" rows="3" placeholder="Any extra context or reminders..."></textarea>

      <label for="assigned_by">Assignee:</label>
      <?php if (!empty($assignableUsers)): ?>
        <select name="assigned_by" id="assigned_by" required>
          <option value="">-- Choose --</option>
          <?php foreach ($assignableUsers as $user): ?>
            <option value="<?= $user['user_id'] ?>"
              <?= ($_SESSION['user_id'] ?? '') == $user['user_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>, <?= htmlspecialchars($user['role']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <input type="text" value="<?= htmlspecialchars($_SESSION['email'] ?? 'Current User') ?>" disabled>
        <input type="hidden" name="assigned_by" value="<?= $_SESSION['user_id'] ?? '' ?>">
      <?php endif; ?>

      <button type="submit">Assign</button>
    </form>
  </div>
</div>
</body>
</html>