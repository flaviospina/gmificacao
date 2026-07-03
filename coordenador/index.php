<?php
// coordenador/index.php — Visão geral da escola
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_coordenador();

$pdo = db();
$eid = escola_id();

$q = fn(string $sql, array $p = []) => (function () use ($pdo, $sql, $p) { $s = $pdo->prepare($sql); $s->execute($p); return $s; })();

$total_alunos      = (int)$q("SELECT COUNT(*) FROM usuarios WHERE escola_id = ? AND perfil = 'aluno' AND ativo = 1", [$eid])->fetchColumn();
$total_professores = (int)$q("SELECT COUNT(*) FROM usuarios WHERE escola_id = ? AND perfil = 'professor' AND ativo = 1", [$eid])->fetchColumn();
$total_turmas      = (int)$q('SELECT COUNT(*) FROM turmas WHERE escola_id = ? AND ativa = 1', [$eid])->fetchColumn();
$total_atividades  = (int)$q('SELECT COUNT(*) FROM atividades a JOIN turmas t ON t.id = a.turma_id WHERE t.escola_id = ? AND a.publicada = 1', [$eid])->fetchColumn();
$xp_semana         = (int)$q('SELECT COALESCE(SUM(xe.xp),0) FROM xp_eventos xe JOIN usuarios u ON u.id = xe.aluno_id WHERE u.escola_id = ? AND YEARWEEK(xe.criado_em,1) = YEARWEEK(CURDATE(),1)', [$eid])->fetchColumn();
$checkins_semana   = (int)$q('SELECT COUNT(*) FROM checkins_bemestar cb JOIN usuarios u ON u.id = cb.aluno_id WHERE u.escola_id = ? AND cb.data >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)', [$eid])->fetchColumn();

$engajamento = $q('SELECT * FROM vw_engajamento_turmas WHERE turma_id IN (SELECT id FROM turmas WHERE escola_id = ?) ORDER BY turma_nome', [$eid])->fetchAll();
$clima = $q('SELECT * FROM vw_clima_turma WHERE escola_id = ?', [$eid])->fetchAll();
$clima_map = array_column($clima, null, 'turma_id');

$top = $q('SELECT aluno_nome, xp_total, nivel FROM vw_ranking_escola WHERE escola_id = ? ORDER BY posicao_escola LIMIT 5', [$eid])->fetchAll();

pagina_inicio('Visão Geral da Escola', 'inicio');
?>
<div class="stats" style="grid-template-columns:repeat(6,1fr);">
  <div class="stat"><div class="stat-v"><?= $total_alunos ?></div><div class="stat-l">Alunos</div></div>
  <div class="stat"><div class="stat-v"><?= $total_professores ?></div><div class="stat-l">Professores</div></div>
  <div class="stat"><div class="stat-v"><?= $total_turmas ?></div><div class="stat-l">Turmas</div></div>
  <div class="stat"><div class="stat-v"><?= $total_atividades ?></div><div class="stat-l">Atividades</div></div>
  <div class="stat"><div class="stat-v"><?= fmt_num($xp_semana) ?></div><div class="stat-l">XP na semana</div></div>
  <div class="stat"><div class="stat-v">💚 <?= $checkins_semana ?></div><div class="stat-l">Check-ins (7d)</div></div>
</div>

<div class="two-col">
  <div class="card" style="overflow-x:auto;">
    <div class="card-title">🏫 Engajamento por turma</div>
    <?php if (empty($engajamento)): ?><div style="color:#aaa;font-size:13px;font-weight:700;">Sem turmas cadastradas.</div><?php endif; ?>
    <table>
      <tr><th>Turma</th><th>Professor</th><th>Alunos</th><th>Ativ.</th><th>XP médio</th><th>Engaj.</th><th>Clima</th></tr>
      <?php foreach ($engajamento as $e2): $c = $clima_map[$e2['turma_id']] ?? null; ?>
      <tr>
        <td style="font-weight:800;"><?= e($e2['turma_nome']) ?></td>
        <td><?= e($e2['professor_nome'] ?? '—') ?><br><span style="font-size:11px;color:#aaa;"><?= e($e2['disciplina'] ?? '') ?></span></td>
        <td><?= (int)$e2['total_alunos'] ?></td>
        <td><?= (int)$e2['total_atividades'] ?></td>
        <td><?= fmt_num((int)$e2['xp_medio']) ?></td>
        <td><?= badge_engajamento((float)$e2['pct_engajamento']) ?></td>
        <td><?= $c && $c['humor_medio'] !== null ? emoji_humor((int)round($c['humor_medio'])) . ' ' . number_format((float)$c['humor_medio'], 1, ',', '') : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="card">
    <div class="card-title">🏆 Top 5 da escola</div>
    <?php foreach ($top as $i => $r): ?>
    <div class="rank-row">
      <span style="font-size:18px;"><?= medalha($i + 1) ?></span>
      <div style="flex:1;">
        <div style="font-size:13px;font-weight:800;"><?= e($r['aluno_nome']) ?></div>
        <div style="font-size:11px;color:#aaa;"><?= fmt_num((int)$r['xp_total']) ?> XP · Nível <?= (int)$r['nivel'] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
    <div style="text-align:center;margin-top:10px;"><a href="destaques.php" class="btn btn-outline" style="font-size:12px;">Ver destaques →</a></div>
  </div>
</div>
<?php pagina_fim(); ?>
