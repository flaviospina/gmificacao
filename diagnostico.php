<?php
// ============================================================
//  diagnostico.php — Autoteste do ambiente (APAGUE APÓS O USO)
//  Verifica exatamente o que causa "sessão expirada":
//  cookie, gravação de sessão, banco e URL base.
// ============================================================
require_once __DIR__ . '/includes/bootstrap.php';

$agora = time();

// Passo 1: grava um valor na sessão e manda recarregar.
// Passo 2 (?v=1): confere se o valor sobreviveu à recarga.
$verificando = isset($_GET['v']);
if (!$verificando) {
    $_SESSION['diag_teste'] = $agora;
}
$persistiu = $verificando && !empty($_SESSION['diag_teste']);

// Checagens do ambiente
$checks = [];
$checks['Versão do PHP'] = [PHP_VERSION, version_compare(PHP_VERSION, '8.1.0', '>=')];
$checks['Extensão pdo_mysql'] = [extension_loaded('pdo_mysql') ? 'carregada' : 'AUSENTE', extension_loaded('pdo_mysql')];

try {
    $n = db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    $checks['Conexão com o banco'] = ["ok ({$n} usuários)", true];
} catch (Throwable $e) {
    $checks['Conexão com o banco'] = ['FALHOU', false];
}

try {
    $n = db()->query('SELECT COUNT(*) FROM sessoes')->fetchColumn();
    $checks['Tabela sessoes (sessões no banco)'] = ["ok ({$n} sessão(ões) gravada(s))", true];
} catch (Throwable $e) {
    $checks['Tabela sessoes (sessões no banco)'] = ['não existe ainda — será criada no 1º login', true];
}

$checks['BASE_URL detectada'] = [BASE_URL, true];
$checks['Host acessado'] = [$_SERVER['HTTP_HOST'] ?? '—', true];
$checks['HTTPS visto pelo PHP'] = [(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'sim' : 'não (' . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'sem X-Forwarded-Proto') . ')', true];
$checks['Cookie de sessão recebido'] = [isset($_COOKIE[SESSION_NAME]) ? 'sim (' . SESSION_NAME . ')' : 'ainda não (normal na 1ª visita)', !$verificando || isset($_COOKIE[SESSION_NAME])];
$checks['save_path do PHP (não usado mais)'] = [ini_get('session.save_path') ?: '(vazio)', true];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Diagnóstico — Gamifica</title>
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
<style>body{padding:2rem;max-width:760px;margin:0 auto;}</style>
</head>
<body>
<div class="card">
  <div class="card-title">🩺 Diagnóstico do Gamifica <span class="badge br">apague este arquivo após o uso</span></div>

  <?php if (!$verificando): ?>
    <div class="alert alert-info">Passo 1 de 2: um valor de teste foi gravado na sua sessão. Clique abaixo para verificar se ele sobrevive à recarga (é exatamente isso que falha no erro "sessão expirada").</div>
    <a href="?v=1" class="btn btn-primary">▶ Verificar persistência da sessão</a>
  <?php elseif ($persistiu): ?>
    <div class="alert alert-ok">✅ <strong>Sessão persistiu!</strong> O mecanismo de sessão está funcionando — faça login normalmente. Se ainda falhar, limpe os cookies do navegador e tente de novo.</div>
  <?php else: ?>
    <div class="alert alert-erro">❌ <strong>A sessão NÃO persistiu.</strong> Veja a tabela abaixo — provavelmente a conexão com o banco falhou (confira config/db.php) ou o cookie não chegou.</div>
  <?php endif; ?>

  <table style="margin-top:14px;">
    <tr><th>Verificação</th><th>Resultado</th></tr>
    <?php foreach ($checks as $nome => [$valor, $ok]): ?>
    <tr>
      <td style="font-weight:800;"><?= e($nome) ?></td>
      <td><?= $ok ? '✅' : '❌' ?> <?= e((string)$valor) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <p style="font-size:12px;color:#888;font-weight:600;margin-top:14px;">
    Nenhuma senha ou dado sensível é exibido. Ainda assim, por segurança,
    <strong>exclua este arquivo (diagnostico.php) do servidor</strong> assim que terminar.
  </p>
</div>
</body>
</html>
