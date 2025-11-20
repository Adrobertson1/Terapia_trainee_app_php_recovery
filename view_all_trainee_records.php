<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

// Fetch all trainees with supervisor name
$stmt = $pdo->prepare("
  SELECT t.*, 
         CONCAT_WS(' ', s.first_name, s.surname_prefix, s.surname) AS supervisor_name
  FROM trainees t
  LEFT JOIN supervisors s ON t.supervisor_id = s.supervisor_id
  ORDER BY t.surname
");
$stmt->execute();
$trainees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>All Trainee Records</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; max-width: 1400px; margin: auto; }
    .trainee-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .trainee-table th, .trainee-table td {
      padding: 10px;
      border-bottom: 1px solid #ccc;
      vertical-align: top;
    }
    .trainee-table th {
      background-color: #6a1b9a;
      color: white;
    }
    .btn {
      display: inline-block;
      padding: 8px 16px;
      background-color: #6a1b9a;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      font-weight: bold;
      margin-bottom: 20px;
    }
    .btn:hover {
      background-color: #4a148c;
    }
    .profile-img {
      max-width: 80px;
      max-height: 80px;
      border-radius: 4px;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>All Trainee Records</h2>

    <?php if (count($trainees) === 0): ?>
      <p class="no-results">No trainees found.</p>
    <?php else: ?>
      <table class="trainee-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Telephone</th>
            <th>Start Date</th>
            <th>Archived</th>
            <th>DOB</th>
            <th>Disability</th>
            <th>Location</th>
            <th>Profile</th>
            <th>Code</th>
            <th>Address</th>
            <th>Supervisor</th>
            <th>Individual Supervisor</th>
            <th>View</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($trainees as $t): ?>
            <tr>
              <td><?= htmlspecialchars($t['trainee_id']) ?></td>
              <td><?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?></td>
              <td><?= htmlspecialchars($t['email']) ?></td>
              <td><?= htmlspecialchars($t['telephone']) ?></td>
              <td><?= htmlspecialchars($t['start_date']) ?></td>
              <td><?= $t['is_archived'] ? 'Yes' : 'No' ?></td>
              <td><?= htmlspecialchars($t['date_of_birth']) ?></td>
              <td>
                <?= htmlspecialchars($t['disability_status']) ?><br>
                <?= htmlspecialchars($t['disability_type']) ?>
              </td>
              <td>
                <?= htmlspecialchars($t['town_city']) ?><br>
                <?= htmlspecialchars($t['postcode']) ?>
              </td>
              <td>
                <?php if (!empty($t['profile_image'])): ?>
                  <img src="<?= htmlspecialchars($t['profile_image']) ?>" class="profile-img" alt="Profile">
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($t['trainee_code']) ?></td>
              <td><?= htmlspecialchars($t['address_line1']) ?></td>
              <td><?= htmlspecialchars($t['supervisor_name'] ?? 'Unassigned') ?></td>
              <td><?= htmlspecialchars($t['individual_supervisor']) ?></td>
              <td>
                <a href="view_trainee.php?id=<?= $t['trainee_id'] ?>" class="btn" target="_blank">View</a>
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