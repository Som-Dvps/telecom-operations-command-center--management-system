<?php
require_once __DIR__ . '/includes/functions.php';
require_role(['Super Admin', 'System Admin']);

$roles = ['Super Admin', 'NOC Engineer', 'Security Admin', 'Jeeb Service Manager', 'Executive Manager', 'System Admin'];
$statuses = ['Active', 'Suspended'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            $status = $_POST['status'] ?? 'Active';
            $mfaEnabled = isset($_POST['mfa_enabled']) ? 1 : 0;

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || !in_array($role, $roles, true) || !in_array($status, $statuses, true)) {
                throw new RuntimeException('Enter a valid name, email, role, status, and password of at least 8 characters.');
            }

            $stmt = db()->prepare('INSERT INTO users (name, email, password, role, status, mfa_enabled, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $status, $mfaEnabled]);
            $message = 'User account created successfully.';
        }

        if ($action === 'update') {
            $id = (int) ($_POST['user_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            $status = $_POST['status'] ?? 'Active';
            $mfaEnabled = isset($_POST['mfa_enabled']) ? 1 : 0;

            if ($id <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, $roles, true) || !in_array($status, $statuses, true)) {
                throw new RuntimeException('Enter valid user details before saving changes.');
            }

            if ($id === (int) current_user()['id'] && $status === 'Suspended') {
                throw new RuntimeException('You cannot suspend your own active session account.');
            }

            if ($password !== '') {
                if (strlen($password) < 8) {
                    throw new RuntimeException('New password must be at least 8 characters.');
                }
                $stmt = db()->prepare('UPDATE users SET name=?, email=?, password=?, role=?, status=?, mfa_enabled=? WHERE id=?');
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $status, $mfaEnabled, $id]);
            } else {
                $stmt = db()->prepare('UPDATE users SET name=?, email=?, role=?, status=?, mfa_enabled=? WHERE id=?');
                $stmt->execute([$name, $email, $role, $status, $mfaEnabled, $id]);
            }

            if ($id === (int) current_user()['id']) {
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['role'] = $role;
            }

            $message = 'User account updated successfully.';
        }
    } catch (PDOException $exception) {
        $error = str_contains($exception->getMessage(), 'Duplicate') ? 'That email address is already used by another account.' : 'Database error while saving the user.';
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$pageTitle = 'User Management';
$users = db()->query('SELECT id, name, email, role, status, mfa_enabled, last_login, created_at FROM users ORDER BY role, name')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="panel h-100">
            <h2 class="section-title">Create User</h2>
            <form method="post" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="create">
                <label class="form-label">Full Name</label>
                <input class="form-control mb-3" name="name" required>
                <label class="form-label">Email</label>
                <input class="form-control mb-3" type="email" name="email" required>
                <label class="form-label">Password</label>
                <input class="form-control mb-3" type="password" name="password" minlength="8" required>
                <label class="form-label">Role</label>
                <select class="form-select mb-3" name="role" required>
                    <?php foreach ($roles as $role): ?><option><?= e($role) ?></option><?php endforeach; ?>
                </select>
                <label class="form-label">Status</label>
                <select class="form-select mb-3" name="status">
                    <?php foreach ($statuses as $status): ?><option><?= e($status) ?></option><?php endforeach; ?>
                </select>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" name="mfa_enabled" id="createMfa" checked>
                    <label class="form-check-label" for="createMfa">MFA enabled</label>
                </div>
                <button class="btn btn-primary w-100"><i class="fa-solid fa-user-plus me-2"></i>Create User</button>
            </form>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="panel">
            <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center mb-3">
                <div>
                    <h2 class="section-title mb-1">RBAC Accounts</h2>
                    <p class="muted mb-0">Create, edit, and control dashboard access by role.</p>
                </div>
                <span class="badge badge-soft"><?= count($users) ?> users</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>MFA</th><th>Last Login</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?= e($user['name']) ?></strong><div class="muted small">Created <?= e($user['created_at']) ?></div></td>
                            <td><?= e($user['email']) ?></td>
                            <td><span class="badge badge-soft"><?= e($user['role']) ?></span></td>
                            <td><span class="status-badge <?= e($user['status']) ?>"><?= e($user['status']) ?></span></td>
                            <td><?= $user['mfa_enabled'] ? 'Enabled' : 'Disabled' ?></td>
                            <td><?= e($user['last_login'] ?: 'Never') ?></td>
                            <td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUser<?= (int) $user['id'] ?>"><i class="fa-solid fa-pen-to-square"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php foreach ($users as $user): ?>
<div class="modal fade" id="editUser<?= (int) $user['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content ssnms-modal" method="post">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input class="form-control" name="name" value="<?= e($user['name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" value="<?= e($user['email']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <?php foreach ($roles as $role): ?><option <?= $user['role'] === $role ? 'selected' : '' ?>><?= e($role) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <?php foreach ($statuses as $status): ?><option <?= $user['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">New Password</label>
                        <input class="form-control" type="password" name="password" minlength="8" placeholder="Leave blank to keep current password">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="mfa_enabled" id="mfa<?= (int) $user['id'] ?>" <?= $user['mfa_enabled'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="mfa<?= (int) $user['id'] ?>">MFA enabled</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
