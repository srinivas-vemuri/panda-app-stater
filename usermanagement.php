<?php
// usermanagement.php — admin only page for user management
require_once __DIR__ . '/inc/header.php';

// Only admins can access this page
require_admin_or_redirect();

$pdo = get_db_connection();
$error = '';
$success = '';

// Handle user status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    
    // Prevent admin from deactivating themselves
    if ($user_id === get_current_user_id()) {
        $error = 'You cannot modify your own account status.';
    } else {
        $new_status = ($action === 'activate') ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE users SET user_status = ? WHERE user_id = ?");
        if ($stmt->execute([$new_status, $user_id])) {
            $success = 'User status updated successfully!';
        } else {
            $error = 'Failed to update user status.';
        }
    }
}

// Get all users
$stmt = $pdo->prepare("SELECT user_id, user_username, user_name, user_email, user_type, user_status, user_createdate FROM users ORDER BY user_createdate DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">User Management</h5>
    <span class="badge bg-primary">Admin</span>
  </div>
  <div class="card-body">
    <?php if ($error): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>User ID</th>
            <th>Username</th>
            <th>Name</th>
            <th>Email</th>
            <th>Type</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>

          <tr>
            <td><code><?= htmlspecialchars($user['user_id']) ?></code></td>
            <td><?= htmlspecialchars($user['user_username']) ?></td>
            <td><?= htmlspecialchars($user['user_name']) ?></td>
            <td><?= htmlspecialchars($user['user_email']) ?></td>
            <td>
              <?php if ($user['user_type'] == 2): ?>
              <span class="badge bg-danger">Super Admin</span>
              <?php elseif ($user['user_type'] == 1): ?>
              <span class="badge bg-warning">Admin</span>
              <?php else: ?>
              <span class="badge bg-secondary">User</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($user['user_status'] == 1): ?>
                <span class="badge bg-success">Active</span>
              <?php else: ?>
                <span class="badge bg-warning">Inactive</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars(date('Y-m-d', strtotime($user['user_createdate']))) ?></td>
    
                <td>
                <?php if ($user['user_id'] !== get_current_user_id()): ?>
                    <div class="btn-group btn-group-sm" role="group">
                    <a href="useredit.php?user_id=<?= htmlspecialchars($user['user_id']) ?>" class="btn btn-outline-primary">Edit</a>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['user_id']) ?>">
                        <?php if ($user['user_status'] == 1): ?>
                        <button type="submit" name="action" value="deactivate" class="btn btn-outline-warning" 
                                onclick="return confirm('Are you sure you want to deactivate this user?')">
                            Deactivate
                        </button>
                        <?php else: ?>
                        <button type="submit" name="action" value="activate" class="btn btn-outline-success">
                            Activate
                        </button>
                        <?php endif; ?>
                    </form>
                    </div>
                <?php else: ?>
                    <span class="text-muted">Current User</span>
                <?php endif; ?>
                </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    
    <?php if (empty($users)): ?>
      <div class="text-center py-4">
        <p class="text-muted">No users found.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
require_once __DIR__ . '/inc/footer.php';
?>