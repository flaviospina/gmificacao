<?php
// coordenador/clima.php — Índice de Clima da escola
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_coordenador();
if (!modulo_ativo('bemestar')) { flash_set('O módulo de bem-estar está desativado.', 'erro'); redirect(BASE_URL . '/coordenador/'); }

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM vw_clima_turma WHERE escola_id = ? ORDER BY turma_nome');
$stmt->execute([escola_id()]);
$clima = $stmt->fetchAll();

$indices = [];
foreach ($clima as $c) {
    $humor_pct = $c['humor_medio'] !== null ? ($c['humor_medio'] - 1) / 4 * 100 : 0;
    $indices[$c['turma_id']] = (int)round(0.6 * $humor_pct + 0.4 * (float)$c['pct_participacao']);
}
$indice_escola = $indices ? (int)round(array_sum($indices) / count($indices)) : 0;

pagina_inicio('Clima da Escola', 'clima');
?>
<div class="alert alert-info">💚 O <strong>Índice de Clima</strong> (equivalente Gamifica ao "school climate index") combina o humor médio dos check-ins dos alunos (60%) com a participação (40%). Use para acolher turmas — nunca para punir.</div>

<div class="card" style="text-align:center;">
  <div class="card-title" style="justify-content:center;">Índice de Clima da escola (últimos 7 dias)</div>
  <div style="font-size:52px;font-weight:800;color:<?= $indice_escola >= 70 ? '#16a34a' : ($indice_escola >= 40 ? '#d97706' : '#dc2626') ?>;"><?= $indice_escola ?><span style="font-size:22px;color:#aaa;">/100</span></div>
</div>

<?php foreach ($clima as $c): $idx = $indices[$c['turma_id']]; ?>
<div class="card">
  <div class="card-title">
    <span><?= $c['humor_medio'] !== null ? emoji_humor((int)round($c['humor_medio'])) : '❔' ?> Turma <?= e($c['turma_nome']) ?></span>
    <span class="badge <?= $idx >= 70 ? 'bg' : ($idx >= 40 ? 'ba' : 'br') ?>">Índice: <?= $idx ?>/100</span>
  </div>
  <div class="progress" style="height:10px;"><div class="prog-fill" style="width:<?= $idx ?>%;background:<?= cor_progresso((float)$idx) ?>;"></div></div>
  <div style="display:flex;gap:18px;font-size:12px;font-weight:700;color:#888;margin-top:6px;flex-wrap:wrap;">
    <span>Humor médio: <?= $c['humor_medio'] !== null ? number_format((float)$c['humor_medio'], 1, ',', '') . '/5' : 'sem check-ins' ?></span>
    <span>Participação: <?= (int)$c['alunos_checkin'] ?>/<?= (int)$c['total_alunos'] ?> (<?= number_format((float)$c['pct_participacao'], 0) ?>%)</span>
  </div>
</div>
<?php endforeach; ?>
<?php pagina_fim(); ?>
