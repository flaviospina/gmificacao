<?php
// responsavel/index.php — Portal da Família: progresso dos filhos + feed positivo
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_responsavel();

$pdo     = db();
$resp_id = usuario_id();

// Filhos vinculados
$filhos = $pdo->prepare(
    'SELECT u.id, u.nome, ra.parentesco,
            COALESCE(ap.xp_total, 0) AS xp_total, COALESCE(ap.nivel_atual, 1) AS nivel,
            COALESCE(ap.streak_dias, 0) AS streak, COALESCE(ap.missoes_concluidas, 0) AS missoes,
            n.nome AS nivel_nome, t.nome AS turma_nome
     FROM responsavel_aluno ra
     JOIN usuarios u ON u.id = ra.aluno_id AND u.ativo = 1
     LEFT JOIN aluno_perfil ap ON ap.aluno_id = u.id
     LEFT JOIN niveis n ON n.nivel = COALESCE(ap.nivel_atual, 1)
     LEFT JOIN aluno_turma at2 ON at2.usuario_id = u.id AND at2.ativo = 1
     LEFT JOIN turmas t ON t.id = at2.turma_id
     WHERE ra.responsavel_id = ?'
);
$filhos->execute([$resp_id]);
$filhos = $filhos->fetchAll();
$filho_ids = array_map('intval', array_column($filhos, 'id'));
$in = $filho_ids ? implode(',', $filho_ids) : '0';

// Feed: posts da turma dos filhos ou específicos deles
$feed = $pdo->query(
    "SELECT f.*, t.nome AS turma_nome, ua.nome AS aluno_nome, up.nome AS autor_nome
     FROM feed_familia f
     LEFT JOIN turmas t ON t.id = f.turma_id
     LEFT JOIN usuarios ua ON ua.id = f.aluno_id
     JOIN usuarios up ON up.id = f.autor_id
     WHERE (f.aluno_id IN ($in))
        OR (f.aluno_id IS NULL AND f.turma_id IN (SELECT turma_id FROM aluno_turma WHERE usuario_id IN ($in) AND ativo = 1))
     ORDER BY f.criado_em DESC LIMIT 15"
)->fetchAll();

// Conquistas recentes dos filhos
$conq = $pdo->query(
    "SELECT u.nome AS aluno_nome, c.nome, c.icone, ac.desbloqueada_em
     FROM aluno_conquistas ac
     JOIN conquistas c ON c.id = ac.conquista_id
     JOIN usuarios u ON u.id = ac.aluno_id
     WHERE ac.aluno_id IN ($in)
     ORDER BY ac.desbloqueada_em DESC LIMIT 6"
)->fetchAll();

// Portfólio aprovado e visível
$portfolio = $pdo->query(
    "SELECT pi.titulo, pi.descricao, pi.atualizado_em, u.nome AS aluno_nome
     FROM portfolio_itens pi JOIN usuarios u ON u.id = pi.aluno_id
     WHERE pi.aluno_id IN ($in) AND pi.status = 'aprovado' AND pi.visivel_familia = 1
     ORDER BY pi.atualizado_em DESC LIMIT 6"
)->fetchAll();

$tipo_cfg = ['elogio' => ['bg', '💚 Elogio'], 'aviso' => ['bb', '📢 Aviso'], 'conquista' => ['ba', '🏅 Conquista']];

pagina_inicio('Meus Filhos', 'inicio');
?>
<div class="alert alert-info">💜 No Gamifica não existe pontuação negativa: tudo que você vê aqui é progresso. Seu filho pode tentar quantas vezes quiser — só o melhor resultado conta.</div>

<?php if (empty($filhos)): ?>
  <div class="card" style="text-align:center;padding:2rem;color:#aaa;">Nenhum aluno vinculado à sua conta. Fale com a escola.</div>
<?php endif; ?>

<?php foreach ($filhos as $f): ?>
<div class="xp-banner">
  <div class="xp-banner-top">
    <div>
      <div style="font-size:17px;font-weight:800;">👧 <?= e($f['nome']) ?> <span class="badge bk">Turma <?= e($f['turma_nome'] ?? '—') ?></span></div>
      <span class="badge bp" style="margin-top:6px;">🏅 Nível <?= (int)$f['nivel'] ?> — <?= e($f['nivel_nome'] ?? 'Iniciante') ?></span>
    </div>
    <div class="xp-nums">
      <div class="xp-num-item"><div class="xp-val"><?= fmt_num((int)$f['xp_total']) ?></div><div class="xp-lbl">XP total</div></div>
      <div class="xp-num-item"><div class="xp-val">🔥 <?= (int)$f['streak'] ?></div><div class="xp-lbl">Dias seguidos</div></div>
      <div class="xp-num-item"><div class="xp-val"><?= (int)$f['missoes'] ?></div><div class="xp-lbl">Missões</div></div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<div class="two-col">
  <div>
    <div class="section-label">📰 Feed da escola</div>
    <?php if (empty($feed)): ?><div class="card" style="color:#aaa;font-weight:700;font-size:13px;">Nenhuma novidade ainda.</div><?php endif; ?>
    <?php foreach ($feed as $p): [$cls, $rot] = $tipo_cfg[$p['tipo']]; ?>
    <div class="feed-post">
      <div style="display:flex;justify-content:space-between;gap:8px;">
        <span class="badge <?= $cls ?>"><?= $rot ?></span>
        <span style="font-size:11px;color:#aaa;font-weight:700;"><?= date('d/m/Y', strtotime($p['criado_em'])) ?></span>
      </div>
      <div style="font-size:15px;font-weight:800;margin-top:6px;"><?= e($p['titulo']) ?></div>
      <?php if ($p['mensagem']): ?><div style="font-size:13px;color:#555;font-weight:600;margin-top:4px;"><?= nl2br(e($p['mensagem'])) ?></div><?php endif; ?>
      <div style="font-size:11px;color:#888;font-weight:700;margin-top:6px;">Prof. <?= e($p['autor_nome']) ?><?= $p['turma_nome'] ? ' · Turma ' . e($p['turma_nome']) : '' ?><?= $p['aluno_nome'] ? ' · sobre ' . e($p['aluno_nome']) : '' ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div>
    <div class="card">
      <div class="card-title">🏅 Conquistas recentes</div>
      <?php if (empty($conq)): ?><div style="color:#aaa;font-size:13px;font-weight:700;">As conquistas aparecerão aqui.</div><?php endif; ?>
      <?php foreach ($conq as $c): ?>
      <div class="rank-row">
        <span style="font-size:18px;"><?= $c['icone'] ?></span>
        <div style="flex:1;">
          <div style="font-size:13px;font-weight:800;"><?= e($c['nome']) ?></div>
          <div style="font-size:11px;color:#aaa;"><?= e($c['aluno_nome']) ?> · <?= date('d/m/Y', strtotime($c['desbloqueada_em'])) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <div class="card-title">🎨 Portfólio aprovado</div>
      <?php if (empty($portfolio)): ?><div style="color:#aaa;font-size:13px;font-weight:700;">Os trabalhos aprovados aparecerão aqui.</div><?php endif; ?>
      <?php foreach ($portfolio as $p): ?>
      <div style="margin-bottom:10px;">
        <div style="font-size:13px;font-weight:800;">🎨 <?= e($p['titulo']) ?></div>
        <div style="font-size:11px;color:#aaa;"><?= e($p['aluno_nome']) ?> · <?= date('d/m/Y', strtotime($p['atualizado_em'])) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php pagina_fim(); ?>
