<?php
require_once 'config.php';
requireAuth();

$db = getDB();

$stats['total_rules']   = $db->query("SELECT COUNT(*) FROM firewall_rules WHERE is_active=1")->fetchColumn();
$stats['blocked_today'] = $db->query("SELECT COUNT(*) FROM traffic_logs WHERE action_taken='BLOCKED' AND DATE(timestamp)=CURDATE()")->fetchColumn();
$stats['allowed_today'] = $db->query("SELECT COUNT(*) FROM traffic_logs WHERE action_taken='ALLOWED' AND DATE(timestamp)=CURDATE()")->fetchColumn();
$stats['active_alerts'] = $db->query("SELECT COUNT(*) FROM alerts WHERE is_resolved=0")->fetchColumn();
$stats['blacklisted']   = $db->query("SELECT COUNT(*) FROM blocked_ips")->fetchColumn();

$recentLogs = $db->query("SELECT * FROM traffic_logs ORDER BY timestamp DESC LIMIT 10")->fetchAll();
$alerts     = $db->query("SELECT * FROM alerts WHERE is_resolved=0 ORDER BY created_at DESC LIMIT 5")->fetchAll();
$chartData  = $db->query("SELECT action_taken, COUNT(*) as count FROM traffic_logs GROUP BY action_taken")->fetchAll();

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
    <title>FireGuard - Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0d1117; font-family:'Segoe UI',sans-serif; color:#c9d1d9; }
        .sidebar { position:fixed;top:0;left:0;width:220px;height:100vh;background:#161b22;border-right:1px solid #30363d;padding:20px 0;display:flex;flex-direction:column; }
        .sidebar-logo { padding:0 20px 24px;border-bottom:1px solid #30363d;font-size:20px;font-weight:700;color:#58a6ff; }
        .nav-item { display:flex;align-items:center;gap:10px;padding:12px 20px;color:#8b949e;text-decoration:none;font-size:14px;transition:all 0.2s; }
        .nav-item:hover,.nav-item.active { color:#c9d1d9;background:#21262d; }
        .nav-item.active { border-left:3px solid #58a6ff; }
        .sidebar-footer { margin-top:auto;padding:16px 20px;border-top:1px solid #30363d;font-size:12px;color:#8b949e; }
        .sidebar-footer strong { color:#c9d1d9;display:block; }
        a.logout { color:#f85149;font-size:12px;text-decoration:none; }
        .main { margin-left:220px;padding:24px; }
        .page-title { font-size:22px;font-weight:700;color:#f0f6fc;margin-bottom:24px; }
        .stats-grid { display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:24px; }
        .stat-card { background:#161b22;border:1px solid #30363d;border-radius:10px;padding:18px;text-align:center; }
        .stat-card .value { font-size:32px;font-weight:700; }
        .stat-card .label { font-size:12px;color:#8b949e;margin-top:4px; }
        .stat-card.green .value  { color:#3fb950; }
        .stat-card.red .value    { color:#f85149; }
        .stat-card.blue .value   { color:#58a6ff; }
        .stat-card.orange .value { color:#f0883e; }
        .stat-card.purple .value { color:#bc8cff; }
        .grid-2 { display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px; }
        .panel { background:#161b22;border:1px solid #30363d;border-radius:10px;overflow:hidden; }
        .panel-header { padding:14px 18px;border-bottom:1px solid #30363d;font-weight:600;font-size:14px;display:flex;justify-content:space-between;align-items:center; }
        .panel-body { padding:16px 18px; }
        table { width:100%;border-collapse:collapse;font-size:13px; }
        th { color:#8b949e;font-weight:500;padding:8px 10px;text-align:left;border-bottom:1px solid #21262d; }
        td { padding:9px 10px;border-bottom:1px solid #21262d; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#1c2128; }
        .badge { display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600; }
        .badge-green  { background:#1a3a1a;color:#3fb950; }
        .badge-red    { background:#3d1212;color:#f85149; }
        .badge-orange { background:#3d2200;color:#f0883e; }
        .alert-item { padding:10px 14px;border-radius:8px;margin-bottom:8px;border-left:4px solid;font-size:13px; }
        .alert-item:last-child { margin-bottom:0; }
        .alert-meta { font-size:11px;color:#8b949e;margin-top:3px; }
    </style>
</head>
<body>
<nav class="sidebar">
    <div class="sidebar-logo">🔥 FireGuard</div>
    <a class="nav-item active" href="dashboard.php">📊 Dashboard</a>
    <a class="nav-item" href="rules.php">🛡️ Firewall Rules</a>
    <a class="nav-item" href="blacklist.php">🚫 IP Blacklist</a>
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
    <div class="page-title">Dashboard Overview</div>
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="value"><?= $stats['total_rules'] ?></div>
            <div class="label">Active Rules</div>
        </div>
        <div class="stat-card red">
            <div class="value"><?= $stats['blocked_today'] ?></div>
            <div class="label">Blocked Today</div>
        </div>
        <div class="stat-card green">
            <div class="value"><?= $stats['allowed_today'] ?></div>
            <div class="label">Allowed Today</div>
        </div>
        <div class="stat-card orange">
            <div class="value"><?= $stats['active_alerts'] ?></div>
            <div class="label">Active Alerts</div>
        </div>
        <div class="stat-card purple">
            <div class="value"><?= $stats['blacklisted'] ?></div>
            <div class="label">Blacklisted IPs</div>
        </div>
    </div>

    <div class="grid-2">
        <div class="panel">
            <div class="panel-header">📋 Recent Traffic Logs</div>
            <div class="panel-body" style="padding:0">
                <table>
                    <thead><tr>
                        <th>Source IP</th><th>Dest IP</th><th>Protocol</th>
                        <th>Port</th><th>Action</th><th>Time</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['source_ip']) ?></td>
                        <td><?= htmlspecialchars($log['dest_ip']) ?></td>
                        <td><?= htmlspecialchars($log['protocol']) ?></td>
                        <td><?= $log['dest_port'] ?></td>
                        <td>
                            <span class="badge <?= $log['action_taken']==='ALLOWED' ? 'badge-green' : 'badge-red' ?>">
                                <?= $log['action_taken'] ?>
                            </span>
                        </td>
                        <td><?= date('H:i:s', strtotime($log['timestamp'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="panel">
                <div class="panel-header">📈 Traffic Split</div>
                <div class="panel-body" style="text-align:center">
                    <canvas id="trafficChart" height="170"></canvas>
                </div>
            </div>
            <div class="panel">
                <div class="panel-header">
                    🔔 Active Alerts
                    <span class="badge badge-red"><?= $stats['active_alerts'] ?></span>
                </div>
                <div class="panel-body">
                    <?php foreach ($alerts as $alert): ?>
                    <div class="alert-item" style="background:<?= $severityColors[$alert['severity']] ?>18;border-color:<?= $severityColors[$alert['severity']] ?>">
                        <strong><?= str_replace('_',' ',$alert['alert_type']) ?></strong>
                        <span class="badge" style="background:<?= $severityColors[$alert['severity']] ?>22;color:<?= $severityColors[$alert['severity']] ?>;margin-left:6px">
                            <?= $alert['severity'] ?>
                        </span>
                        <div class="alert-meta">
                            <?= htmlspecialchars($alert['source_ip']) ?>
                            · <?= date('M d H:i', strtotime($alert['created_at'])) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($alerts)): ?>
                    <p style="color:#8b949e;font-size:13px;text-align:center">No active alerts ✅</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
const chartData = <?= json_encode($chartData) ?>;
const labels = chartData.map(d => d.action_taken);
const counts = chartData.map(d => parseInt(d.count));
const colors = chartData.map(d => d.action_taken === 'ALLOWED' ? '#3fb950' : '#f85149');

new Chart(document.getElementById('trafficChart'), {
    type: 'doughnut',
    data: { labels, datasets: [{ data: counts, backgroundColor: colors, borderWidth: 0 }] },
    options: {
        plugins: { legend: { labels: { color: '#c9d1d9', font: { size: 13 } } } },
        cutout: '65%'
    }
});
</script>
</body>
</html>