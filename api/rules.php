<?php
require_once '../config.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = intval($_GET['id'] ?? 0);

if ($method === 'GET' && !$action) {
    $rules = $db->query("SELECT * FROM firewall_rules WHERE is_active=1 ORDER BY priority ASC")->fetchAll();
    jsonResponse(['rules' => $rules]);
}

if ($method === 'POST' && !$action) {
    requireAdmin();
    $body = json_decode(file_get_contents('php://input'), true);

    foreach (['rule_name', 'protocol', 'action'] as $field) {
        if (empty($body[$field])) jsonResponse(['error' => "Missing: $field"], 400);
    }

    $port = $body['dest_port'] ?? 'ANY';
    if ($port !== 'ANY' && (!is_numeric($port) || $port < 1 || $port > 65535)) {
        jsonResponse(['error' => 'Invalid port number'], 400);
    }

    $stmt = $db->prepare("
        INSERT INTO firewall_rules
        (rule_name, protocol, source_ip, dest_ip, dest_port, action, priority, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        htmlspecialchars($body['rule_name']),
        $body['protocol'],
        $body['source_ip'] ?? 'ANY',
        $body['dest_ip']   ?? 'ANY',
        $port,
        $body['action'],
        intval($body['priority'] ?? 100),
        $_SESSION['user_id']
    ]);
    jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
}

if ($method === 'POST' && $action === 'toggle' && $id) {
    requireAdmin();
    $stmt = $db->prepare("SELECT is_active FROM firewall_rules WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) jsonResponse(['error' => 'Rule not found'], 404);
    $newVal = $row['is_active'] ? 0 : 1;
    $db->prepare("UPDATE firewall_rules SET is_active=? WHERE id=?")->execute([$newVal, $id]);
    jsonResponse(['success' => true, 'is_active' => $newVal]);
}

if ($method === 'POST' && $action === 'delete' && $id) {
    requireAdmin();
    $db->prepare("DELETE FROM firewall_rules WHERE id=?")->execute([$id]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Bad request'], 400);
?>