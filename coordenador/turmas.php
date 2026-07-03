<?php
// coordenador/turmas.php — Engajamento detalhado por turma
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_coordenador();

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM vw_engajamento_turmas WHERE turma_id IN (SELECT id FROM turmas WHERE escola_id = ?) ORDER BY pct_engajamento DESC, turma_nome');
$stmt->execute([escola_id()]);
$turmas = $stmt->fetchAll();

pagina_inicio('Turmas', 'turmas');
?>
<?php if (empty($turmas)): ?>
  <div class="card" style="text-align:center;padding:2rem;color:#aaa;">Nenhuma turma cadastrada.</div>
<?php endif; ?>
<?php foreach ($turmas as $t): $pct = (float)$t['pct_engajamento']; ?>
<div class="card">
  <div class="card-title">
    <span>🏫 Turma <?= e($t['turma_nome']) ?> <span class="badge bk"><?= e($t['disciplina'] ?? '') ?></span></span>
    <?= badge_engajamento($pct) ?>
  </div>
  <div class="progress" style="height:10px;"><div class="prog-fill" style="width:<?= min(100, $pct) ?>%;background:<?= cor_progresso($pct) ?>;"></div></div>
  <div style="display:flex;gap:18px;font-size:12px;font-weight:700;color:#888;margin-top:8px;flex-wrap:wrap;">
    <span>👥 <?= (int)$t['total_alunos'] ?> alunos</span>
    <span>📝 <?= (int)$t['total_atividades'] ?> atividades</span>
    <span>⭐ XP médio: <?= fmt_num((int)$t['xp_medio']) ?></span>
    <span>🧑‍🏫 <?= e($t['professor_nome'] ?? '—') ?></span>
  </div>
</div>
<?php endforeach; ?>
<?php pagina_fim(); ?>
