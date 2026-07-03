<?php
// professor/familia.php — Feed positivo + mensagens com responsáveis
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_professor();
if (!modulo_ativo('familia')) { flash_set('O Portal da Família está desativado.', 'erro'); redirect(BASE_URL . '/professor/'); }

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
$in = $turma_ids ? implode(',', $turma_ids) : '0';

// ── Ações ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'postar') {
        $turma_id = (int)($_POST['turma_id'] ?? 0);
        $aluno_id = (int)($_POST['aluno_id'] ?? 0) ?: null;
        $tipo     = in_array($_POST['tipo'] ?? '', ['elogio', 'aviso', 'conquista'], true) ? $_POST['tipo'] : 'elogio';
        $titulo   = trim($_POST['titulo'] ?? '');
        if (in_array($turma_id, $turma_ids, true) && $titulo !== '') {
            $pdo->prepare(
                'INSERT INTO feed_familia (escola_id, turma_id, aluno_id, autor_id, tipo, titulo, mensagem) VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([escola_id(), $turma_id, $aluno_id, $prof_id, $tipo, $titulo, trim($_POST['mensagem'] ?? '') ?: null]);

            // Notifica responsáveis dos alunos afetados
            $resp = $aluno_id
                ? $pdo->prepare('SELECT responsavel_id FROM responsavel_aluno WHERE aluno_id = ?')
                : $pdo->prepare("SELECT DISTINCT ra.responsavel_id FROM responsavel_aluno ra JOIN aluno_turma at2 ON at2.usuario_id = ra.aluno_id AND at2.turma_id = ? AND at2.ativo = 1");
            $resp->execute([$aluno_id ?: $turma_id]);
            foreach ($resp->fetchAll(PDO::FETCH_COLUMN) as $rid) {
                notificar((int)$rid, 'familia', 'Novidade da escola! 👨‍👩‍👧', $titulo);
            }
            flash_set('Post publicado no feed da família! 👨‍👩‍👧');
        } else {
            flash_set('Preencha turma e título.', 'erro');
        }
    }

    if ($acao === 'mensagem') {
        $para = (int)($_POST['para_id'] ?? 0);
        $texto = trim($_POST['texto'] ?? '');
        $ok = $pdo->query(
            "SELECT 1 FROM responsavel_aluno ra
             JOIN aluno_turma at2 ON at2.usuario_id = ra.aluno_id AND at2.ativo = 1
             WHERE ra.responsavel_id = {$para} AND at2.turma_id IN ($in) LIMIT 1"
        )->fetch();
        if ($ok && $texto !== '') {
            $pdo->prepare('INSERT INTO mensagens (de_id, para_id, aluno_id, texto) VALUES (?, ?, ?, ?)')
                ->execute([$prof_id, $para, (int)($_POST['aluno_id'] ?? 0) ?: null, $texto]);
            notificar($para, 'mensagem', 'Nova mensagem do professor 💬', mb_substr($texto, 0, 80));
            flash_set('Mensagem enviada! 💬');
        }
        redirect(BASE_URL . '/professor/familia.php?conversa=' . $para);
    }
    if ($acao !== 'mensagem') redirect(BASE_URL . '/professor/familia.php');
}

// Alunos das minhas turmas (p/ post individual)
$alunos = $pdo->query(
    "SELECT u.id, u.nome, at2.turma_id FROM usuarios u
     JOIN aluno_turma at2 ON at2.usuario_id = u.id AND at2.ativo = 1
     WHERE at2.turma_id IN ($in) AND u.perfil = 'aluno' AND u.ativo = 1 ORDER BY u.nome"
)->fetchAll();

// Responsáveis vinculados às minhas turmas
$responsaveis = $pdo->query(
    "SELECT DISTINCT ur.id, ur.nome, ua.nome AS aluno_nome, ra.aluno_id
     FROM responsavel_aluno ra
     JOIN usuarios ur ON ur.id = ra.responsavel_id AND ur.ativo = 1
     JOIN usuarios ua ON ua.id = ra.aluno_id
     JOIN aluno_turma at2 ON at2.usuario_id = ra.aluno_id AND at2.ativo = 1
     WHERE at2.turma_id IN ($in) ORDER BY ur.nome"
)->fetchAll();

// Conversa selecionada
$conversa_id = (int)($_GET['conversa'] ?? 0);
$conversa = [];
if ($conversa_id) {
    $c = $pdo->prepare(
        'SELECT m.*, u.nome AS de_nome FROM mensagens m JOIN usuarios u ON u.id = m.de_id
         WHERE (m.de_id = ? AND m.para_id = ?) OR (m.de_id = ? AND m.para_id = ?)
         ORDER BY m.criado_em ASC LIMIT 100'
    );
    $c->execute([$prof_id, $conversa_id, $conversa_id, $prof_id]);
    $conversa = $c->fetchAll();
    $pdo->prepare('UPDATE mensagens SET lida = 1 WHERE de_id = ? AND para_id = ?')->execute([$conversa_id, $prof_id]);
}

// Feed recente
$feed = $pdo->query(
    "SELECT f.*, t.nome AS turma_nome, ua.nome AS aluno_nome FROM feed_familia f
     LEFT JOIN turmas t ON t.id = f.turma_id
     LEFT JOIN usuarios ua ON ua.id = f.aluno_id
     WHERE f.turma_id IN ($in) ORDER BY f.criado_em DESC LIMIT 10"
)->fetchAll();

$tipo_cfg = ['elogio' => ['bg', '💚 Elogio'], 'aviso' => ['bb', '📢 Aviso'], 'conquista' => ['ba', '🏅 Conquista']];

pagina_inicio('Portal da Família', 'familia');
?>
<div class="two-col" style="grid-template-columns:1fr 1fr;">
  <div>
    <div class="card">
      <div class="card-title">➕ Publicar no feed (sempre positivo 💜)</div>
      <form method="POST">
        <?= csrf_input() ?>
        <input type="hidden" name="acao" value="postar">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
          <div class="fld"><label>Turma</label>
            <select name="turma_id" id="feedTurma"><?php foreach ($turmas as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['nome']) ?></option><?php endforeach; ?></select>
          </div>
          <div class="fld"><label>Aluno (opcional)</label>
            <select name="aluno_id"><option value="0">Turma inteira</option>
              <?php foreach ($alunos as $a): ?><option value="<?= (int)$a['id'] ?>" data-turma="<?= (int)$a['turma_id'] ?>"><?= e($a['nome']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="fld"><label>Tipo</label>
            <select name="tipo"><option value="elogio">💚 Elogio</option><option value="aviso">📢 Aviso</option><option value="conquista">🏅 Conquista</option></select>
          </div>
        </div>
        <div class="fld"><label>Título</label><input type="text" name="titulo" required maxlength="150"></div>
        <div class="fld"><label>Mensagem</label><textarea name="mensagem" rows="2"></textarea></div>
        <button type="submit" class="btn btn-primary">Publicar →</button>
      </form>
    </div>

    <div class="card">
      <div class="card-title">📰 Últimos posts</div>
      <?php if (empty($feed)): ?><div style="color:#aaa;font-size:13px;font-weight:700;">Nenhum post ainda.</div><?php endif; ?>
      <?php foreach ($feed as $f): [$cls, $rot] = $tipo_cfg[$f['tipo']]; ?>
      <div class="feed-post" style="padding:12px 14px;">
        <div style="display:flex;justify-content:space-between;gap:8px;">
          <span class="badge <?= $cls ?>"><?= $rot ?></span>
          <span style="font-size:11px;color:#aaa;font-weight:700;"><?= date('d/m H:i', strtotime($f['criado_em'])) ?></span>
        </div>
        <div style="font-size:14px;font-weight:800;margin-top:6px;"><?= e($f['titulo']) ?></div>
        <div style="font-size:11px;color:#888;font-weight:700;"><?= e($f['turma_nome'] ?? '') ?><?= $f['aluno_nome'] ? ' · ' . e($f['aluno_nome']) : ' · turma inteira' ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-title">💬 Conversas com responsáveis</div>
      <?php if (empty($responsaveis)): ?>
        <div style="color:#aaa;font-size:13px;font-weight:700;">Nenhum responsável cadastrado nas suas turmas. O admin vincula responsáveis aos alunos em Usuários.</div>
      <?php endif; ?>
      <?php foreach ($responsaveis as $r): ?>
        <a href="?conversa=<?= (int)$r['id'] ?>" class="rank-row" style="text-decoration:none;color:inherit;<?= $conversa_id === (int)$r['id'] ? 'background:#f5f3ff;border-radius:10px;' : '' ?>">
          <span>👨‍👩‍👧</span>
          <div style="flex:1;">
            <div style="font-size:13px;font-weight:800;"><?= e($r['nome']) ?></div>
            <div style="font-size:11px;color:#aaa;">responsável por <?= e($r['aluno_nome']) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($conversa_id): ?>
    <div class="card">
      <div class="card-title">💬 Conversa</div>
      <div class="chat-box">
        <?php if (empty($conversa)): ?><div style="color:#aaa;font-size:12px;font-weight:700;text-align:center;">Comece a conversa!</div><?php endif; ?>
        <?php foreach ($conversa as $msg): $eu = (int)$msg['de_id'] === $prof_id; ?>
        <div class="chat-msg <?= $eu ? 'chat-eu' : 'chat-outro' ?>">
          <?= nl2br(e($msg['texto'])) ?>
          <div class="chat-hora"><?= date('d/m H:i', strtotime($msg['criado_em'])) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <form method="POST" style="display:flex;gap:8px;margin-top:10px;">
        <?= csrf_input() ?>
        <input type="hidden" name="acao" value="mensagem">
        <input type="hidden" name="para_id" value="<?= $conversa_id ?>">
        <input type="text" name="texto" required placeholder="Escreva sua mensagem..." style="flex:1;">
        <button type="submit" class="btn btn-primary">Enviar</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php pagina_fim(); ?>
