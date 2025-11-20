<?php
session_start();
require 'db.php';
require_once 'functions.php'; // Includes logAction function

// ✅ Determine if this is a quick exit
$isQuickExit = isset($_GET['quick_exit']) && $_GET['quick_exit'] == '1';
$redirectUrl = $isQuickExit ? 'https://www.bbc.co.uk/news' : 'login.php';
$actionNote = $isQuickExit ? 'Quick exit triggered' : 'User logged out';

// ✅ Log the logout or quick exit action
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    logAction($pdo, $_SESSION['user_id'], $_SESSION['role'], 'logout', $actionNote);
}

// ✅ Unset all session variables
$_SESSION = [];
session_unset();
session_destroy();

// ✅ Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ✅ Prevent browser caching of protected pages
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

// ✅ Redirect based on intent
header("Location: $redirectUrl");
exit;