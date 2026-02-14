<?php
// forgotpassword.php — public page for password recovery
require_once __DIR__ . '/inc/externalheader.php';

// If already logged in, go to index.php
if (is_logged_in()) {
    redirect_to('index.php');
}

$error = '';
$success = '';

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $pdo = get_db_connection();
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT user_id, user_username, user_name FROM users WHERE user_email = ? AND user_status = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generate 6-digit alphanumeric reset code
            $reset_code = generate_reset_code();
            
            // Update user record with reset code
            $stmt = $pdo->prepare("UPDATE users SET user_resetcode = ? WHERE user_id = ?");
            $stmt->execute([$reset_code, $user['user_id']]);
            
           
                    // Prepare email details
                    $appName = getSettingValue('appname');
                    $appUrl = getSettingValue('appurl');
                    $organisation_signature = getSettingValue('appemailsignature');

                    // Build reset link dynamically
                    $resetLink = rtrim($appUrl, '/') . '/resetpassword.php';

                    $subject = "Password Reset Request - {$appName}";

                    // Render template
                    $emailHtml = renderTemplate('email_password_reset', [
                        'user_name' => $user['user_name'],
                        'reset_code' => $reset_code,
                        'resetLink' => $resetLink,
                        'appName' => $appName,
                        'organisation_signature' => $organisation_signature
                    ]);

                    // Wrap in global HTML email design
                    $message = wrapEmailHtml($emailHtml, $subject);

                    // Send email
                    if (sendmail($email, $subject, $message, null, true)) {
                        $success = 'Password reset instructions have been sent to your email.';
                    } else {
                        $error = 'Failed to send reset email. Please try again later.';
                    }


        } else {
            $error = 'Email address not found or account is inactive.';
        }
    }
}

function generate_reset_code() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

showExternalHeader('Forgot Password');
?>

<div class="container my-5" style="max-width:480px;">
  <div class="card shadow-sm">
    <div class="card-header">Forgot Password</div>
    <div class="card-body">
      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <form method="post" autocomplete="off">
        <div class="mb-3">
          <label for="email" class="form-label">Enter your email address</label>
          <input id="email" name="email" type="email" class="form-control" required 
                 value="<?= htmlspecialchars($email ?? '') ?>">
          <div class="form-text">We'll send a password reset code to your email.</div>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <button class="btn btn-primary">Send Reset Code</button>
          <a href="login.php" class="text-decoration-none">Back to Login</a>
       

        </div>
      </form>
        <div class="mt-3 text-center">
        <a href="resetpassword.php" class="text-decoration-none">Already have a reset code? Reset Password</a>
        </div>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/inc/externalfooter.php';
?>