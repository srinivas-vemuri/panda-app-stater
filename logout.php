<?php
// logout.php — destroys session and redirects to login
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';

do_logout();
redirect_to('login.php');
