<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // A date in the past

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// install.php — first-time system setup


require_once __DIR__ . '/inc/externalheader.php';

// Check if system is already installed
$db_file = __DIR__ . 'data.sqlite';
if (file_exists($db_file)) {
    // System already installed, redirect to login
    redirect_to('login.php');
}

$error = '';
$success = '';
$installation_complete = false;

// Handle installation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    
    // Validation
    if (empty($email) || empty($password) || empty($full_name)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters long.';
    } else {
        // Initialize database and create first super admin
        require_once __DIR__ . '/inc/database.php';
        require_once __DIR__ . '/inc/email.php';
        init_database();
        $pdo = get_db_connection();
        
        // Create first user (super admin - type 2)
        $user_id = generate_unique_user_id($pdo);
        $hashed_password = md5($password);
        $username = 'superadmin'; // Default username for first user
        
        $sql = "INSERT INTO users (user_id, user_username, user_name, user_email, user_password, user_type, user_status) 
                VALUES (?, ?, ?, ?, ?, 2, 1)";
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$user_id, $username, $full_name, $email, $hashed_password])) {
            $installation_complete = true;
            $success = 'System installed successfully! Redirecting to login...';
            
            // Auto-redirect after 3 seconds
            header('Refresh: 3; URL=login.php');
        } else {
            $error = 'Installation failed. Please try again.';
        }
    }
}

// Only show header if not redirecting
if (!$installation_complete) {
    showExternalHeader('System Installation');
}
?>

<?php if (!$installation_complete): ?>
<div class="container my-5" style="max-width:480px;">
  <div class="card shadow-sm">
    <div class="card-header bg-success text-white">
      <h5 class="card-title mb-0">🐼 Panda PHP - First Time Setup</h5>
    </div>
    <div class="card-body">
      <div class="alert alert-info">
        <strong>Welcome!</strong> This appears to be your first time running Panda PHP. 
        Let's create your super administrator account.
      </div>
      
      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      
      <form method="post" autocomplete="off">
        <div class="mb-3">
          <label for="full_name" class="form-label">Your Full Name *</label>
          <input id="full_name" name="full_name" type="text" class="form-control" required 
                 value="<?= htmlspecialchars($full_name ?? '') ?>">
        </div>
        
        <div class="mb-3">
          <label for="email" class="form-label">Email Address *</label>
          <input id="email" name="email" type="email" class="form-control" required 
                 value="<?= htmlspecialchars($email ?? '') ?>">
          <div class="form-text">This will be your login email and system admin contact.</div>
        </div>
        
        <div class="mb-3">
          <label for="password" class="form-label">Password *</label>
          <input id="password" name="password" type="password" class="form-control" required>
          <div class="form-text">Minimum 4 characters.</div>
        </div>
        
        <div class="mb-3">
          <label for="confirm_password" class="form-label">Confirm Password *</label>
          <input id="confirm_password" name="confirm_password" type="password" class="form-control" required>
        </div>
        
        <div class="alert alert-warning">
          <strong>Super Admin Account Details:</strong>
          <ul class="mb-0 mt-1">
            <li><strong>Username:</strong> superadmin (this will be your login username)</li>
            <li><strong>Email:</strong> The email you enter above</li>
            <li><strong>Access Level:</strong> Full system access + developer features</li>
          </ul>
        </div>
        
        <button type="submit" class="btn btn-success btn-lg w-100">Complete Installation</button>
      </form>
    </div>
  </div>
</div>

<script>
// Real-time password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
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

<?php else: ?>
<!-- Success page with redirect -->
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Installation Complete - Panda PHP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5" style="max-width:480px;">
  <div class="card shadow-sm">
    <div class="card-header bg-success text-white">
      <h5 class="card-title mb-0">✅ Installation Complete</h5>
    </div>
    <div class="card-body text-center">
      <div class="alert alert-success">
        <?= htmlspecialchars($success) ?>
      </div>
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Redirecting...</span>
      </div>
      <p class="mt-2">Redirecting to login page...</p>
      <p class="text-muted small">If you are not redirected, <a href="login.php">click here</a>.</p>
    </div>
  </div>
</div>
</body>
</html>
<?php endif; ?>

<?php

require_once __DIR__ . '/inc/externalfooter.php';

?>