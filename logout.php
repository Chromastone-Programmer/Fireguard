<?php
// ============================================
// WHY: Three steps to properly log out
// 1. Start session so we can access it
// 2. Destroy all session data
// 3. Redirect to login
// Node.js equivalent: req.session.destroy()
// ============================================
session_start();    // Step 1 - access session
session_destroy();  // Step 2 - wipe everything
header("Location: login.php"); // Step 3 - redirect
exit;
?>