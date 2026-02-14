<?php
// changepassword.php — protected page for password change
require_once __DIR__ . '/inc/header.php';

$error = '';
$success = '';

// Handle password change submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $reset_code = $_POST['reset_code'] ?? '';
    
    $pdo = get_db_connection();
    
    // Get current user info
    $stmt = $pdo->prepare("SELECT user_password, user_resetcode FROM users WHERE user_id = ?");
    $stmt->execute([get_current_user_id()]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!empty($reset_code)) {
        // Using reset code flow
        if ($reset_code !== $user['user_resetcode']) {
            $error = 'Invalid reset code.';
        } elseif (empty($new_password)) {
            $error = 'New password is required.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_password) < 4) {
            $error = 'New password must be at least 4 characters long.';
        } else {
            // Update password and clear reset code
            $hashed_password = md5($new_password);
            $stmt = $pdo->prepare("UPDATE users SET user_password = ?, user_resetcode = NULL WHERE user_id = ?");
            if ($stmt->execute([$hashed_password, get_current_user_id()])) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password. Please try again.';
            }
        }
    } else {
        // Normal password change flow
        if (empty($old_password) || empty($new_password)) {
            $error = 'All fields are required.';
        } elseif (md5($old_password) !== $user['user_password']) {
            $error = 'Current password is incorrect.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_password) < 4) {
            $error = 'New password must be at least 4 characters long.';
        } else {
            // Update password
            $hashed_password = md5($new_password);
            $stmt = $pdo->prepare("UPDATE users SET user_password = ? WHERE user_id = ?");
            if ($stmt->execute([$hashed_password, get_current_user_id()])) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password. Please try again.';
            }
        }
    }
}
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header">
        <h5 class="card-title mb-0">Change Password</h5>
      </div>
      <div class="card-body">
        <?php if ($error): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="post" autocomplete="off">
          <!-- Normal Password Change -->
          <div class="mb-3">
            <label for="old_password" class="form-label">Current Password</label>
            <input id="old_password" name="old_password" type="password" class="form-control">
          </div>
          
          <div class="mb-3">
            <label for="new_password" class="form-label">New Password</label>
            <input id="new_password" name="new_password" type="password" class="form-control" required>
            <div class="form-text">Minimum 4 characters.</div>
          </div>
          
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <input id="confirm_password" name="confirm_password" type="password" class="form-control" required>
          </div>
          
          <hr>
          
          <!-- Reset Code Option -->
          <div class="mb-3">
            <label for="reset_code" class="form-label">Or use reset code (if you requested one)</label>
            <input id="reset_code" name="reset_code" type="text" class="form-control" placeholder="Enter 6-digit reset code">
          </div>
          
          <div class="d-flex justify-content-between align-items-center">
            <button type="submit" class="btn btn-primary">Change Password</button>
            <a href="forgotpassword.php" class="text-decoration-none">Forgot Password?</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Real-time password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && password !== confirmPassword) {
        this.classList.add('is-invalid');
        this.classList.remove('is-valid');
    } else if (confirmPassword) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    }
});
</script>

<?php
require_once __DIR__ . '/inc/footer.php';
?>