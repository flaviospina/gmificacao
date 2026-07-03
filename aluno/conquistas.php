<?php
// aluno/conquistas.php — Todas as conquistas (desbloqueadas + bloqueadas)
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_aluno();

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT c.*, ac.desbloqueada_em
     FROM conquistas c
     LEFT JOIN aluno_conquistas ac ON ac.conquista_id = c.id AND ac.aluno_id = ?
     WHERE c.ativa = 1
     ORDER BY (ac.id IS NULL), ac.desbloqueada_em DESC, c.id'
);
$stmt->execute([usuario_id()]);
$todas = $stmt->fetchAll();
$desbloqueadas = count(array_filter($todas, fn($c) => $c['desbloqueada_em'] !== null));

pagina_inicio('Conquistas', 'conquistas');
?>
<div class="card">
  <div class="card-title">🏅 <?= $desbloqueadas ?> de <?= count($todas) ?> conquistas desbloqueadas</div>
  <div class="progress" style="height:10px;"><div class="prog-fill" style="width:<?= count($todas) ? round(100 * $desbloqueadas / count($todas)) : 0 ?>%;background:linear-gradient(90deg,#f59e0b,#f97316);"></div></div>
</div>

<div class="loja-grid">
  <?php foreach ($todas as $c): $tem = $c['desbloqueada_em'] !== null; ?>
  <div class="loja-item" style="<?= $tem ? '' : 'opacity:.45;filter:grayscale(.8);' ?>">
    <div class="loja-icone"><?= $c['icone'] ?></div>
    <div style="font-size:14px;font-weight:800;"><?= e($c['nome']) ?></div>
    <div style="font-size:12px;color:#888;font-weight:600;margin-top:4px;"><?= e($c['descricao']) ?></div>
    <div style="font-size:11px;font-weight:700;margin-top:8px;color:<?= $tem ? '#16a34a' : '#aaa' ?>;">
      <?= $tem ? '✅ ' . date('d/m/Y', strtotime($c['desbloqueada_em'])) : '🔒 Bloqueada' ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php pagina_fim(); ?>
