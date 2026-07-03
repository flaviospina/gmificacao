<?php
// aluno/loja.php — Loja de GamiCoins (economia 100% positiva)
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_aluno();
if (!modulo_ativo('loja')) { flash_set('A loja está desativada no momento.', 'erro'); redirect(BASE_URL . '/aluno/'); }

$pdo      = db();
$aluno_id = usuario_id();

// ── Comprar item ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comprar']) && csrf_valido($_POST['csrf_token'] ?? '')) {
    $item_id = (int)$_POST['comprar'];
    $it = $pdo->prepare('SELECT * FROM loja_itens WHERE id = ? AND escola_id = ? AND ativo = 1');
    $it->execute([$item_id, escola_id()]);
    $item = $it->fetch();

    if (!$item) {
        flash_set('Item indisponível.', 'erro');
    } elseif (saldo_moedas($aluno_id) < (int)$item['preco_moedas']) {
        flash_set('GamiCoins insuficientes. Complete missões para ganhar mais! 🪙', 'erro');
    } else {
        $ja_tem = false;
        if ($item['categoria'] === 'avatar') {
            $chk = $pdo->prepare('SELECT id FROM aluno_avatar WHERE aluno_id = ? AND item_id = ?');
            $chk->execute([$aluno_id, $item_id]);
            $ja_tem = (bool)$chk->fetch();
        }
        if ($ja_tem) {
            flash_set('Você já possui este item de avatar. 😉', 'erro');
        } else {
            $pdo->beginTransaction();
            try {
                $pdo->prepare('INSERT INTO moeda_eventos (aluno_id, tipo, origem, referencia_id, moedas) VALUES (?, "gasto", "loja", ?, ?)')
                    ->execute([$aluno_id, $item_id, (int)$item['preco_moedas']]);
                $status = $item['requer_aprovacao'] ? 'pendente' : 'entregue';
                $pdo->prepare('INSERT INTO loja_resgates (item_id, aluno_id, moedas_gastas, status) VALUES (?, ?, ?, ?)')
                    ->execute([$item_id, $aluno_id, (int)$item['preco_moedas'], $status]);
                if ($item['categoria'] === 'avatar') {
                    $pdo->prepare('UPDATE aluno_avatar SET equipado = 0 WHERE aluno_id = ?')->execute([$aluno_id]);
                    $pdo->prepare('INSERT INTO aluno_avatar (aluno_id, item_id, equipado) VALUES (?, ?, 1)')
                        ->execute([$aluno_id, $item_id]);
                }
                $pdo->commit();
                recalcular_perfil($aluno_id);
                flash_set($item['categoria'] === 'avatar'
                    ? 'Item comprado e equipado! ' . $item['icone']
                    : 'Resgate solicitado! Seu professor vai aprovar. ' . $item['icone']);
            } catch (PDOException $e) {
                $pdo->rollBack();
                log_erro('Loja', $e->getMessage());
                flash_set('Erro ao processar a compra. Tente novamente.', 'erro');
            }
        }
    }
    redirect(BASE_URL . '/aluno/loja.php');
}

// ── Equipar/desequipar moldura ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipar']) && csrf_valido($_POST['csrf_token'] ?? '')) {
    $item_id = (int)$_POST['equipar'];
    $pdo->prepare('UPDATE aluno_avatar SET equipado = 0 WHERE aluno_id = ?')->execute([$aluno_id]);
    if ($item_id > 0) {
        $pdo->prepare('UPDATE aluno_avatar SET equipado = 1 WHERE aluno_id = ? AND item_id = ?')->execute([$aluno_id, $item_id]);
    }
    flash_set('Avatar atualizado!');
    redirect(BASE_URL . '/aluno/loja.php');
}

$saldo = saldo_moedas($aluno_id);

$itens = $pdo->prepare('SELECT * FROM loja_itens WHERE escola_id = ? AND ativo = 1 ORDER BY categoria, preco_moedas');
$itens->execute([escola_id()]);
$itens = $itens->fetchAll();

$meus_avatares = $pdo->prepare(
    'SELECT aa.*, li.nome, li.icone FROM aluno_avatar aa JOIN loja_itens li ON li.id = aa.item_id WHERE aa.aluno_id = ?'
);
$meus_avatares->execute([$aluno_id]);
$meus_avatares = $meus_avatares->fetchAll();

$resgates = $pdo->prepare(
    'SELECT lr.*, li.nome, li.icone FROM loja_resgates lr JOIN loja_itens li ON li.id = lr.item_id
     WHERE lr.aluno_id = ? ORDER BY lr.criado_em DESC LIMIT 10'
);
$resgates->execute([$aluno_id]);
$resgates = $resgates->fetchAll();

$cat_nome = ['avatar' => '🖼️ Avatar', 'privilegio' => '🎟️ Privilégios', 'material' => '🎁 Recompensas'];
$st_badge = ['pendente' => 'ba', 'aprovado' => 'bb', 'entregue' => 'bg', 'cancelado' => 'bk'];

pagina_inicio('Loja', 'loja');
?>
<div class="card" style="display:flex;justify-content:space-between;align-items:center;">
  <div style="font-size:15px;font-weight:800;">Seu saldo</div>
  <div style="font-size:24px;font-weight:800;color:#d97706;">🪙 <?= fmt_num($saldo) ?> GamiCoins</div>
</div>
<div class="alert alert-info">💡 GamiCoins são ganhas junto com o XP e <strong>nunca são retiradas</strong> por comportamento. Gastar é escolha sua — e se um resgate for cancelado, as moedas voltam.</div>

<?php foreach (['avatar', 'privilegio', 'material'] as $cat):
  $do_grupo = array_filter($itens, fn($i) => $i['categoria'] === $cat);
  if (!$do_grupo) continue;
?>
<div class="section-label"><?= $cat_nome[$cat] ?></div>
<div class="loja-grid" style="margin-bottom:1.25rem;">
  <?php foreach ($do_grupo as $i):
    $tem = $cat === 'avatar' && array_filter($meus_avatares, fn($a) => (int)$a['item_id'] === (int)$i['id']);
  ?>
  <div class="loja-item">
    <div class="loja-icone"><?= $i['icone'] ?></div>
    <div style="font-size:14px;font-weight:800;"><?= e($i['nome']) ?></div>
    <?php if ($i['descricao']): ?><div style="font-size:12px;color:#888;font-weight:600;margin-top:2px;"><?= e($i['descricao']) ?></div><?php endif; ?>
    <div class="loja-preco">🪙 <?= (int)$i['preco_moedas'] ?></div>
    <?php if ($tem): ?>
      <span class="badge bg">✅ Já é seu</span>
    <?php else: ?>
      <form method="POST">
        <?= csrf_input() ?>
        <button type="submit" name="comprar" value="<?= (int)$i['id'] ?>" class="btn <?= $saldo >= $i['preco_moedas'] ? 'btn-primary' : 'btn-outline' ?>" <?= $saldo < $i['preco_moedas'] ? 'disabled title="Saldo insuficiente"' : '' ?>>
          <?= $saldo >= $i['preco_moedas'] ? 'Comprar' : 'Faltam ' . ($i['preco_moedas'] - $saldo) . ' 🪙' ?>
        </button>
      </form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>

<?php if ($meus_avatares): ?>
<div class="card">
  <div class="card-title">🖼️ Minhas molduras — clique para equipar</div>
  <form method="POST" style="display:flex;gap:8px;flex-wrap:wrap;">
    <?= csrf_input() ?>
    <?php foreach ($meus_avatares as $a): ?>
      <button type="submit" name="equipar" value="<?= (int)$a['item_id'] ?>" class="humor-btn" style="<?= $a['equipado'] ? 'border-color:#7c6ef0;background:#f5f3ff;' : '' ?>" title="<?= e($a['nome']) ?>"><?= $a['icone'] ?></button>
    <?php endforeach; ?>
    <button type="submit" name="equipar" value="0" class="btn btn-outline" style="align-self:center;">Sem moldura</button>
  </form>
</div>
<?php endif; ?>

<?php if ($resgates): ?>
<div class="card">
  <div class="card-title">📦 Meus resgates</div>
  <table>
    <tr><th>Item</th><th>Moedas</th><th>Status</th><th>Data</th></tr>
    <?php foreach ($resgates as $r): ?>
    <tr>
      <td><?= $r['icone'] ?> <?= e($r['nome']) ?></td>
      <td>🪙 <?= (int)$r['moedas_gastas'] ?></td>
      <td><span class="badge <?= $st_badge[$r['status']] ?>"><?= ucfirst($r['status']) ?></span></td>
      <td><?= date('d/m/Y', strtotime($r['criado_em'])) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif; ?>
<?php pagina_fim(); ?>
