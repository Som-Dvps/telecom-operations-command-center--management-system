<?php
require_once __DIR__ . '/includes/functions.php';
require_role(['Super Admin', 'NOC Engineer', 'Security Admin', 'Jeeb Service Manager', 'Executive Manager', 'System Admin']);
$pageTitle = 'Telecom Data Console';
$devices = db()->query('SELECT id, name FROM devices ORDER BY name')->fetchAll();
$csrf = csrf_token();
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3 mb-3" id="dataStats">
    <?php foreach (['Devices', 'Active Devices', 'Open Alerts', 'Security Today', 'Jeeb Health %', 'Uptime %'] as $label): ?>
        <div class="col-6 col-xl-2"><div class="kpi-card compact"><span class="muted"><?= e($label) ?></span><div class="value">0</div></div></div>
    <?php endforeach; ?>
</div>

<div class="panel mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="section-title mb-1">Dynamic Telecom Datasets</h2>
            <p class="muted mb-0">Manage stored NOC data and preview the latest records without reloading the page.</p>
        </div>
        <button class="btn btn-primary" id="generateSample"><i class="fa-solid fa-wand-magic-sparkles me-2"></i>Generate Sample Data</button>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="panel sticky-panel">
            <ul class="nav nav-pills data-tabs mb-3" id="datasetTabs">
                <li class="nav-item"><button class="nav-link active" data-dataset="devices">Devices</button></li>
                <li class="nav-item"><button class="nav-link" data-dataset="traffic">Traffic</button></li>
                <li class="nav-item"><button class="nav-link" data-dataset="jeeb">Jeeb</button></li>
                <li class="nav-item"><button class="nav-link" data-dataset="security">Security</button></li>
                <li class="nav-item"><button class="nav-link" data-dataset="alerts">Alerts</button></li>
            </ul>
            <form id="dataForm" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="id" id="recordId">
                <div id="dynamicFields"></div>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-primary flex-fill"><i class="fa-solid fa-floppy-disk me-2"></i>Save</button>
                    <button class="btn btn-outline-secondary" type="button" id="resetForm">Clear</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="panel mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h2 class="section-title mb-0">Live Stored Data Preview</h2>
                <input class="form-control data-search" id="dataSearch" placeholder="Search and filter records">
            </div>
            <div class="table-responsive"><table class="table align-middle" id="dataTable"></table></div>
        </div>
        <div class="row g-3">
            <div class="col-lg-7"><div class="panel"><h2 class="section-title">Analytics Preview</h2><div class="chart-box small-chart"><canvas id="dataPreviewChart"></canvas></div></div></div>
            <div class="col-lg-5"><div class="panel"><h2 class="section-title">AI Prediction Results</h2><div id="predictionPreview"></div></div></div>
        </div>
    </div>
</div>

<script>
window.SSNMS_DATA = {
    csrf: <?= json_encode($csrf) ?>,
    devices: <?= json_encode($devices) ?>
};
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
