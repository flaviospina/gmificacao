<?php
// coordenador/destaques.php — Top alunos: XP total e evolução da semana
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_coordenador();

$pdo = db();
$eid = escola_id();

$top_xp = $pdo->prepare('SELECT aluno_nome, xp_total, nivel, missoes FROM vw_ranking_escola WHERE escola_id = ? ORDER BY posicao_escola LIMIT 10');
$top_xp->execute([$eid]);
$top_xp = $top_xp->fetchAll();

$top_evo = $pdo->prepare(
    'SELECT aluno_nome, turma_nome, xp_semana FROM vw_ranking_evolucao
     WHERE escola_id = ? AND xp_semana > 0
     ORDER BY xp_semana DESC, aluno_nome LIMIT 10'
);
$top_evo->execute([$eid]);
$top_evo = $top_evo->fetchAll();

$top_streak = $pdo->prepare(
    "SELECT u.nome, ap.streak_dias FROM aluno_perfil ap JOIN usuarios u ON u.id = ap.aluno_id
     WHERE u.escola_id = ? AND u.ativo = 1 AND ap.streak_dias > 0
     ORDER BY ap.streak_dias DESC, u.nome LIMIT 10"
);
$top_streak->execute([$eid]);
$top_streak = $top_streak->fetchAll();

pagina_inicio('Destaques', 'destaques');
?>
<div class="stats" style="grid-template-columns:repeat(3,1fr);align-items:start;">
  <div class="card" style="margin:0;">
    <div class="card-title">🏆 Top 10 — XP total</div>
    <?php foreach ($top_xp as $i => $r): ?>
    <div class="rank-row">
      <span><?= medalha($i + 1) ?></span>
      <div style="flex:1;font-size:13px;font-weight:700;"><?= e($r['aluno_nome']) ?></div>
      <span style="font-size:12px;font-weight:800;"><?= fmt_num((int)$r['xp_total']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="card" style="margin:0;">
    <div class="card-title">🚀 Top 10 — Evolução da semana</div>
    <?php if (empty($top_evo)): ?><div style="color:#aaa;font-size:12px;font-weight:700;">Sem XP ganho nesta semana ainda.</div><?php endif; ?>
    <?php foreach ($top_evo as $i => $r): ?>
    <div class="rank-row">
      <span><?= medalha($i + 1) ?></span>
      <div style="flex:1;font-size:13px;font-weight:700;"><?= e($r['aluno_nome']) ?> <span style="font-size:10px;color:#aaa;">(<?= e($r['turma_nome']) ?>)</span></div>
      <span style="font-size:12px;font-weight:800;color:#7c6ef0;">+<?= fmt_num((int)$r['xp_semana']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="card" style="margin:0;">
    <div class="card-title">🔥 Top 10 — Sequência de dias</div>
    <?php if (empty($top_streak)): ?><div style="color:#aaa;font-size:12px;font-weight:700;">Nenhuma sequência ativa.</div><?php endif; ?>
    <?php foreach ($top_streak as $i => $r): ?>
    <div class="rank-row">
      <span><?= medalha($i + 1) ?></span>
      <div style="flex:1;font-size:13px;font-weight:700;"><?= e($r['nome']) ?></div>
      <span style="font-size:12px;font-weight:800;">🔥 <?= (int)$r['streak_dias'] ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php pagina_fim(); ?>
