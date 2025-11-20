<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$stmtActive = $pdo->prepare("SELECT * FROM tutors WHERE is_archived = 0 ORDER BY surname");
$stmtActive->execute();
$activeTutors = $stmtActive->fetchAll();

$stmtArchived = $pdo->prepare("SELECT * FROM tutors WHERE is_archived = 1 ORDER BY surname");
$stmtArchived->execute();
$archivedTutors = $stmtArchived->fetchAll();

$view = $_GET['view'] ?? 'active';
$tutorList = $view === 'archived' ? $archivedTutors : $activeTutors;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Tutors</title>
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
    <h2>Registered Tutors</h2>

    <?php if (isset($_GET['restored']) && $_GET['restored'] == '1'): ?>
      <div class="message-success">✅ Tutor successfully restored.</div>
    <?php endif; ?>
    <?php if (isset($_GET['archived']) && $_GET['archived'] == '1'): ?>
      <div class="message-warning">🗂️ Tutor successfully archived.</div>
    <?php endif; ?>

    <div class="top-actions">
      <div class="tab-buttons">
        <a href="?view=active" class="btn-sm <?= $view === 'active' ? 'btn-active' : 'btn-default' ?>">Active Tutors</a>
        <a href="?view=archived" class="btn-sm <?= $view === 'archived' ? 'btn-archived' : 'btn-default' ?>">Archived Tutors</a>
      </div>
      <a href="add_tutor.php" class="btn-sm btn-active">
        <i class="fas fa-user-plus"></i> Add New Tutor
      </a>
    </div>

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
        <?php foreach ($tutorList as $t): ?>
          <tr>
            <td>
              <?php if (!empty($t['profile_image'])): ?>
                <img src="uploads/<?= htmlspecialchars($t['profile_image']) ?>" alt="Photo" class="thumbnail">
              <?php else: ?>
                <span style="color:#999;">No image</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="view_tutor.php?id=<?= $t['tutor_id'] ?>">
                <?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?>
              </a>
            </td>
            <td><?= htmlspecialchars($t['email'] ?? '-') ?></td>
            <td><?= htmlspecialchars($t['telephone'] ?? '-') ?></td>
            <td><?= htmlspecialchars($t['start_date'] ?? '-') ?></td>
            <td class="action-buttons">
              <?php if ($view === 'active'): ?>
                <a href="edit_tutor.php?tutor_id=<?= $t['tutor_id'] ?>" class="btn-sm btn-edit">
                  <i class="fas fa-edit"></i> Edit
                </a>
                <form method="post" action="archived_tutor.php" onsubmit="return confirm('Archive this tutor?');">
                  <input type="hidden" name="tutor_id" value="<?= $t['tutor_id'] ?>">
                  <button type="submit" class="btn-sm btn-archive">
                    <i class="fas fa-box-archive"></i> Archive
                  </button>
                </form>
              <?php else: ?>
                <form method="post" action="restore_tutor.php" onsubmit="return confirm('Restore this tutor?');">
                  <input type="hidden" name="tutor_id" value="<?= $t['tutor_id'] ?>">
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