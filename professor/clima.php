<?php
// professor/clima.php — Termômetro de bem-estar + Índice de Clima da turma
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_professor();
if (!modulo_ativo('bemestar')) { flash_set('O módulo de bem-estar está desativado.', 'erro'); redirect(BASE_URL . '/professor/'); }

$pdo     = db();
$prof_id = usuario_id();

$turma_ids = $pdo->prepare('SELECT DISTINCT turma_id FROM professor_turma WHERE professor_id = ? AND ativo = 1');
$turma_ids->execute([$prof_id]);
$turma_ids = array_map('intval', $turma_ids->fetchAll(PDO::FETCH_COLUMN));
$in = $turma_ids ? implode(',', $turma_ids) : '0';

$clima = $pdo->query("SELECT * FROM vw_clima_turma WHERE turma_id IN ($in)")->fetchAll();

// Série diária (14 dias) por turma
$serie = $pdo->query(
    "SELECT at2.turma_id, cb.data, ROUND(AVG(cb.humor), 2) AS humor, COUNT(*) AS respostas
     FROM checkins_bemestar cb
     JOIN aluno_turma at2 ON at2.usuario_id = cb.aluno_id AND at2.ativo = 1
     WHERE at2.turma_id IN ($in) AND cb.data >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
     GROUP BY at2.turma_id, cb.data
     ORDER BY cb.data"
)->fetchAll(PDO::FETCH_GROUP);

pagina_inicio('Clima da Turma', 'clima');
?>
<div class="alert alert-info">💚 <strong>Termômetro de Bem-estar (exclusivo Gamifica):</strong> o dado vem do check-in diário dos próprios alunos, sempre agregado — sirva para <u>acolher</u>, nunca para punir. O Índice de Clima combina humor médio e participação.</div>

<?php if (empty($clima)): ?>
  <div class="card" style="text-align:center;padding:2rem;color:#aaa;">Sem turmas vinculadas.</div>
<?php endif; ?>

<?php foreach ($clima as $c):
  // Índice de Clima: 60% humor (normalizado 0–100) + 40% participação
  $humor_pct = $c['humor_medio'] !== null ? ($c['humor_medio'] - 1) / 4 * 100 : 0;
  $indice = (int)round(0.6 * $humor_pct + 0.4 * (float)$c['pct_participacao']);
?>
<div class="card">
  <div class="card-title">
    <span><?= $c['humor_medio'] !== null ? emoji_humor((int)round($c['humor_medio'])) : '❔' ?> Turma <?= e($c['turma_nome']) ?></span>
    <span class="badge <?= $indice >= 70 ? 'bg' : ($indice >= 40 ? 'ba' : 'br') ?>">Índice de Clima: <?= $indice ?>/100</span>
  </div>
  <div class="stats" style="grid-template-columns:repeat(3,1fr);margin-bottom:10px;">
    <div class="stat" style="box-shadow:none;border:2px solid #f0eff5;"><div class="stat-v"><?= $c['humor_medio'] !== null ? number_format((float)$c['humor_medio'], 1, ',', '') : '—' ?>/5</div><div class="stat-l">Humor médio (7 dias)</div></div>
    <div class="stat" style="box-shadow:none;border:2px solid #f0eff5;"><div class="stat-v"><?= (int)$c['alunos_checkin'] ?>/<?= (int)$c['total_alunos'] ?></div><div class="stat-l">Alunos participando</div></div>
    <div class="stat" style="box-shadow:none;border:2px solid #f0eff5;"><div class="stat-v"><?= number_format((float)$c['pct_participacao'], 0) ?>%</div><div class="stat-l">Participação</div></div>
  </div>
  <?php if (!empty($serie[$c['turma_id']])): ?>
  <div class="section-label">últimos 14 dias</div>
  <div style="display:flex;gap:6px;align-items:flex-end;height:80px;">
    <?php foreach ($serie[$c['turma_id']] as $dia):
      $h = max(8, (float)$dia['humor'] / 5 * 76); ?>
      <div style="text-align:center;" title="<?= date('d/m', strtotime($dia['data'])) ?>: humor <?= $dia['humor'] ?> (<?= (int)$dia['respostas'] ?> resp.)">
        <div style="width:26px;height:<?= (int)$h ?>px;border-radius:6px 6px 0 0;background:linear-gradient(180deg,#7c6ef0,#a855f7);"></div>
        <div style="font-size:9px;color:#aaa;font-weight:700;"><?= date('d/m', strtotime($dia['data'])) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
<?php pagina_fim(); ?>
