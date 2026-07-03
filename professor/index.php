<?php
// professor/index.php — Dashboard do Professor (V2)
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_professor();

$pdo     = db();
$prof_id = usuario_id();

// Minhas turmas
$turmas = $pdo->prepare(
    'SELECT DISTINCT t.id, t.nome FROM turmas t
     JOIN professor_turma pt ON pt.turma_id = t.id AND pt.ativo = 1
     WHERE pt.professor_id = ? AND t.ativa = 1 ORDER BY t.nome'
);
$turmas->execute([$prof_id]);
$turmas = $turmas->fetchAll();
$turma_ids = array_column($turmas, 'id');
$in = $turma_ids ? implode(',', array_map('intval', $turma_ids)) : '0';

// Stats
$total_alunos = (int)$pdo->query(
    "SELECT COUNT(DISTINCT usuario_id) FROM aluno_turma WHERE turma_id IN ($in) AND ativo = 1"
)->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM atividades WHERE professor_id = ? AND publicada = 1');
$stmt->execute([$prof_id]);
$total_atividades = (int)$stmt->fetchColumn();

// Pendências: entregas de projeto + portfólio + resgates
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM projeto_entregas en
     JOIN projeto_etapas pe ON pe.id = en.etapa_id
     JOIN atividades a ON a.id = pe.atividade_id
     WHERE a.professor_id = ? AND en.status = 'enviada'"
);
$stmt->execute([$prof_id]);
$pend_entregas = (int)$stmt->fetchColumn();

$pend_portfolio = (int)$pdo->query(
    "SELECT COUNT(*) FROM portfolio_itens pi
     JOIN aluno_turma at2 ON at2.usuario_id = pi.aluno_id AND at2.ativo = 1
     WHERE at2.turma_id IN ($in) AND pi.status = 'enviado'"
)->fetchColumn();

$pend_resgates = (int)$pdo->query(
    "SELECT COUNT(*) FROM loja_resgates lr
     JOIN aluno_turma at2 ON at2.usuario_id = lr.aluno_id AND at2.ativo = 1
     WHERE at2.turma_id IN ($in) AND lr.status = 'pendente'"
)->fetchColumn();

// Alunos com baixo engajamento (< 30% do XP disponível das minhas atividades)
$baixo = $pdo->prepare(
    "SELECT u.id, u.nome, t.nome AS turma_nome,
            COALESCE(SUM(ax.xp_melhor), 0) AS xp_ganho,
            (SELECT COALESCE(SUM(a2.xp_recompensa), 0) FROM atividades a2
             WHERE a2.turma_id = t.id AND a2.publicada = 1) AS xp_disponivel
     FROM usuarios u
     JOIN aluno_turma at2 ON at2.usuario_id = u.id AND at2.ativo = 1
     JOIN turmas t ON t.id = at2.turma_id
     LEFT JOIN aluno_xp ax ON ax.aluno_id = u.id
          AND ax.atividade_id IN (SELECT id FROM atividades WHERE turma_id = t.id AND publicada = 1)
     WHERE at2.turma_id IN ($in) AND u.perfil = 'aluno' AND u.ativo = 1
     GROUP BY u.id, u.nome, t.id, t.nome
     HAVING xp_disponivel > 0 AND xp_ganho < 0.30 * xp_disponivel
     ORDER BY xp_ganho ASC LIMIT 8"
);
$baixo->execute();
$baixo = $baixo->fetchAll();

// Clima resumido das minhas turmas
$clima = $pdo->query("SELECT * FROM vw_clima_turma WHERE turma_id IN ($in)")->fetchAll();

pagina_inicio('Painel do Professor', 'inicio');
?>
<div class="stats">
  <div class="stat"><div class="stat-v"><?= count($turmas) ?></div><div class="stat-l">Turmas</div></div>
  <div class="stat"><div class="stat-v"><?= $total_alunos ?></div><div class="stat-l">Alunos</div></div>
  <div class="stat"><div class="stat-v"><?= $total_atividades ?></div><div class="stat-l">Atividades publicadas</div></div>
  <div class="stat"><div class="stat-v"><?= $pend_entregas + $pend_portfolio + $pend_resgates ?></div><div class="stat-l">Pendências</div>
    <div class="stat-s"><?= $pend_entregas ?> entregas · <?= $pend_portfolio ?> portfólio · <?= $pend_resgates ?> resgates</div></div>
</div>

<div class="two-col">
  <div>
    <div class="card">
      <div class="card-title">⚡ Ações rápidas</div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="atividade_form.php" class="btn btn-primary">➕ Nova atividade</a>
        <a href="aovivo.php" class="btn btn-outline">🎤 Iniciar quiz ao vivo</a>
        <a href="metas.php" class="btn btn-outline">🎯 Criar meta da turma</a>
        <a href="portfolio.php" class="btn btn-outline">🎨 Revisar entregas (<?= $pend_entregas + $pend_portfolio ?>)</a>
        <a href="loja.php" class="btn btn-outline">🛍️ Resgates (<?= $pend_resgates ?>)</a>
      </div>
    </div>

    <div class="card">
      <div class="card-title">🚨 Alunos com baixo engajamento (&lt; 30%)</div>
      <?php if (empty($baixo)): ?>
        <div style="color:#16a34a;font-weight:700;font-size:13px;">🎉 Nenhum aluno abaixo de 30% — turma engajada!</div>
      <?php else: ?>
        <table>
          <tr><th>Aluno</th><th>Turma</th><th>Progresso</th></tr>
          <?php foreach ($baixo as $b):
            $pct = $b['xp_disponivel'] ? round(100 * $b['xp_ganho'] / $b['xp_disponivel']) : 0; ?>
          <tr>
            <td><?= e($b['nome']) ?></td>
            <td><?= e($b['turma_nome']) ?></td>
            <td><?= badge_engajamento($pct) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <p style="font-size:12px;color:#888;font-weight:600;margin-top:8px;">💡 Dica: crie uma <a href="mentoria.php" style="color:#7c6ef0;">mentoria entre pares</a> — colegas ajudam e ganham XP juntos.</p>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-title">💚 Clima das turmas (7 dias)</div>
      <?php if (empty($clima)): ?><div style="color:#aaa;font-size:13px;font-weight:700;">Sem turmas vinculadas.</div><?php endif; ?>
      <?php foreach ($clima as $c): ?>
      <div class="rank-row">
        <span style="font-size:18px;"><?= $c['humor_medio'] !== null ? emoji_humor((int)round($c['humor_medio'])) : '❔' ?></span>
        <div style="flex:1;">
          <div style="font-size:13px;font-weight:800;">Turma <?= e($c['turma_nome']) ?></div>
          <div style="font-size:11px;color:#aaa;"><?= $c['humor_medio'] !== null ? 'Humor ' . number_format((float)$c['humor_medio'], 1, ',', '') . '/5' : 'Sem check-ins' ?> · <?= (int)$c['alunos_checkin'] ?>/<?= (int)$c['total_alunos'] ?> participaram</div>
        </div>
      </div>
      <?php endforeach; ?>
      <div style="text-align:center;margin-top:10px;"><a href="clima.php" class="btn btn-outline" style="font-size:12px;">Ver clima completo →</a></div>
    </div>
  </div>
</div>
<?php pagina_fim(); ?>
