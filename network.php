<?php
require_once __DIR__ . '/includes/functions.php';
require_role(['Super Admin', 'NOC Engineer', 'Executive Manager']);
$pageTitle = 'Network Monitoring';
$devices = db()->query('SELECT * FROM devices ORDER BY FIELD(status,"Offline","Warning","Online"), name')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div class="panel">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0">Routers, BTS, Core and API Nodes</h2>
        <span class="badge badge-soft">AJAX refresh every 12 seconds</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Device</th><th>Type</th><th>Location</th><th>CPU</th><th>RAM</th><th>Traffic</th><th>Status</th></tr></thead>
            <tbody id="liveDevices">
            <?php foreach ($devices as $device): ?>
                <tr><td><span class="status-dot <?= e($device['status']) ?>"></span><?= e($device['name']) ?></td><td><?= e($device['type']) ?></td><td><?= e($device['location']) ?></td><td><?= (int) $device['cpu_usage'] ?>%</td><td><?= (int) $device['ram_usage'] ?>%</td><td><?= e($device['traffic_mbps']) ?> Mbps</td><td><?= e($device['status']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
