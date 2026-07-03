<?php
// professor/turma.php — Alunos da turma (progresso individual)
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_professor();

$pdo     = db();
$prof_id = usuario_id();

$turmas = $pdo->prepare(
    'SELECT DISTINCT t.id, t.nome FROM turmas t
     JOIN professor_turma pt ON pt.turma_id = t.id AND pt.ativo = 1
     WHERE pt.professor_id = ? AND t.ativa = 1 ORDER BY t.nome'
);
$turmas->execute([$prof_id]);
$turmas = $turmas->fetchAll();
$turma_ids = array_map('intval', array_column($turmas, 'id'));

$turma_id = (int)($_GET['id'] ?? ($turma_ids[0] ?? 0));
if (!in_array($turma_id, $turma_ids, true)) $turma_id = $turma_ids[0] ?? 0;

$alunos = [];
if ($turma_id) {
    $stmt = $pdo->prepare(
        'SELECT r.aluno_id, r.aluno_nome, r.xp_total, r.nivel, r.streak, r.missoes, r.posicao_turma,
                re.xp_semana
         FROM vw_ranking_turma r
         LEFT JOIN vw_ranking_evolucao re ON re.aluno_id = r.aluno_id AND re.turma_id = r.turma_id
         WHERE r.turma_id = ?
         ORDER BY r.posicao_turma, r.aluno_nome'
    );
    $stmt->execute([$turma_id]);
    $alunos = $stmt->fetchAll();
}

pagina_inicio('Minha Turma', 'inicio');
?>
<div class="tabs">
  <?php foreach ($turmas as $t): ?>
    <a href="?id=<?= (int)$t['id'] ?>" class="tab <?= (int)$t['id'] === $turma_id ? 'on' : '' ?>" style="text-decoration:none;">Turma <?= e($t['nome']) ?></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <?php if (empty($alunos)): ?>
    <div style="color:#aaa;font-weight:700;font-size:13px;text-align:center;padding:1.5rem;">Nenhum aluno nesta turma.</div>
  <?php else: ?>
  <table>
    <tr><th>#</th><th>Aluno</th><th>XP total</th><th>Nível</th><th>XP na semana 🚀</th><th>Streak</th><th>Missões</th></tr>
    <?php foreach ($alunos as $a): ?>
    <tr>
      <td><?= medalha((int)$a['posicao_turma']) ?></td>
      <td style="font-weight:800;"><?= e($a['aluno_nome']) ?></td>
      <td><?= fmt_num((int)$a['xp_total']) ?></td>
      <td><span class="badge bp">Nv <?= (int)$a['nivel'] ?></span></td>
      <td style="color:#7c6ef0;font-weight:800;">+<?= fmt_num((int)($a['xp_semana'] ?? 0)) ?></td>
      <td>🔥 <?= (int)$a['streak'] ?></td>
      <td><?= (int)$a['missoes'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php pagina_fim(); ?>
