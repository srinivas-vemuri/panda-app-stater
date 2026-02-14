<h2>Hello <?= htmlspecialchars($name ?? 'Friend') ?> 👋</h2>
<p>Welcome to <strong><?= htmlspecialchars($appName ?? 'Panda PHP App') ?></strong>!</p>

<p>This is a <em>test email</em> sent using your new templating system.  
If you can read this message with grey background and a white box, everything’s working beautifully.</p>

<p>Here’s a quick variable test:</p>
<ul>
  <li><strong>Username:</strong> <?= htmlspecialchars($username ?? 'Not provided') ?></li>
  <li><strong>Test code:</strong> <?= htmlspecialchars($code ?? 'N/A') ?></li>
</ul>

<p style="margin-top:20px;">Best,<br><?= htmlspecialchars($organisation_signature ?? $appName ?? 'Panda PHP') ?></p>
