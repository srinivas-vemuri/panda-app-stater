<h2>Welcome, <?= htmlspecialchars($name ?? 'User') ?>!</h2>
<p>Thank you for registering with <?= htmlspecialchars($appName ?? getSettingValue('appname')) ?>.</p>
<p>Your username is <strong><?= htmlspecialchars($username ?? '') ?></strong>.</p>
<p>You can now log in and start using the app.</p>
<p>Best regards,<br><?= htmlspecialchars($organisation_signature ?? getSettingValue('appname')) ?></p>
