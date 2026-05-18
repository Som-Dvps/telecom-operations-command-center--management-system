<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json');

simulate_monitoring_tick();

$devices = db()->query('SELECT name, type, location, cpu_usage, ram_usage, traffic_mbps, status FROM devices ORDER BY FIELD(status,"Offline","Warning","Online"), cpu_usage DESC LIMIT 15')->fetchAll();
$alerts = db()->query('SELECT alert_type, severity, message, created_at FROM alerts WHERE status="Open" ORDER BY created_at DESC LIMIT 10')->fetchAll();

echo json_encode([
    'devices' => $devices,
    'alerts' => $alerts,
    'timestamp' => date('c'),
]);
