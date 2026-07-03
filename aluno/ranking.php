<?php
// aluno/ranking.php — Ranking: turma · escola · evolução (inédito)
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_aluno();

$pdo      = db();
$aluno_id = usuario_id();
$aba      = in_array($_GET['aba'] ?? '', ['turma', 'escola', 'evolucao'], true) ? $_GET['aba'] : 'turma';

$rt = $pdo->prepare('SELECT turma_id, turma_nome FROM vw_ranking_turma WHERE aluno_id = ? LIMIT 1');
$rt->execute([$aluno_id]);
$minha_turma = $rt->fetch() ?: ['turma_id' => 0, 'turma_nome' => '—'];

if ($aba === 'turma') {
    $stmt = $pdo->prepare(
        'SELECT aluno_id, aluno_nome, xp_total AS valor, nivel, posicao_turma AS posicao
         FROM vw_ranking_turma WHERE turma_id = ? ORDER BY posicao ASC, aluno_nome LIMIT 50'
    );
    $stmt->execute([$minha_turma['turma_id']]);
    $unidade = 'XP';
} elseif ($aba === 'escola') {
    $stmt = $pdo->prepare(
        'SELECT aluno_id, aluno_nome, xp_total AS valor, nivel, posicao_escola AS posicao
         FROM vw_ranking_escola WHERE escola_id = ? ORDER BY posicao ASC, aluno_nome LIMIT 50'
    );
    $stmt->execute([escola_id()]);
    $unidade = 'XP';
} else {
    $stmt = $pdo->prepare(
        'SELECT aluno_id, aluno_nome, xp_semana AS valor, NULL AS nivel, posicao_evolucao AS posicao
         FROM vw_ranking_evolucao WHERE turma_id = ? ORDER BY posicao ASC, aluno_nome LIMIT 50'
    );
    $stmt->execute([$minha_turma['turma_id']]);
    $unidade = 'XP na semana';
}
$linhas = $stmt->fetchAll();

pagina_inicio('Ranking', 'ranking');
?>
<div class="tabs">
  <a href="?aba=turma"    class="tab <?= $aba === 'turma' ? 'on' : '' ?>"    style="text-decoration:none;">🏫 Turma <?= e($minha_turma['turma_nome']) ?></a>
  <a href="?aba=escola"   class="tab <?= $aba === 'escola' ? 'on' : '' ?>"   style="text-decoration:none;">🌍 Escola</a>
  <a href="?aba=evolucao" class="tab <?= $aba === 'evolucao' ? 'on' : '' ?>" style="text-decoration:none;">🚀 Evolução da semana</a>
</div>

<?php if ($aba === 'evolucao'): ?>
<div class="alert alert-info">🚀 <strong>Ranking de Evolução:</strong> conta só o XP ganho <u>nesta semana</u> — todo mundo recomeça a disputa na segunda-feira. Cresça e apareça!</div>
<?php endif; ?>

<div class="card">
  <?php if (empty($linhas)): ?>
    <div style="text-align:center;color:#aaa;padding:1.5rem;font-weight:700;">Sem dados ainda. Complete missões!</div>
  <?php endif; ?>
  <?php foreach ($linhas as $r): $eu = ((int)$r['aluno_id'] === $aluno_id); $p = (int)$r['posicao']; ?>
  <div class="rank-row <?= $eu ? 'rank-me' : '' ?>">
    <span style="font-size:<?= $p <= 3 ? '20' : '13' ?>px;<?= $p > 3 ? 'color:#aaa;font-weight:700;' : '' ?>width:30px;text-align:center;"><?= medalha($p) ?></span>
    <div style="flex:1;">
      <div style="font-size:14px;font-weight:<?= $eu ? '800' : '700' ?>;color:<?= $eu ? '#7c6ef0' : 'inherit' ?>"><?= $eu ? 'Você' : e($r['aluno_nome']) ?></div>
      <div style="font-size:11px;color:#aaa;"><?= fmt_num((int)$r['valor']) ?> <?= $unidade ?><?= $r['nivel'] !== null ? ' · Nível ' . (int)$r['nivel'] : '' ?></div>
    </div>
    <?php if ($eu): ?><span style="font-size:10px;font-weight:800;color:#7c6ef0;">← você</span><?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php pagina_fim(); ?>
