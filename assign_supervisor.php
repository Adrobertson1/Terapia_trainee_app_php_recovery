<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

// Handle assignment update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trainee_id'], $_POST['supervisor_id'])) {
  $trainee_id = $_POST['trainee_id'];
  $supervisor_id = $_POST['supervisor_id'] ?: null;

  $stmt = $pdo->prepare("UPDATE trainees SET supervisor_id = ? WHERE trainee_id = ?");
  $stmt->execute([$supervisor_id, $trainee_id]);
}

// Filter toggle
$unassignedOnly = isset($_GET['unassigned']) && $_GET['unassigned'] == '1';

// Fetch all trainees
$query = "
  SELECT t.trainee_id, t.first_name, t.surname, t.email, t.supervisor_id,
         s.first_name AS sup_first, s.surname AS sup_surname
  FROM trainees t
  LEFT JOIN supervisors s ON t.supervisor_id = s.supervisor_id
";
if ($unassignedOnly) {
  $query .= " WHERE t.supervisor_id IS NULL";
}
$query .= " ORDER BY t.surname ASC";

$trainees = $pdo->query($query)->fetchAll();

// Fetch all supervisors
$supervisors = $pdo->query("
  SELECT supervisor_id, first_name, surname FROM supervisors ORDER BY surname
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Assign Supervisors</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: #E6D6EC; margin: 0; }
    .dashboard-wrapper { display: flex; }
    .main-content { flex: 1; padding: 40px; }
    h2 { color: #850069; font-family: 'Josefin Sans', sans-serif; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 10px; border: 1px solid #ccc; vertical-align: middle; }
    th { background-color: #f0f0f0; }
    select { padding: 6px; width: 100%; }
    .btn-assign {
      padding: 6px 12px;
      background-color: #1976d2;
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 0.9em;
      cursor: pointer;
    }
    .btn-assign:hover { background-color: #0d47a1; }
    .filter-form { margin-bottom: 20px; }
    .filter-form label { font-weight: bold; margin-right: 10px; }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Assign Supervisors to Trainees</h2>

    <form method="get" class="filter-form">
      <label>
        <input type="checkbox" name="unassigned" value="1" <?= $unassignedOnly ? 'checked' : '' ?>>
        Show only unassigned trainees
      </label>
      <button type="submit" class="btn-assign" style="margin-left: 10px;">Apply Filter</button>
    </form>

    <?php if (empty($trainees)): ?>
      <p>No trainees found.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Trainee</th>
            <th>Email</th>
            <th>Current Supervisor</th>
            <th>Assign New Supervisor</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($trainees as $t): ?>
            <tr>
              <td><?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?></td>
              <td><?= htmlspecialchars($t['email']) ?></td>
              <td>
                <?= $t['sup_first'] ? htmlspecialchars($t['sup_first'] . ' ' . $t['sup_surname']) : '<em>None</em>' ?>
              </td>
              <td>
                <form method="post" style="display: flex; gap: 10px; align-items: center;">
                  <input type="hidden" name="trainee_id" value="<?= $t['trainee_id'] ?>">
                  <select name="supervisor_id">
                    <option value="">None</option>
                    <?php foreach ($supervisors as $s): ?>
                      <option value="<?= $s['supervisor_id'] ?>" <?= $t['supervisor_id'] == $s['supervisor_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['first_name'] . ' ' . $s['surname']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn-assign">Assign</button>
                </form>
              </td>
              <td><a href="edit_trainee.php?trainee_id=<?= $t['trainee_id'] ?>">Edit Trainee</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>