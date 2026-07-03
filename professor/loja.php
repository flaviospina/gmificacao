<?php
// professor/loja.php — Aprovar/entregar/cancelar resgates da loja
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_professor();
if (!modulo_ativo('loja')) { flash_set('A loja está desativada.', 'erro'); redirect(BASE_URL . '/professor/'); }

$pdo     = db();
$prof_id = usuario_id();

$turma_ids = $pdo->prepare('SELECT DISTINCT turma_id FROM professor_turma WHERE professor_id = ? AND ativo = 1');
$turma_ids->execute([$prof_id]);
$turma_ids = array_map('intval', $turma_ids->fetchAll(PDO::FETCH_COLUMN));
$in = $turma_ids ? implode(',', $turma_ids) : '0';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    $resgate_id = (int)($_POST['resgate_id'] ?? 0);
    $acao = $_POST['acao'] ?? '';

    $r = $pdo->query(
        "SELECT lr.*, li.nome AS item_nome, li.icone FROM loja_resgates lr
         JOIN loja_itens li ON li.id = lr.item_id
         JOIN aluno_turma at2 ON at2.usuario_id = lr.aluno_id AND at2.ativo = 1
         WHERE lr.id = {$resgate_id} AND at2.turma_id IN ($in) LIMIT 1"
    )->fetch();

    if ($r && in_array($acao, ['aprovado', 'entregue', 'cancelado'], true) && $r['status'] !== 'cancelado') {
        $pdo->prepare('UPDATE loja_resgates SET status = ? WHERE id = ?')->execute([$acao, $resgate_id]);
        if ($acao === 'cancelado') {
            // Estorno integral — cancelamento não é punição
            $pdo->prepare('INSERT INTO moeda_eventos (aluno_id, tipo, origem, referencia_id, moedas) VALUES (?, "estorno", "loja", ?, ?)')
                ->execute([(int)$r['aluno_id'], $resgate_id, (int)$r['moedas_gastas']]);
            recalcular_perfil((int)$r['aluno_id']);
            notificar((int)$r['aluno_id'], 'resgate', 'Resgate cancelado — moedas devolvidas 🪙', $r['icone'] . ' ' . $r['item_nome'] . ' — suas ' . $r['moedas_gastas'] . ' moedas voltaram.');
            flash_set('Resgate cancelado e moedas devolvidas ao aluno.');
        } else {
            notificar((int)$r['aluno_id'], 'resgate', 'Resgate ' . $acao . '! 🎁', $r['icone'] . ' ' . $r['item_nome']);
            flash_set('Resgate marcado como ' . $acao . '.');
        }
    }
    redirect(BASE_URL . '/professor/loja.php');
}

$resgates = $pdo->query(
    "SELECT lr.*, li.nome AS item_nome, li.icone, li.categoria, u.nome AS aluno_nome, t.nome AS turma_nome
     FROM loja_resgates lr
     JOIN loja_itens li ON li.id = lr.item_id
     JOIN usuarios u ON u.id = lr.aluno_id
     JOIN aluno_turma at2 ON at2.usuario_id = lr.aluno_id AND at2.ativo = 1
     JOIN turmas t ON t.id = at2.turma_id
     WHERE at2.turma_id IN ($in) AND li.categoria <> 'avatar'
     GROUP BY lr.id
     ORDER BY lr.status = 'pendente' DESC, lr.criado_em DESC
     LIMIT 50"
)->fetchAll();

$st_badge = ['pendente' => 'ba', 'aprovado' => 'bb', 'entregue' => 'bg', 'cancelado' => 'bk'];

pagina_inicio('Resgates da Loja', 'loja');
?>
<div class="alert alert-info">🛍️ Alunos trocam GamiCoins por privilégios e recompensas. Aprove, marque como entregue ou cancele (com <strong>estorno automático</strong> — cancelar nunca vira punição).</div>

<div class="card">
  <?php if (empty($resgates)): ?>
    <div style="color:#aaa;font-weight:700;font-size:13px;text-align:center;padding:1.5rem;">Nenhum resgate por enquanto.</div>
  <?php else: ?>
  <table>
    <tr><th>Aluno</th><th>Turma</th><th>Item</th><th>Moedas</th><th>Status</th><th>Data</th><th>Ações</th></tr>
    <?php foreach ($resgates as $r): ?>
    <tr>
      <td><?= e($r['aluno_nome']) ?></td>
      <td><?= e($r['turma_nome']) ?></td>
      <td><?= $r['icone'] ?> <?= e($r['item_nome']) ?></td>
      <td>🪙 <?= (int)$r['moedas_gastas'] ?></td>
      <td><span class="badge <?= $st_badge[$r['status']] ?>"><?= ucfirst($r['status']) ?></span></td>
      <td><?= date('d/m H:i', strtotime($r['criado_em'])) ?></td>
      <td>
        <?php if (in_array($r['status'], ['pendente', 'aprovado'], true)): ?>
        <form method="POST" style="display:inline-flex;gap:4px;">
          <?= csrf_input() ?>
          <input type="hidden" name="resgate_id" value="<?= (int)$r['id'] ?>">
          <?php if ($r['status'] === 'pendente'): ?>
            <button type="submit" name="acao" value="aprovado" class="btn btn-primary" style="font-size:11px;padding:5px 10px;">Aprovar</button>
          <?php endif; ?>
          <button type="submit" name="acao" value="entregue" class="btn btn-outline" style="font-size:11px;padding:5px 10px;">Entregue</button>
          <button type="submit" name="acao" value="cancelado" class="btn btn-outline" style="font-size:11px;padding:5px 10px;" onclick="return confirm('Cancelar e devolver as moedas?')">Cancelar</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php pagina_fim(); ?>
