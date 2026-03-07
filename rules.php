<?php
require_once 'config.php';
requireAuth();
$db = getDB();

// ============================================
// WHY: JOIN users table to show who created
// each rule instead of just showing user ID
// LEFT JOIN → show rule even if creator
// account was deleted
// ============================================
$rules = $db->query("
    SELECT r.*, u.username as created_by_name
    FROM firewall_rules r
    LEFT JOIN users u ON r.created_by = u.id
    ORDER BY r.priority ASC, r.created_at DESC
")->fetchAll();

// Maps action to badge CSS class
$actionColors = [
    'ALLOW' => 'badge-green',
    'BLOCK' => 'badge-red',
    'LOG'   => 'badge-orange'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FireGuard - Rules</title>
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
        .badge-green { background:#1a3a1a;color:#3fb950; }
        .badge-red { background:#3d1212;color:#f85149; }
        .badge-orange { background:#3d2200;color:#f0883e; }
        .toggle-btn { background:none;border:none;cursor:pointer;font-size:16px; }
        .action-btn { padding:4px 10px;border-radius:5px;font-size:12px;border:1px solid #30363d;background:none;color:#8b949e;cursor:pointer; }
        .action-btn.del:hover { border-color:#f85149;color:#f85149; }

        /* ── MODAL ──
           WHY: Modal lets admin add rules
           without leaving the page
           display:none by default
           display:flex when .open class added */
        .modal-overlay { display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:100;align-items:center;justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal { background:#161b22;border:1px solid #30363d;border-radius:12px;padding:28px;width:480px; }
        .modal h3 { margin-bottom:20px;font-size:16px; }
        .form-row { display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px; }
        .form-group { margin-bottom:12px; }
        label { display:block;color:#8b949e;font-size:12px;margin-bottom:5px; }
        input, select { width:100%;padding:8px 12px;background:#0d1117;border:1px solid #30363d;border-radius:6px;color:#c9d1d9;font-size:13px; }
        input:focus, select:focus { outline:none;border-color:#58a6ff; }
        .modal-footer { display:flex;gap:10px;justify-content:flex-end;margin-top:20px; }
        .btn-cancel { padding:8px 16px;background:none;border:1px solid #30363d;color:#8b949e;border-radius:6px;cursor:pointer;font-size:13px; }
    </style>
</head>
<body>
<nav class="sidebar">
    <div class="sidebar-logo">🔥 FireGuard</div>
    <a class="nav-item" href="dashboard.php">📊 Dashboard</a>
    <a class="nav-item active" href="rules.php">🛡️ Firewall Rules</a>
    <a class="nav-item" href="blacklist.php">🚫 IP Blacklist</a>
    <a class="nav-item" href="alerts.php">🔔 Alerts</a>
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
        <div class="page-title">🛡️ Firewall Rules</div>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <button class="btn-primary" onclick="openModal()">+ Add Rule</button>
        <?php endif; ?>
    </div>

    <div class="panel">
        <table>
            <thead><tr>
                <th>#</th><th>Rule Name</th><th>Protocol</th>
                <th>Source IP</th><th>Dest IP</th><th>Port</th>
                <th>Action</th><th>Priority</th><th>Status</th>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <th>Manage</th>
                <?php endif; ?>
            </tr></thead>
            <tbody>
            <?php foreach ($rules as $i => $r): ?>
            <tr id="rule-<?= $r['id'] ?>">
                <td style="color:#8b949e"><?= $i+1 ?></td>
                <td><?= htmlspecialchars($r['rule_name']) ?></td>
                <td>
                    <span class="badge badge-orange">
                        <?= $r['protocol'] ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($r['source_ip']) ?></td>
                <td><?= htmlspecialchars($r['dest_ip']) ?></td>
                <td><?= htmlspecialchars($r['dest_port']) ?></td>
                <td>
                    <span class="badge <?= $actionColors[$r['action']] ?>">
                        <?= $r['action'] ?>
                    </span>
                </td>
                <td><?= $r['priority'] ?></td>
                <td>
                    <!--
                        WHY: Toggle button changes rule
                        active/inactive without page reload
                        onclick passes rule ID to JavaScript
                    -->
                    <button class="toggle-btn"
                        onclick="toggleRule(<?= $r['id'] ?>, this)">
                        <?= $r['is_active'] ? '✅' : '⛔' ?>
                    </button>
                </td>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <td>
                    <button class="action-btn del"
                        onclick="deleteRule(<?= $r['id'] ?>)">
                        Delete
                    </button>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- ADD RULE MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <h3>➕ Add Firewall Rule</h3>
        <div class="form-group">
            <label>Rule Name</label>
            <input type="text" id="rName" placeholder="e.g., Block Telnet">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Protocol</label>
                <select id="rProto">
                    <option>TCP</option>
                    <option>UDP</option>
                    <option>ICMP</option>
                    <option>ANY</option>
                </select>
            </div>
            <div class="form-group">
                <label>Action</label>
                <select id="rAction">
                    <option>ALLOW</option>
                    <option>BLOCK</option>
                    <option>LOG</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Source IP</label>
                <input type="text" id="rSrcIP" placeholder="ANY or 192.168.1.0/24">
            </div>
            <div class="form-group">
                <label>Dest IP</label>
                <input type="text" id="rDstIP" placeholder="ANY">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Dest Port</label>
                <input type="text" id="rPort" placeholder="ANY or 80">
            </div>
            <div class="form-group">
                <label>Priority</label>
                <input type="number" id="rPriority" value="100">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-primary" onclick="submitRule()">Add Rule</button>
        </div>
    </div>
</div>

<script>
// ============================================
// MODAL OPEN/CLOSE
// WHY: Adding/removing CSS class is cleaner
// than toggling display style directly
// ============================================
function openModal()  {
    document.getElementById('addModal').classList.add('open');
}
function closeModal() {
    document.getElementById('addModal').classList.remove('open');
}

// ============================================
// ADD RULE VIA AJAX
// WHY: fetch() sends data to api/rules.php
// without reloading the page
// Same concept as axios.post() in Node.js
// ============================================
function submitRule() {
    const payload = {
        rule_name: document.getElementById('rName').value,
        protocol:  document.getElementById('rProto').value,
        action:    document.getElementById('rAction').value,
        source_ip: document.getElementById('rSrcIP').value || 'ANY',
        dest_ip:   document.getElementById('rDstIP').value || 'ANY',
        dest_port: document.getElementById('rPort').value  || 'ANY',
        priority:  document.getElementById('rPriority').value
    };

    fetch('api/rules.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            // WHY: This header tells PHP it's an AJAX request
            // isAjax() in config.php checks for this
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeModal();
            location.reload(); // Refresh to show new rule
        } else {
            alert('Error: ' + data.error);
        }
    });
}

// ============================================
// TOGGLE RULE ON/OFF
// WHY: Sends POST to api/rules.php?action=toggle
// PHP flips is_active value in database
// Response tells JS new state → update icon
// ============================================
function toggleRule(id, btn) {
    fetch(`api/rules.php?action=toggle&id=${id}`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Update button icon based on new state
            btn.textContent = data.is_active ? '✅' : '⛔';
        }
    });
}

// ============================================
// DELETE RULE
// WHY: confirm() shows browser dialog
// Prevents accidental deletions
// On success → remove row from DOM directly
// No page reload needed
// ============================================
function deleteRule(id) {
    if (!confirm('Delete this rule?')) return;
    fetch(`api/rules.php?action=delete&id=${id}`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Remove row from table without reload
            document.getElementById('rule-' + id).remove();
        }
    });
}
</script>
</body>
</html>