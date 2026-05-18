<?php
require_once __DIR__ . '/includes/functions.php';
require_role(['Super Admin', 'Executive Manager', 'System Admin']);
$pageTitle = 'Executive Dashboard';
$kpis = [
    ['Total Devices', metric('SELECT COUNT(*) FROM devices'), 'fa-server', '#2563eb'],
    ['Active Alerts', metric('SELECT COUNT(*) FROM alerts WHERE status="Open"'), 'fa-triangle-exclamation', '#dc2626'],
    ['Uptime %', metric('SELECT ROUND(AVG(uptime_percent),2) FROM devices'), 'fa-signal', '#16a34a'],
    ['Security Events', metric('SELECT COUNT(*) FROM security_logs WHERE DATE(created_at)=CURDATE()'), 'fa-shield-halved', '#d97706'],
    ['Jeeb Success %', metric('SELECT ROUND(100 * SUM(status="Success") / COUNT(*),2) FROM jeeb_transactions'), 'fa-money-bill-transfer', '#0f766e'],
    ['AI Predictions', count(ai_predictions()), 'fa-brain', '#7c3aed'],
];
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3 mb-3">
    <?php foreach ($kpis as [$label, $value, $icon, $color]): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="kpi-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="muted"><?= e($label) ?></span>
                    <span class="icon" style="background:<?= e($color) ?>"><i class="fa-solid <?= e($icon) ?>"></i></span>
                </div>
                <div class="value" data-count="<?= (int) $value ?>">0</div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<div class="row g-3">
    <div class="col-xl-8"><div class="panel"><h2 class="section-title">Live Network Traffic</h2><div class="chart-box"><canvas id="trafficChart"></canvas></div></div></div>
    <div class="col-xl-4"><div class="panel"><h2 class="section-title">Alert Severity</h2><div class="chart-box"><canvas id="alertsChart"></canvas></div></div></div>
    <div class="col-xl-7"><div class="panel"><h2 class="section-title">Jeeb Financial Continuity</h2><div class="chart-box"><canvas id="jeebChart"></canvas></div></div></div>
    <div class="col-xl-5">
        <div class="panel">
            <h2 class="section-title">AI Prediction Feed</h2>
            <?php foreach (ai_predictions() as $prediction): ?>
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div><strong><?= e($prediction['target']) ?></strong><div class="muted small"><?= e($prediction['label']) ?></div></div>
                    <span class="badge badge-soft"><?= (int) $prediction['risk'] ?>%</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
