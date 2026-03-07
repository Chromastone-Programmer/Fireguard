<?php
require_once '../config.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    $ips = $db->query("
        SELECT b.*, u.username as blocked_by_name
        FROM blocked_ips b
        LEFT JOIN users u ON b.blocked_by = u.id
        ORDER BY b.blocked_at DESC
    ")->fetchAll();
    jsonResponse(['ips' => $ips]);
}

if ($method === 'POST' && !$action) {
    requireAdmin();
    $body = json_decode(file_get_contents('php://input'), true);
    $ip   = $body['ip_address'] ?? '';

    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        jsonResponse(['error' => 'Invalid IP address format'], 400);
    }

    $exists = $db->prepare("SELECT id FROM blocked_ips WHERE ip_address=?");
    $exists->execute([$ip]);
    if ($exists->fetch()) jsonResponse(['error' => 'IP already blacklisted'], 409);

    $stmt = $db->prepare("INSERT INTO blocked_ips (ip_address, reason, blocked_by) VALUES (?, ?, ?)");
    $stmt->execute([$ip, $body['reason'] ?? 'Manually blocked', $_SESSION['user_id']]);

    $db->prepare("INSERT INTO alerts (alert_type, source_ip, description, severity) VALUES ('RULE_VIOLATION', ?, ?, 'MEDIUM')")
       ->execute([$ip, "IP manually blacklisted by {$_SESSION['user']}: " . ($body['reason'] ?? '')]);

    jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
}

if ($method === 'POST' && $action === 'unblock') {
    requireAdmin();
    $id = intval($_GET['id'] ?? 0);
    $db->prepare("DELETE FROM blocked_ips WHERE id=?")->execute([$id]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Bad request'], 400);
?>