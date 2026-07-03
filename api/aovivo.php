<?php
// ============================================================
//  api/aovivo.php — Estado e respostas do Modo Ao Vivo (polling)
//  GET  ?sessao=ID            → estado atual (JSON)
//  POST sessao, alternativa   → registra resposta do aluno
// ============================================================
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_perfil(['aluno', 'professor', 'admin']);

header('Content-Type: application/json; charset=utf-8');

$pdo       = db();
$uid       = usuario_id();
$sessao_id = (int)($_GET['sessao'] ?? $_POST['sessao'] ?? 0);

$s = $pdo->prepare(
    'SELECT s.*, a.titulo, a.xp_recompensa, a.turma_id
     FROM sessoes_ao_vivo s JOIN atividades a ON a.id = s.atividade_id
     WHERE s.id = ?'
);
$s->execute([$sessao_id]);
$sessao = $s->fetch();
if (!$sessao) { echo json_encode(['erro' => 'Sessão não encontrada']); exit; }

$eh_professor = in_array(usuario_perfil(), ['professor', 'admin'], true) && (int)$sessao['professor_id'] === $uid;

// Questão em exibição (pela ordem)
$questao = null;
if ($sessao['questao_atual'] > 0) {
    $q = $pdo->prepare(
        'SELECT id, enunciado FROM quiz_questoes WHERE atividade_id = ? ORDER BY ordem, id LIMIT 1 OFFSET ' . ((int)$sessao['questao_atual'] - 1)
    );
    $q->execute([$sessao['atividade_id']]);
    $questao = $q->fetch() ?: null;
}

// ── POST: aluno responde ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && usuario_perfil() === 'aluno') {
    if (!csrf_valido($_POST['csrf_token'] ?? '')) { echo json_encode(['erro' => 'CSRF']); exit; }
    if ($sessao['status'] !== 'questao' || !$questao) { echo json_encode(['erro' => 'Fora do tempo']); exit; }

    $decorrido = time() - strtotime($sessao['questao_iniciada_em']);
    if ($decorrido > (int)$sessao['tempo_questao'] + 2) { echo json_encode(['erro' => 'Tempo esgotado']); exit; }

    $alt_id = (int)($_POST['alternativa'] ?? 0);
    $alt = $pdo->prepare('SELECT correta FROM quiz_alternativas WHERE id = ? AND questao_id = ?');
    $alt->execute([$alt_id, $questao['id']]);
    $a = $alt->fetch();
    if (!$a) { echo json_encode(['erro' => 'Alternativa inválida']); exit; }

    $correta = (int)$a['correta'];
    $restante = max(0, min((int)$sessao['tempo_questao'], (int)$sessao['tempo_questao'] - $decorrido));
    $pontos = $correta ? 100 + (int)round(100 * $restante / max(1, (int)$sessao['tempo_questao'])) : 0;

    $ins = $pdo->prepare(
        'INSERT IGNORE INTO sessao_respostas (sessao_id, questao_id, aluno_id, alternativa_id, correta, tempo_ms, pontos)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([$sessao_id, $questao['id'], $uid, $alt_id, $correta, $decorrido * 1000, $pontos]);
    if ($ins->rowCount() > 0 && $pontos > 0) {
        $pdo->prepare('UPDATE sessao_participantes SET pontos = pontos + ? WHERE sessao_id = ? AND aluno_id = ?')
            ->execute([$pontos, $sessao_id, $uid]);
    }
    echo json_encode(['ok' => true, 'correta' => $correta, 'pontos' => $pontos]);
    exit;
}

// ── GET: estado ──────────────────────────────────────────────
$resp = [
    'status'        => $sessao['status'],
    'titulo'        => $sessao['titulo'],
    'questao_atual' => (int)$sessao['questao_atual'],
    'tempo_questao' => (int)$sessao['tempo_questao'],
    'tempo_restante'=> null,
    'questao'       => null,
];

if ($sessao['status'] === 'questao' && $questao) {
    $resp['tempo_restante'] = max(0, (int)$sessao['tempo_questao'] - (time() - strtotime($sessao['questao_iniciada_em'])));
    $alts = $pdo->prepare('SELECT id, texto FROM quiz_alternativas WHERE questao_id = ? ORDER BY ordem, id');
    $alts->execute([$questao['id']]);
    $resp['questao'] = ['id' => (int)$questao['id'], 'enunciado' => $questao['enunciado'], 'alternativas' => $alts->fetchAll()];

    if (usuario_perfil() === 'aluno') {
        $r = $pdo->prepare('SELECT correta, pontos FROM sessao_respostas WHERE sessao_id = ? AND questao_id = ? AND aluno_id = ?');
        $r->execute([$sessao_id, $questao['id'], $uid]);
        $resp['minha_resposta'] = $r->fetch() ?: null;
    }
}

// Ranking parcial (revisão/encerrada/host)
if (in_array($sessao['status'], ['revisao', 'encerrada'], true) || $eh_professor) {
    $rk = $pdo->prepare(
        'SELECT u.nome, sp.pontos FROM sessao_participantes sp JOIN usuarios u ON u.id = sp.aluno_id
         WHERE sp.sessao_id = ? ORDER BY sp.pontos DESC, u.nome LIMIT 10'
    );
    $rk->execute([$sessao_id]);
    $resp['ranking'] = $rk->fetchAll();
}

if ($eh_professor && $questao) {
    $cnt = $pdo->prepare('SELECT COUNT(*) FROM sessao_respostas WHERE sessao_id = ? AND questao_id = ?');
    $cnt->execute([$sessao_id, $questao['id']]);
    $resp['respostas_recebidas'] = (int)$cnt->fetchColumn();
}
if ($eh_professor) {
    $cnt = $pdo->prepare('SELECT COUNT(*) FROM sessao_participantes WHERE sessao_id = ?');
    $cnt->execute([$sessao_id]);
    $resp['participantes'] = (int)$cnt->fetchColumn();
}

if (usuario_perfil() === 'aluno') {
    $p = $pdo->prepare('SELECT pontos FROM sessao_participantes WHERE sessao_id = ? AND aluno_id = ?');
    $p->execute([$sessao_id, $uid]);
    $resp['meus_pontos'] = (int)($p->fetchColumn() ?: 0);
}

echo json_encode($resp);
