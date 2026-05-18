<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

require_once __DIR__ . '/../config/database.php';

const APP_NAME = 'SOMNET SMART NETWORK MANAGEMENT SYSTEM';
const APP_SHORT = 'SSNMS';
const APP_TAGLINE = 'Predict. Protect. Perform. Somnet.';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_role(array $roles): void
{
    require_login();
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        include __DIR__ . '/../403.php';
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function role_home(string $role): string
{
    return match ($role) {
        'Super Admin', 'Executive Manager' => 'dashboard.php',
        'NOC Engineer' => 'network.php',
        'Security Admin' => 'security.php',
        'Jeeb Service Manager' => 'jeeb.php',
        'System Admin' => 'users.php',
        default => 'dashboard.php',
    };
}

function can_access(string $module): bool
{
    $role = current_user()['role'] ?? '';
    $map = [
        'dashboard' => ['Super Admin', 'Executive Manager', 'System Admin'],
        'network' => ['Super Admin', 'NOC Engineer', 'Executive Manager'],
        'alerts' => ['Super Admin', 'NOC Engineer', 'Security Admin', 'Jeeb Service Manager', 'Executive Manager'],
        'data' => ['Super Admin', 'NOC Engineer', 'Security Admin', 'Jeeb Service Manager', 'Executive Manager', 'System Admin'],
        'jeeb' => ['Super Admin', 'Jeeb Service Manager', 'Executive Manager'],
        'security' => ['Super Admin', 'Security Admin', 'System Admin', 'Executive Manager'],
        'ai' => ['Super Admin', 'NOC Engineer', 'Jeeb Service Manager', 'Executive Manager'],
        'reports' => ['Super Admin', 'Executive Manager', 'System Admin'],
        'users' => ['Super Admin', 'System Admin'],
        'docs' => ['Super Admin', 'Executive Manager', 'System Admin'],
    ];
    return in_array($role, $map[$module] ?? [], true);
}

function metric(string $sql, array $params = []): int|float|string
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() ?: 0;
}

function create_alert(string $type, string $severity, string $message, ?int $deviceId = null): void
{
    $stmt = db()->prepare('INSERT INTO alerts (device_id, alert_type, severity, message, status, created_at) VALUES (?, ?, ?, ?, "Open", NOW())');
    $stmt->execute([$deviceId, $type, $severity, $message]);
}

function log_security_event(string $eventType, string $ip, string $severity, string $description): void
{
    $stmt = db()->prepare('INSERT INTO security_logs (event_type, ip_address, severity, description, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$eventType, $ip, $severity, $description]);
}

function simulate_monitoring_tick(): void
{
    $devices = db()->query('SELECT id, name, type FROM devices ORDER BY RAND() LIMIT 5')->fetchAll();
    foreach ($devices as $device) {
        $cpu = random_int(15, 98);
        $ram = random_int(20, 96);
        $traffic = random_int(120, 9800) / 100;
        $latency = random_int(8, 210);
        $packetLoss = random_int(0, 850) / 100;
        $download = $traffic * (random_int(55, 82) / 100);
        $upload = max(0.5, $traffic - $download);
        $status = ($cpu > 93 || random_int(1, 50) === 1) ? 'Warning' : 'Online';

        db()->prepare('UPDATE devices SET cpu_usage=?, ram_usage=?, traffic_mbps=?, status=?, uptime_percent=?, last_seen=NOW() WHERE id=?')
            ->execute([$cpu, $ram, $traffic, $status, random_int(9600, 9999) / 100, $device['id']]);

        db()->prepare('INSERT INTO network_logs (device_id, cpu_usage, ram_usage, traffic_mbps, packet_loss, latency_ms, upload_mbps, download_mbps, uptime_percent, logged_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())')
            ->execute([$device['id'], $cpu, $ram, $traffic, $packetLoss, $latency, $upload, $download, random_int(9600, 9999) / 100]);

        if ($cpu > 90) {
            create_alert('High Traffic', 'High', $device['name'] . ' CPU crossed predictive threshold.', (int) $device['id']);
        }
    }
}

function ai_predictions(): array
{
    $devices = db()->query('SELECT * FROM devices ORDER BY cpu_usage DESC, ram_usage DESC LIMIT 6')->fetchAll();
    $predictions = [];

    foreach ($devices as $device) {
        $risk = min(99, (int) round(($device['cpu_usage'] * 0.45) + ($device['ram_usage'] * 0.35) + ($device['traffic_mbps'] * 0.2)));
        $predictions[] = [
            'target' => $device['name'],
            'type' => $device['type'],
            'risk' => $risk,
            'label' => $risk >= 80 ? 'Critical failure risk' : ($risk >= 65 ? 'Capacity pressure' : 'Stable'),
        ];
    }

    $jeebFailureRate = (float) metric('SELECT ROUND(100 * SUM(status="Failed") / COUNT(*), 2) FROM jeeb_transactions');
    $predictions[] = [
        'target' => 'Jeeb API Gateway',
        'type' => 'Financial',
        'risk' => min(99, (int) ($jeebFailureRate * 10)),
        'label' => $jeebFailureRate > 5 ? 'Transaction anomaly likely' : 'Healthy transaction flow',
    ];

    return $predictions;
}
