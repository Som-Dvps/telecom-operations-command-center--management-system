<?php
require_once __DIR__ . '/includes/functions.php';
require_role(['Super Admin', 'Jeeb Service Manager', 'Executive Manager']);
$pageTitle = 'Jeeb Financial Protection';
$transactions = db()->query('SELECT * FROM jeeb_transactions ORDER BY created_at DESC LIMIT 80')->fetchAll();
$frauds = db()->query('SELECT * FROM fraud_alerts ORDER BY created_at DESC LIMIT 20')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-xl-8">
        <div class="panel">
            <h2 class="section-title">Transaction Monitoring</h2>
            <div class="table-responsive"><table class="table"><thead><tr><th>Reference</th><th>Customer</th><th>Amount</th><th>Status</th><th>API</th><th>Time</th></tr></thead><tbody>
            <?php foreach ($transactions as $tx): ?><tr><td><?= e($tx['reference_no']) ?></td><td><?= e($tx['customer_msisdn']) ?></td><td>$<?= e($tx['amount']) ?></td><td><?= e($tx['status']) ?></td><td><?= e($tx['api_status']) ?></td><td><?= e($tx['created_at']) ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="panel">
            <h2 class="section-title">Fraud Anomaly Alerts</h2>
            <?php foreach ($frauds as $fraud): ?><div class="border-bottom py-2"><strong><?= e($fraud['risk_score']) ?>% Risk</strong><div class="muted small"><?= e($fraud['description']) ?></div></div><?php endforeach; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
