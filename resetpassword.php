<?php
// resetpassword.php — public page for password reset with code (STEP 2)
require_once __DIR__ . '/inc/externalheader.php';

// If already logged in, go to index.php
if (is_logged_in()) {
    redirect_to('index.php');
}

$error = '';
$success = '';

// Handle password reset with code
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $reset_code = trim($_POST['reset_code'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($email) || empty($reset_code) || empty($new_password)) {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($new_password) < 4) {
        $error = 'Password must be at least 4 characters long.';
    } else {
        $pdo = get_db_connection();
        
        // Check if email and reset code match
        $stmt = $pdo->prepare("SELECT user_id, user_name FROM users WHERE user_email = ? AND user_resetcode = ? AND user_status = 1");
        $stmt->execute([$email, $reset_code]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Update password and clear reset code
            $hashed_password = md5($new_password);
            $stmt = $pdo->prepare("UPDATE users SET user_password = ?, user_resetcode = NULL WHERE user_id = ?");
           // --- inside your existing POST handler, replace the success branch with this ---
            if ($stmt->execute([$hashed_password, $user['user_id']])) {
                // --- Prepare email data ---
                $appName = getSettingValue('appname');
                $appUrl = getSettingValue('appurl');
                $organisation_signature = getSettingValue('appemailsignature');
                $loginLink = rtrim($appUrl ?? '', '/') . '/login.php';

                $subject = "Password Reset Successful - {$appName}";

                // Render the HTML body using template system
                $emailHtml = renderTemplate('email_password_reset_success', [
                    'user_name' => $user['user_name'],
                    'appName' => $appName,
                    'organisation_signature' => $organisation_signature,
                    'loginLink' => $loginLink
                ]);

                // Wrap in global email HTML wrapper
                $messageHtml = wrapEmailHtml($emailHtml, $subject);

                // Send (sendmail returns boolean)
                $sent = sendmail($email, $subject, $messageHtml, null, true);

                // Set messages shown on the page
                if ($sent) {
                    $success = 'Password reset successfully! A confirmation email has been sent. You can now log in with your new password.';
                } else {
                    // Password changed but email failed to send — still a success, but warn admin/user
                    $success = 'Password reset successfully! However, we were unable to send the confirmation email. Please contact the administrator if you need assistance.';
                    // Optional: write a debug log for admins
                    // file_put_contents(__DIR__.'/../mail_debug.log', "[".date('c')."] Password reset email failed to: {$email}\n", FILE_APPEND);
                }

                // Clear sensitive form vars (so inputs are blank on reload)
                $email = $reset_code = $new_password = $confirm_password = '';
            } else {
                $error = 'Failed to reset password. Please try again.';
            }




        } else {
            $error = 'Invalid email or reset code. Please check and try again.';
        }
    }
}

showExternalHeader('Reset Password');
?>

<div class="container my-5" style="max-width:480px;">
  <div class="card shadow-sm">
    <div class="card-header">
      <h5 class="card-title mb-0">Reset Password - Step 2</h5>
    </div>
    <div class="card-body">
      <div class="alert alert-info">
        <strong>Step 2 of 2:</strong> Enter the reset code sent to your email and your new password.
      </div>
      
      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      
      <form method="post" autocomplete="off">
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <input id="email" name="email" type="email" class="form-control" required 
                 value="<?= htmlspecialchars($email ?? '') ?>">
        </div>
        
        <div class="mb-3">
          <label for="reset_code" class="form-label">Reset Code</label>
          <input id="reset_code" name="reset_code" type="text" class="form-control" required 
                 value="<?= htmlspecialchars($reset_code ?? '') ?>" 
                 placeholder="Enter the 6-digit code sent to your email">
          <div class="form-text">Check your email for the 6-digit reset code.</div>
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
        
        <div class="d-flex justify-content-between align-items-center">
          <button class="btn btn-primary">Reset Password</button>
          <a href="forgotpassword.php" class="text-decoration-none">Need a reset code?</a>
          <a href="login.php" class="text-decoration-none ">Back to Login</a>
        </div>
      </form>
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
require_once __DIR__ . '/inc/externalfooter.php';
?>