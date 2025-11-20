<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff', 'tutor', 'trainee'])) {
  die("Access denied.");
}

$stmt = $pdo->prepare("SELECT trainee_id FROM trainees WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$trainee = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM trainee_portfolio WHERE trainee_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$trainee['trainee_id']]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Portfolio</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="page-container">
      <div class="page-header">
        <h2>My Portfolio</h2>
        <a href="upload_portfolio.php" class="btn">+ Upload New Item</a>
      </div>

      <?php if (isset($_GET['success'])): ?>
        <p style="color:green;">✅ Evidence uploaded successfully!</p>
      <?php endif; ?>

      <?php if (empty($items)): ?>
        <p>No portfolio items uploaded yet.</p>
      <?php else: ?>
        <table class="calendar-grid">
          <thead>
            <tr>
              <th>Title</th>
              <th>Category</th>
              <th>Skill</th>
              <th>Assignment</th>
              <th>File</th>
              <th>Description</th>
              <th>Uploaded</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?= htmlspecialchars($item['title']) ?></td>
                <td><?= htmlspecialchars($item['category']) ?></td>
                <td><?= htmlspecialchars($item['linked_skill']) ?></td>
                <td>
                  <?php
                  if ($item['linked_assignment_id']) {
                    $stmt = $pdo->prepare("SELECT title FROM assignments WHERE assignment_id = ?");
                    $stmt->execute([$item['linked_assignment_id']]);
                    $assignment = $stmt->fetch();
                    echo htmlspecialchars($assignment['title']);
                  }
                  ?>
                </td>
                <td>
                  <?php if ($item['file_path']): ?>
                    <a href="<?= htmlspecialchars($item['file_path']) ?>" target="_blank">Download</a>
                  <?php endif; ?>
                </td>
                <td><?= nl2br(htmlspecialchars($item['description'])) ?></td>
                <td><?= htmlspecialchars($item['uploaded_at']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>