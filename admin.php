<?php
session_start();
require_once 'config.php';
requireAdmin(); // Admin only page
$db = getDB();

$users = $db->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
$totalRules  = $db->query("SELECT COUNT(*) FROM firewall_rules")->fetchColumn();
$totalLogs   = $db->query("SELECT COUNT(*) FROM traffic_logs")->fetchColumn();
$totalAlerts = $db->query("SELECT COUNT(*) FROM alerts")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FireGuard - Admin Panel</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0d1117; font-family:'Segoe UI',sans-serif; color:#c9d1d9; }
        .sidebar { position:fixed;top:0;left:0;width:220px;height:100vh;background:#161b22;border-right:1px solid #30363d;padding:20px 0;display:flex;flex-direction:column; }
        .sidebar-logo { padding:0 20px 24px;border-bottom:1px solid #30363d;font-size:20px;font-weight:700;color:#58a6ff; }
        .nav-item { display:flex;align-items:center;gap:10px;padding:12px 20px;color:#8b949e;text-decoration:none;font-size:14px; }
        .nav-item:hover,.nav-item.active { color:#c9d1d9;background:#21262d; }
        .nav-item.active { border-left:3px solid #58a6ff; }
        .sidebar-footer { margin-top:auto;padding:16px 20px;border-top:1px solid #30363d;font-size:12px;color:#8b949e; }
        .sidebar-footer strong { color:#c9d1d9;display:block; }
        a.logout { color:#f85149;font-size:12px;text-decoration:none; }
        .main { margin-left:220px;padding:24px; }
        .page-title { font-size:22px;font-weight:700;color:#f0f6fc;margin-bottom:24px; }
        .stats-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px; }
        .stat-card { background:#161b22;border:1px solid #30363d;border-radius:10px;padding:18px;text-align:center; }
        .stat-card .value { font-size:32px;font-weight:700;color:#58a6ff; }
        .stat-card .label { font-size:12px;color:#8b949e;margin-top:4px; }
        .panel { background:#161b22;border:1px solid #30363d;border-radius:10px;overflow:hidden;margin-bottom:24px; }
        .panel-header { padding:14px 18px;border-bottom:1px solid #30363d;font-weight:600;font-size:14px; }
        table { width:100%;border-collapse:collapse;font-size:13px; }
        th { color:#8b949e;font-weight:500;padding:10px 14px;text-align:left;border-bottom:1px solid #21262d; }
        td { padding:10px 14px;border-bottom:1px solid #21262d; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#1c2128; }
        .badge { display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600; }
        .badge-blue  { background:#0d2137;color:#58a6ff; }
        .badge-green { background:#1a3a1a;color:#3fb950; }
        .warning-box { background:#3d2200;border:1px solid #f0883e;border-radius:8px;padding:14px 18px;margin-bottom:24px;font-size:13px;color:#f0883e; }
    </style>
</head>
<body>
<nav class="sidebar">
    <div class="sidebar-logo">🔥 FireGuard</div>
    <a class="nav-item" href="dashboard.php">📊 Dashboard</a>
    <a class="nav-item" href="rules.php">🛡️ Firewall Rules</a>
    <a class="nav-item" href="blacklist.php">🚫 IP Blacklist</a>
    <a class="nav-item" href="alerts.php">🔔 Alerts</a>
    <a class="nav-item active" href="admin.php">⚙️ Admin Panel</a>
    <div class="sidebar-footer">
        <strong><?= htmlspecialchars($_SESSION['user']) ?></strong>
        <?= ucfirst($_SESSION['role']) ?>
        · <a class="logout" href="logout.php">Logout</a>
    </div>
</nav>

<main class="main">
    <div class="page-title">⚙️ Admin Panel</div>

    <div class="warning-box">
        ⚠️ This panel is restricted to administrators only.
        All actions are logged.
    </div>

    <!-- System Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="value"><?= $totalRules ?></div>
            <div class="label">Total Rules</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= $totalLogs ?></div>
            <div class="label">Total Log Entries</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= $totalAlerts ?></div>
            <div class="label">Total Alerts</div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="panel">
        <div class="panel-header">👥 System Users</div>
        <table>
            <thead><tr>
                <th>#</th><th>Username</th><th>Role</th><th>Created</th>
            </tr></thead>
            <tbody>
            <?php foreach ($users as $i => $user): ?>
            <tr>
                <td style="color:#8b949e"><?= $i+1 ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td>
                    <span class="badge <?= $user['role'] === 'admin' ? 'badge-blue' : 'badge-green' ?>">
                        <?= ucfirst($user['role']) ?>
                    </span>
                </td>
                <td><?= date('M d Y H:i', strtotime($user['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
