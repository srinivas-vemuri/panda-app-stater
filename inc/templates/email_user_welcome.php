<h2>Welcome, <?= htmlspecialchars($name ?? 'User') ?> 👋</h2>

<p>Thank you for registering with <strong><?= htmlspecialchars($appName ?? 'Our Application') ?></strong>!</p>

<p>Your account has been created successfully.</p>

<table cellpadding="6" cellspacing="0" style="background:#f9f9f9;border-radius:5px;">
  <tr><td><strong>Username:</strong></td><td><?= htmlspecialchars($username ?? '') ?></td></tr>
  <tr><td><strong>Email:</strong></td><td><?= htmlspecialchars($email ?? '') ?></td></tr>
</table>

<p>You can now log in and start using our services:</p>
<p>
  <a href="<?= htmlspecialchars($appUrl ?? '#') ?>/login.php"
     style="background:#336699;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;">
     Login Now
  </a>
</p>

<p style="margin-top:20px;">If you have any questions, please contact us.<br>
Best regards,<br>
<?= htmlspecialchars($organisation_signature ?? $appName ?? 'Team') ?></p>
