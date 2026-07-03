<?php
// aluno/missoes.php — Todas as missões da turma
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_aluno();

$pdo      = db();
$aluno_id = usuario_id();
$filtro   = trim($_GET['disciplina'] ?? '');

$disc = $pdo->prepare(
    'SELECT DISTINCT a.disciplina FROM atividades a
     JOIN aluno_turma at2 ON at2.turma_id = a.turma_id AND at2.usuario_id = ? AND at2.ativo = 1
     WHERE a.publicada = 1 ORDER BY a.disciplina'
);
$disc->execute([$aluno_id]);
$disciplinas = $disc->fetchAll(PDO::FETCH_COLUMN);

$sql = 'SELECT a.id, a.titulo, a.tipo, a.disciplina, a.xp_recompensa, a.data_fim,
               COALESCE(ax.xp_melhor, 0) AS melhor_xp, (ax.id IS NOT NULL) AS concluida,
               (SELECT COUNT(*) FROM tentativas t WHERE t.atividade_id = a.id AND t.aluno_id = ? AND t.concluida = 1) AS total_tentativas
        FROM atividades a
        JOIN aluno_turma at2 ON at2.turma_id = a.turma_id AND at2.usuario_id = ? AND at2.ativo = 1
        LEFT JOIN aluno_xp ax ON ax.atividade_id = a.id AND ax.aluno_id = ?
        WHERE a.publicada = 1';
$params = [$aluno_id, $aluno_id, $aluno_id];
if ($filtro !== '') { $sql .= ' AND a.disciplina = ?'; $params[] = $filtro; }
$sql .= ' ORDER BY (ax.id IS NULL) DESC, a.criado_em DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$missoes = $stmt->fetchAll();

pagina_inicio('Todas as Missões', 'missoes');
?>
<div class="tabs">
  <a href="missoes.php" class="tab <?= $filtro === '' ? 'on' : '' ?>" style="text-decoration:none;">Todas</a>
  <?php foreach ($disciplinas as $d): ?>
    <a href="missoes.php?disciplina=<?= urlencode($d) ?>" class="tab <?= $filtro === $d ? 'on' : '' ?>" style="text-decoration:none;"><?= e($d) ?></a>
  <?php endforeach; ?>
</div>

<?php if (empty($missoes)): ?>
  <div class="card" style="text-align:center;padding:2rem;color:#aaa;">Nenhuma missão por aqui ainda.</div>
<?php endif; ?>

<?php foreach ($missoes as $m):
  $concluida = (bool)$m['concluida'];
  $expirada  = $m['data_fim'] && strtotime($m['data_fim']) < time();
  $pct = $m['xp_recompensa'] ? min(100, round(100 * $m['melhor_xp'] / $m['xp_recompensa'])) : 0;
?>
<div class="mission <?= $concluida ? 'mission--done' : '' ?>">
  <div class="mission-inner">
    <div class="mission-icon"><?= icone_tipo($m['tipo']) ?></div>
    <div class="mission-body">
      <div class="mission-header">
        <span class="mission-title"><?= e($m['titulo']) ?></span>
        <span class="xp-badge">⭐ <?= (int)$m['xp_recompensa'] ?> XP</span>
      </div>
      <div class="mission-meta">
        <span class="badge ba"><?= e($m['disciplina']) ?></span>
        <span class="badge bk"><?= ucfirst($m['tipo']) ?></span>
        <?php if ($expirada): ?><span class="badge br">Encerrada</span>
        <?php elseif ($m['data_fim']): ?><span style="font-size:11px;color:#aaa;font-weight:600;">Prazo: <?= date('d/m/Y', strtotime($m['data_fim'])) ?></span><?php endif; ?>
      </div>
      <div class="progress"><div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $concluida ? '#16a34a' : '#7c6ef0' ?>"></div></div>
      <div class="mission-footer">
        <span style="font-size:12px;color:#888;font-weight:600;">
          <?= $concluida ? "✅ Melhor: {$m['melhor_xp']} XP ({$pct}%)" : ($m['total_tentativas'] > 0 ? 'Em andamento' : 'Não iniciado') ?>
        </span>
        <?php if (!$expirada): ?>
          <a href="missao.php?id=<?= (int)$m['id'] ?>" class="btn <?= $concluida ? 'btn-outline' : 'btn-primary' ?>">
            <?= $concluida ? 'Refazer' : 'Iniciar' ?> →
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php pagina_fim(); ?>
