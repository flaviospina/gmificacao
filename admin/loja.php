<?php
// admin/loja.php — Gerenciar itens da loja
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_admin();

$pdo = db();
$eid = escola_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        $cat  = in_array($_POST['categoria'] ?? '', ['avatar', 'privilegio', 'material'], true) ? $_POST['categoria'] : 'material';
        if ($nome !== '' && (int)$_POST['preco'] > 0) {
            $pdo->prepare(
                'INSERT INTO loja_itens (escola_id, nome, descricao, icone, categoria, preco_moedas, requer_aprovacao)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $eid, $nome, trim($_POST['descricao'] ?? '') ?: null,
                trim($_POST['icone'] ?? '') ?: '🎁', $cat, (int)$_POST['preco'],
                $cat === 'avatar' ? 0 : 1,
            ]);
            flash_set('Item criado! 🛍️');
        } else {
            flash_set('Informe nome e preço.', 'erro');
        }
    }
    if ($acao === 'toggle') {
        $pdo->prepare('UPDATE loja_itens SET ativo = 1 - ativo WHERE id = ? AND escola_id = ?')
            ->execute([(int)$_POST['item_id'], $eid]);
        flash_set('Item atualizado.');
    }
    redirect(BASE_URL . '/admin/loja.php');
}

$itens = $pdo->prepare(
    'SELECT li.*, (SELECT COUNT(*) FROM loja_resgates lr WHERE lr.item_id = li.id AND lr.status <> "cancelado") AS resgates
     FROM loja_itens li WHERE li.escola_id = ? ORDER BY li.categoria, li.preco_moedas'
);
$itens->execute([$eid]);
$itens = $itens->fetchAll();

pagina_inicio('Itens da Loja', 'loja');
?>
<div class="card">
  <div class="card-title">➕ Novo item</div>
  <form method="POST" style="display:grid;grid-template-columns:60px 2fr 1fr 1fr 1fr auto;gap:10px;align-items:end;">
    <?= csrf_input() ?>
    <input type="hidden" name="acao" value="criar">
    <div class="fld" style="margin:0;"><label>Ícone</label><input type="text" name="icone" maxlength="4" placeholder="🎁" style="text-align:center;"></div>
    <div class="fld" style="margin:0;"><label>Nome</label><input type="text" name="nome" required maxlength="100"></div>
    <div class="fld" style="margin:0;"><label>Categoria</label>
      <select name="categoria">
        <option value="avatar">🖼️ Avatar (entrega automática)</option>
        <option value="privilegio">🎟️ Privilégio</option>
        <option value="material">🎁 Recompensa física</option>
      </select>
    </div>
    <div class="fld" style="margin:0;"><label>Preço (🪙)</label><input type="number" name="preco" min="1" required value="10"></div>
    <div class="fld" style="margin:0;"><label>Descrição</label><input type="text" name="descricao" maxlength="255"></div>
    <button type="submit" class="btn btn-primary">Criar</button>
  </form>
</div>

<div class="card" style="overflow-x:auto;">
  <div class="card-title">🛍️ Itens cadastrados</div>
  <table>
    <tr><th>Item</th><th>Categoria</th><th>Preço</th><th>Aprovação</th><th>Resgates</th><th>Status</th><th></th></tr>
    <?php foreach ($itens as $i): ?>
    <tr>
      <td style="font-weight:800;"><?= $i['icone'] ?> <?= e($i['nome']) ?></td>
      <td><span class="badge bk"><?= ucfirst($i['categoria']) ?></span></td>
      <td>🪙 <?= (int)$i['preco_moedas'] ?></td>
      <td><?= $i['requer_aprovacao'] ? 'Professor aprova' : 'Automática' ?></td>
      <td><?= (int)$i['resgates'] ?></td>
      <td><span class="badge <?= $i['ativo'] ? 'bg' : 'br' ?>"><?= $i['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
      <td>
        <form method="POST">
          <?= csrf_input() ?>
          <input type="hidden" name="acao" value="toggle">
          <input type="hidden" name="item_id" value="<?= (int)$i['id'] ?>">
          <button type="submit" class="btn btn-outline" style="font-size:10px;padding:4px 8px;"><?= $i['ativo'] ? 'Desativar' : 'Ativar' ?></button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php pagina_fim(); ?>
