<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json');

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function can_manage_dataset(string $dataset): bool
{
    $role = current_user()['role'] ?? '';
    if (in_array($role, ['Super Admin', 'System Admin'], true)) {
        return true;
    }
    return match ($dataset) {
        'devices', 'traffic', 'alerts' => in_array($role, ['NOC Engineer', 'Executive Manager'], true),
        'jeeb' => in_array($role, ['Jeeb Service Manager', 'Executive Manager'], true),
        'security' => in_array($role, ['Security Admin', 'Executive Manager'], true),
        default => false,
    };
}

function can_view_dataset(string $dataset): bool
{
    return can_manage_dataset($dataset) || in_array(current_user()['role'] ?? '', ['Executive Manager'], true);
}

function request_data(): array
{
    $raw = file_get_contents('php://input');
    if ($raw !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
    }
    return $_POST;
}

function require_ajax_csrf(array $input): void
{
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'] ?? '')) {
        json_out(['ok' => false, 'message' => 'Invalid CSRF token.'], 419);
    }
}

function latest_rows(string $dataset, string $search = ''): PDOStatement
{
    $like = '%' . $search . '%';
    return match ($dataset) {
        'devices' => db()->prepare('SELECT id, name, type, ip_address, cpu_usage, ram_usage, status, location, traffic_mbps, uptime_percent FROM devices WHERE name LIKE ? OR type LIKE ? OR ip_address LIKE ? OR location LIKE ? ORDER BY id DESC LIMIT 80'),
        'traffic' => db()->prepare('SELECT n.id, n.device_id, d.name device_name, n.cpu_usage, n.ram_usage, n.traffic_mbps, n.packet_loss, n.latency_ms, n.upload_mbps, n.download_mbps, n.uptime_percent, n.logged_at FROM network_logs n JOIN devices d ON d.id=n.device_id WHERE d.name LIKE ? OR n.logged_at LIKE ? OR n.traffic_mbps LIKE ? OR n.latency_ms LIKE ? ORDER BY n.logged_at DESC LIMIT 80'),
        'jeeb' => db()->prepare('SELECT id, reference_no, sender_msisdn, receiver_msisdn, amount, status, api_status, created_at FROM jeeb_transactions WHERE reference_no LIKE ? OR sender_msisdn LIKE ? OR receiver_msisdn LIKE ? OR status LIKE ? ORDER BY created_at DESC LIMIT 80'),
        'security' => db()->prepare('SELECT id, event_type, ip_address, failed_attempts, severity, description, created_at FROM security_logs WHERE event_type LIKE ? OR ip_address LIKE ? OR severity LIKE ? OR description LIKE ? ORDER BY created_at DESC LIMIT 80'),
        'alerts' => db()->prepare('SELECT a.id, a.device_id, a.alert_type, a.severity, a.message, a.status, COALESCE(d.name,"Platform") device_name, a.created_at FROM alerts a LEFT JOIN devices d ON d.id=a.device_id WHERE a.alert_type LIKE ? OR a.severity LIKE ? OR a.status LIKE ? OR COALESCE(d.name,"Platform") LIKE ? ORDER BY a.created_at DESC LIMIT 80'),
        default => json_out(['ok' => false, 'message' => 'Unknown dataset.'], 400),
    };
}

function fetch_dataset(string $dataset, string $search = ''): array
{
    $stmt = latest_rows($dataset, $search);
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like, $like, $like]);
    return $stmt->fetchAll();
}

function stats(): array
{
    return [
        'devices' => (int) metric('SELECT COUNT(*) FROM devices'),
        'active_devices' => (int) metric('SELECT COUNT(*) FROM devices WHERE status="Online"'),
        'alerts' => (int) metric('SELECT COUNT(*) FROM alerts WHERE status="Open"'),
        'security' => (int) metric('SELECT COUNT(*) FROM security_logs WHERE DATE(created_at)=CURDATE()'),
        'jeeb_health' => (float) metric('SELECT ROUND(100 * SUM(status="Success") / COUNT(*),2) FROM jeeb_transactions'),
        'uptime' => (float) metric('SELECT ROUND(AVG(uptime_percent),2) FROM devices'),
    ];
}

function generate_sample_data(int $count = 12): void
{
    $deviceTypes = ['Router', 'BTS', 'Core Switch', 'Firewall', 'Jeeb API', 'Server'];
    $locations = ['Mogadishu', 'Hargeisa', 'Bosaso', 'Kismayo', 'Baidoa', 'Garowe'];
    $statuses = ['Online', 'Online', 'Online', 'Warning', 'Offline'];
    $vendors = ['Cisco', 'Huawei', 'Ericsson', 'Nokia', 'Juniper', 'Fortinet'];

    for ($i = 0; $i < $count; $i++) {
        $name = $locations[array_rand($locations)] . '-' . $deviceTypes[array_rand($deviceTypes)] . '-' . random_int(100, 999);
        $ip = '10.' . random_int(80, 220) . '.' . random_int(0, 254) . '.' . random_int(1, 254);
        db()->prepare('INSERT IGNORE INTO devices (name,type,vendor,ip_address,location,status,cpu_usage,ram_usage,traffic_mbps,uptime_percent,last_seen) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())')
            ->execute([$name, $deviceTypes[array_rand($deviceTypes)], $vendors[array_rand($vendors)], $ip, $locations[array_rand($locations)], $statuses[array_rand($statuses)], random_int(10, 95), random_int(15, 96), random_int(500, 15000) / 100, random_int(9450, 9999) / 100]);
    }

    for ($i = 0; $i < ($count * 2); $i++) {
        simulate_monitoring_tick();
    }

    for ($i = 0; $i < $count; $i++) {
        $ref = 'JEEB-' . time() . random_int(100, 999);
        db()->prepare('INSERT INTO jeeb_transactions (reference_no, customer_msisdn, sender_msisdn, receiver_msisdn, amount, status, api_status, channel, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())')
            ->execute([$ref, '25261' . random_int(1000000, 9999999), '25261' . random_int(1000000, 9999999), '25262' . random_int(1000000, 9999999), random_int(100, 150000) / 100, ['Success','Success','Failed','Pending'][array_rand(['Success','Success','Failed','Pending'])], ['Healthy','Healthy','Degraded','Offline'][array_rand(['Healthy','Healthy','Degraded','Offline'])], ['Mobile App','USSD','Merchant API','Agent'][array_rand(['Mobile App','USSD','Merchant API','Agent'])]]);
    }

    for ($i = 0; $i < $count; $i++) {
        log_security_event(['IDS Alert','Firewall Block','Failed Login','Suspicious IP'][array_rand(['IDS Alert','Firewall Block','Failed Login','Suspicious IP'])], random_int(41, 196) . '.' . random_int(1, 250) . '.' . random_int(1, 250) . '.' . random_int(1, 250), ['Low','Medium','High','Critical'][array_rand(['Low','Medium','High','Critical'])], 'Generated telecom security monitoring event.');
    }
}

$input = request_data();
$action = $_GET['action'] ?? $input['action'] ?? 'list';
$dataset = $_GET['dataset'] ?? $input['dataset'] ?? 'devices';

if ($action !== 'stats' && !can_view_dataset($dataset)) {
    json_out(['ok' => false, 'message' => 'You are not authorized for this dataset.'], 403);
}

if ($action === 'stats') {
    json_out(['ok' => true, 'stats' => stats(), 'predictions' => ai_predictions()]);
}

if ($action === 'list') {
    json_out(['ok' => true, 'rows' => fetch_dataset($dataset, trim($_GET['search'] ?? ''))]);
}

require_ajax_csrf($input);

if (!can_manage_dataset($dataset)) {
    json_out(['ok' => false, 'message' => 'You cannot modify this dataset.'], 403);
}

if ($action === 'delete') {
    $id = (int) ($input['id'] ?? 0);
    $table = ['devices' => 'devices', 'traffic' => 'network_logs', 'jeeb' => 'jeeb_transactions', 'security' => 'security_logs', 'alerts' => 'alerts'][$dataset] ?? '';
    if ($id <= 0 || $table === '') {
        json_out(['ok' => false, 'message' => 'Invalid delete request.'], 400);
    }
    db()->prepare("DELETE FROM {$table} WHERE id=?")->execute([$id]);
    json_out(['ok' => true, 'message' => 'Record deleted.']);
}

if ($action === 'generate') {
    generate_sample_data(10);
    json_out(['ok' => true, 'message' => 'Sample telecom data generated.']);
}

$id = (int) ($input['id'] ?? 0);

try {
    if ($dataset === 'devices') {
        $values = [trim($input['name'] ?? ''), $input['type'] ?? 'Router', trim($input['vendor'] ?? 'Somnet'), trim($input['ip_address'] ?? ''), trim($input['location'] ?? ''), $input['status'] ?? 'Online', (int) $input['cpu_usage'], (int) $input['ram_usage'], (float) $input['traffic_mbps'], (float) ($input['uptime_percent'] ?? 99.0)];
        if ($id > 0) {
            db()->prepare('UPDATE devices SET name=?, type=?, vendor=?, ip_address=?, location=?, status=?, cpu_usage=?, ram_usage=?, traffic_mbps=?, uptime_percent=?, last_seen=NOW() WHERE id=?')->execute([...$values, $id]);
        } else {
            db()->prepare('INSERT INTO devices (name,type,vendor,ip_address,location,status,cpu_usage,ram_usage,traffic_mbps,uptime_percent,last_seen) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())')->execute($values);
        }
    } elseif ($dataset === 'traffic') {
        $values = [(int) $input['device_id'], (int) ($input['cpu_usage'] ?? 40), (int) ($input['ram_usage'] ?? 45), (float) $input['traffic_mbps'], (float) $input['packet_loss'], (int) $input['latency_ms'], (float) $input['upload_mbps'], (float) $input['download_mbps'], (float) ($input['uptime_percent'] ?? 99.0)];
        if ($id > 0) {
            db()->prepare('UPDATE network_logs SET device_id=?, cpu_usage=?, ram_usage=?, traffic_mbps=?, packet_loss=?, latency_ms=?, upload_mbps=?, download_mbps=?, uptime_percent=? WHERE id=?')->execute([...$values, $id]);
        } else {
            db()->prepare('INSERT INTO network_logs (device_id,cpu_usage,ram_usage,traffic_mbps,packet_loss,latency_ms,upload_mbps,download_mbps,uptime_percent,logged_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())')->execute($values);
        }
    } elseif ($dataset === 'jeeb') {
        $ref = trim($input['reference_no'] ?? ('JEEB-' . time()));
        $values = [$ref, trim($input['sender_msisdn'] ?? ''), trim($input['receiver_msisdn'] ?? ''), trim($input['sender_msisdn'] ?? ''), (float) $input['amount'], $input['status'] ?? 'Success', $input['api_status'] ?? 'Healthy', $input['channel'] ?? 'Mobile App'];
        if ($id > 0) {
            db()->prepare('UPDATE jeeb_transactions SET reference_no=?, sender_msisdn=?, receiver_msisdn=?, customer_msisdn=?, amount=?, status=?, api_status=?, channel=? WHERE id=?')->execute([...$values, $id]);
        } else {
            db()->prepare('INSERT INTO jeeb_transactions (reference_no,sender_msisdn,receiver_msisdn,customer_msisdn,amount,status,api_status,channel,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())')->execute($values);
        }
    } elseif ($dataset === 'security') {
        $values = [$input['event_type'] ?? 'IDS Alert', trim($input['ip_address'] ?? ''), (int) ($input['failed_attempts'] ?? 0), $input['severity'] ?? 'Medium', trim($input['description'] ?? '')];
        if ($id > 0) {
            db()->prepare('UPDATE security_logs SET event_type=?, ip_address=?, failed_attempts=?, severity=?, description=? WHERE id=?')->execute([...$values, $id]);
        } else {
            db()->prepare('INSERT INTO security_logs (event_type,ip_address,failed_attempts,severity,description,created_at) VALUES (?,?,?,?,?,NOW())')->execute($values);
        }
    } elseif ($dataset === 'alerts') {
        $deviceId = (int) ($input['device_id'] ?? 0) ?: null;
        $values = [$deviceId, $input['alert_type'] ?? 'High Traffic', $input['severity'] ?? 'Medium', trim($input['message'] ?? ''), $input['status'] ?? 'Open'];
        if ($id > 0) {
            db()->prepare('UPDATE alerts SET device_id=?, alert_type=?, severity=?, message=?, status=? WHERE id=?')->execute([...$values, $id]);
        } else {
            db()->prepare('INSERT INTO alerts (device_id,alert_type,severity,message,status,created_at) VALUES (?,?,?,?,?,NOW())')->execute($values);
        }
    }
} catch (Throwable $exception) {
    json_out(['ok' => false, 'message' => 'Save failed: ' . $exception->getMessage()], 400);
}

json_out(['ok' => true, 'message' => 'Record saved.']);
