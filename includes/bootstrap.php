<?php
// ============================================================
//  includes/bootstrap.php — inclui tudo que toda página precisa
// ============================================================
require_once dirname(__DIR__) . '/config/app.php';
require_once ROOT_PATH . '/config/db.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/auth.php';
require_once ROOT_PATH . '/includes/layout.php';

session_iniciar();
