<?php
// aluno/mentoria.php — Minhas mentorias (mentor ou aprendiz)
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_aluno();
if (!modulo_ativo('mentoria')) { flash_set('O módulo de mentoria está desativado.', 'erro'); redirect(BASE_URL . '/aluno/'); }

$pdo      = db();
$aluno_id = usuario_id();

$stmt = $pdo->prepare(
    'SELECT m.*, um.nome AS mentor_nome, ua.nome AS aprendiz_nome, t.nome AS turma_nome
     FROM mentorias m
     JOIN usuarios um ON um.id = m.mentor_id
     JOIN usuarios ua ON ua.id = m.aprendiz_id
     JOIN turmas t    ON t.id = m.turma_id
     WHERE (m.mentor_id = ? OR m.aprendiz_id = ?)
     ORDER BY m.status = "ativa" DESC, m.criado_em DESC'
);
$stmt->execute([$aluno_id, $aluno_id]);
$mentorias = $stmt->fetchAll();

$bonus = $pdo->prepare(
    "SELECT COALESCE(SUM(xp),0) AS total, COUNT(*) AS eventos FROM xp_eventos WHERE aluno_id = ? AND origem = 'mentoria'"
);
$bonus->execute([$aluno_id]);
$bonus = $bonus->fetch();

pagina_inicio('Mentoria', 'mentoria');
?>
<div class="alert alert-info">🧭 <strong>Mentoria entre Pares:</strong> quando seu colega aprendiz melhora a pontuação, você (mentor) ganha XP de bônus. Ajudar vale XP — e ninguém perde nada.</div>

<div class="stats" style="grid-template-columns:1fr 1fr;">
  <div class="stat"><div class="stat-v">🧭 <?= fmt_num((int)$bonus['total']) ?></div><div class="stat-l">XP ganho como mentor</div></div>
  <div class="stat"><div class="stat-v"><?= (int)$bonus['eventos'] ?></div><div class="stat-l">Bônus recebidos</div></div>
</div>

<?php if (empty($mentorias)): ?>
  <div class="card" style="text-align:center;padding:2rem;color:#aaa;">
    Você ainda não participa de nenhuma mentoria.<br>Seu professor pode formar duplas de mentoria na turma. 🧭
  </div>
<?php endif; ?>

<?php foreach ($mentorias as $m): $sou_mentor = ((int)$m['mentor_id'] === $aluno_id); ?>
<div class="card">
  <div class="card-title">
    <span>🧭 <?= $sou_mentor ? 'Você é mentor de ' . e($m['aprendiz_nome']) : e($m['mentor_nome']) . ' é seu mentor' ?></span>
    <span class="badge <?= $m['status'] === 'ativa' ? 'bg' : 'bk' ?>"><?= $m['status'] === 'ativa' ? 'Ativa' : 'Encerrada' ?></span>
  </div>
  <div class="mission-meta">
    <span class="badge ba"><?= e($m['disciplina'] ?: 'Geral') ?></span>
    <span class="badge bk">Turma <?= e($m['turma_nome']) ?></span>
    <span class="badge bp">Bônus do mentor: <?= (int)$m['pct_bonus'] ?>% da melhora</span>
  </div>
  <p style="font-size:13px;color:#888;font-weight:600;margin-top:8px;">
    <?= $sou_mentor
        ? 'Ajude seu colega a refazer missões e melhorar a pontuação — cada melhora dele te dá XP também!'
        : 'Peça ajuda ao seu mentor e refaça as missões: você melhora seu XP e ele ganha bônus por te ajudar. Todo mundo cresce junto!' ?>
  </p>
</div>
<?php endforeach; ?>
<?php pagina_fim(); ?>
