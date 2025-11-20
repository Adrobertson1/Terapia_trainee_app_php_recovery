<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

// Handle supervisor archival
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_supervisor'])) {
  $stmt = $pdo->prepare("UPDATE supervisors SET is_archived = 1 WHERE supervisor_id = ?");
  $stmt->execute([$_POST['supervisor_id']]);
  header("Location: supervisors.php?archived=1");
  exit;
}

// Handle supervisor restoration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_supervisor'])) {
  $stmt = $pdo->prepare("UPDATE supervisors SET is_archived = 0 WHERE supervisor_id = ?");
  $stmt->execute([$_POST['supervisor_id']]);
  header("Location: supervisors.php?restored=1&view=archived");
  exit;
}

// Handle trainee assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_trainee'])) {
  $trainee_id = $_POST['trainee_id'] ?? '';
  $supervisor_id = $_POST['supervisor_id'] ?? '';
  if ($trainee_id && $supervisor_id) {
    $assignStmt = $pdo->prepare("UPDATE trainees SET supervisor_id = ? WHERE trainee_id = ?");
    $assignStmt->execute([$supervisor_id, $trainee_id]);
    header("Location: supervisors.php?assigned=1");
    exit;
  }
}

// View mode toggle
$viewArchived = ($_GET['view'] ?? '') === 'archived';

// Handle filter and sort
$filter = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? '';
$params = $filter ? ['%' . $filter . '%', '%' . $filter . '%'] : [];

$orderClause = ($sort === 'trainee_count') ? 'trainee_count DESC' : 's.surname ASC';
$whereClause = $viewArchived ? 's.is_archived = 1' : 's.is_archived = 0';
$filterClause = $filter ? "AND (s.first_name LIKE ? OR s.surname LIKE ?)" : "";

$stmt = $pdo->prepare("
  SELECT s.supervisor_id, s.first_name, s.surname, s.email, s.profile_image,
         COUNT(t.trainee_id) AS trainee_count
  FROM supervisors s
  LEFT JOIN trainees t ON s.supervisor_id = t.supervisor_id
  WHERE $whereClause $filterClause
  GROUP BY s.supervisor_id
  ORDER BY $orderClause
");
$stmt->execute($params);
$supervisors = $stmt->fetchAll();

// Fetch unassigned trainees
$unassignedStmt = $pdo->query("
  SELECT trainee_id, first_name, surname
  FROM trainees
  WHERE supervisor_id IS NULL AND is_archived = 0
  ORDER BY surname
");
$unassignedTrainees = $unassignedStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Supervisors</title>
  <link rel="stylesheet" href="style.css">
  <style>
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 10px; border: 1px solid #ccc; vertical-align: top; }
    th { background-color: #f0f0f0; }
    .badge-supervisor {
      background-color: #6a1b9a;
      color: white;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.75em;
      font-weight: bold;
    }
    .btn {
      padding: 6px 12px;
      background-color: #1976d2;
      color: white;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      font-size: 0.9em;
      margin-right: 5px;
      display: inline-block;
    }
    .btn:hover { background-color: #0d47a1; }
    .btn-archive { background-color: #d32f2f; }
    .btn-archive:hover { background-color: #b71c1c; }
    .btn-restore { background-color: #4CAF50; }
    .btn-restore:hover { background-color: #388E3C; }
    .filter-form { margin-top: 20px; margin-bottom: 20px; }
    .filter-form input, .filter-form select {
      padding: 6px;
      margin-right: 10px;
    }
    .filter-form button {
      padding: 6px 12px;
      background-color: #850069;
      color: white;
      border: none;
      border-radius: 4px;
    }
    .filter-form button:hover { background-color: #BB9DC6; }
    .trainee-list { margin-top: 10px; padding-left: 20px; font-size: 0.9em; }
    details summary { cursor: pointer; font-weight: bold; }
    .dashboard-wrapper { display: flex; }
    .main-content { flex: 1; padding: 20px; }
    select[name="trainee_id"] {
      margin-top: 8px;
      padding: 6px;
      border-radius: 4px;
      border: 1px solid #ccc;
      min-width: 180px;
    }
    .profile-thumb {
      width: 50px;
      height: 50px;
      object-fit: cover;
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
    <h2><?= $viewArchived ? 'Archived Supervisors' : 'Manage Supervisors' ?></h2>

    <form method="get" class="filter-form">
      <input type="text" name="search" placeholder="Search by name..." value="<?= htmlspecialchars($filter) ?>">
      <select name="sort">
        <option value="">Sort by Name</option>
        <option value="trainee_count" <?= $sort === 'trainee_count' ? 'selected' : '' ?>>Sort by Trainee Count</option>
      </select>
      <input type="hidden" name="view" value="<?= $viewArchived ? 'archived' : 'active' ?>">
      <button type="submit">Apply</button>
    </form>

    <p>
      <?php if ($viewArchived): ?>
        <a class="btn" href="supervisors.php?view=active">View Active Supervisors</a>
      <?php else: ?>
        <a class="btn" href="supervisors.php?view=archived">View Archived Supervisors</a>
      <?php endif; ?>
    </p>

    <?php if (empty($supervisors)): ?>
      <p>No supervisors found.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Photo</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Trainees</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($supervisors as $s): ?>
            <tr>
              <td>
                <?php
                $imagePath = $s['profile_image'];
                if (!empty($imagePath) && file_exists($imagePath)) {
                  echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Photo" class="profile-thumb">';
                } else {
                  echo '<span style="color:#999;">No image</span>';
                }
                ?>
              </td>
              <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['surname']) ?></td>
              <td><?= htmlspecialchars($s['email']) ?></td>
              <td><span class="badge-supervisor">Supervisor</span></td>
              <td>
                <?= $s['trainee_count'] ?> assigned
                <?php if ($s['trainee_count'] > 0): ?>
                  <details class="trainee-list">
                    <summary>Preview Trainees</summary>
                    <ul>
                      <?php
                      $tStmt = $pdo->prepare("SELECT first_name, surname FROM trainees WHERE supervisor_id = ?");
                      $tStmt->execute([$s['supervisor_id']]);
                      foreach ($tStmt->fetchAll() as $t): ?>
                        <li><?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?></li>
                      <?php endforeach; ?>
                    </ul>
                  </details>
                <?php endif; ?>
              </td>
              <td>
             <a class="btn" href="view_supervisor.php?id=<?= $s['supervisor_id'] ?>">View Profile</a>
<?php if (!$viewArchived): ?>
  <a class="btn" href="edit_supervisors.php?supervisor_id=<?= $s['supervisor_id'] ?>">Edit</a>
  <a class="btn" href="delete_user.php?id=<?= $s['supervisor_id'] ?>" onclick="return confirm('Delete this supervisor?');">Delete</a>
  <a class="btn" href="view_supervisor_trainees.php?supervisor_id=<?= $s['supervisor_id'] ?>">View Trainees</a>
  <form method="post" style="display:inline;" onsubmit="return confirm('Archive this supervisor?');">
    <input type="hidden" name="supervisor_id" value="<?= $s['supervisor_id'] ?>">
    <button type="submit" name="archive_supervisor" class="btn btn-archive">Archive</button>
  </form>

  <?php if (!empty($unassignedTrainees)): ?>
    <form method="post" style="margin-top:10px;">
      <input type="hidden" name="supervisor_id" value="<?= $s['supervisor_id'] ?>">
      <select name="trainee_id" required>
        <option value="">Assign Trainee...</option>
        <?php foreach ($unassignedTrainees as $ut): ?>
          <option value="<?= $ut['trainee_id'] ?>">
            <?= htmlspecialchars($ut['first_name'] . ' ' . $ut['surname']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" name="assign_trainee" class="btn">Assign</button>
    </form>
  <?php endif; ?>
<?php else: ?>
  <form method="post" style="display:inline;" onsubmit="return confirm('Restore this supervisor?');">
    <input type="hidden" name="supervisor_id" value="<?= $s['supervisor_id'] ?>">
    <button type="submit" name="restore_supervisor" class="btn btn-restore">Restore</button>
  </form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php if (!$viewArchived): ?>
  <p><a class="btn" href="add_supervisor.php">Add New Supervisor</a></p>
  <p><a class="btn" href="export_supervisors.php">Export as CSV</a></p>
<?php endif; ?>
</div>
</div>
</body>
</html>