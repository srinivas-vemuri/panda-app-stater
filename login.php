<?php
// login.php — public page
require_once __DIR__ . '/inc/externalheader.php';


// Check if system needs installation - BUT only if database doesn't exist
$db_file = __DIR__ . '/data.sqlite';
if (!file_exists($db_file)) {
    redirect_to('install.php');
}

// If already logged in, go to index.php
if (is_logged_in()) {
    redirectToAppStart(); // send logged-in user to configured start page
}


$error = '';

// Handle login submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } elseif (do_login($username, $password)) {
        redirect_to('index.php');
    } else {
        // Check what type of error occurred
        if (isset($_SESSION['login_error'])) {
            if ($_SESSION['login_error'] === 'inactive') {
                $error = 'Your credentials are correct, but your account is currently inactive. Please contact the administrator.';
            } else {
                $error = 'Invalid username/email or password.';
            }
            // Clear the session error
            unset($_SESSION['login_error']);
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}

showExternalHeader('Login');
?>

<div class="container my-5" style="max-width:480px; margin-top: 1rem !important;">
  <div class="card shadow-sm">
    <div class="card-header">Login</div>
    <div class="card-body">
      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post" autocomplete="off">
        <div class="mb-3">
          <label for="username" class="form-label">Username or Email</label>
          <input id="username" name="username" type="text" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input id="password" name="password" type="password" class="form-control" required>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <button class="btn btn-primary">Login</button>
          <a href="forgotpassword.php" class="text-decoration-none">Forgot Password?</a>
        </div>
        <div class="mt-3 text-center">
          <a href="register.php" class="text-decoration-none">Don't have an account? Register</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/inc/externalfooter.php';
?>