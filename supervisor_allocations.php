<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

// Handle update of supervisor assignment start date
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_start_date'])) {
  $stmt = $pdo->prepare("UPDATE trainees SET start_date = ? WHERE trainee_id = ?");
  $stmt->execute([$_POST['start_date'], $_POST['trainee_id']]);
  header("Location: supervisor_allocations.php?updated=1");
  exit;
}

// Handle removal of supervisor allocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_allocation'])) {
  $stmt = $pdo->prepare("UPDATE trainees SET supervisor_id = NULL, start_date = NULL WHERE trainee_id = ?");
  $stmt->execute([$_POST['trainee_id']]);
  header("Location: supervisor_allocations.php?removed=1");
  exit;
}

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_supervisor'])) {
  $assignStmt = $pdo->prepare("UPDATE trainees SET supervisor_id = ?, start_date = CURDATE() WHERE trainee_id = ?");
  $assignStmt->execute([$_POST['supervisor_id'], $_POST['trainee_id']]);
  header("Location: supervisor_allocations.php?assigned=1");
  exit;
}

// Fetch supervisors
$supervisorsStmt = $pdo->query("SELECT supervisor_id, first_name, surname FROM supervisors ORDER BY surname");
$supervisors = $supervisorsStmt->fetchAll();

// Fetch trainees grouped by supervisor
$traineeGroups = [];
foreach ($supervisors as $s) {
  $stmt = $pdo->prepare("
    SELECT trainee_id, first_name, surname, email, telephone, start_date
    FROM trainees
    WHERE supervisor_id = ? AND is_archived = 0
    ORDER BY surname
  ");
  $stmt->execute([$s['supervisor_id']]);
  $traineeGroups[$s['supervisor_id']] = $stmt->fetchAll();
}

// Fetch unallocated trainees
$unallocatedStmt = $pdo->query("
  SELECT trainee_id, first_name, surname, email, telephone
  FROM trainees
  WHERE supervisor_id IS NULL AND is_archived = 0
  ORDER BY surname
");
$unallocated = $unallocatedStmt->fetchAll();

// Fetch all allocated trainees with supervisor details
$allocatedStmt = $pdo->query("
  SELECT t.trainee_id, t.first_name, t.surname, t.email, t.telephone, t.start_date,
         s.first_name AS supervisor_first, s.surname AS supervisor_surname
  FROM trainees t
  JOIN supervisors s ON t.supervisor_id = s.supervisor_id
  WHERE t.supervisor_id IS NOT NULL AND t.is_archived = 0
  ORDER BY s.surname, t.surname
");
$allocatedAll = $allocatedStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Supervisor Allocations</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .dashboard-section {
      margin-bottom: 40px;
    }
    .allocation-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      padding: 10px;
      border-bottom: 1px solid #ccc;
      text-align: left;
    }
    th {
      background-color: #6a1b9a;
      color: white;
    }
    .btn-sm {
      padding: 6px 12px;
      font-size: 14px;
      border-radius: 4px;
      text-decoration: none;
      display: inline-block;
      border: none;
      cursor: pointer;
    }
    .btn-assign { background-color: #4CAF50; color: white; }
    .btn-edit { background-color: #2196F3; color: white; }
    .btn-remove { background-color: #d32f2f; color: white; }
    .assign-form {
      display: flex;
      gap: 10px;
      align-items: center;
      margin-top: 10px;
    }
    .assign-form select, .assign-form input[type="date"] {
      padding: 6px;
      border-radius: 4px;
      border: 1px solid #ccc;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Supervisor Allocations</h2>

    <?php foreach ($supervisors as $s): ?>
      <div class="dashboard-section">
        <h3>🧑‍🏫 <?= htmlspecialchars($s['first_name'] . ' ' . $s['surname']) ?></h3>
        <?php if (count($traineeGroups[$s['supervisor_id']]) === 0): ?>
          <p>No trainees currently allocated.</p>
        <?php else: ?>
          <table class="allocation-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Telephone</th>
                <th>Start Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($traineeGroups[$s['supervisor_id']] as $t): ?>
                <tr>
                  <td>
                    <a href="view_trainee.php?id=<?= $t['trainee_id'] ?>">
                      <?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?>
                    </a>
                  </td>
                  <td><?= htmlspecialchars($t['email']) ?></td>
                  <td><?= htmlspecialchars($t['telephone']) ?></td>
                  <td>
                    <form method="post" style="display:inline-block;">
                      <input type="hidden" name="trainee_id" value="<?= $t['trainee_id'] ?>">
                      <input type="date" name="start_date" value="<?= htmlspecialchars($t['start_date']) ?>" required>
                      <button type="submit" name="update_start_date" class="btn-sm btn-edit">Update</button>
                    </form>
                  </td>
                  <td>
                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Remove supervisor allocation?');">
                      <input type="hidden" name="trainee_id" value="<?= $t['trainee_id'] ?>">
                      <button type="submit" name="remove_allocation" class="btn-sm btn-remove">Remove</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <div class="dashboard-section">
      <h3>📋 All Allocated Trainees</h3>
      <?php if (count($allocatedAll) === 0): ?>
        <p>No trainees are currently allocated to supervisors.</p>
      <?php else: ?>
        <table class="allocation-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Telephone</th>
              <th>Supervisor</th>
              <th>Start Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($allocatedAll as $t): ?>
              <tr>
                <td>
                  <a href="view_trainee.php?id=<?= $t['trainee_id'] ?>">
                    <?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?>
                  </a>
                </td>
                <td><?= htmlspecialchars($t['email']) ?></td>
                <td><?= htmlspecialchars($t['telephone']) ?></td>
                <td><?= htmlspecialchars($t['supervisor_first'] . ' ' . $t['supervisor_surname']) ?></td>
                <td><?= htmlspecialchars($t['start_date'] ?? '—') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="dashboard-section">
      <h3>🚫 Unallocated Trainees</h3>
      <?php if (count($unallocated) === 0): ?>
        <p>All trainees are currently assigned to supervisors.</p>
      <?php else: ?>
        <table class="allocation-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Telephone</th>
              <th>Assign Supervisor</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($unallocated as $t): ?>
              <tr>
                <td>
                  <a href="view_trainee.php?id=<?= $t['trainee_id'] ?>">
  <?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?>
                  </a>
              </td>
              <td><?= htmlspecialchars($t['email']) ?></td>
              <td><?= htmlspecialchars($t['telephone']) ?></td>
              <td>
                <form method="post" class="assign-form">
                  <input type="hidden" name="trainee_id" value="<?= $t['trainee_id'] ?>">
                  <select name="supervisor_id" required>
                    <option value="">-- Select Supervisor --</option>
                    <?php foreach ($supervisors as $s): ?>
                      <option value="<?= $s['supervisor_id'] ?>">
                        <?= htmlspecialchars($s['first_name'] . ' ' . $s['surname']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" name="assign_supervisor" class="btn-sm btn-assign">Assign</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>