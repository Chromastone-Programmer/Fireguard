<?php
// ============================================
// WHY: session_start() must be called
// before any session variables are used
// Must also be before any HTML output
// ============================================
session_start();

// Already logged in? No need to see login page
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

// ============================================
// WHY: Same file handles GET and POST
// GET  → just show the form
// POST → process the form submission
// Node.js equivalent:
// app.get('/login') and app.post('/login')
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php';
    
    // WHY: trim() removes accidental spaces
    // from username input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = getDB();
        
        // ============================================
        // WHY: Prepared statement - never put
        // $username directly in the query string
        // That would allow SQL injection like:
        // username: admin'-- (bypasses password check)
        // ============================================
        $stmt = $db->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // ============================================
        // WHY: password_verify() compares submitted
        // password against bcrypt hash in database
        // We never store plain text passwords
        // Node.js equivalent: bcrypt.compare()
        // ============================================
        if ($user && password_verify($password, $user['password_hash'])) {
            // Save user info in session
            // Now every page knows who is logged in
            $_SESSION['user']    = $user['username'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FireGuard - Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0d1117;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-box {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 12px;
            padding: 40px;
            width: 380px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo span { font-size: 28px; font-weight: 700; color: #58a6ff; }
        .logo small { display: block; color: #8b949e; font-size: 13px; margin-top: 4px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; color: #c9d1d9; font-size: 13px; margin-bottom: 6px; }
        input {
            width: 100%;
            padding: 10px 14px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            color: #c9d1d9;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        input:focus { outline: none; border-color: #58a6ff; }
        .btn {
            width: 100%;
            padding: 11px;
            background: #238636;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 6px;
        }
        .btn:hover { background: #2ea043; }
        .error {
            background: #3d1f1f;
            border: 1px solid #f85149;
            border-radius: 6px;
            padding: 10px 14px;
            color: #f85149;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .hint { color: #8b949e; font-size: 12px; text-align: center; margin-top: 18px; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">
            <span>🔥 FireGuard</span>
            <small>Network Firewall Dashboard</small>
        </div>

        <!--
            WHY: Only show error box if there is an error
            PHP echoes dynamic content inside HTML
            Same concept as EJS: <%= error %>
        -->
        <?php if ($error): ?>
        <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!--
            WHY: method="POST" sends form data
            securely in request body
            action is empty = submits to same file
        -->
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="admin" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn">Sign In</button>
        </form>
        <p class="hint">Demo: admin / password</p>
    </div>
</body>
</html>