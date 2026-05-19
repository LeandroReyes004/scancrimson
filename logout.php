<?php
define('AUTH_NO_GUARD', 1);
require_once __DIR__ . '/auth.php';
auth_clear();
header('Location: login.php');
exit;
