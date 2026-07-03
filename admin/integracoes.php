<?php
// admin/integracoes.php — Google Classroom / Microsoft 365 (estrutura pronta)
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_admin();

$pdo = db();
$eid = escola_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    $servico = in_array($_POST['servico'] ?? '', ['google_classroom', 'microsoft_teams'], true) ? $_POST['servico'] : 'google_classroom';
    $cid  = trim($_POST['client_id'] ?? '');
    $csec = trim($_POST['client_secret'] ?? '');
    $pdo->prepare(
        'INSERT INTO integracoes (escola_id, servico, client_id, client_secret, ativo)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE client_id = VALUES(client_id), client_secret = VALUES(client_secret), ativo = VALUES(ativo)'
    )->execute([$eid, $servico, $cid ?: null, $csec ?: null, (int)($cid !== '' && $csec !== '')]);
    flash_set($cid !== '' && $csec !== ''
        ? 'Credenciais salvas — integração pronta para ativação com a escola. 🔗'
        : 'Credenciais salvas (integração inativa até preencher Client ID e Secret).');
    redirect(BASE_URL . '/admin/integracoes.php');
}

$ints = $pdo->prepare('SELECT * FROM integracoes WHERE escola_id = ?');
$ints->execute([$eid]);
$ints = array_column($ints->fetchAll(), null, 'servico');

$vinculos = $pdo->query(
    'SELECT tv.*, t.nome AS turma_nome FROM turma_vinculos tv JOIN turmas t ON t.id = tv.turma_id ORDER BY t.nome'
)->fetchAll();

pagina_inicio('Integrações', 'integracoes');
?>
<div class="alert alert-info">🔗 A estrutura de integração está pronta (credenciais por escola + vínculo turma ↔ curso). Para ativar o <strong>Google Classroom</strong>, crie um projeto no Google Cloud Console da escola, habilite a API do Classroom e informe as credenciais abaixo. A sincronização de alunos é liberada com credenciais válidas.</div>

<?php foreach ([
    'google_classroom' => ['🟢 Google Classroom', 'Importação de turmas e alunos do Classroom.'],
    'microsoft_teams'  => ['🟦 Microsoft 365 / Teams', 'Prevista — mesma estrutura de credenciais.'],
] as $srv => [$titulo, $desc]):
    $cfg = $ints[$srv] ?? null; ?>
<div class="card">
  <div class="card-title">
    <span><?= $titulo ?></span>
    <span class="badge <?= ($cfg['ativo'] ?? 0) ? 'bg' : 'bk' ?>"><?= ($cfg['ativo'] ?? 0) ? 'Configurada' : 'Inativa' ?></span>
  </div>
  <p style="font-size:13px;color:#888;font-weight:600;margin-bottom:12px;"><?= $desc ?></p>
  <form method="POST" style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end;">
    <?= csrf_input() ?>
    <input type="hidden" name="servico" value="<?= $srv ?>">
    <div class="fld" style="margin:0;"><label>Client ID</label><input type="text" name="client_id" value="<?= e($cfg['client_id'] ?? '') ?>" autocomplete="off"></div>
    <div class="fld" style="margin:0;"><label>Client Secret</label><input type="password" name="client_secret" value="<?= e($cfg['client_secret'] ?? '') ?>" autocomplete="off"></div>
    <button type="submit" class="btn btn-primary">Salvar</button>
  </form>
</div>
<?php endforeach; ?>

<div class="card">
  <div class="card-title">🔁 Turmas vinculadas a cursos externos</div>
  <?php if (empty($vinculos)): ?>
    <div style="color:#aaa;font-size:13px;font-weight:700;">Nenhum vínculo ainda — disponível após configurar as credenciais e autorizar o acesso da escola.</div>
  <?php else: ?>
  <table>
    <tr><th>Turma</th><th>Serviço</th><th>Curso externo</th><th>Última sincronização</th></tr>
    <?php foreach ($vinculos as $v): ?>
    <tr>
      <td><?= e($v['turma_nome']) ?></td>
      <td><?= e($v['servico']) ?></td>
      <td><?= e($v['externo_nome'] ?? $v['externo_id']) ?></td>
      <td><?= $v['ultima_sync'] ? date('d/m/Y H:i', strtotime($v['ultima_sync'])) : '—' ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php pagina_fim(); ?>
