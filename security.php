<?php
require_once __DIR__ . '/includes/functions.php';
require_role(['Super Admin', 'Security Admin', 'System Admin', 'Executive Manager']);
$pageTitle = 'Security Monitoring';
$logs = db()->query('SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 100')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div class="panel">
    <h2 class="section-title">IDS, Firewall and Login Events</h2>
    <div class="table-responsive"><table class="table"><thead><tr><th>Event</th><th>IP Address</th><th>Severity</th><th>Description</th><th>Time</th></tr></thead><tbody>
    <?php foreach ($logs as $log): ?><tr><td><?= e($log['event_type']) ?></td><td><?= e($log['ip_address']) ?></td><td><?= e($log['severity']) ?></td><td><?= e($log['description']) ?></td><td><?= e($log['created_at']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
