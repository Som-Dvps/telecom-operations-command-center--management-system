<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect(role_home(current_user()['role']));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $mfa = trim($_POST['mfa_code'] ?? '');

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND status = "Active" LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password']) && ($user['mfa_enabled'] === 0 || $mfa === '123456')) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
        redirect(role_home($user['role']));
    }

    log_security_event('Failed Login', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', 'Medium', 'Failed login attempt for ' . $email);
    $error = 'Invalid credentials or MFA code. Demo MFA code is 123456.';
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
    <section class="auth-shell">
        <div class="auth-hero">
            <div class="auth-brand-row">
                <div class="brand-mark auth-mark">S</div>
                <div>
                    <strong>SSNMS</strong>
                    <span><?= APP_TAGLINE ?></span>
                </div>
            </div>
            <div class="auth-hero-content">
                <span class="auth-kicker">Somnet NOC Intelligence</span>
                <h1>Secure telecom operations command center</h1>
                <p>Predictive monitoring, Jeeb continuity, security intelligence, and executive visibility in one platform.</p>
            </div>
            <div class="auth-signal-grid">
                <div><strong>99.92%</strong><span>Core uptime</span></div>
                <div><strong>&lt;2m</strong><span>Fault detection</span></div>
                <div><strong>24/7</strong><span>NOC visibility</span></div>
            </div>
        </div>
        <form class="auth-card auth-card-advanced" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="mb-4 auth-form-heading">
            <span class="auth-kicker">Authorized access</span>
            <h2 class="mb-1">Welcome back</h2>
            <p class="text-muted mb-0">Sign in to manage Somnet network operations.</p>
        </div>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <label class="form-label">Email</label>
        <div class="input-icon mb-3">
            <i class="fa-solid fa-envelope"></i>
            <input class="form-control" type="email" name="email" value="admin@somnet.so" required>
        </div>
        <label class="form-label">Password</label>
        <div class="input-icon mb-3">
            <i class="fa-solid fa-lock"></i>
            <input class="form-control" type="password" name="password" value="password123" required>
        </div>
        <label class="form-label">MFA Simulation Code</label>
        <div class="input-icon mb-3">
            <i class="fa-solid fa-key"></i>
            <input class="form-control" name="mfa_code" value="123456">
        </div>
        <button class="btn btn-primary w-100 auth-submit" type="submit"><i class="fa-solid fa-right-to-bracket me-2"></i>Sign in</button>
        <div class="auth-demo-box">
            <span>Demo Super Admin</span>
            <strong>admin@somnet.so / password123 / 123456</strong>
        </div>
        <a class="small d-block mt-3 text-center" href="register.php">Request a new operator account</a>
        </form>
    </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
