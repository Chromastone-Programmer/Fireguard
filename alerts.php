<?php
session_start();
require_once 'config.php';
requireAuth();
$db = getDB();

$alerts = $db->query("SELECT * FROM alerts ORDER BY created_at DESC")->fetchAll();

$severityColors = [
    'LOW'      => '#3fb950',
    'MEDIUM'   => '#d29922',
    'HIGH'     => '#f0883e',
    'CRITICAL' => '#f85149'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FireGuard - Alerts</title>
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
        .panel { background:#161b22;border:1px solid #30363d;border-radius:10px;overflow:hidden; }
        table { width:100%;border-collapse:collapse;font-size:13px; }
        th { color:#8b949e;font-weight:500;padding:10px 14px;text-align:left;border-bottom:1px solid #21262d; }
        td { padding:10px 14px;border-bottom:1px solid #21262d; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#1c2128; }
        .badge { display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600; }
        .badge-green { background:#1a3a1a;color:#3fb950; }
        .resolve-btn { padding:4px 10px;border-radius:5px;font-size:12px;border:1px solid #30363d;background:none;color:#8b949e;cursor:pointer; }
        .resolve-btn:hover { border-color:#3fb950;color:#3fb950; }
    </style>
</head>
<body>
<nav class="sidebar">
    <div class="sidebar-logo">🔥 FireGuard</div>
    <a class="nav-item" href="dashboard.php">📊 Dashboard</a>
    <a class="nav-item" href="rules.php">🛡️ Firewall Rules</a>
    <a class="nav-item" href="blacklist.php">🚫 IP Blacklist</a>
    <a class="nav-item active" href="alerts.php">🔔 Alerts</a>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <a class="nav-item" href="admin.php">⚙️ Admin Panel</a>
    <?php endif; ?>
    <div class="sidebar-footer">
        <strong><?= htmlspecialchars($_SESSION['user']) ?></strong>
        <?= ucfirst($_SESSION['role']) ?>
        · <a class="logout" href="logout.php">Logout</a>
    </div>
</nav>

<main class="main">
    <div class="page-title">🔔 Threat Alerts</div>
    <div class="panel">
        <table>
            <thead><tr>
                <th>Type</th><th>Source IP</th><th>Description</th>
                <th>Severity</th><th>Status</th><th>Time</th>
                <?php if ($_SESSION['role'] === 'admin'): ?><th>Action</th><?php endif; ?>
            </tr></thead>
            <tbody>
            <?php foreach ($alerts as $alert): ?>
            <tr id="alert-<?= $alert['id'] ?>">
                <td><strong><?= str_replace('_', ' ', $alert['alert_type']) ?></strong></td>
                <td><?= htmlspecialchars($alert['source_ip'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($alert['description']) ?></td>
                <td>
                    <span class="badge" style="background:<?= $severityColors[$alert['severity']] ?>22;color:<?= $severityColors[$alert['severity']] ?>">
                        <?= $alert['severity'] ?>
                    </span>
                </td>
                <td>
                    <span class="badge <?= $alert['is_resolved'] ? 'badge-green' : '' ?>"
                          style="<?= !$alert['is_resolved'] ? 'background:#3d1212;color:#f85149' : '' ?>">
                        <?= $alert['is_resolved'] ? 'RESOLVED' : 'ACTIVE' ?>
                    </span>
                </td>
                <td><?= date('M d H:i', strtotime($alert['created_at'])) ?></td>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <td>
                    <?php if (!$alert['is_resolved']): ?>
                    <button class="resolve-btn" onclick="resolveAlert(<?= $alert['id'] ?>)">Resolve</button>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($alerts)): ?>
            <tr><td colspan="7" style="text-align:center;color:#8b949e;padding:24px">No alerts ✅</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
function resolveAlert(id) {
    if (!confirm('Mark this alert as resolved?')) return;
    fetch(`api/alerts.php?action=resolve&id=${id}`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => { if (data.success) location.reload(); });
}
</script>
</body>
</html>
