<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$view = $_GET['view'] ?? 'active';
$where = "WHERE is_archived = " . ($view === 'archived' ? "1" : "0");
$params = [];

// Fetch course options
$courseOptions = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_name")->fetchAll();

// Apply filters
if (!empty($_GET['name'])) {
  $where .= " AND (first_name LIKE ? OR surname LIKE ?)";
  $params[] = '%' . $_GET['name'] . '%';
  $params[] = '%' . $_GET['name'] . '%';
}
if (!empty($_GET['email'])) {
  $where .= " AND email LIKE ?";
  $params[] = '%' . $_GET['email'] . '%';
}
if (!empty($_GET['telephone'])) {
  $where .= " AND telephone LIKE ?";
  $params[] = '%' . $_GET['telephone'] . '%';
}
if (!empty($_GET['course'])) {
  $where .= " AND trainee_id IN (
    SELECT trainee_id FROM trainee_courses tc
    JOIN courses c ON tc.course_id = c.course_id
    WHERE c.course_name LIKE ?
  )";
  $params[] = '%' . $_GET['course'] . '%';
}

// Fetch trainees
$stmt = $pdo->prepare("SELECT * FROM trainees $where ORDER BY surname");
$stmt->execute($params);
$traineeList = $stmt->fetchAll();

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename=trainee_export.csv');
  header('Pragma: no-cache');
  header('Expires: 0');

  $output = fopen('php://output', 'w');
  fputcsv($output, [
    'trainee_id', 'first_name', 'surname', 'individual_supervisor', 'email', 'is_archived',
    'date_of_birth', 'disability_status', 'disability_type', 'town_city', 'postcode',
    'password', 'profile_image', 'trainee_code', 'address_line1', 'telephone',
    'supervisor_id', 'start_date'
  ]);

  foreach ($traineeList as $t) {
    fputcsv($output, [
      $t['trainee_id'], $t['first_name'], $t['surname'], $t['individual_supervisor'], $t['email'],
      $t['is_archived'], $t['date_of_birth'], $t['disability_status'], $t['disability_type'],
      $t['town_city'], $t['postcode'], $t['password'], $t['profile_image'], $t['trainee_code'],
      $t['address_line1'], $t['telephone'], $t['supervisor_id'], $t['start_date']
    ]);
  }

  fclose($output);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Trainees</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .main-content { padding: 40px; }
    .top-actions {
      display: flex;
      flex-direction: column;
      gap: 20px;
      margin-bottom: 30px;
    }
    .tab-buttons {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
    .action-buttons {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
    .search-form {
      margin-bottom: 30px;
    }
    .search-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 12px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      table-layout: fixed;
    }
    th, td {
      padding: 12px;
      border-bottom: 1px solid #ccc;
      text-align: left;
      vertical-align: middle;
      word-wrap: break-word;
    }
    th {
      background-color: #6a1b9a;
      color: white;
    }
    .thumbnail {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 4px;
      border: 1px solid #ccc;
    }
    .action-buttons td {
      vertical-align: top;
    }
    .action-buttons form,
    .action-buttons a {
      display: inline-block;
    }
    .message-success {
      background-color: #e0f7e9;
      color: #2e7d32;
      padding: 10px;
      margin-bottom: 20px;
      border-left: 5px solid #2e7d32;
    }
    .message-warning {
      background-color: #fff3cd;
      color: #856404;
      padding: 10px;
      margin-bottom: 20px;
      border-left: 5px solid #856404;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Registered Trainees</h2>

    <?php if ($_GET['restored'] ?? '' === '1'): ?>
      <div class="message-success">✅ Trainee successfully restored.</div>
    <?php endif; ?>
    <?php if ($_GET['archived'] ?? '' === '1'): ?>
      <div class="message-warning">🗂️ Trainee successfully archived.</div>
    <?php endif; ?>

    <div class="top-actions">
      <div class="tab-buttons">
        <a href="?view=active" class="btn-sm <?= $view === 'active' ? 'btn-active' : 'btn-default' ?>">Active Trainees</a>
        <a href="?view=archived" class="btn-sm <?= $view === 'archived' ? 'btn-archived' : 'btn-default' ?>">Archived Trainees</a>
      </div>
      <div class="action-buttons">
        <a href="add_trainee.php" class="btn-sm btn-active"><i class="fas fa-user-plus"></i> Add New Trainee</a>
        <a href="bulk_upload_trainees.php" class="btn-sm btn-default"><i class="fas fa-file-upload"></i> Bulk Upload Trainees</a>
        <a href="Uploads/trainee_template.csv" class="btn-sm btn-default" download><i class="fas fa-download"></i> Download CSV Template</a>
        <a href="trainees.php?export=csv&view=<?= htmlspecialchars($view) ?>" class="btn-sm btn-default"><i class="fas fa-download"></i> Export All Trainees (CSV)</a>
      </div>
    </div>

    <form method="get" class="search-form">
      <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
      <div class="search-grid">
        <input type="text" name="name" placeholder="Name" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>">
        <input type="text" name="email" placeholder="Email" value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
        <input type="text" name="telephone" placeholder="Telephone" value="<?= htmlspecialchars($_GET['telephone'] ?? '') ?>">
        <select name="course">
          <option value="">-- Select Course --</option>
          <?php foreach ($courseOptions as $course): ?>
            <option value="<?= htmlspecialchars($course['course_name']) ?>"
              <?= ($_GET['course'] ?? '') === $course['course_name'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($course['course_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-sm btn-default">Search</button>
        <a href="trainees.php?view=<?= htmlspecialchars($view) ?>" class="btn-sm btn-default">Reset</a>
      </div>
    </form>

    <table>
      <thead>
        <tr>
          <th>Photo</th>
          <th>Name</th>
          <th>Email</th>
          <th>Telephone</th>
          <th>Start Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($traineeList as $t): ?>
          <tr>
            <td>
              <?php
              $imagePath = $t['profile_image'];
              if (!empty($imagePath) && file_exists($imagePath)) {
                echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Photo" class="thumbnail">';
} else {
  echo '<span style="color:#999;">No image</span>';
}
?>
</td>
<td>
  <a href="view_trainee.php?id=<?= $t['trainee_id'] ?>">
    <?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?>
  </a>
</td>
<td><?= htmlspecialchars($t['email'] ?? '—') ?></td>
<td><?= htmlspecialchars($t['telephone'] ?? '—') ?></td>
<td><?= htmlspecialchars($t['start_date'] ?? '—') ?></td>
<td class="action-buttons">
  <?php if ($view === 'active'): ?>
    <a href="edit_trainee.php?trainee_id=<?= $t['trainee_id'] ?>" class="btn-sm btn-edit">
      <i class="fas fa-edit"></i> Edit
    </a>
    <form method="post" action="archived_trainee.php" onsubmit="return confirm('Archive this trainee?');">
      <input type="hidden" name="trainee_id" value="<?= $t['trainee_id'] ?>">
      <button type="submit" class="btn-sm btn-archive">
        <i class="fas fa-box-archive"></i> Archive
      </button>
    </form>
  <?php else: ?>
    <form method="post" action="restore_trainee.php" onsubmit="return confirm('Restore this trainee?');">
      <input type="hidden" name="trainee_id" value="<?= $t['trainee_id'] ?>">
      <button type="submit" class="btn-sm btn-restore">
        <i class="fas fa-rotate-left"></i> Restore
      </button>
    </form>
  <?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</body>
</html>