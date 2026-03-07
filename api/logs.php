<?php
require_once '../config.php';
requireAuth();

$db     = getDB();
$limit  = min(intval($_GET['limit']  ?? 20), 100);
$offset = intval($_GET['offset'] ?? 0);
$filter = $_GET['filter'] ?? 'all';

$where = '';
if ($filter === 'blocked') $where = "WHERE action_taken='BLOCKED'";
if ($filter === 'allowed') $where = "WHERE action_taken='ALLOWED'";

$logs  = $db->query(
    "SELECT * FROM traffic_logs $where ORDER BY timestamp DESC LIMIT $limit OFFSET $offset"
)->fetchAll();

$total = $db->query(
    "SELECT COUNT(*) FROM traffic_logs $where"
)->fetchColumn();

jsonResponse(['logs' => $logs, 'total' => intval($total), 'limit' => $limit, 'offset' => $offset]);
?>