<?php
// professor/atividades.php — Listar/publicar/encerrar atividades + concluir grupo
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_professor();

$pdo     = db();
$prof_id = usuario_id();

// ── Ações ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    $id = (int)($_POST['id'] ?? 0);
    $chk = $pdo->prepare('SELECT * FROM atividades WHERE id = ? AND professor_id = ?');
    $chk->execute([$id, $prof_id]);
    $atv = $chk->fetch();

    if ($atv) {
        switch ($_POST['acao'] ?? '') {
            case 'publicar':
                $pdo->prepare('UPDATE atividades SET publicada = 1 WHERE id = ?')->execute([$id]);
                notificar_turma((int)$atv['turma_id'], 'atividade', 'Nova missão disponível! ' . icone_tipo($atv['tipo']), $atv['titulo'], BASE_URL . '/aluno/missao.php?id=' . $id);
                flash_set('Atividade publicada e turma notificada! 🚀');
                break;
            case 'despublicar':
                $pdo->prepare('UPDATE atividades SET publicada = 0 WHERE id = ?')->execute([$id]);
                flash_set('Atividade despublicada.');
                break;
            case 'encerrar':
                $pdo->prepare('UPDATE atividades SET data_fim = NOW() WHERE id = ?')->execute([$id]);
                flash_set('Atividade encerrada — o melhor resultado de cada aluno está garantido.');
                break;
            case 'excluir':
                if (!$atv['publicada']) {
                    $pdo->prepare('DELETE FROM atividades WHERE id = ?')->execute([$id]);
                    flash_set('Rascunho excluído.');
                } else {
                    flash_set('Atividades publicadas não podem ser excluídas — encerre-a.', 'erro');
                }
                break;
            case 'concluir_grupo':
                if ($atv['tipo'] === 'grupo') {
                    $membros = $pdo->prepare('SELECT aluno_id FROM grupo_membros WHERE atividade_id = ?');
                    $membros->execute([$id]);
                    $n = 0;
                    foreach ($membros->fetchAll(PDO::FETCH_COLUMN) as $aluno_id) {
                        $pdo->prepare(
                            'INSERT INTO tentativas (aluno_id, atividade_id, numero, acertos, total_questoes, percentual, xp_ganho, concluida, finalizada_em)
                             VALUES (?, ?, 1, 1, 1, 100, ?, 1, NOW())'
                        )->execute([(int)$aluno_id, $id, (int)$atv['xp_recompensa']]);
                        gamifica_registrar_resultado((int)$aluno_id, $id, (int)$atv['xp_recompensa'], 'missao', $atv['titulo'] . ' (grupo)');
                        notificar((int)$aluno_id, 'atividade', 'Trabalho em grupo concluído! 🤝', $atv['titulo'] . ' — +' . $atv['xp_recompensa'] . ' XP para todo o grupo');
                        $n++;
                    }
                    flash_set("Grupo concluído! XP creditado para {$n} aluno(s). 🤝");
                }
                break;
        }
    }
    redirect(BASE_URL . '/professor/atividades.php');
}

$lista = $pdo->prepare(
    'SELECT a.*, t.nome AS turma_nome,
            (SELECT COUNT(DISTINCT ax.aluno_id) FROM aluno_xp ax WHERE ax.atividade_id = a.id) AS concluiram,
            (SELECT COUNT(*) FROM aluno_turma at2 WHERE at2.turma_id = a.turma_id AND at2.ativo = 1) AS total_alunos
     FROM atividades a
     JOIN turmas t ON t.id = a.turma_id
     WHERE a.professor_id = ?
     ORDER BY a.criado_em DESC'
);
$lista->execute([$prof_id]);
$atividades = $lista->fetchAll();

pagina_inicio('Minhas Atividades', 'atividades');
?>
<div style="margin-bottom:14px;">
  <a href="atividade_form.php" class="btn btn-primary">➕ Nova atividade</a>
</div>

<?php if (empty($atividades)): ?>
  <div class="card" style="text-align:center;padding:2rem;color:#aaa;">Nenhuma atividade criada ainda.</div>
<?php endif; ?>

<?php foreach ($atividades as $a):
  $expirada = $a['data_fim'] && strtotime($a['data_fim']) < time();
  $pct = $a['total_alunos'] ? round(100 * $a['concluiram'] / $a['total_alunos']) : 0;
?>
<div class="mission">
  <div class="mission-inner">
    <div class="mission-icon"><?= icone_tipo($a['tipo']) ?></div>
    <div class="mission-body">
      <div class="mission-header">
        <span class="mission-title"><?= e($a['titulo']) ?></span>
        <span class="xp-badge">⭐ <?= (int)$a['xp_recompensa'] ?> XP</span>
      </div>
      <div class="mission-meta">
        <span class="badge bb">Turma <?= e($a['turma_nome']) ?></span>
        <span class="badge ba"><?= e($a['disciplina']) ?></span>
        <span class="badge bk"><?= ucfirst($a['tipo']) ?></span>
        <?php if (!$a['publicada']): ?><span class="badge br">Rascunho</span>
        <?php elseif ($expirada): ?><span class="badge bk">Encerrada</span>
        <?php else: ?><span class="badge bg">Publicada</span><?php endif; ?>
        <span style="font-size:11px;color:#aaa;font-weight:700;"><?= (int)$a['concluiram'] ?>/<?= (int)$a['total_alunos'] ?> concluíram (<?= $pct ?>%)</span>
      </div>
      <div class="progress"><div class="prog-fill" style="width:<?= $pct ?>%;background:<?= cor_progresso($pct) ?>;"></div></div>
      <div class="mission-footer" style="gap:6px;flex-wrap:wrap;">
        <a href="atividade_form.php?id=<?= (int)$a['id'] ?>" class="btn btn-outline" style="font-size:12px;">✏️ Editar / conteúdo</a>
        <form method="POST" style="display:inline-flex;gap:6px;flex-wrap:wrap;">
          <?= csrf_input() ?>
          <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
          <?php if (!$a['publicada']): ?>
            <button type="submit" name="acao" value="publicar" class="btn btn-primary" style="font-size:12px;">🚀 Publicar</button>
            <button type="submit" name="acao" value="excluir" class="btn btn-outline" style="font-size:12px;" onclick="return confirm('Excluir este rascunho?')">🗑️</button>
          <?php else: ?>
            <?php if (!$expirada): ?><button type="submit" name="acao" value="encerrar" class="btn btn-outline" style="font-size:12px;" onclick="return confirm('Encerrar esta atividade?')">🏁 Encerrar</button><?php endif; ?>
            <?php if ($a['tipo'] === 'grupo' && !$a['concluiram']): ?>
              <button type="submit" name="acao" value="concluir_grupo" class="btn btn-primary" style="font-size:12px;" onclick="return confirm('Concluir e creditar XP para todo o grupo?')">🤝 Concluir grupo</button>
            <?php endif; ?>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php pagina_fim(); ?>
