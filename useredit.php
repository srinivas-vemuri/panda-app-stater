<?php
// useredit.php — admin only page for editing user details
require_once __DIR__ . '/inc/header.php';

// Only admins can access this page
require_admin_or_redirect();

$pdo = get_db_connection();
$error = '';
$success = '';

// Get user ID from URL
$edit_user_id = $_GET['user_id'] ?? '';

if (empty($edit_user_id)) {
    redirect_to('usermanagement.php');
}

// Fetch user details
$stmt = $pdo->prepare("SELECT user_id, user_username, user_name, user_email, user_type, user_status FROM users WHERE user_id = ?");
$stmt->execute([$edit_user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirect_to('usermanagement.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name = trim($_POST['user_name'] ?? '');
    $user_email = trim($_POST['user_email'] ?? '');
    $user_type = intval($_POST['user_type'] ?? 0);
    $user_status = intval($_POST['user_status'] ?? 0);
    
    // Validation
    if (empty($user_name) || empty($user_email)) {
        $error = 'Name and email are required.';
    } else {
        // Check if email already exists for another user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_email = ? AND user_id != ?");
        $stmt->execute([$user_email, $edit_user_id]);
        $email_exists = $stmt->fetchColumn();
        
        if ($email_exists) {
            $error = 'Email address already exists for another user.';
        } else {
            // Update user
            $sql = "UPDATE users SET user_name = ?, user_email = ?, user_type = ?, user_status = ? WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$user_name, $user_email, $user_type, $user_status, $edit_user_id])) {
                $success = 'User updated successfully!';
                // Refresh user data
                $stmt = $pdo->prepare("SELECT user_id, user_username, user_name, user_email, user_type, user_status FROM users WHERE user_id = ?");
                $stmt->execute([$edit_user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = 'Failed to update user. Please try again.';
            }
        }
    }
}
?>

<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Edit User</h5>
        <a href="usermanagement.php" class="btn btn-secondary btn-sm">← Back to User Management</a>
      </div>
      <div class="card-body">
        <?php if ($error): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="post">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="user_id" class="form-label">User ID</label>
                <input type="text" class="form-control" id="user_id" value="<?= htmlspecialchars($user['user_id']) ?>" readonly>
                <div class="form-text">User ID cannot be changed.</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="user_username" class="form-label">Username</label>
                <input type="text" class="form-control" id="user_username" value="<?= htmlspecialchars($user['user_username']) ?>" readonly>
                <div class="form-text">Username cannot be changed.</div>
              </div>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="user_name" class="form-label">Full Name *</label>
            <input type="text" class="form-control" id="user_name" name="user_name" 
                   value="<?= htmlspecialchars($user['user_name']) ?>" required>
          </div>
          
          <div class="mb-3">
            <label for="user_email" class="form-label">Email Address *</label>
            <input type="email" class="form-control" id="user_email" name="user_email" 
                   value="<?= htmlspecialchars($user['user_email']) ?>" required>
          </div>
          
          <div class="row">
            <div class="col-md-6">

                 <!-- // Replace the user_type select with:-->
            <div class="mb-3">
                <?php
                $currentUserType = $_SESSION['user_type'] ?? 0; // logged-in user
                $editingType = $user['user_type'] ?? 0;
                ?>
                <label for="user_type" class="form-label">User Type</label>
                <select class="form-select" id="user_type" name="user_type">
                    <option value="0" <?= $user['user_type'] == 0 ? 'selected' : '' ?>>Regular User</option>
                    <option value="1" <?= $user['user_type'] == 1 ? 'selected' : '' ?>>Admin</option>

                    <?php if ($currentUserType == 2): // only super admins can see or assign super admin ?>
                    <option value="2" <?= $editingType == 2 ? 'selected' : '' ?>>Super Admin</option>
                    <?php endif; ?>
                    
                </select>
                <div class="form-text">
                    <strong>Super Admin:</strong> Full system access + developer features<br>
                    <strong>Admin:</strong> User management + basic settings<br>
                    <strong>User:</strong> Normal application access
                </div>
            </div>

            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="user_status" class="form-label">Account Status</label>
                <select class="form-select" id="user_status" name="user_status">
                  <option value="1" <?= $user['user_status'] == 1 ? 'selected' : '' ?>>Active</option>
                  <option value="0" <?= $user['user_status'] == 0 ? 'selected' : '' ?>>Inactive</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="d-flex justify-content-between align-items-center">
            <button type="submit" class="btn btn-primary">Update User</button>
            <a href="usermanagement.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
    
    <!-- User Information Card -->
    <div class="card mt-4 shadow-sm">
      <div class="card-header">
        <h6 class="card-title mb-0">User Information</h6>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <strong>User ID:</strong> <code><?= htmlspecialchars($user['user_id']) ?></code>
          </div>
          <div class="col-md-6">
            <strong>Username:</strong> <?= htmlspecialchars($user['user_username']) ?>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col-md-6">
            <strong>Current Type:</strong> 
            <?php if ($user['user_type'] == 1): ?>
              <span class="badge bg-danger">Administrator</span>
            <?php else: ?>
              <span class="badge bg-secondary">Regular User</span>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <strong>Current Status:</strong> 
            <?php if ($user['user_status'] == 1): ?>
              <span class="badge bg-success">Active</span>
            <?php else: ?>
              <span class="badge bg-warning">Inactive</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/inc/footer.php';
?>