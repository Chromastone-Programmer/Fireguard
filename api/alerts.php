<?php
require_once '../config.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = intval($_GET['id'] ?? 0);

// GET → return all alerts
if ($method === 'GET') {
    $alerts = $db->query("SELECT * FROM alerts ORDER BY created_at DESC")->fetchAll();
    jsonResponse(['alerts' => $alerts]);
}

// POST resolve → mark alert as resolved
if ($method === 'POST' && $action === 'resolve' && $id) {
    requireAdmin();
    $db->prepare("UPDATE alerts SET is_resolved=1 WHERE id=?")->execute([$id]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Bad request'], 400);
?>
