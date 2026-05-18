<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json');

$trafficRows = db()->query('SELECT DATE_FORMAT(logged_at, "%H:%i") label, ROUND(AVG(traffic_mbps),2) value FROM network_logs GROUP BY label ORDER BY MIN(logged_at) DESC LIMIT 12')->fetchAll();
$trafficRows = array_reverse($trafficRows);

$alertRows = db()->query('SELECT severity, COUNT(*) total FROM alerts GROUP BY severity')->fetchAll();
$jeebRows = db()->query('SELECT DATE(created_at) day, SUM(status="Success") success, SUM(status="Failed") failed FROM jeeb_transactions GROUP BY day ORDER BY day DESC LIMIT 7')->fetchAll();
$jeebRows = array_reverse($jeebRows);

echo json_encode([
    'traffic' => [
        'labels' => array_column($trafficRows, 'label'),
        'values' => array_map('floatval', array_column($trafficRows, 'value')),
    ],
    'alerts' => [
        'labels' => array_column($alertRows, 'severity'),
        'values' => array_map('intval', array_column($alertRows, 'total')),
    ],
    'jeeb' => [
        'labels' => array_column($jeebRows, 'day'),
        'success' => array_map('intval', array_column($jeebRows, 'success')),
        'failed' => array_map('intval', array_column($jeebRows, 'failed')),
    ],
]);
