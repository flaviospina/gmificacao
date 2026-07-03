<?php
// aluno/aovivo.php — Entrar e jogar o Modo Ao Vivo (PIN)
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_aluno();
if (!modulo_ativo('aovivo')) { flash_set('O Modo Ao Vivo está desativado.', 'erro'); redirect(BASE_URL . '/aluno/'); }

$pdo      = db();
$aluno_id = usuario_id();

// ── Entrar com PIN ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin']) && csrf_valido($_POST['csrf_token'] ?? '')) {
    $pin = preg_replace('/\D/', '', $_POST['pin'] ?? '');
    $s = $pdo->prepare(
        "SELECT s.id, a.turma_id FROM sessoes_ao_vivo s
         JOIN atividades a ON a.id = s.atividade_id
         WHERE s.pin = ? AND s.status <> 'encerrada'
         ORDER BY s.criado_em DESC LIMIT 1"
    );
    $s->execute([$pin]);
    $sessao = $s->fetch();

    if (!$sessao) {
        flash_set('PIN inválido ou sessão encerrada.', 'erro');
        redirect(BASE_URL . '/aluno/aovivo.php');
    }
    $chk = $pdo->prepare('SELECT id FROM aluno_turma WHERE usuario_id = ? AND turma_id = ? AND ativo = 1');
    $chk->execute([$aluno_id, $sessao['turma_id']]);
    if (!$chk->fetch()) {
        flash_set('Esta sessão é de outra turma.', 'erro');
        redirect(BASE_URL . '/aluno/aovivo.php');
    }
    $pdo->prepare('INSERT IGNORE INTO sessao_participantes (sessao_id, aluno_id) VALUES (?, ?)')
        ->execute([$sessao['id'], $aluno_id]);
    redirect(BASE_URL . '/aluno/aovivo.php?sessao=' . (int)$sessao['id']);
}

$sessao_id = (int)($_GET['sessao'] ?? 0);
$sessao = null;
if ($sessao_id) {
    $s = $pdo->prepare(
        'SELECT s.*, a.titulo FROM sessoes_ao_vivo s
         JOIN atividades a ON a.id = s.atividade_id
         JOIN sessao_participantes sp ON sp.sessao_id = s.id AND sp.aluno_id = ?
         WHERE s.id = ?'
    );
    $s->execute([$aluno_id, $sessao_id]);
    $sessao = $s->fetch();
}

pagina_inicio('Modo Ao Vivo', 'aovivo');
?>

<?php if (!$sessao): ?>
<div class="card" style="max-width:420px;margin:0 auto;text-align:center;">
  <div style="font-size:44px;margin-bottom:8px;">🎤</div>
  <div style="font-size:17px;font-weight:800;margin-bottom:4px;">Entrar no quiz ao vivo</div>
  <p style="font-size:13px;color:#888;font-weight:600;margin-bottom:14px;">Digite o PIN de 6 dígitos que está no telão.</p>
  <form method="POST">
    <?= csrf_input() ?>
    <div class="fld">
      <input type="text" name="pin" required pattern="\d{6}" maxlength="6" inputmode="numeric"
             placeholder="000000" style="text-align:center;font-size:26px;letter-spacing:8px;font-weight:800;">
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;font-size:15px;padding:12px;">Entrar →</button>
  </form>
  <p style="font-size:12px;color:#aaa;font-weight:700;margin-top:12px;">💜 Aqui ninguém perde ponto: no fim, o resultado só pode <u>melhorar</u> seu XP.</p>
</div>

<?php else: ?>
<div class="card" id="palco" style="text-align:center;min-height:320px;">
  <div style="font-size:13px;font-weight:800;color:#aaa;text-transform:uppercase;letter-spacing:.6px;"><?= e($sessao['titulo']) ?></div>
  <div id="conteudo" style="margin-top:16px;">
    <div style="font-size:40px;">⏳</div>
    <div style="font-weight:800;font-size:16px;">Aguardando o professor começar...</div>
  </div>
  <div id="placar" style="margin-top:14px;font-size:14px;font-weight:800;color:#7c6ef0;"></div>
</div>

<script>
(function(){
  const sessaoId = <?= (int)$sessao['id'] ?>;
  const csrf = '<?= csrf_token() ?>';
  const api = '<?= BASE_URL ?>/api/aovivo.php?sessao=' + sessaoId;
  const conteudo = document.getElementById('conteudo');
  const placar = document.getElementById('placar');
  let respondida = 0;      // id da questão respondida localmente
  let ultimaQuestao = 0;

  function render(est) {
    placar.textContent = '🪙 Seus pontos: ' + (est.meus_pontos || 0);

    if (est.status === 'lobby') {
      conteudo.innerHTML = '<div style="font-size:40px;">⏳</div><div style="font-weight:800;font-size:16px;">Aguardando o professor começar...</div>';
      return;
    }
    if (est.status === 'encerrada') {
      let html = '<div style="font-size:44px;">🏁</div><div style="font-weight:800;font-size:18px;">Sessão encerrada!</div>' +
                 '<p style="font-size:13px;color:#888;font-weight:600;">Seu XP foi atualizado — só para melhor. 😉</p>';
      if (est.ranking) {
        html += '<div style="max-width:340px;margin:12px auto;text-align:left;">';
        est.ranking.forEach((r, i) => {
          html += '<div class="rank-row"><span>' + (['🥇','🥈','🥉'][i] || (i+1)) + '</span><div style="flex:1;font-size:13px;font-weight:700;">' + r.nome + '</div><span style="font-weight:800;">' + r.pontos + '</span></div>';
        });
        html += '</div><a href="<?= BASE_URL ?>/aluno/" class="btn btn-primary">Voltar ao painel →</a>';
      }
      conteudo.innerHTML = html;
      clearInterval(timer);
      return;
    }
    if (est.status === 'revisao') {
      let html = '<div style="font-size:36px;">📊</div><div style="font-weight:800;font-size:16px;">Placar parcial</div>';
      if (est.ranking) {
        html += '<div style="max-width:340px;margin:12px auto;text-align:left;">';
        est.ranking.forEach((r, i) => {
          html += '<div class="rank-row"><span>' + (['🥇','🥈','🥉'][i] || (i+1)) + '</span><div style="flex:1;font-size:13px;font-weight:700;">' + r.nome + '</div><span style="font-weight:800;">' + r.pontos + '</span></div>';
        });
        html += '</div>';
      }
      conteudo.innerHTML = html;
      return;
    }
    if (est.status === 'questao' && est.questao) {
      if (est.questao.id !== ultimaQuestao) { ultimaQuestao = est.questao.id; respondida = 0; }
      if (est.minha_resposta || respondida === est.questao.id) {
        conteudo.innerHTML = '<div style="font-size:40px;">✅</div><div style="font-weight:800;font-size:16px;">Resposta enviada! Aguarde...</div>';
        return;
      }
      let html = '<div style="font-size:12px;font-weight:800;color:#d97706;">⏱ ' + est.tempo_restante + 's</div>' +
                 '<div style="font-size:18px;font-weight:800;margin:10px 0;">' + est.questao.enunciado + '</div>' +
                 '<div class="aovivo-alts">';
      est.questao.alternativas.forEach((a, i) => {
        html += '<button class="aovivo-alt alt-c' + (i % 4) + '" data-alt="' + a.id + '">' + a.texto + '</button>';
      });
      html += '</div>';
      conteudo.innerHTML = html;
      conteudo.querySelectorAll('.aovivo-alt').forEach(btn => {
        btn.addEventListener('click', function(){
          respondida = ultimaQuestao;
          conteudo.innerHTML = '<div style="font-size:40px;">📨</div><div style="font-weight:800;">Enviando...</div>';
          const fd = new FormData();
          fd.append('sessao', sessaoId);
          fd.append('alternativa', this.dataset.alt);
          fd.append('csrf_token', csrf);
          fetch('<?= BASE_URL ?>/api/aovivo.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(j => {
              if (j.ok) {
                conteudo.innerHTML = j.correta
                  ? '<div style="font-size:44px;">🎉</div><div style="font-weight:800;font-size:17px;color:#16a34a;">Acertou! +' + j.pontos + ' pontos</div>'
                  : '<div style="font-size:44px;">💪</div><div style="font-weight:800;font-size:17px;">Não foi dessa vez — sem perder nada!</div>';
              }
            });
        });
      });
    }
  }

  function poll(){ fetch(api).then(r => r.json()).then(render).catch(()=>{}); }
  const timer = setInterval(poll, 2500);
  poll();
})();
</script>
<?php endif; ?>
<?php pagina_fim(); ?>
