<?php
// ============================================
// WHY: Every page needs database access
// Putting connection here means we change
// credentials in ONE place only
// ============================================
define('DB_HOST', 'localhost');  // Where MySQL is running
define('DB_NAME', 'firewall_db'); // Which database to use
define('DB_USER', 'root');        // MySQL username
define('DB_PASS', 'mysqlpassword'); // ← CHANGE THIS

// ============================================
// WHY: static $pdo = null means we create
// the connection ONCE and reuse it everywhere
// This is called a Singleton pattern
// Node.js equivalent: const pool = mysql.createPool()
// ============================================
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // DSN = Data Source Name
            // Tells PDO: use mysql, connect here, use this database
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                // WHY: Throw exceptions on errors instead of silent fails
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // WHY: Return results as associative arrays like JS objects
                // e.g. $user['username'] instead of $user[0]
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // WHY: Use real prepared statements, not simulated ones
                // Real ones are safer against SQL injection
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// ============================================
// WHY: Every protected page calls this
// Same concept as Express auth middleware:
// if(!req.session.user) res.redirect('/login')
// ============================================
function requireAuth() {
    session_start();
    if (!isset($_SESSION['user'])) {
        if (isAjax()) {
            http_response_code(401);
            die(json_encode(['error' => 'Unauthorized']));
        }
        header("Location: /fireguard/login.php");
        exit; // WHY: Always exit after redirect
              // Otherwise PHP keeps executing below
    }
}

// ============================================
// WHY: Some actions are admin only
// e.g. adding rules, blocking IPs
// Least privilege principle - viewers can
// only watch, not change anything
// ============================================
function requireAdmin() {
    requireAuth(); // First check logged in
    if ($_SESSION['role'] !== 'admin') {
        if (isAjax()) {
            http_response_code(403);
            die(json_encode(['error' => 'Forbidden']));
        }
        header("Location: /fireguard/dashboard.php?error=forbidden");
        exit;
    }
}

// ============================================
// WHY: API endpoints behave differently
// from regular pages when not authenticated
// Pages redirect → AJAX returns JSON error
// ============================================
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// ============================================
// WHY: Standardizes all API responses
// Same concept as Express:
// res.status(200).json({data})
// ============================================
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>