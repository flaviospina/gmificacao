<?php
// professor/metas.php — Metas Coletivas de Turma [inédito]
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_professor();
if (!modulo_ativo('metas')) { flash_set('O módulo de metas está desativado.', 'erro'); redirect(BASE_URL . '/professor/'); }

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    $turma_id = (int)($_POST['turma_id'] ?? 0);
    $titulo   = trim($_POST['titulo'] ?? '');
    $tipo     = in_array($_POST['tipo'] ?? '', ['xp', 'missoes', 'checkins'], true) ? $_POST['tipo'] : 'xp';
    $alvo     = max(1, (int)($_POST['alvo'] ?? 0));
    $fim      = $_POST['data_fim'] ?? '';

    if (!in_array($turma_id, $turma_ids, true) || $titulo === '' || !$fim || $fim < date('Y-m-d')) {
        flash_set('Preencha turma, título e uma data futura.', 'erro');
    } else {
        $pdo->prepare(
            'INSERT INTO metas_turma (turma_id, criador_id, titulo, descricao, tipo, alvo, recompensa_xp, recompensa_moedas, data_inicio, data_fim)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)'
        )->execute([
            $turma_id, $prof_id, $titulo, trim($_POST['descricao'] ?? ''), $tipo, $alvo,
            max(0, (int)($_POST['recompensa_xp'] ?? 0)), max(0, (int)($_POST['recompensa_moedas'] ?? 0)), $fim,
        ]);
        notificar_turma($turma_id, 'meta', 'Nova meta da turma! 🎯', $titulo . ' — juntos até ' . date('d/m', strtotime($fim)));
        flash_set('Meta criada e turma notificada! 🎯');
    }
    redirect(BASE_URL . '/professor/metas.php');
}

$in = $turma_ids ? implode(',', $turma_ids) : '0';
$metas = $pdo->query(
    "SELECT m.*, t.nome AS turma_nome FROM metas_turma m JOIN turmas t ON t.id = m.turma_id
     WHERE m.turma_id IN ($in) ORDER BY m.status = 'ativa' DESC, m.data_fim DESC"
)->fetchAll();

// Expira metas vencidas
foreach ($metas as &$m) {
    if ($m['status'] === 'ativa' && $m['data_fim'] < date('Y-m-d')) {
        $pdo->prepare("UPDATE metas_turma SET status = 'expirada' WHERE id = ?")->execute([$m['id']]);
        $m['status'] = 'expirada';
    }
}
unset($m);

$tipo_rotulo = ['xp' => 'XP somado da turma', 'missoes' => 'missões concluídas', 'checkins' => 'check-ins de bem-estar'];
$st_badge = ['ativa' => 'bg', 'atingida' => 'bp', 'expirada' => 'bk'];

pagina_inicio('Metas Coletivas', 'metas');
?>
<div class="alert alert-info">🎯 <strong>Metas Coletivas (exclusivo Gamifica):</strong> a turma inteira persegue um objetivo. Se atingir, <u>todos</u> ganham a recompensa — se não atingir, ninguém perde nada. Cooperação em vez de competição.</div>

<div class="card">
  <div class="card-title">➕ Nova meta</div>
  <form method="POST">
    <?= csrf_input() ?>
    <div style="display:grid;grid-template-columns:1fr 2fr;gap:12px;">
      <div class="fld"><label>Turma</label>
        <select name="turma_id"><?php foreach ($turmas as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['nome']) ?></option><?php endforeach; ?></select>
      </div>
      <div class="fld"><label>Título</label><input type="text" name="titulo" required maxlength="150" placeholder="Ex.: 9A rumo aos 5.000 XP!"></div>
    </div>
    <div class="fld"><label>Descrição (opcional)</label><input type="text" name="descricao" maxlength="255"></div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
      <div class="fld"><label>Tipo de meta</label>
        <select name="tipo">
          <option value="xp">XP somado da turma</option>
          <option value="missoes">Nº de missões concluídas</option>
          <option value="checkins">Nº de check-ins de bem-estar</option>
        </select>
      </div>
      <div class="fld"><label>Alvo</label><input type="number" name="alvo" min="1" required placeholder="5000"></div>
      <div class="fld"><label>Prazo</label><input type="date" name="data_fim" required min="<?= date('Y-m-d') ?>"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div class="fld"><label>Recompensa: XP para cada aluno</label><input type="number" name="recompensa_xp" min="0" value="50"></div>
      <div class="fld"><label>Recompensa: GamiCoins para cada aluno</label><input type="number" name="recompensa_moedas" min="0" value="5"></div>
    </div>
    <button type="submit" class="btn btn-primary">Criar meta →</button>
  </form>
</div>

<?php foreach ($metas as $m):
  $prog = progresso_meta($m);
  $pct = min(100, round(100 * $prog / max(1, (int)$m['alvo'])));
?>
<div class="card">
  <div class="card-title">
    <span>🎯 <?= e($m['titulo']) ?> <span class="badge bb">Turma <?= e($m['turma_nome']) ?></span></span>
    <span class="badge <?= $st_badge[$m['status']] ?>"><?= $m['status'] === 'atingida' ? '🏆 Atingida' : ucfirst($m['status']) ?></span>
  </div>
  <div class="progress" style="height:10px;"><div class="prog-fill" style="width:<?= $pct ?>%;background:linear-gradient(90deg,#7c6ef0,#a855f7);"></div></div>
  <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:700;color:#888;margin-top:5px;">
    <span><?= fmt_num($prog) ?> / <?= fmt_num((int)$m['alvo']) ?> <?= $tipo_rotulo[$m['tipo']] ?> (<?= $pct ?>%)</span>
    <span><?= date('d/m/Y', strtotime($m['data_inicio'])) ?> → <?= date('d/m/Y', strtotime($m['data_fim'])) ?> · +<?= (int)$m['recompensa_xp'] ?> XP · 🪙 <?= (int)$m['recompensa_moedas'] ?> p/ aluno</span>
  </div>
</div>
<?php endforeach; ?>
<?php pagina_fim(); ?>
