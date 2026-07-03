<?php
// admin/logs.php — Logs de acesso
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_admin();

$pdo = db();
$busca = trim($_GET['q'] ?? '');
$pagina = max(1, (int)($_GET['p'] ?? 1));
$por_pagina = 50;

$sql = 'SELECT l.*, u.nome FROM logs_acesso l LEFT JOIN usuarios u ON u.id = l.usuario_id';
$params = [];
if ($busca !== '') {
    $sql .= ' WHERE l.email LIKE ? OR l.acao LIKE ? OR u.nome LIKE ?';
    $params = array_fill(0, 3, '%' . $busca . '%');
}
$sql .= ' ORDER BY l.criado_em DESC LIMIT ' . $por_pagina . ' OFFSET ' . (($pagina - 1) * $por_pagina);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

pagina_inicio('Logs de Acesso', 'logs');
?>
<div class="card">
  <form method="GET" style="display:flex;gap:8px;margin-bottom:14px;">
    <input type="text" name="q" value="<?= e($busca) ?>" placeholder="Buscar por e-mail, ação ou nome..." style="flex:1;">
    <button type="submit" class="btn btn-primary">Buscar</button>
  </form>
  <div style="overflow-x:auto;">
  <table>
    <tr><th>Data</th><th>Usuário</th><th>Ação</th><th>IP</th></tr>
    <?php foreach ($logs as $l): ?>
    <tr>
      <td style="white-space:nowrap;"><?= date('d/m/Y H:i:s', strtotime($l['criado_em'])) ?></td>
      <td><?= e($l['nome'] ?? $l['email'] ?? '—') ?></td>
      <td><span class="badge <?= str_contains($l['acao'], 'fail') ? 'br' : 'bk' ?>"><?= e($l['acao']) ?></span></td>
      <td style="font-size:12px;"><?= e($l['ip'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <div style="display:flex;justify-content:space-between;margin-top:12px;">
    <?php if ($pagina > 1): ?><a href="?q=<?= urlencode($busca) ?>&p=<?= $pagina - 1 ?>" class="btn btn-outline" style="font-size:12px;">← Anteriores</a><?php else: ?><span></span><?php endif; ?>
    <?php if (count($logs) === $por_pagina): ?><a href="?q=<?= urlencode($busca) ?>&p=<?= $pagina + 1 ?>" class="btn btn-outline" style="font-size:12px;">Próximos →</a><?php endif; ?>
  </div>
</div>
<?php pagina_fim(); ?>
