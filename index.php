<?php
// index.php — redireciona para o módulo do usuário ou para o login
require_once __DIR__ . '/includes/bootstrap.php';

if (esta_logado()) {
    redirect(REDIRECT_PERFIL[usuario_perfil()] ?? BASE_URL . '/login.php');
}
redirect(BASE_URL . '/login.php');
