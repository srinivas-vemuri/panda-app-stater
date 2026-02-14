<?php
// register.php — public page for user registration
require_once __DIR__ . '/inc/externalheader.php';



// If already logged in, go to index.php
if (is_logged_in()) {
    redirect_to('index.php');
}

$error = '';
$success = '';

// Handle registration submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $error = 'Username can only contain letters and numbers (no spaces or special characters).';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters long.';
    } else {
        $pdo = get_db_connection();
        
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_username = ? OR user_email = ?");
        $stmt->execute([$username, $email]);
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            $error = 'Username or email already exists.';
        } else {
            // Create new user
            $user_id = generate_unique_user_id($pdo);
            $hashed_password = md5($password);
            
            $sql = "INSERT INTO users (user_id, user_username, user_name, user_email, user_password, user_type, user_status) 
                    VALUES (?, ?, ?, ?, ?, 0, 0)";
            
            $stmt = $pdo->prepare($sql);
                    
              if ($stmt->execute([$user_id, $username, $name, $email, $hashed_password])) {
                // --- Prepare shared data ---
                $appName = getSettingValue('appname');
                $appUrl = getSettingValue('appurl');
                $organisation_signature = getSettingValue('appemailsignature');

                // --- User welcome email ---
                $user_subject = "Welcome to {$appName}";
                $user_html = renderTemplate('email_user_welcome', [
                    'name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'appName' => $appName,
                    'appUrl' => $appUrl,
                    'organisation_signature' => $organisation_signature
                ]);
                $user_message = wrapEmailHtml($user_html, $user_subject);
                sendmail($email, $user_subject, $user_message, null, true);

                // --- Admin notification ---
                $admin_emails = get_admin_emails();
                if (!empty($admin_emails)) {
                    $admin_subject = "New User Registration - {$appName}";
                    $admin_html = renderTemplate('email_admin_new_user', [
                        'user_id' => $user_id,
                        'username' => $username,
                        'name' => $name,
                        'email' => $email,
                        'reg_date' => date('Y-m-d H:i:s'),
                        'appName' => $appName,
                        'organisation_signature' => $organisation_signature
                    ]);
                    $admin_message = wrapEmailHtml($admin_html, $admin_subject);

                    foreach ($admin_emails as $admin_email) {
                        sendmail($admin_email, $admin_subject, $admin_message, null, true);
                    }
                }

                $success = 'Registration successful! A welcome email has been sent to your email address. You can now login.';
                $username = $name = $email = '';
              } else {
                  $error = 'Registration failed. Please try again.';
              }


        }
    }
}

showExternalHeader('Register');

if (!isRegistrationActive()) {
    //require_once __DIR__ . '/inc/externalheader.php';
    echo '<div class="container py-5 text-center">';
    echo '<div class="alert alert-warning">';
    echo '🛑 Signup / Registration is currently turned off by the administrator.';
    echo '</div></div>';
    require_once __DIR__ . '/inc/externalfooter.php';
    exit;
}


?>

<div class="container my-5" style="max-width:480px;">
  <div class="card shadow-sm">
    <div class="card-header">Register</div>
    <div class="card-body">
      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <form method="post" autocomplete="off" id="registerForm">
        <div class="mb-3">
          <label for="username" class="form-label">Username *</label>
          <input id="username" name="username" type="text" class="form-control" required 
                 value="<?= htmlspecialchars($username ?? '') ?>"
                 onblur="validateUsername(this)">
          <div class="form-text">Only letters and numbers, no spaces or special characters.</div>
        </div>
        <div class="mb-3">
          <label for="name" class="form-label">Full Name *</label>
          <input id="name" name="name" type="text" class="form-control" required 
                 value="<?= htmlspecialchars($name ?? '') ?>">
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email *</label>
          <input id="email" name="email" type="email" class="form-control" required 
                 value="<?= htmlspecialchars($email ?? '') ?>">
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
        <div class="d-flex justify-content-between align-items-center">
          <button type="submit" class="btn btn-primary">Register</button>
          <a href="login.php" class="text-decoration-none">Already have an account? Login</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function validateUsername(input) {
    const username = input.value.trim();
    const usernameRegex = /^[a-zA-Z0-9]+$/;
    
    if (username && !usernameRegex.test(username)) {
        input.classList.add('is-invalid');
        let errorDiv = input.nextElementSibling;
        if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            input.parentNode.insertBefore(errorDiv, input.nextSibling);
        }
        errorDiv.textContent = 'Username can only contain letters and numbers (no spaces or special characters).';
    } else {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    }
}

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

<?php
require_once __DIR__ . '/inc/externalfooter.php';
?>