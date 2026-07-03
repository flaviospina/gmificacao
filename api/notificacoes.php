<?php
// api/notificacoes.php — marca todas as notificações do usuário como lidas
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_perfil(PERFIS);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    db()->prepare('UPDATE notificacoes SET lida = 1 WHERE usuario_id = ?')->execute([usuario_id()]);
}
redirect($_SERVER['HTTP_REFERER'] ?? (REDIRECT_PERFIL[usuario_perfil()] ?? BASE_URL));
