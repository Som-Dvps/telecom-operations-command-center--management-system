<?php
require_once __DIR__ . '/includes/functions.php';
require_role(['Super Admin', 'NOC Engineer', 'Jeeb Service Manager', 'Executive Manager']);
$pageTitle = 'AI Predictive Analytics';
$predictions = ai_predictions();
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <?php foreach ($predictions as $prediction): ?>
        <div class="col-md-6 col-xl-4">
            <div class="panel">
                <div class="d-flex justify-content-between align-items-start">
                    <div><h2 class="section-title mb-1"><?= e($prediction['target']) ?></h2><p class="muted mb-0"><?= e($prediction['type']) ?> · <?= e($prediction['label']) ?></p></div>
                    <span class="badge <?= $prediction['risk'] >= 80 ? 'text-bg-danger' : 'badge-soft' ?>"><?= (int) $prediction['risk'] ?>%</span>
                </div>
                <div class="progress mt-3" role="progressbar"><div class="progress-bar" style="width: <?= (int) $prediction['risk'] ?>%"></div></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<div class="panel mt-3">
    <h2 class="section-title">Rule-Based AI Engine</h2>
    <p class="muted mb-0">SSNMS scores risk using CPU, RAM, traffic load, uptime, failed Jeeb transactions, and alert severity thresholds. The structure is API-ready for future ML model integration.</p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
