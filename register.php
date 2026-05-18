<?php
require_once __DIR__ . '/includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'NOC Engineer';
    $allowed = ['NOC Engineer', 'Security Admin', 'Jeeb Service Manager', 'Executive Manager', 'System Admin'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || !in_array($role, $allowed, true)) {
        $error = 'Enter a valid email, role, and password of at least 8 characters.';
    } else {
        try {
            $stmt = db()->prepare('INSERT INTO users (name, email, password, role, status, mfa_enabled, created_at) VALUES (?, ?, ?, ?, "Active", 1, NOW())');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            redirect('login.php');
        } catch (PDOException) {
            $error = 'Email already exists.';
        }
    }
}

$pageTitle = 'Register';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
    <form class="auth-card" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <h2>Create Account</h2>
        <p class="text-muted">Accounts are active immediately for local XAMPP demonstration.</p>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <input class="form-control mb-3" name="name" placeholder="Full name" required>
        <input class="form-control mb-3" type="email" name="email" placeholder="Email" required>
        <input class="form-control mb-3" type="password" name="password" placeholder="Password" required>
        <select class="form-select mb-3" name="role">
            <option>NOC Engineer</option><option>Security Admin</option><option>Jeeb Service Manager</option><option>Executive Manager</option><option>System Admin</option>
        </select>
        <button class="btn btn-primary w-100">Register</button>
        <a class="small d-block mt-3" href="login.php">Back to login</a>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
