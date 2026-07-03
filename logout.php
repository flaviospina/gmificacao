<?php
// logout.php
require_once __DIR__ . '/includes/bootstrap.php';
logout();
header('Location: ' . BASE_URL . '/login.php?msg=logout');
exit;
