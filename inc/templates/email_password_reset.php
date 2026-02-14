<h2>Password Reset Request</h2>

<p>Hello <?= htmlspecialchars($user_name ?? 'User') ?>,</p>

<p>We received a request to reset the password for your account at 
<strong><?= htmlspecialchars($appName ?? 'Our Application') ?></strong>.</p>

<p>Your reset code is:</p>

<div style="font-size:22px;font-weight:bold;color:#336699;padding:10px 15px;
            background:#f8f9fa;border-radius:8px;border:1px solid #ddd;
            display:inline-block;">
  <?= htmlspecialchars($reset_code ?? '') ?>
</div>

<p style="margin-top:20px;">
To reset your password, click the button below or go to the reset password page and enter this code along with your email address.
</p>

<p>
  <a href="<?= htmlspecialchars($resetLink ?? '#') ?>"
     style="background:#336699;color:#fff;padding:10px 20px;text-decoration:none;
            border-radius:6px;display:inline-block;">Reset Your Password</a>
</p>

<p style="margin-top:20px;">
If you did not request this, please ignore this email.
</p>

<p>Best regards,<br><?= htmlspecialchars($organisation_signature ?? $appName ?? 'Team') ?></p>
