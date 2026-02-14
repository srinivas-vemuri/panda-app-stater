<?php
// inc/external.php
// Helper functions for external pages (login, register, forgot password, etc.)

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
  <meta charset="utf-8">
  <title><?= htmlspecialchars($fullTitle) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- App CSS -->
  <link rel="stylesheet" href="inc/style.css">
</head>
<body class="bg-light">
    <?php
}

function showExternalFooter() {
    ?>
<!-- Bootstrap 5 JS (bundle) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    <?php
}
?>