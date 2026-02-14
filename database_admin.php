<?php
// database_admin.php — admin only database management
require_once __DIR__ . '/inc/header.php';

// Only admins can access this page
require_admin_or_redirect();

//echo '<iframe src="phpliteadmin.php" style="width:100%; height:1000px;"></iframe>';
?>

<?php
include ('phpliteadmin.php');
require_once __DIR__ . '/inc/footer.php';
?>