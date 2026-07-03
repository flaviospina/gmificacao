<?php
// ============================================================
//  aluno/index.php — Dashboard do Aluno (V2)
// ============================================================
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_aluno();

$pdo      = db();
$aluno_id = usuario_id();

// ── Check-in de bem-estar (POST) ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['humor']) && csrf_valido($_POST['csrf_token'] ?? '')) {
    if (registrar_checkin($aluno_id, (int)$_POST['humor'])) {
        flash_set('Check-in registrado! +' . config('checkin_xp_bonus', '5') . ' XP 💚');
    } else {
        flash_set('Você já fez seu check-in hoje. Até amanhã! 😊');
    }
    redirect(BASE_URL . '/aluno/');
}

// ── Perfil e XP ──────────────────────────────────────────────
$perfil = $pdo->prepare(
    'SELECT ap.*, n.nome AS nivel_nome, n.xp_minimo, n2.xp_minimo AS xp_proximo_nivel
     FROM aluno_perfil ap
     JOIN niveis n  ON n.nivel  = ap.nivel_atual
     LEFT JOIN niveis n2 ON n2.nivel = ap.nivel_atual + 1
     WHERE ap.aluno_id = ?'
);
$perfil->execute([$aluno_id]);
$ap = $perfil->fetch() ?: [
    'xp_total' => 0, 'moedas' => 0, 'nivel_atual' => 1, 'nivel_nome' => 'Iniciante',
    'streak_dias' => 0, 'missoes_concluidas' => 0, 'xp_minimo' => 0, 'xp_proximo_nivel' => 100,
];
$base   = (int)$ap['xp_minimo'];
$prox   = $ap['xp_proximo_nivel'] !== null ? (int)$ap['xp_proximo_nivel'] : null;
$xp_pct = $prox ? min(100, round(100 * ((int)$ap['xp_total'] - $base) / max(1, $prox - $base))) : 100;

// ── Posição e turma ──────────────────────────────────────────
$rank = $pdo->prepare('SELECT posicao_turma, turma_id, turma_nome FROM vw_ranking_turma WHERE aluno_id = ? LIMIT 1');
$rank->execute([$aluno_id]);
$pos = $rank->fetch() ?: ['posicao_turma' => '—', 'turma_id' => 0, 'turma_nome' => '—'];

// ── Check-in de hoje ─────────────────────────────────────────
$chk = $pdo->prepare('SELECT humor FROM checkins_bemestar WHERE aluno_id = ? AND data = CURDATE()');
$chk->execute([$aluno_id]);
$checkin_hoje = $chk->fetchColumn();

// ── Meta coletiva ativa da turma ─────────────────────────────
$meta = null;
if ($pos['turma_id'] && modulo_ativo('metas')) {
    $m = $pdo->prepare(
        "SELECT * FROM metas_turma WHERE turma_id = ? AND status = 'ativa' AND data_fim >= CURDATE()
         ORDER BY data_fim ASC LIMIT 1"
    );
    $m->execute([$pos['turma_id']]);
    $meta = $m->fetch() ?: null;
    if ($meta) {
        $meta['progresso'] = progresso_meta($meta);
        $meta['pct'] = min(100, round(100 * $meta['progresso'] / max(1, (int)$meta['alvo'])));
    }
}

// ── Missões disponíveis (4 em destaque) ──────────────────────
$missoes = $pdo->prepare(
    'SELECT a.id, a.titulo, a.tipo, a.disciplina, a.xp_recompensa, a.total_etapas, a.data_fim,
            COALESCE(ax.xp_melhor, 0) AS melhor_xp,
            (ax.id IS NOT NULL)       AS concluida,
            (SELECT COUNT(*) FROM tentativas t WHERE t.atividade_id = a.id AND t.aluno_id = ? AND t.concluida = 1) AS total_tentativas
     FROM atividades a
     JOIN aluno_turma at2 ON at2.turma_id = a.turma_id AND at2.usuario_id = ? AND at2.ativo = 1
     LEFT JOIN aluno_xp ax ON ax.atividade_id = a.id AND ax.aluno_id = ?
     WHERE a.publicada = 1
       AND (a.data_inicio IS NULL OR a.data_inicio <= NOW())
       AND (a.data_fim IS NULL OR a.data_fim >= NOW())
     ORDER BY (ax.id IS NULL) DESC, a.criado_em DESC
     LIMIT 4'
);
$missoes->execute([$aluno_id, $aluno_id, $aluno_id]);
$lista_missoes = $missoes->fetchAll();

// ── Ranking rápido (top 5 da turma) ──────────────────────────
$top5 = [];
if ($pos['turma_id']) {
    $ranking_turma = $pdo->prepare(
        'SELECT aluno_id, aluno_nome, xp_total, nivel, posicao_turma
         FROM vw_ranking_turma WHERE turma_id = ?
         ORDER BY posicao_turma ASC, aluno_nome ASC LIMIT 5'
    );
    $ranking_turma->execute([$pos['turma_id']]);
    $top5 = $ranking_turma->fetchAll();
}

// ── Conquistas recentes ──────────────────────────────────────
$conquistas = $pdo->prepare(
    'SELECT c.nome, c.icone, ac.desbloqueada_em
     FROM aluno_conquistas ac
     JOIN conquistas c ON c.id = ac.conquista_id
     WHERE ac.aluno_id = ?
     ORDER BY ac.desbloqueada_em DESC LIMIT 3'
);
$conquistas->execute([$aluno_id]);
$ult_conquistas = $conquistas->fetchAll();

pagina_inicio('Missões', 'missoes');
?>

<!-- XP BANNER -->
<div class="xp-banner">
  <div class="xp-banner-top">
    <div class="xp-badge-wrap">
      <span class="badge bp">🏅 Nível <?= (int)$ap['nivel_atual'] ?> — <?= e($ap['nivel_nome']) ?></span>
      <?php if ($prox): ?>
        <span class="xp-falta"><?= fmt_num($prox - (int)$ap['xp_total']) ?> XP para o próximo nível</span>
      <?php else: ?>
        <span class="xp-falta">🎉 Nível máximo atingido!</span>
      <?php endif; ?>
    </div>
    <div class="xp-nums">
      <div class="xp-num-item"><div class="xp-val"><?= fmt_num((int)$ap['xp_total']) ?></div><div class="xp-lbl">XP Total</div></div>
      <div class="xp-num-item"><div class="xp-val">🪙 <?= fmt_num((int)$ap['moedas']) ?></div><div class="xp-lbl">GamiCoins</div></div>
      <div class="xp-num-item"><div class="xp-val">🔥 <?= (int)$ap['streak_dias'] ?></div><div class="xp-lbl">Dias seguidos</div></div>
      <div class="xp-num-item"><div class="xp-val"><?= e((string)$pos['posicao_turma']) ?>°</div><div class="xp-lbl">Na turma</div></div>
      <div class="xp-num-item"><div class="xp-val"><?= (int)$ap['missoes_concluidas'] ?></div><div class="xp-lbl">Missões</div></div>
    </div>
  </div>
  <div class="xp-bar"><div class="xp-fill" style="width:<?= $xp_pct ?>%"></div></div>
  <div class="xp-bar-labels">
    <span><?= fmt_num((int)$ap['xp_total']) ?> XP</span>
    <span><?= $prox ? fmt_num($prox) . ' XP' : '—' ?></span>
  </div>
</div>

<?php if (modulo_ativo('bemestar')): ?>
<!-- CHECK-IN DE BEM-ESTAR -->
<div class="card">
  <div class="card-title">💚 Como você está hoje?</div>
  <?php if ($checkin_hoje): ?>
    <div style="font-size:14px;font-weight:700;color:#16a34a;">
      Check-in de hoje: <?= emoji_humor((int)$checkin_hoje) ?> — obrigado por compartilhar! Volte amanhã.
    </div>
  <?php else: ?>
    <form method="POST" class="humor-row">
      <?= csrf_input() ?>
      <?php foreach ([1 => '😢', 2 => '😕', 3 => '😐', 4 => '🙂', 5 => '😄'] as $v => $emo): ?>
        <button type="submit" name="humor" value="<?= $v ?>" class="humor-btn" title="Registrar humor"><?= $emo ?></button>
      <?php endforeach; ?>
      <span style="font-size:12px;color:#aaa;font-weight:700;align-self:center;">1 clique · +<?= (int)config('checkin_xp_bonus', '5') ?> XP · só o professor vê o clima geral da turma</span>
    </form>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($meta): ?>
<!-- META COLETIVA DA TURMA -->
<div class="card" style="border:2px solid #e4deff;">
  <div class="card-title">🎯 Meta da turma <?= e($pos['turma_nome']) ?> <span style="font-size:11px;color:#aaa;">até <?= date('d/m', strtotime($meta['data_fim'])) ?></span></div>
  <div style="font-size:15px;font-weight:800;margin-bottom:6px;"><?= e($meta['titulo']) ?></div>
  <?php if ($meta['descricao']): ?><div style="font-size:13px;color:#888;font-weight:600;margin-bottom:8px;"><?= e($meta['descricao']) ?></div><?php endif; ?>
  <div class="progress" style="height:10px;"><div class="prog-fill" style="width:<?= $meta['pct'] ?>%;background:linear-gradient(90deg,#7c6ef0,#a855f7);"></div></div>
  <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:700;color:#7c6ef0;margin-top:4px;">
    <span><?= fmt_num($meta['progresso']) ?> / <?= fmt_num((int)$meta['alvo']) ?> <?= $meta['tipo'] === 'xp' ? 'XP' : ($meta['tipo'] === 'missoes' ? 'missões' : 'check-ins') ?> (<?= $meta['pct'] ?>%)</span>
    <span>Recompensa p/ todos: <?= $meta['recompensa_xp'] ? '+' . (int)$meta['recompensa_xp'] . ' XP' : '' ?> <?= $meta['recompensa_moedas'] ? '· 🪙 ' . (int)$meta['recompensa_moedas'] : '' ?></span>
  </div>
</div>
<?php endif; ?>

<div class="two-col">
  <!-- MISSÕES -->
  <div>
    <div class="section-label">⚔️ Missões disponíveis · tentativas ilimitadas</div>

    <?php if (empty($lista_missoes)): ?>
      <div class="card" style="text-align:center;padding:2rem;color:#aaa;">
        <div style="font-size:32px;margin-bottom:8px;">🎉</div>
        <div style="font-weight:800;font-size:15px;color:#1a1a2e;">Tudo concluído por enquanto!</div>
        <div style="font-size:13px;margin-top:4px;">Aguarde seu professor publicar novas atividades.</div>
      </div>
    <?php endif; ?>

    <?php foreach ($lista_missoes as $m):
      $concluida    = (bool)$m['concluida'];
      $pct_progress = $m['xp_recompensa'] ? min(100, round(100 * $m['melhor_xp'] / $m['xp_recompensa'])) : 0;
    ?>
    <div class="mission <?= $concluida ? 'mission--done' : '' ?>">
      <div class="mission-inner">
        <div class="mission-icon"><?= icone_tipo($m['tipo']) ?></div>
        <div class="mission-body">
          <div class="mission-header">
            <span class="mission-title"><?= e($m['titulo']) ?></span>
            <span class="xp-badge">⭐ <?= (int)$m['xp_recompensa'] ?> XP</span>
          </div>
          <div class="mission-meta">
            <span class="badge ba"><?= e($m['disciplina']) ?></span>
            <span class="badge bk"><?= ucfirst($m['tipo']) ?></span>
            <?php if ($m['data_fim']): ?><span style="font-size:11px;color:#aaa;font-weight:600;">Prazo: <?= date('d/m/Y', strtotime($m['data_fim'])) ?></span><?php endif; ?>
            <?php if ($m['total_tentativas'] > 0): ?><span style="font-size:11px;color:#aaa;font-weight:600;"><?= (int)$m['total_tentativas'] ?> tentativa(s)</span><?php endif; ?>
          </div>
          <div class="progress"><div class="prog-fill" style="width:<?= $pct_progress ?>%;background:<?= $concluida ? '#16a34a' : '#7c6ef0' ?>"></div></div>
          <div class="mission-footer">
            <span style="font-size:12px;color:#888;font-weight:600;">
              <?php if ($concluida): ?>✅ Melhor: <?= (int)$m['melhor_xp'] ?> XP (<?= $pct_progress ?>%)
              <?php elseif ($m['total_tentativas'] > 0): ?>Em andamento
              <?php else: ?>Não iniciado<?php endif; ?>
            </span>
            <a href="missao.php?id=<?= (int)$m['id'] ?>" class="btn <?= $concluida ? 'btn-outline' : 'btn-primary' ?>">
              <?= $concluida ? 'Refazer' : ($m['total_tentativas'] > 0 ? 'Continuar' : 'Iniciar') ?> →
            </a>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <div style="text-align:center;margin-top:12px;">
      <a href="missoes.php" class="btn btn-outline">Ver todas as missões →</a>
    </div>
  </div>

  <!-- RANKING + CONQUISTAS -->
  <div>
    <div class="card">
      <div class="card-title">📊 Ranking — Turma <?= e($pos['turma_nome']) ?></div>
      <?php foreach ($top5 as $i => $r): $eu = ($r['aluno_id'] == $aluno_id); ?>
      <div class="rank-row <?= $eu ? 'rank-me' : '' ?>">
        <span style="font-size:<?= $i < 3 ? '18' : '13' ?>px;<?= $i >= 3 ? 'color:#aaa;font-weight:700;width:22px;text-align:center;' : '' ?>"><?= medalha($i + 1) ?></span>
        <div style="flex:1;">
          <div style="font-size:13px;font-weight:<?= $eu ? '800' : '700' ?>;color:<?= $eu ? '#7c6ef0' : 'inherit' ?>"><?= $eu ? 'Você' : e($r['aluno_nome']) ?></div>
          <div style="font-size:11px;color:<?= $eu ? '#a78bfa' : '#aaa' ?>"><?= fmt_num((int)$r['xp_total']) ?> XP · Nível <?= (int)$r['nivel'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <div style="margin-top:12px;text-align:center;">
        <a href="ranking.php" class="btn btn-outline" style="font-size:12px;">Ver ranking completo →</a>
      </div>
    </div>

    <div class="card">
      <div class="card-title">🏅 Conquistas recentes</div>
      <?php if (empty($ult_conquistas)): ?>
        <div style="text-align:center;padding:1rem;color:#aaa;font-size:13px;">Complete missões para desbloquear conquistas!</div>
      <?php endif; ?>
      <?php foreach ($ult_conquistas as $c): ?>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <div style="width:36px;height:36px;background:#fffbeb;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;"><?= $c['icone'] ?></div>
        <div>
          <div style="font-size:13px;font-weight:800;"><?= e($c['nome']) ?></div>
          <div style="font-size:11px;color:#aaa;"><?= date('d/m/Y', strtotime($c['desbloqueada_em'])) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <div style="text-align:center;margin-top:8px;">
        <a href="conquistas.php" class="btn btn-outline" style="font-size:12px;">Ver todas →</a>
      </div>
    </div>
  </div>
</div>

<?php pagina_fim(); ?>
