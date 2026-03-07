<?php
session_start();
require_once 'config.php';
requireAuth();
$db = getDB();

$blockedIPs = $db->query("
    SELECT b.*, u.username as blocked_by_name
    FROM blocked_ips b
    LEFT JOIN users u ON b.blocked_by = u.id
    ORDER BY b.blocked_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FireGuard - Blacklist</title>
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
        .page-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:24px; }
        .page-title { font-size:22px;font-weight:700;color:#f0f6fc; }
        .btn-primary { padding:9px 18px;background:#238636;color:white;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer; }
        .btn-primary:hover { background:#2ea043; }
        .panel { background:#161b22;border:1px solid #30363d;border-radius:10px;overflow:hidden; }
        table { width:100%;border-collapse:collapse;font-size:13px; }
        th { color:#8b949e;font-weight:500;padding:10px 14px;text-align:left;border-bottom:1px solid #21262d; }
        td { padding:10px 14px;border-bottom:1px solid #21262d; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#1c2128; }
        .badge { display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600; }
        .badge-red { background:#3d1212;color:#f85149; }
        .action-btn { padding:4px 10px;border-radius:5px;font-size:12px;border:1px solid #30363d;background:none;color:#8b949e;cursor:pointer; }
        .action-btn.del:hover { border-color:#f85149;color:#f85149; }
        .modal-overlay { display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:100;align-items:center;justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal { background:#161b22;border:1px solid #30363d;border-radius:12px;padding:28px;width:440px; }
        .modal h3 { margin-bottom:20px;font-size:16px; }
        .form-group { margin-bottom:14px; }
        label { display:block;color:#8b949e;font-size:12px;margin-bottom:5px; }
        input { width:100%;padding:8px 12px;background:#0d1117;border:1px solid #30363d;border-radius:6px;color:#c9d1d9;font-size:13px; }
        input:focus { outline:none;border-color:#58a6ff; }
        .modal-footer { display:flex;gap:10px;justify-content:flex-end;margin-top:20px; }
        .btn-cancel { padding:8px 16px;background:none;border:1px solid #30363d;color:#8b949e;border-radius:6px;cursor:pointer;font-size:13px; }
    </style>
</head>
<body>
<nav class="sidebar">
    <div class="sidebar-logo">🔥 FireGuard</div>
    <a class="nav-item" href="dashboard.php">📊 Dashboard</a>
    <a class="nav-item" href="rules.php">🛡️ Firewall Rules</a>
    <a class="nav-item active" href="blacklist.php">🚫 IP Blacklist</a>
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
    <div class="page-header">
        <div class="page-title">🚫 IP Blacklist</div>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <button class="btn-primary" onclick="openModal()">+ Block IP</button>
        <?php endif; ?>
    </div>
    <div class="panel">
        <table>
            <thead><tr>
                <th>IP Address</th><th>Reason</th><th>Blocked By</th><th>Blocked At</th>
                <?php if ($_SESSION['role'] === 'admin'): ?><th>Action</th><?php endif; ?>
            </tr></thead>
            <tbody>
            <?php foreach ($blockedIPs as $ip): ?>
            <tr id="ip-<?= $ip['id'] ?>">
                <td><span class="badge badge-red"><?= htmlspecialchars($ip['ip_address']) ?></span></td>
                <td><?= htmlspecialchars($ip['reason'] ?? 'No reason given') ?></td>
                <td><?= htmlspecialchars($ip['blocked_by_name'] ?? 'System') ?></td>
                <td><?= date('M d Y H:i', strtotime($ip['blocked_at'])) ?></td>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <td><button class="action-btn del" onclick="unblockIP(<?= $ip['id'] ?>)">Unblock</button></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($blockedIPs)): ?>
            <tr><td colspan="5" style="text-align:center;color:#8b949e;padding:24px">No blocked IPs ✅</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<div class="modal-overlay" id="addModal">
    <div class="modal">
        <h3>🚫 Block IP Address</h3>
        <div class="form-group">
            <label>IP Address</label>
            <input type="text" id="bIP" placeholder="e.g., 192.168.1.99">
        </div>
        <div class="form-group">
            <label>Reason</label>
            <input type="text" id="bReason" placeholder="e.g., Port scan detected">
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-primary" onclick="submitBlock()">Block IP</button>
        </div>
    </div>
</div>

<script>
function openModal()  { document.getElementById('addModal').classList.add('open'); }
function closeModal() { document.getElementById('addModal').classList.remove('open'); }

function submitBlock() {
    const ip     = document.getElementById('bIP').value.trim();
    const reason = document.getElementById('bReason').value.trim();
    if (!ip) { alert('Please enter an IP address'); return; }
    fetch('api/blacklist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ ip_address: ip, reason: reason })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { closeModal(); location.reload(); }
        else alert('Error: ' + data.error);
    });
}

function unblockIP(id) {
    if (!confirm('Unblock this IP address?')) return;
    fetch(`api/blacklist.php?action=unblock&id=${id}`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => { if (data.success) document.getElementById('ip-'+id).remove(); });
}
</script>
</body>
</html>
