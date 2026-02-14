<?php
// inc/header.php
// Includes + auth guard + page <head> + navbar + opens container (sticky footer compatible)

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';


// Check if system needs installation - BUT only if database doesn't exist
$db_file = __DIR__ . '/../data.sqlite';
if (!file_exists($db_file) && basename($_SERVER['PHP_SELF']) !== 'install.php') {
    redirect_to('install.php');
}

require_login_or_redirect();
?><!doctype html>
<html lang="en">
<head>
    <?php includePWAAssets(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- App CSS -->
  <link rel="stylesheet" href="inc/style.css">
  <!-- Optional custom JS -->
  <script src="inc/custom.js" defer></script>
</head>

<!-- Flex column layout for sticky footer -->
<body class="d-flex flex-column min-vh-100">

    <nav <?= renderNavClassStyle(); ?>>

  <div class="container-fluid">
        <?php
        $basic = getSettings('basic');
        $appURL = rtrim($basic['appurl']['value'] ?? '', '/');
        $linkHref = $appURL ? $appURL : '#';
        $appName = getSettingValue('appname') ?: 'Your App Name';
        ?>
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= htmlspecialchars($linkHref) ?>" style="text-decoration:none;">
        <?php renderSmallHeaderLogo(); ?>
        <span style="font-weight:600; font-size:1.1rem;"><?= htmlspecialchars($appName) ?></span>
        </a>

    <!-- Custom toggle (no Bootstrap collapse) -->
    <button
      id="menuToggle"
      class="navbar-toggler ms-auto"
      type="button"
      aria-label="Toggle menu"
      aria-controls="quickMenu"
      aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>
  </div>
</nav>

<!-- Floating overlay menu panel (does not stretch header) -->
<div id="quickMenu" class="menu-panel" aria-hidden="true">
  <ul class="list-group list-group-flush">
    <li><a class="list-group-item list-group-item-action" href="index.php">Home</a></li>
    <li><a class="list-group-item list-group-item-action" href="changepassword.php">Change Password</a></li>
    
    <?php if (is_admin()): ?>
    <li><a class="list-group-item list-group-item-action" href="usermanagement.php">User Management</a></li>
    <li><a class="list-group-item list-group-item-action" href="database_admin.php">SQLite Admin</a></li>
    <li><a class="list-group-item list-group-item-action" href="settings.php">App Settings</a></li>
    <?php endif; ?>
    <?php echo renderMobileNavigation();?>
    <li><a class="list-group-item list-group-item-action" href="logout.php">Logout</a></li>
  </ul>
</div>

<!-- Open main container; grows to push footer down -->
<div class="container my-4 flex-grow-1">