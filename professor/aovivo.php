<?php
// professor/aovivo.php — Hospedar quiz ao vivo (PIN, controle de questões)
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_professor();
if (!modulo_ativo('aovivo')) { flash_set('O Modo Ao Vivo está desativado.', 'erro'); redirect(BASE_URL . '/professor/'); }

$pdo     = db();
$prof_id = usuario_id();

// ── Ações ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        $atv_id = (int)($_POST['atividade_id'] ?? 0);
        $chk = $pdo->prepare(
            "SELECT a.id FROM atividades a
             WHERE a.id = ? AND a.professor_id = ? AND a.tipo IN ('quiz','leitura') AND a.publicada = 1"
        );
        $chk->execute([$atv_id, $prof_id]);
        if ($chk->fetch()) {
            $pin = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $pdo->prepare(
                'INSERT INTO sessoes_ao_vivo (atividade_id, professor_id, pin, tempo_questao) VALUES (?, ?, ?, ?)'
            )->execute([$atv_id, $prof_id, $pin, max(10, min(120, (int)($_POST['tempo'] ?? config('aovivo_tempo_questao', '30'))))]);
            redirect(BASE_URL . '/professor/aovivo.php?sessao=' . (int)$pdo->lastInsertId());
        }
        flash_set('Escolha um quiz publicado para o modo ao vivo.', 'erro');
        redirect(BASE_URL . '/professor/aovivo.php');
    }

    $sessao_id = (int)($_POST['sessao'] ?? 0);
    $s = $pdo->prepare('SELECT s.*, a.xp_recompensa, a.titulo, a.turma_id FROM sessoes_ao_vivo s JOIN atividades a ON a.id = s.atividade_id WHERE s.id = ? AND s.professor_id = ?');
    $s->execute([$sessao_id, $prof_id]);
    $sessao = $s->fetch();

    if ($sessao) {
        $qn = $pdo->prepare('SELECT COUNT(*) FROM quiz_questoes WHERE atividade_id = ?');
        $qn->execute([$sessao['atividade_id']]);
        $total_questoes = (int)$qn->fetchColumn();

        if ($acao === 'proxima' && $sessao['status'] !== 'encerrada') {
            if ((int)$sessao['questao_atual'] < $total_questoes) {
                $pdo->prepare("UPDATE sessoes_ao_vivo SET status = 'questao', questao_atual = questao_atual + 1, questao_iniciada_em = NOW() WHERE id = ?")
                    ->execute([$sessao_id]);
            } else {
                flash_set('Todas as questões já foram exibidas — encerre a sessão.', 'erro');
            }
        }
        if ($acao === 'revisao' && $sessao['status'] === 'questao') {
            $pdo->prepare("UPDATE sessoes_ao_vivo SET status = 'revisao' WHERE id = ?")->execute([$sessao_id]);
        }
        if ($acao === 'encerrar' && $sessao['status'] !== 'encerrada') {
            $pdo->prepare("UPDATE sessoes_ao_vivo SET status = 'encerrada', encerrada_em = NOW() WHERE id = ?")->execute([$sessao_id]);

            // Converte pontos em XP — só pode MELHORAR o resultado do aluno
            $max_pontos = max(1, $total_questoes * 200);
            $parts = $pdo->prepare('SELECT aluno_id, pontos FROM sessao_participantes WHERE sessao_id = ?');
            $parts->execute([$sessao_id]);
            foreach ($parts->fetchAll() as $p) {
                $xp = (int)round((int)$sessao['xp_recompensa'] * min(1, $p['pontos'] / $max_pontos));
                $acertos = $pdo->prepare('SELECT COUNT(*) FROM sessao_respostas WHERE sessao_id = ? AND aluno_id = ? AND correta = 1');
                $acertos->execute([$sessao_id, $p['aluno_id']]);
                $ac = (int)$acertos->fetchColumn();
                $pct = $total_questoes ? round(100 * $ac / $total_questoes, 2) : 0;

                $pdo->prepare(
                    "INSERT INTO tentativas (aluno_id, atividade_id, origem, numero, acertos, total_questoes, percentual, xp_ganho, concluida, finalizada_em)
                     VALUES (?, ?, 'aovivo', 1, ?, ?, ?, ?, 1, NOW())"
                )->execute([(int)$p['aluno_id'], (int)$sessao['atividade_id'], $ac, $total_questoes, $pct, $xp]);

                gamifica_registrar_resultado((int)$p['aluno_id'], (int)$sessao['atividade_id'], $xp, 'aovivo', $sessao['titulo'] . ' (ao vivo)');
                notificar((int)$p['aluno_id'], 'aovivo', 'Quiz ao vivo finalizado! 🎤', $sessao['titulo'] . ' — resultado registrado (só melhora seu XP).');
            }
            flash_set('Sessão encerrada e XP distribuído! 🎤');
        }
    }
    redirect(BASE_URL . '/professor/aovivo.php' . ($sessao_id && $acao !== 'encerrar' ? '?sessao=' . $sessao_id : ''));
}

// ── Sessão em andamento? ─────────────────────────────────────
$sessao_id = (int)($_GET['sessao'] ?? 0);
$sessao = null;
if ($sessao_id) {
    $s = $pdo->prepare(
        'SELECT s.*, a.titulo, t.nome AS turma_nome,
                (SELECT COUNT(*) FROM quiz_questoes q WHERE q.atividade_id = s.atividade_id) AS total_questoes
         FROM sessoes_ao_vivo s
         JOIN atividades a ON a.id = s.atividade_id
         JOIN turmas t ON t.id = a.turma_id
         WHERE s.id = ? AND s.professor_id = ?'
    );
    $s->execute([$sessao_id, $prof_id]);
    $sessao = $s->fetch();
}

pagina_inicio('Modo Ao Vivo', 'aovivo');
?>

<?php if (!$sessao): ?>
<?php
$quizzes = $pdo->prepare(
    "SELECT a.id, a.titulo, t.nome AS turma_nome,
            (SELECT COUNT(*) FROM quiz_questoes q WHERE q.atividade_id = a.id) AS questoes
     FROM atividades a JOIN turmas t ON t.id = a.turma_id
     WHERE a.professor_id = ? AND a.tipo IN ('quiz','leitura') AND a.publicada = 1
     HAVING questoes > 0
     ORDER BY a.criado_em DESC"
);
$quizzes->execute([$prof_id]);
$quizzes = $quizzes->fetchAll();

$anteriores = $pdo->prepare(
    "SELECT s.*, a.titulo, (SELECT COUNT(*) FROM sessao_participantes sp WHERE sp.sessao_id = s.id) AS participantes
     FROM sessoes_ao_vivo s JOIN atividades a ON a.id = s.atividade_id
     WHERE s.professor_id = ? ORDER BY s.criado_em DESC LIMIT 5"
);
$anteriores->execute([$prof_id]);
$anteriores = $anteriores->fetchAll();
?>
<div class="alert alert-info">🎤 <strong>Modo Ao Vivo:</strong> projete esta tela, os alunos entram com o PIN e respondem no próprio dispositivo. Pontos por acerto + velocidade. No fim, o resultado <u>só pode melhorar</u> o XP de cada aluno — nossa resposta ao Kahoot, sem punição.</div>

<div class="card">
  <div class="card-title">▶️ Iniciar nova sessão</div>
  <?php if (empty($quizzes)): ?>
    <div style="color:#aaa;font-weight:700;font-size:13px;">Publique um quiz (com questões) para jogar ao vivo. <a href="atividade_form.php" style="color:#7c6ef0;">Criar agora →</a></div>
  <?php else: ?>
  <form method="POST" style="display:grid;grid-template-columns:2fr 1fr auto;gap:12px;align-items:end;">
    <?= csrf_input() ?>
    <input type="hidden" name="acao" value="criar">
    <div class="fld" style="margin:0;"><label>Quiz</label>
      <select name="atividade_id">
        <?php foreach ($quizzes as $q): ?>
          <option value="<?= (int)$q['id'] ?>"><?= e($q['titulo']) ?> · <?= e($q['turma_nome']) ?> (<?= (int)$q['questoes'] ?> questões)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="fld" style="margin:0;"><label>Segundos por questão</label><input type="number" name="tempo" min="10" max="120" value="<?= (int)config('aovivo_tempo_questao', '30') ?>"></div>
    <button type="submit" class="btn btn-primary">Gerar PIN →</button>
  </form>
  <?php endif; ?>
</div>

<?php if ($anteriores): ?>
<div class="card">
  <div class="card-title">🕘 Sessões recentes</div>
  <table>
    <tr><th>Quiz</th><th>PIN</th><th>Status</th><th>Alunos</th><th>Data</th><th></th></tr>
    <?php foreach ($anteriores as $an): ?>
    <tr>
      <td><?= e($an['titulo']) ?></td>
      <td style="font-weight:800;letter-spacing:2px;"><?= e($an['pin']) ?></td>
      <td><span class="badge <?= $an['status'] === 'encerrada' ? 'bk' : 'bg' ?>"><?= ucfirst($an['status']) ?></span></td>
      <td><?= (int)$an['participantes'] ?></td>
      <td><?= date('d/m H:i', strtotime($an['criado_em'])) ?></td>
      <td><?php if ($an['status'] !== 'encerrada'): ?><a href="?sessao=<?= (int)$an['id'] ?>" class="btn btn-outline" style="font-size:11px;">Retomar →</a><?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif; ?>

<?php else: ?>
<div class="card" style="text-align:center;">
  <div class="card-title" style="justify-content:center;">🎤 <?= e($sessao['titulo']) ?> · Turma <?= e($sessao['turma_nome']) ?></div>
  <div style="font-size:13px;font-weight:700;color:#888;">Alunos entram em <strong>Ao Vivo</strong> com o PIN:</div>
  <div class="pin-grande"><?= e($sessao['pin']) ?></div>

  <div id="hostInfo" style="font-size:14px;font-weight:800;color:#7c6ef0;margin-bottom:12px;">Carregando...</div>

  <form method="POST" style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
    <?= csrf_input() ?>
    <input type="hidden" name="sessao" value="<?= (int)$sessao['id'] ?>">
    <button type="submit" name="acao" value="proxima" class="btn btn-primary">▶️ <?= (int)$sessao['questao_atual'] === 0 ? 'Começar (1ª questão)' : 'Próxima questão' ?></button>
    <button type="submit" name="acao" value="revisao" class="btn btn-outline">📊 Mostrar placar</button>
    <button type="submit" name="acao" value="encerrar" class="btn btn-outline" onclick="return confirm('Encerrar e distribuir o XP?')">🏁 Encerrar e dar XP</button>
  </form>

  <div id="hostQuestao" style="margin-top:18px;"></div>
  <div id="hostRanking" style="max-width:380px;margin:14px auto 0;text-align:left;"></div>
</div>

<script>
(function(){
  const api = '<?= BASE_URL ?>/api/aovivo.php?sessao=<?= (int)$sessao['id'] ?>';
  const info = document.getElementById('hostInfo');
  const qbox = document.getElementById('hostQuestao');
  const rbox = document.getElementById('hostRanking');

  function poll(){
    fetch(api).then(r => r.json()).then(est => {
      let txt = '👥 ' + (est.participantes || 0) + ' aluno(s) na sala';
      if (est.status === 'questao') {
        txt += ' · ⏱ ' + est.tempo_restante + 's · 📨 ' + (est.respostas_recebidas || 0) + ' resposta(s)';
        qbox.innerHTML = est.questao
          ? '<div style="font-size:20px;font-weight:800;">Questão ' + est.questao_atual + '/<?= (int)$sessao['total_questoes'] ?>: ' + est.questao.enunciado + '</div>'
          : '';
      } else if (est.status === 'lobby') {
        qbox.innerHTML = '<div style="font-size:15px;font-weight:700;color:#888;">Aguardando você começar…</div>';
      } else if (est.status === 'encerrada') {
        qbox.innerHTML = '<div style="font-size:16px;font-weight:800;color:#16a34a;">Sessão encerrada — XP distribuído! 🎉</div>';
      } else {
        qbox.innerHTML = '';
      }
      info.textContent = txt;
      rbox.innerHTML = '';
      (est.ranking || []).forEach((r, i) => {
        rbox.innerHTML += '<div class="rank-row"><span>' + (['🥇','🥈','🥉'][i] || (i+1)) + '</span><div style="flex:1;font-size:13px;font-weight:700;">' + r.nome + '</div><span style="font-weight:800;">' + r.pontos + '</span></div>';
      });
    }).catch(()=>{});
  }
  setInterval(poll, 2500);
  poll();
})();
</script>
<?php endif; ?>
<?php pagina_fim(); ?>
