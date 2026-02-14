<h2>New User Registration</h2>

<p>Hello Administrator,</p>

<p>A new user has registered on <strong><?= htmlspecialchars($appName ?? 'the application') ?></strong>:</p>

<table cellpadding="6" cellspacing="0" style="background:#f9f9f9;border-radius:5px;">
  <tr><td><strong>User ID:</strong></td><td><?= htmlspecialchars($user_id ?? '') ?></td></tr>
  <tr><td><strong>Username:</strong></td><td><?= htmlspecialchars($username ?? '') ?></td></tr>
  <tr><td><strong>Full Name:</strong></td><td><?= htmlspecialchars($name ?? '') ?></td></tr>
  <tr><td><strong>Email:</strong></td><td><?= htmlspecialchars($email ?? '') ?></td></tr>
  <tr><td><strong>Registration Date:</strong></td><td><?= htmlspecialchars($reg_date ?? date('Y-m-d H:i:s')) ?></td></tr>
</table>

<p>You can manage this user from the User Management panel.</p>

<p>Best regards,<br>
<?= htmlspecialchars($organisation_signature ?? $appName ?? 'Team') ?></p>
