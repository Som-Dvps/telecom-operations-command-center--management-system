<?php
require_once __DIR__ . '/includes/functions.php';
require_role(['Super Admin', 'NOC Engineer', 'Security Admin', 'Jeeb Service Manager', 'Executive Manager']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    db()->prepare('UPDATE alerts SET status = "Resolved", resolved_at = NOW() WHERE id = ?')->execute([(int) $_POST['alert_id']]);
}
$pageTitle = 'Alert Management';
$alerts = db()->query('SELECT a.*, d.name device_name FROM alerts a LEFT JOIN devices d ON d.id=a.device_id ORDER BY a.created_at DESC LIMIT 100')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div class="panel">
    <h2 class="section-title">Incident Queue</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Type</th><th>Device</th><th>Severity</th><th>Message</th><th>Status</th><th>Created</th><th></th></tr></thead>
            <tbody id="liveAlerts">
            <?php foreach ($alerts as $alert): ?>
                <tr>
                    <td><?= e($alert['alert_type']) ?></td><td><?= e($alert['device_name'] ?? 'Platform') ?></td><td><?= e($alert['severity']) ?></td><td><?= e($alert['message']) ?></td><td class="<?= e($alert['status']) ?>"><?= e($alert['status']) ?></td><td><?= e($alert['created_at']) ?></td>
                    <td><?php if ($alert['status'] === 'Open'): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= csrf_token() ?>"><input type="hidden" name="alert_id" value="<?= (int) $alert['id'] ?>"><button class="btn btn-sm btn-success">Resolve</button></form><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
