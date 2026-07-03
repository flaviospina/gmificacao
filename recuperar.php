<?php
// recuperar.php — recuperação de senha (via administrador)
require_once __DIR__ . '/includes/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar senha — <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
<style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#1a1a2e,#16213e,#0f3460);}</style>
</head>
<body>
<div class="card" style="text-align:center;max-width:430px;padding:2.5rem;">
  <div style="font-size:44px;margin-bottom:10px;">🔑</div>
  <h1 style="font-size:20px;margin-bottom:8px;">Esqueceu sua senha?</h1>
  <p style="color:#888;font-weight:600;font-size:14px;margin-bottom:8px;">
    Se você entra com o Google Workspace, use o botão <strong>"Entrar com Google"</strong> — não precisa de senha.
  </p>
  <p style="color:#888;font-weight:600;font-size:14px;margin-bottom:18px;">
    Se usa e-mail e senha, peça ao <strong>administrador da sua escola</strong> para redefinir sua senha.
    Você receberá uma senha temporária e trocará no primeiro acesso.
  </p>
  <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">← Voltar ao login</a>
</div>
</body>
</html>
