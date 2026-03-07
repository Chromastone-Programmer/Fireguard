<?php
// ============================================
// WHY: Single entry point
// User visits http://localhost/fireguard/
// Instead of showing a blank page
// we decide where to send them
// ============================================
session_start();

// If logged in → go to dashboard
// If not → go to login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
header("Location: dashboard.php");
exit;
?>