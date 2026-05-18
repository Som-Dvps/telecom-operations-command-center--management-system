<?php
require_once __DIR__ . '/includes/functions.php';
require_role(['Super Admin', 'Executive Manager', 'System Admin']);

if (isset($_GET['export'])) {
    $type = $_GET['export'];
    $rows = db()->query('SELECT alert_type, severity, message, status, created_at FROM alerts ORDER BY created_at DESC')->fetchAll();
    if ($type === 'csv' || $type === 'excel') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="ssnms-alert-report.' . ($type === 'excel' ? 'xls' : 'csv') . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Type', 'Severity', 'Message', 'Status', 'Created']);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        exit;
    }
    if ($type === 'pdf') {
        header('Content-Type: text/html');
        echo '<h1>SSNMS PDF Export Simulation</h1><p>Use browser print to save this report as PDF.</p>';
        foreach ($rows as $row) {
            echo '<p>' . e(implode(' | ', $row)) . '</p>';
        }
        exit;
    }
}

$pageTitle = 'Reports & Analytics';
include __DIR__ . '/includes/header.php';
?>
<div class="panel">
    <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center">
        <div><h2 class="section-title mb-1">Operational Reports</h2><p class="muted mb-0">Downtime, device health, security incidents, Jeeb continuity, and monthly analytics.</p></div>
        <div class="btn-group"><a class="btn btn-outline-primary" href="?export=csv">CSV</a><a class="btn btn-outline-primary" href="?export=excel">Excel</a><a class="btn btn-outline-primary" href="?export=pdf" target="_blank">PDF</a></div>
    </div>
</div>
<div class="row g-3 mt-1">
    <?php foreach (['Downtime Report','Security Incident Report','Jeeb Transaction Report','Device Health Report','Monthly Analytics'] as $name): ?>
    <div class="col-md-6 col-xl-4"><div class="kpi-card"><span class="muted"><?= e($name) ?></span><strong>Ready</strong><span class="small muted">Generated from live MySQL records.</span></div></div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
