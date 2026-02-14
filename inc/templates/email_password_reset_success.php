<h2>Password Reset Successful</h2>

<p>Hello <?= htmlspecialchars($user_name ?? 'User') ?>,</p>

<p>Your password for <strong><?= htmlspecialchars($appName ?? 'our application') ?></strong> has been successfully reset.</p>

<p>If you did not perform this action, please contact us immediately.</p>

<p style="margin-top:20px;">
  <a href="<?= htmlspecialchars($loginLink ?? '#') ?>"
     style="background:#336699;color:#fff;padding:10px 20px;text-decoration:none;
            border-radius:6px;display:inline-block;">Login to Your Account</a>
</p>

<p style="margin-top:20px;">Best regards,<br><?= htmlspecialchars($organisation_signature ?? $appName ?? 'Team') ?></p>
