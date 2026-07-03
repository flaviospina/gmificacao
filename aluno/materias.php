<?php
// aluno/materias.php — Progresso por disciplina
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_aluno();

$pdo      = db();
$aluno_id = usuario_id();

$stmt = $pdo->prepare(
    'SELECT a.disciplina,
            COUNT(a.id)                        AS total_atividades,
            COALESCE(SUM(a.xp_recompensa), 0)  AS xp_disponivel,
            COUNT(ax.id)                       AS concluidas,
            COALESCE(SUM(ax.xp_melhor), 0)     AS xp_ganho
     FROM atividades a
     JOIN aluno_turma at2 ON at2.turma_id = a.turma_id AND at2.usuario_id = ? AND at2.ativo = 1
     LEFT JOIN aluno_xp ax ON ax.atividade_id = a.id AND ax.aluno_id = ?
     WHERE a.publicada = 1
     GROUP BY a.disciplina
     ORDER BY a.disciplina'
);
$stmt->execute([$aluno_id, $aluno_id]);
$materias = $stmt->fetchAll();

pagina_inicio('Matérias', 'materias');
?>
<?php if (empty($materias)): ?>
  <div class="card" style="text-align:center;padding:2rem;color:#aaa;">Nenhuma matéria com atividades ainda.</div>
<?php endif; ?>

<?php foreach ($materias as $m):
  $pct = $m['xp_disponivel'] ? min(100, round(100 * $m['xp_ganho'] / $m['xp_disponivel'])) : 0;
?>
<div class="card">
  <div class="card-title">
    <span>📚 <?= e($m['disciplina']) ?></span>
    <span class="badge bp"><?= (int)$m['concluidas'] ?>/<?= (int)$m['total_atividades'] ?> atividades</span>
  </div>
  <div class="progress" style="height:10px;"><div class="prog-fill" style="width:<?= $pct ?>%;background:<?= cor_progresso($pct) ?>;"></div></div>
  <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:700;color:#888;margin-top:6px;">
    <span><?= fmt_num((int)$m['xp_ganho']) ?> / <?= fmt_num((int)$m['xp_disponivel']) ?> XP (<?= $pct ?>%)</span>
    <a href="missoes.php?disciplina=<?= urlencode($m['disciplina']) ?>" style="color:#7c6ef0;font-weight:800;text-decoration:none;">Ver missões →</a>
  </div>
</div>
<?php endforeach; ?>
<?php pagina_fim(); ?>
