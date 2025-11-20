<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Set timezone to UK (handles BST/GMT automatically)
date_default_timezone_set('Europe/London');
$now = new DateTime('now', new DateTimeZone('Europe/London'));
$currentTime = $now->format('l, j F Y, H:i');

$dbVersion = '0.1';
?>
<div class="header-bar">
  <div class="header-left">
    <div class="logo-box">
      <img src="assets/logo.png" alt="Logo" style="height:40px; vertical-align:middle;">
    </div>
    <span class="header-title">Terapia – Trainee Database</span>
  </div>
  <div class="header-right">
    <div class="header-info">
      <span class="welcome-text">
        Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>
        <span class="role-badge"><?= ucfirst($_SESSION['role'] ?? 'Guest') ?></span>
      </span><br>
      <span class="meta-text" title="Europe/London timezone">
        🕒 <strong>Local Time:</strong> <?= htmlspecialchars($currentTime) ?>
      </span><br>
      <span class="meta-text"><strong>DB Version:</strong> <?= htmlspecialchars($dbVersion) ?></span>
    </div>
    <div class="header-actions">
      <div class="quick-exit-wrapper">
        <a href="logout.php?quick_exit=1" class="quick-exit-link">Quick Exit</a>
      </div>
      <div class="logout-wrapper">
        <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
  </div>
</div>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600&display=swap');

  .header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(to right, #6a1b9a, #7b1fa2);
    color: white;
    padding: 10px 20px;
    border-bottom: 1px solid #F3EAF5;
  }
  .header-left {
    display: flex;
    align-items: center;
  }
  .logo-box {
    background-color: #F3EAF5;
    padding: 6px;
    border-radius: 6px;
  }
  .header-title {
    font-family: 'Josefin Sans', sans-serif;
    font-size: 1.8em;
    font-weight: 600;
    margin-left: 10px;
    letter-spacing: 0.5px;
    color: #F3EAF5;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
  }
  .header-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    text-align: right;
    flex-grow: 1;
  }
  .header-info {
    font-size: 13px;
    line-height: 1.4;
    margin-bottom: 6px;
  }
  .welcome-text {
    font-weight: 500;
    font-size: 14px;
  }
  .role-badge {
    background-color: #F3EAF5;
    color: #6a1b9a;
    font-size: 12px;
    padding: 2px 6px;
    border-radius: 12px;
    margin-left: 6px;
    font-weight: 600;
  }
  .meta-text {
    font-size: 12px;
    color: #e0e0e0;
  }
  .header-actions {
    display: flex;
    justify-content: space-between;
    width: 100%;
    max-width: 300px;
  }
  .logout-link,
  .quick-exit-link {
    color: white;
    text-decoration: none;
    font-weight: bold;
    font-size: 16px;
    padding: 6px 12px;
    border-radius: 4px;
    display: inline-block;
  }
  .logout-link {
    background-color: #4a148c;
  }
  .logout-link:hover {
    background-color: #7b1fa2;
    text-decoration: none;
  }
  .quick-exit-link {
    background-color: #d32f2f;
  }
  .quick-exit-link:hover {
    background-color: #b71c1c;
    text-decoration: none;
  }
</style>