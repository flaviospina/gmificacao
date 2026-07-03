<?php
// professor/mentoria.php — Formar e acompanhar duplas de mentoria [inédito]
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_professor();
if (!modulo_ativo('mentoria')) { flash_set('O módulo de mentoria está desativado.', 'erro'); redirect(BASE_URL . '/professor/'); }

$pdo     = db();
$prof_id = usuario_id();

$vinculos = $pdo->prepare(
    'SELECT pt.turma_id, t.nome AS turma_nome, pt.disciplina FROM professor_turma pt
     JOIN turmas t ON t.id = pt.turma_id AND t.ativa = 1
     WHERE pt.professor_id = ? AND pt.ativo = 1 ORDER BY t.nome'
);
$vinculos->execute([$prof_id]);
$vinculos = $vinculos->fetchAll();
$turma_ids = array_unique(array_map('intval', array_column($vinculos, 'turma_id')));
$in = $turma_ids ? implode(',', $turma_ids) : '0';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    if (($_POST['acao'] ?? '') === 'encerrar') {
        $pdo->prepare("UPDATE mentorias SET status = 'encerrada', encerrada_em = NOW() WHERE id = ? AND criador_id = ?")
            ->execute([(int)$_POST['mentoria_id'], $prof_id]);
        flash_set('Mentoria encerrada. Os XP ganhos permanecem — nada se perde. 😉');
    } else {
        $turma_id = (int)($_POST['turma_id'] ?? 0);
        $mentor   = (int)($_POST['mentor_id'] ?? 0);
        $aprendiz = (int)($_POST['aprendiz_id'] ?? 0);

        $valida = function (int $aluno, int $turma) use ($pdo): bool {
            $s = $pdo->prepare("SELECT 1 FROM aluno_turma at2 JOIN usuarios u ON u.id = at2.usuario_id AND u.perfil='aluno' AND u.ativo=1 WHERE at2.usuario_id = ? AND at2.turma_id = ? AND at2.ativo = 1");
            $s->execute([$aluno, $turma]);
            return (bool)$s->fetch();
        };

        if (!in_array($turma_id, $turma_ids, true) || $mentor === $aprendiz || !$valida($mentor, $turma_id) || !$valida($aprendiz, $turma_id)) {
            flash_set('Escolha dois alunos diferentes da mesma turma.', 'erro');
        } else {
            $pdo->prepare(
                'INSERT INTO mentorias (turma_id, mentor_id, aprendiz_id, disciplina, criador_id, pct_bonus)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$turma_id, $mentor, $aprendiz, trim($_POST['disciplina'] ?? '') ?: null, $prof_id, (int)config('mentor_pct_bonus', '20')]);
            notificar($mentor, 'mentoria', 'Você agora é mentor! 🧭', 'Ajude seu colega a evoluir — cada melhora dele te dá XP de bônus.');
            notificar($aprendiz, 'mentoria', 'Você ganhou um mentor! 🧭', 'Um colega vai te ajudar a evoluir. Refazer missões só melhora seu XP!');
            flash_set('Dupla de mentoria criada! 🧭');
        }
    }
    redirect(BASE_URL . '/professor/mentoria.php');
}

$alunos = $pdo->query(
    "SELECT u.id, u.nome, at2.turma_id FROM usuarios u
     JOIN aluno_turma at2 ON at2.usuario_id = u.id AND at2.ativo = 1
     WHERE at2.turma_id IN ($in) AND u.perfil = 'aluno' AND u.ativo = 1
     ORDER BY u.nome"
)->fetchAll();

$mentorias = $pdo->query(
    "SELECT m.*, um.nome AS mentor_nome, ua.nome AS aprendiz_nome, t.nome AS turma_nome,
            (SELECT COALESCE(SUM(xe.xp),0) FROM xp_eventos xe WHERE xe.origem = 'mentoria' AND xe.referencia_id = m.id) AS xp_gerado
     FROM mentorias m
     JOIN usuarios um ON um.id = m.mentor_id
     JOIN usuarios ua ON ua.id = m.aprendiz_id
     JOIN turmas t ON t.id = m.turma_id
     WHERE m.turma_id IN ($in)
     ORDER BY m.status = 'ativa' DESC, m.criado_em DESC"
)->fetchAll();

pagina_inicio('Mentoria entre Pares', 'mentoria');
?>
<div class="alert alert-info">🧭 <strong>Mentoria entre Pares (exclusivo Gamifica):</strong> forme duplas. Quando o aprendiz melhora a pontuação, o mentor ganha <?= (int)config('mentor_pct_bonus', '20') ?>% da melhora em XP. Ajudar o colega vale XP — e ninguém perde nada.</div>

<div class="card">
  <div class="card-title">➕ Formar dupla</div>
  <form method="POST" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:12px;align-items:end;">
    <?= csrf_input() ?>
    <div class="fld" style="margin:0;"><label>Turma</label>
      <select name="turma_id" id="selTurma">
        <?php foreach (array_unique(array_column($vinculos, 'turma_nome', 'turma_id')) as $tid => $tnome): ?>
          <option value="<?= (int)$tid ?>"><?= e($tnome) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="fld" style="margin:0;"><label>Mentor (quem ajuda)</label>
      <select name="mentor_id" class="selAluno">
        <?php foreach ($alunos as $a): ?><option value="<?= (int)$a['id'] ?>" data-turma="<?= (int)$a['turma_id'] ?>"><?= e($a['nome']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="fld" style="margin:0;"><label>Aprendiz</label>
      <select name="aprendiz_id" class="selAluno">
        <?php foreach ($alunos as $a): ?><option value="<?= (int)$a['id'] ?>" data-turma="<?= (int)$a['turma_id'] ?>"><?= e($a['nome']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="fld" style="margin:0;"><label>Disciplina (opcional)</label><input type="text" name="disciplina" maxlength="80" placeholder="Geral"></div>
    <button type="submit" class="btn btn-primary">Criar →</button>
  </form>
</div>
<script>
(function(){
  const turma = document.getElementById('selTurma');
  function filtra(){
    document.querySelectorAll('.selAluno option').forEach(o => {
      o.hidden = o.dataset.turma !== turma.value;
    });
    document.querySelectorAll('.selAluno').forEach(s => {
      if (s.selectedOptions[0] && s.selectedOptions[0].hidden) {
        const v = s.querySelector('option:not([hidden])');
        if (v) s.value = v.value;
      }
    });
  }
  turma.addEventListener('change', filtra);
  filtra();
})();
</script>

<?php if (empty($mentorias)): ?>
  <div class="card" style="text-align:center;padding:2rem;color:#aaa;">Nenhuma mentoria criada ainda.</div>
<?php endif; ?>

<?php foreach ($mentorias as $m): ?>
<div class="card">
  <div class="card-title">
    <span>🧭 <?= e($m['mentor_nome']) ?> → <?= e($m['aprendiz_nome']) ?> <span class="badge bb"><?= e($m['turma_nome']) ?></span> <span class="badge ba"><?= e($m['disciplina'] ?: 'Geral') ?></span></span>
    <span class="badge <?= $m['status'] === 'ativa' ? 'bg' : 'bk' ?>"><?= ucfirst($m['status']) ?></span>
  </div>
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
    <span style="font-size:13px;font-weight:700;color:#7c6ef0;">XP de bônus já gerado para o mentor: <?= fmt_num((int)$m['xp_gerado']) ?></span>
    <?php if ($m['status'] === 'ativa'): ?>
    <form method="POST">
      <?= csrf_input() ?>
      <input type="hidden" name="acao" value="encerrar">
      <input type="hidden" name="mentoria_id" value="<?= (int)$m['id'] ?>">
      <button type="submit" class="btn btn-outline" style="font-size:12px;" onclick="return confirm('Encerrar esta mentoria?')">Encerrar</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php pagina_fim(); ?>
