<?php
// erro.php — página de erro amigável (403/404/500)
require_once __DIR__ . '/config/app.php';
$cod  = (int)($_GET['cod'] ?? 404);
$msgs = [
    403 => ['⛔', 'Acesso negado', 'Você não tem permissão para acessar este recurso.'],
    404 => ['🧭', 'Página não encontrada', 'O caminho que você tentou acessar não existe.'],
    500 => ['⚙️', 'Erro interno', 'Algo deu errado do nosso lado. Tente novamente em instantes.'],
];
[$icone, $titulo, $texto] = $msgs[$cod] ?? $msgs[404];
http_response_code(in_array($cod, [403, 404, 500], true) ? $cod : 404);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $titulo ?> — <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
<style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;}</style>
</head>
<body>
<div class="card" style="text-align:center;max-width:420px;padding:2.5rem;">
  <div style="font-size:52px;margin-bottom:10px;"><?= $icone ?></div>
  <h1 style="font-size:22px;margin-bottom:6px;"><?= $titulo ?> (<?= $cod ?>)</h1>
  <p style="color:#888;font-weight:600;font-size:14px;margin-bottom:18px;"><?= $texto ?></p>
  <a href="<?= BASE_URL ?>/" class="btn btn-primary">Voltar ao início →</a>
</div>
</body>
</html>
