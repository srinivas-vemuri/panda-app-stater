<?php
// index.php
require_once __DIR__ . '/inc/header.php';
?>
  <div class="card shadow-sm">
This is the new page where the photos will show.
<?php var_dump(getSettingValue('appadminemail'), getSettingValue('appurl'), getSettingValue('appemailsignature')); exit;

?>
  </div>
<?php
require_once __DIR__ . '/inc/footer.php';