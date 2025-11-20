<?php
require 'db.php';

$trainee_id = $_GET['id'] ?? null;
if (!$trainee_id) {
    die("No trainee ID provided.");
}

// Fetch current supervisor (if any)
$currentStmt = $pdo->prepare("
  SELECT u.user_id, u.first_name, u.surname
  FROM individual_supervision i
  JOIN users u ON i.user_id = u.user_id
  WHERE i.trainee_id = ?
");
$currentStmt->execute([$trainee_id]);
$currentSupervisor = $currentStmt->fetch();

// Fetch all eligible supervisors
$supervisors = $pdo->query("
  SELECT user_id, first_name, surname
  FROM users
  WHERE role = 'supervisor'
  ORDER BY surname
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_user_id = $_POST['user_id'] ?? null;
    if ($new_user_id) {
        // Assign or reassign supervisor
        $stmt = $pdo->prepare("
          REPLACE INTO individual_supervision (trainee_id, user_id, assigned_date)
          VALUES (?, ?, CURDATE())
        ");
        $stmt->execute([$trainee_id, $new_user_id]);

        // Log the reassignment
        $logStmt = $pdo->prepare("
          INSERT INTO trainee_logs (trainee_id, action_type, description, timestamp, performed_by)
          VALUES (?, 'supervision', ?, NOW(), ?)
        ");
        $description = "Supervisor reassigned to user_id $new_user_id";
        $performed_by = $_SESSION['username'] ?? 'system';
        $logStmt->execute([$trainee_id, $description, $performed_by]);

        header("Location: view_trainee.php?id=" . $trainee_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reassign Supervisor</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .form-box {
      background: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      max-width: 500px;
      margin-bottom: 30px;
      border: 1px solid #ccc;
    }
    .form-box label {
      display: block;
      margin-bottom: 6px;
      font-weight: bold;
    }
    .form-box select, .form-box button {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 4px;
      border: 1px solid #ccc;
    }
    .form-box button {
      background-color: #6a1b9a;
      color: white;
      border: none;
      cursor: pointer;
    }
    .form-box button:hover {
      background-color: #8e24aa;
    }
    .current-supervisor {
      margin-bottom: 15px;
      font-style: italic;
      color: #333;
    }
    .btn-back {
      display: inline-block;
      margin-top: 10px;
      padding: 8px 14px;
      background-color: #ccc;
      color: #333;
      border-radius: 4px;
      text-decoration: none;
    }
    .btn-back:hover {
      background-color: #bbb;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Reassign Supervisor</h2>

    <div class="form-box">
      <?php if ($currentSupervisor): ?>
        <div class="current-supervisor">
          Current Supervisor: <?= htmlspecialchars($currentSupervisor['first_name'] . ' ' . $currentSupervisor['surname']) ?>
        </div>
      <?php else: ?>
        <div class="current-supervisor">No supervisor currently assigned.</div>
      <?php endif; ?>

      <form method="post" onsubmit="return confirmSupervisorChange();">
        <label for="user_id">Select New Supervisor:</label>
        <select name="user_id" id="user_id" required>
          <option value="">-- Choose Supervisor --</option>
          <?php foreach ($supervisors as $s): ?>
            <option value="<?= $s['user_id'] ?>">
              <?= htmlspecialchars($s['surname'] . ', ' . $s['first_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit">Reassign Supervisor</button>
      </form>
    </div>

    <a href="view_trainee.php?id=<?= htmlspecialchars($trainee_id) ?>" class="btn-back">← Back to Trainee Profile</a>
  </div>
</div>

<script>
function confirmSupervisorChange() {
  const select = document.getElementById('user_id');
  const selectedText = select.options[select.selectedIndex].text;
  return confirm(`Are you sure you want to reassign the supervisor to ${selectedText}?`);
}
</script>
</body>
</html>