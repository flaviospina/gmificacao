<?php
// responsavel/mensagens.php — Conversa com os professores dos filhos
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_responsavel();

$pdo     = db();
$resp_id = usuario_id();

// Professores das turmas dos meus filhos
$professores = $pdo->prepare(
    'SELECT DISTINCT up.id, up.nome, t.nome AS turma_nome, pt.disciplina
     FROM responsavel_aluno ra
     JOIN aluno_turma at2 ON at2.usuario_id = ra.aluno_id AND at2.ativo = 1
     JOIN turmas t ON t.id = at2.turma_id
     JOIN professor_turma pt ON pt.turma_id = t.id AND pt.ativo = 1
     JOIN usuarios up ON up.id = pt.professor_id AND up.ativo = 1
     WHERE ra.responsavel_id = ?
     ORDER BY up.nome'
);
$professores->execute([$resp_id]);
$professores = $professores->fetchAll();
$prof_ids = array_map('intval', array_column($professores, 'id'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    $para  = (int)($_POST['para_id'] ?? 0);
    $texto = trim($_POST['texto'] ?? '');
    if (in_array($para, $prof_ids, true) && $texto !== '') {
        $pdo->prepare('INSERT INTO mensagens (de_id, para_id, texto) VALUES (?, ?, ?)')
            ->execute([$resp_id, $para, $texto]);
        notificar($para, 'mensagem', 'Nova mensagem de responsável 💬', mb_substr($texto, 0, 80));
        flash_set('Mensagem enviada! 💬');
    }
    redirect(BASE_URL . '/responsavel/mensagens.php?conversa=' . $para);
}

$conversa_id = (int)($_GET['conversa'] ?? 0);
if (!in_array($conversa_id, $prof_ids, true)) $conversa_id = $prof_ids[0] ?? 0;

$conversa = [];
if ($conversa_id) {
    $c = $pdo->prepare(
        'SELECT m.* FROM mensagens m
         WHERE (m.de_id = ? AND m.para_id = ?) OR (m.de_id = ? AND m.para_id = ?)
         ORDER BY m.criado_em ASC LIMIT 100'
    );
    $c->execute([$resp_id, $conversa_id, $conversa_id, $resp_id]);
    $conversa = $c->fetchAll();
    $pdo->prepare('UPDATE mensagens SET lida = 1 WHERE de_id = ? AND para_id = ?')->execute([$conversa_id, $resp_id]);
}

pagina_inicio('Mensagens', 'mensagens');
?>
<div class="two-col" style="grid-template-columns:280px 1fr;">
  <div class="card">
    <div class="card-title">🧑‍🏫 Professores</div>
    <?php if (empty($professores)): ?><div style="color:#aaa;font-size:13px;font-weight:700;">Nenhum professor disponível.</div><?php endif; ?>
    <?php foreach ($professores as $p): ?>
      <a href="?conversa=<?= (int)$p['id'] ?>" class="rank-row" style="text-decoration:none;color:inherit;<?= $conversa_id === (int)$p['id'] ? 'background:#f5f3ff;border-radius:10px;' : '' ?>">
        <span>🧑‍🏫</span>
        <div style="flex:1;">
          <div style="font-size:13px;font-weight:800;"><?= e($p['nome']) ?></div>
          <div style="font-size:11px;color:#aaa;"><?= e($p['disciplina']) ?> · Turma <?= e($p['turma_nome']) ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-title">💬 Conversa</div>
    <?php if (!$conversa_id): ?>
      <div style="color:#aaa;font-size:13px;font-weight:700;text-align:center;padding:1.5rem;">Escolha um professor ao lado.</div>
    <?php else: ?>
    <div class="chat-box">
      <?php if (empty($conversa)): ?><div style="color:#aaa;font-size:12px;font-weight:700;text-align:center;">Envie a primeira mensagem!</div><?php endif; ?>
      <?php foreach ($conversa as $m): $eu = (int)$m['de_id'] === $resp_id; ?>
      <div class="chat-msg <?= $eu ? 'chat-eu' : 'chat-outro' ?>">
        <?= nl2br(e($m['texto'])) ?>
        <div class="chat-hora"><?= date('d/m H:i', strtotime($m['criado_em'])) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <form method="POST" style="display:flex;gap:8px;margin-top:10px;">
      <?= csrf_input() ?>
      <input type="hidden" name="para_id" value="<?= $conversa_id ?>">
      <input type="text" name="texto" required placeholder="Escreva sua mensagem..." style="flex:1;">
      <button type="submit" class="btn btn-primary">Enviar</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php pagina_fim(); ?>
