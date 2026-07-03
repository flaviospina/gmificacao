<?php
// admin/index.php — Painel do Administrador
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_admin();

$pdo = db();
$eid = escola_id();

$contagens = $pdo->prepare(
    "SELECT perfil, COUNT(*) AS n FROM usuarios WHERE escola_id = ? AND ativo = 1 GROUP BY perfil"
);
$contagens->execute([$eid]);
$por_perfil = $contagens->fetchAll(PDO::FETCH_KEY_PAIR);

$modulos_ativos = (int)$pdo->query('SELECT COUNT(*) FROM modulos WHERE ativo = 1')->fetchColumn();
$modulos_total  = (int)$pdo->query('SELECT COUNT(*) FROM modulos')->fetchColumn();

$acessos_hoje = (int)$pdo->query(
    "SELECT COUNT(*) FROM logs_acesso WHERE acao IN ('login_ok','login_google_ok') AND DATE(criado_em) = CURDATE()"
)->fetchColumn();

$ultimos_logs = $pdo->query(
    'SELECT l.*, u.nome FROM logs_acesso l LEFT JOIN usuarios u ON u.id = l.usuario_id
     ORDER BY l.criado_em DESC LIMIT 8'
)->fetchAll();

pagina_inicio('Administração', 'inicio');
?>
<div class="stats" style="grid-template-columns:repeat(6,1fr);">
  <div class="stat"><div class="stat-v"><?= (int)($por_perfil['aluno'] ?? 0) ?></div><div class="stat-l">Alunos</div></div>
  <div class="stat"><div class="stat-v"><?= (int)($por_perfil['professor'] ?? 0) ?></div><div class="stat-l">Professores</div></div>
  <div class="stat"><div class="stat-v"><?= (int)($por_perfil['coordenador'] ?? 0) ?></div><div class="stat-l">Coordenadores</div></div>
  <div class="stat"><div class="stat-v"><?= (int)($por_perfil['responsavel'] ?? 0) ?></div><div class="stat-l">Responsáveis</div></div>
  <div class="stat"><div class="stat-v"><?= $modulos_ativos ?>/<?= $modulos_total ?></div><div class="stat-l">Módulos ativos</div></div>
  <div class="stat"><div class="stat-v"><?= $acessos_hoje ?></div><div class="stat-l">Logins hoje</div></div>
</div>

<div class="two-col">
  <div class="card">
    <div class="card-title">⚡ Ações rápidas</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a href="usuarios.php" class="btn btn-primary">👥 Gerenciar usuários</a>
      <a href="modulos.php" class="btn btn-outline">🧩 Módulos</a>
      <a href="loja.php" class="btn btn-outline">🛍️ Itens da loja</a>
      <a href="integracoes.php" class="btn btn-outline">🔗 Google Classroom</a>
      <a href="backup.php" class="btn btn-outline">💾 Backup</a>
    </div>
  </div>

  <div class="card">
    <div class="card-title">📜 Últimos acessos</div>
    <?php foreach ($ultimos_logs as $l): ?>
    <div class="rank-row">
      <span><?= str_contains($l['acao'], 'fail') ? '⚠️' : '✅' ?></span>
      <div style="flex:1;">
        <div style="font-size:12px;font-weight:800;"><?= e($l['nome'] ?? $l['email'] ?? '—') ?> <span class="badge bk"><?= e($l['acao']) ?></span></div>
        <div style="font-size:10px;color:#aaa;"><?= date('d/m H:i', strtotime($l['criado_em'])) ?> · <?= e($l['ip'] ?? '') ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php pagina_fim(); ?>
