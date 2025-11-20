<?php
session_start();
require 'db.php';

if ($_SESSION['role'] !== 'superuser') {
  die("Access denied.");
}

$view = $_GET['view'] ?? 'active';

$stmtActive = $pdo->prepare("SELECT * FROM staff WHERE role = 'admin' AND is_archived = 0 ORDER BY surname");
$stmtActive->execute();
$activeAdmins = $stmtActive->fetchAll();

$stmtArchived = $pdo->prepare("SELECT * FROM staff WHERE role = 'admin' AND is_archived = 1 ORDER BY surname");
$stmtArchived->execute();
$archivedAdmins = $stmtArchived->fetchAll();

$adminList = $view === 'archived' ? $archivedAdmins : $activeAdmins;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Admins</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .message-success {
      background-color: #e0f7e9;
      color: #2e7d32;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
      font-weight: bold;
    }
    .message-warning {
      background-color: #fff3cd;
      color: #856404;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
      font-weight: bold;
    }
    .top-actions {
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .tab-buttons {
      display: flex;
      gap: 10px;
    }
    .btn-sm {
      padding: 8px 14px;
      border-radius: 4px;
      text-decoration: none;
      font-size: 14px;
      font-weight: bold;
      display: inline-block;
      border: none;
      cursor: pointer;
    }
    .btn-active { background-color: #4CAF50; color: white; }
    .btn-archived { background-color: #d32f2f; color: white; }
    .btn-default { background-color: #ccc; color: #333; }
    .btn-edit { background-color: #6a1b9a; color: white; }
    .btn-archive { background-color: #d32f2f; color: white; }
    .btn-restore { background-color: #4CAF50; color: white; }
    table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }
    th, td {
      padding: 12px;
      border-bottom: 1px solid #ccc;
      text-align: left;
      vertical-align: top;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }
    th {
      background-color: #6a1b9a;
      color: white;
    }
    .thumbnail {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 1px solid #ccc;
    }
    .action-buttons {
      display: flex;
      flex-direction: column;
      gap: 6px;
      align-items: flex-start;
      justify-content: center;
    }
    .action-buttons a,
    .action-buttons form {
      display: inline-block;
      margin: 0;
    }
    .action-buttons button,
    .action-buttons .btn-sm {
      margin: 0;
      vertical-align: middle;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Registered Admins</h2>

    <?php if (isset($_GET['restored']) && $_GET['restored'] == '1'): ?>
      <div class="message-success">✅ Admin successfully restored.</div>
    <?php endif; ?>
    <?php if (isset($_GET['archived']) && $_GET['archived'] == '1'): ?>
      <div class="message-warning">🗂️ Admin successfully archived.</div>
    <?php endif; ?>

    <div class="top-actions">
      <div class="tab-buttons">
        <a href="?view=active" class="btn-sm <?= $view === 'active' ? 'btn-active' : 'btn-default' ?>">Active Admins</a>
        <a href="?view=archived" class="btn-sm <?= $view === 'archived' ? 'btn-archived' : 'btn-default' ?>">Archived Admins</a>
      </div>
      <a href="add_staff.php?role=admin" class="btn-sm btn-active">
        <i class="fas fa-user-plus"></i> Add New Admin
      </a>
    </div>

    <table>
      <thead>
        <tr>
          <th>Photo</th>
          <th>Name</th>
          <th>Email</th>
          <th>Job Title</th>
          <th>Start Date</th>
          <th>DBS Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($adminList as $a): ?>
          <tr>
            <td>
              <?php
                $imagePath = $a['profile_image'];
                if (!empty($imagePath) && file_exists($imagePath)) {
                  echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Photo" class="thumbnail">';
                } else {
                  echo '<span style="color:#999;">No image</span>';
                }
              ?>
            </td>
            <td>
              <a href="view_staff.php?id=<?= $a['staff_id'] ?>">
                <?= htmlspecialchars($a['first_name'] . ' ' . $a['surname']) ?>
              </a>
            </td>
            <td><?= htmlspecialchars($a['email'] ?? '-') ?></td>
            <td><?= htmlspecialchars($a['job_title'] ?? '-') ?></td>
            <td><?= htmlspecialchars($a['start_date'] ?? '-') ?></td>
            <td><?= htmlspecialchars($a['dbs_status'] ?? '-') ?></td>
            <td class="action-buttons">
              <?php if ($view === 'active'): ?>
                <a href="edit_staff.php?staff_id=<?= $a['staff_id'] ?>" class="btn-sm btn-edit">
                  <i class="fas fa-edit"></i> Edit
                </a>
                <form method="post" action="archived_staff.php" onsubmit="return confirm('Archive this admin?');">
                  <input type="hidden" name="staff_id" value="<?= $a['staff_id'] ?>">
                  <button type="submit" class="btn-sm btn-archive">
                    <i class="fas fa-box-archive"></i> Archive
                  </button>
                </form>
              <?php else: ?>
                <form method="post" action="restore_staff.php" onsubmit="return confirm('Restore this admin?');">
                  <input type="hidden" name="staff_id" value="<?= $a['staff_id'] ?>">
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