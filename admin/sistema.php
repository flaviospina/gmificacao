<?php
// admin/sistema.php — Configurações do sistema
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    foreach ($_POST['cfg'] ?? [] as $chave => $valor) {
        $pdo->prepare('UPDATE configuracoes SET valor = ? WHERE chave = ?')
            ->execute([trim((string)$valor), (string)$chave]);
    }
    flash_set('Configurações salvas. ⚙️');
    redirect(BASE_URL . '/admin/sistema.php');
}

$configs = $pdo->query('SELECT * FROM configuracoes ORDER BY chave')->fetchAll();

pagina_inicio('Sistema', 'sistema');
?>
<div class="card">
  <div class="card-title">⚙️ Configurações</div>
  <form method="POST">
    <?= csrf_input() ?>
    <?php foreach ($configs as $c): $secreta = str_contains($c['chave'], 'secret'); ?>
    <div class="fld">
      <label><?= e($c['chave']) ?><?= $c['descricao'] ? ' — ' . e($c['descricao']) : '' ?></label>
      <input type="<?= $secreta ? 'password' : 'text' ?>" name="cfg[<?= e($c['chave']) ?>]" value="<?= e($c['valor']) ?>" autocomplete="off">
    </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-primary">Salvar tudo</button>
  </form>
</div>
<?php pagina_fim(); ?>
