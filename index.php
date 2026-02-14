<?php
// index.php
require_once __DIR__ . '/inc/header.php';
?>
  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="card-title mb-3">Welcome, <?= htmlspecialchars($_SESSION['user_username'] ?? 'User') ?>!</h5>
      
      <?php if (is_admin()): ?>
      <div class="alert alert-info">
        <strong>Admin Access:</strong> You have administrator privileges.
      </div>
      <?php endif; ?>
      
      <div class="row">
        <div class="col-md-6">
          <div class="card">
            <div class="card-body">
              <h6 class="card-title">Quick Actions</h6>
              <div class="d-grid gap-2">
                <a href="changepassword.php" class="btn btn-outline-primary" style="min-width:320px;max-width:320px;">Change Password</a>
                <?php if (is_admin()): ?>
                <a href="usermanagement.php" class="btn btn-outline-danger" style="min-width:320px;max-width:320px;">Manage Users</a>
                <?php endif; ?>

                <?php renderDesktopNavigation();?>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-body">
              <h6 class="card-title">Your Info</h6>
              <ul class="list-unstyled">
                <li><strong>User ID:</strong> <code><?= htmlspecialchars(get_current_user_id()) ?></code></li>
                <li><strong>Username:</strong> <?= htmlspecialchars($_SESSION['user_username'] ?? 'N/A') ?></li>
                <li><strong>Role:</strong> <?php renderUserRoleLabel(); ?></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php
require_once __DIR__ . '/inc/footer.php';