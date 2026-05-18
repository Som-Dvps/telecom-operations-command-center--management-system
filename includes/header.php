<?php require_once __DIR__ . '/functions.php'; ?>
<!doctype html>
<html lang="en" data-theme="<?= e($_COOKIE['ssnms_theme'] ?? 'dark') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? APP_SHORT) ?> | <?= APP_SHORT ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
<?php if (is_logged_in()): ?>
<aside class="sidebar">
    <div class="brand">
        <span class="brand-mark">S</span>
        <div><strong>SSNMS</strong><small><?= APP_TAGLINE ?></small></div>
    </div>
    <nav>
        <?php
        $items = [
            'dashboard' => ['dashboard.php', 'fa-gauge-high', 'Executive'],
            'network' => ['network.php', 'fa-tower-cell', 'Network'],
            'alerts' => ['alerts.php', 'fa-triangle-exclamation', 'Alerts'],
            'data' => ['data.php', 'fa-database', 'Data Console'],
            'jeeb' => ['jeeb.php', 'fa-money-bill-transfer', 'Jeeb'],
            'security' => ['security.php', 'fa-shield-halved', 'Security'],
            'ai' => ['ai.php', 'fa-brain', 'AI Analytics'],
            'reports' => ['reports.php', 'fa-file-lines', 'Reports'],
            'users' => ['users.php', 'fa-users-gear', 'Users'],
            'docs' => ['blueprint.php', 'fa-diagram-project', 'Blueprint'],
        ];
        foreach ($items as $module => [$href, $icon, $label]):
            if (!can_access($module)) {
                continue;
            }
            $active = basename($_SERVER['SCRIPT_NAME']) === $href ? 'active' : '';
        ?>
            <a class="<?= $active ?>" href="<?= $href ?>"><i class="fa-solid <?= $icon ?>"></i><span><?= $label ?></span></a>
        <?php endforeach; ?>
    </nav>
</aside>
<main class="app-main">
    <header class="topbar">
        <div>
            <div class="text-muted small"><?= APP_NAME ?></div>
            <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
        </div>
        <div class="top-actions">
            <button class="icon-btn" id="themeToggle" title="Toggle theme"><i class="fa-solid fa-circle-half-stroke"></i></button>
            <span class="user-pill"><?= e(current_user()['role']) ?> · <?= e(current_user()['name']) ?></span>
            <a class="btn btn-sm btn-outline-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </header>
<?php endif; ?>
