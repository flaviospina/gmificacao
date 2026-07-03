<?php
// admin/modulos.php — Ativar/desativar módulos do sistema
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    $pdo->prepare('UPDATE modulos SET ativo = 1 - ativo WHERE id = ?')->execute([(int)$_POST['modulo_id']]);
    flash_set('Módulo atualizado.');
    redirect(BASE_URL . '/admin/modulos.php');
}

$modulos = $pdo->query('SELECT * FROM modulos ORDER BY nome')->fetchAll();

pagina_inicio('Módulos', 'modulos');
?>
<div class="card">
  <div class="card-title">🧩 Módulos do sistema</div>
  <?php foreach ($modulos as $m): ?>
  <div class="rank-row">
    <div style="flex:1;">
      <div style="font-size:14px;font-weight:800;"><?= e($m['nome']) ?> <span class="badge bk"><?= e($m['chave']) ?></span></div>
      <div style="font-size:12px;color:#888;font-weight:600;"><?= e($m['descricao'] ?? '') ?></div>
    </div>
    <form method="POST">
      <?= csrf_input() ?>
      <input type="hidden" name="modulo_id" value="<?= (int)$m['id'] ?>">
      <button type="submit" class="tog <?= $m['ativo'] ? 'on' : 'off' ?>" title="<?= $m['ativo'] ? 'Desativar' : 'Ativar' ?>"></button>
    </form>
  </div>
  <?php endforeach; ?>
</div>
<?php pagina_fim(); ?>
