<?php
// Secure session configuration
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// Regenerate session ID once per session
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Optional: session timeout (30 minutes)
$timeout = 1800;
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $timeout) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

// Security headers
header("X-Frame-Options: SAMEORIGIN");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://code.jquery.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; object-src 'none'; frame-ancestors 'none';");

require 'db.php';

$role = strtolower($_SESSION['role'] ?? '');
$name = htmlspecialchars($_SESSION['name'] ?? 'User');

$valid_roles = ['superuser', 'admin', 'staff', 'tutor', 'trainee'];
if (!in_array($role, $valid_roles)) {
    die("Access denied");
}

// DBS Expiry Monitor
$expiringTutors = [];
$expiringTrainees = [];

if (in_array($role, ['superuser', 'admin'])) {
    $stmt = $pdo->query("
        SELECT tutor_id, first_name, surname, dbs_expiry_date
        FROM tutors
        WHERE dbs_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    ");
    $expiringTutors = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT trainee_id, first_name, surname, dbs_expiry_date
        FROM trainees
        WHERE dbs_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    ");
    $expiringTrainees = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= ucfirst($role) ?> Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #E6D6EC;
      color: #000;
    }
    .dashboard-wrapper {
      display: flex;
      flex-direction: row;
    }
    .main-content {
      flex: 1;
      padding: 40px;
    }
    .card {
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      margin-bottom: 30px;
      width: 100%;
    }
    .card h3 {
      margin-top: 0;
      color: #850069;
      font-family: 'Josefin Sans', sans-serif;
    }
    .guide-section h4 {
      margin-top: 20px;
      color: #850069;
      font-family: 'Josefin Sans', sans-serif;
      font-size: 18px;
    }
    .guide-section ul {
      margin-top: 10px;
      padding-left: 20px;
    }
    .guide-section ul li {
      margin-bottom: 8px;
      font-size: 15px;
      color: #333;
    }
    .guide-section code {
      background: #f0e6f5;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 14px;
    }
    .report-link {
      display: inline-block;
      margin-top: 10px;
      padding: 10px 16px;
      background-color: #850069;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      font-weight: bold;
      font-family: 'Josefin Sans', sans-serif;
    }
    .report-link:hover {
      background-color: #BB9DC6;
    }
    .alert-box {
      background-color: #fff3cd;
      border-left: 6px solid #ffc107;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 30px;
    }
    .alert-box h3 {
      margin-top: 0;
      color: #856404;
    }
    .alert-box ul {
      margin: 10px 0 0 20px;
      padding: 0;
    }
    .alert-box li {
      margin-bottom: 6px;
      font-size: 15px;
    }
  </style>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Welcome, <?= $name ?></h2>

    <div class="card">
      <h3>System Overview</h3>
      <p>You are logged in as <strong><?= $role ?></strong>. Use the sidebar to access your tools and responsibilities.</p>
      <?php if (in_array($role, ['admin', 'staff', 'superuser'])): ?>
        <a href="generate_reports.php" class="report-link">Generate Reports</a>
      <?php endif; ?>
    </div>

    <?php if (!empty($expiringTutors) || !empty($expiringTrainees)): ?>
    <div class="card alert-box">
      <h3>DBS Expiry Monitor</h3>
      <?php if (!empty($expiringTutors)): ?>
        <p><strong>Tutors with expiring DBS:</strong></p>
        <ul>
          <?php foreach ($expiringTutors as $t): ?>
            <li><?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?> – <?= htmlspecialchars($t['dbs_expiry_date']) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <?php if (!empty($expiringTrainees)): ?>
        <p><strong>Trainees with expiring DBS:</strong></p>
        <ul>
          <?php foreach ($expiringTrainees as $t): ?>
            <li><?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?> – <?= htmlspecialchars($t['dbs_expiry_date']) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($role === 'superuser'): ?>
    <div class="card guide-section">
      <h3>Superuser Dashboard</h3>

      <h4>🧭 Governance & Oversight</h4>
      <ul>
        <li>Manage admin accounts and permissions via <strong>Manage Admins</strong>.</li>
        <li>Review system-wide audit logs, login activity, and failed access attempts.</li>
        <li>Oversee safeguarding dashboards and escalation workflows.</li>
        <li>Access full-spectrum operational telemetry across supervision, grading, and security.</li>
      </ul>

      <h4>📊 Strategic Reporting</h4>
      <ul>
        <li>Generate board-level reports and export data for compliance review.</li>
        <li>Monitor role distribution, system usage, and grading outcomes.</li>
        <li>Track DBS expiry across tutors and trainees.</li>
      </ul>

      <h4>🔐 Security & Access Control</h4>
      <ul>
        <li>Review login activity feeds and failed access logs for governance-grade oversight.</li>
        <li>Manage role-based access across all modules and user types.</li>
        <li>Ensure session integrity and enforce secure cookie/session policies.</li>
        <li>Validate grading integrity—locked submissions, override traceability, and admin-only regrading.</li>
      </ul>

      <h4>🧱 Schema & Infrastructure</h4>
      <ul>
        <li>Normalize collation across multilingual tables for schema-safe operations.</li>
        <li>Resolve foreign key constraints and validate referential integrity.</li>
        <li>Deploy transaction-safe migration scripts with rollback options.</li>
        <li>Embed audit columns and traceability into assignment and supervision workflows.</li>
      </ul>

      <h4>🛠 Operational Tools</h4>
      <ul>
        <li>Access grading dashboards with score, pass/fail, feedback, and attribution.</li>
        <li>Surface override attempts and lock grading actions post-submission.</li>
        <li>Prototype and deploy live security modules for login telemetry and role audit summaries.</li>
                <li>Refactor assignment metadata for compliance-ready reporting.</li>
        <li>Restore assignment and group assignment functionality with governance-grade clarity.</li>
        <li>Embed grading workflows with score, pass/fail, feedback, and grader attribution.</li>
        <li>Surface submission notes, override logs, and audit trails for board-level review.</li>
      </ul>
    </div>
<?php endif; ?>