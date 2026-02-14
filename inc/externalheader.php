<?php
// inc/externalheader.php
// Single include for all external pages - handles all requirements

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/email.php';

function showExternalHeader($pageTitle = '') {
    global $siteurl;
    
    $fullTitle = $siteurl;
    if (!empty($pageTitle)) {
        $fullTitle .= ' - ' . $pageTitle;
    }
    ?>
<!doctype html>
<html lang="en">
<head>
  <?php includePWAAssets(); ?>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- App CSS -->
  <link rel="stylesheet" href="inc/style.css">
</head>
<body class="bg-light">
    <?php renderHeaderLogo(); ?>
<?php
}
?>