<?php
// ============================================================
//  includes/bootstrap.php — inclui tudo que toda página precisa
// ============================================================
require_once dirname(__DIR__) . '/config/app.php';
require_once ROOT_PATH . '/config/db.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/auth.php';
require_once ROOT_PATH . '/includes/layout.php';

// Erros fatais não tratados: loga o detalhe no error_log do servidor e
// mostra uma página amigável em vez do erro 500 seco do Apache.
set_exception_handler(function (Throwable $e): void {
    error_log('[Gamifica][Fatal] ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (APP_DEBUG) {
        echo '<pre>' . htmlspecialchars((string)$e) . '</pre>';
        exit;
    }
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>Erro — Gamifica</title></head>'
       . '<body style="font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:90vh;background:#f0eff5;">'
       . '<div style="background:#fff;border-radius:16px;padding:2.5rem;max-width:460px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.08);">'
       . '<div style="font-size:48px;">⚙️</div>'
       . '<h1 style="font-size:20px;color:#1a1a2e;">Algo deu errado do nosso lado</h1>'
       . '<p style="color:#888;font-size:14px;">O erro foi registrado. Se você é o administrador, '
       . 'acesse <strong>diagnostico.php</strong> para ver o que falta no ambiente '
       . '(geralmente uma tabela ou view do banco) e consulte o error_log no cPanel.</p>'
       . '<a href="' . htmlspecialchars(BASE_URL) . '/" style="color:#7c6ef0;font-weight:700;">← Voltar ao início</a>'
       . '</div></body></html>';
    exit;
});

session_iniciar();
