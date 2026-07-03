<?php
// professor/portfolio.php — Revisar portfólio + entregas de projeto
// Filosofia: aprova ou devolve para melhorar — nunca "reprova"
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_professor();

$pdo     = db();
$prof_id = usuario_id();

$turma_ids = $pdo->prepare('SELECT DISTINCT turma_id FROM professor_turma WHERE professor_id = ? AND ativo = 1');
$turma_ids->execute([$prof_id]);
$turma_ids = array_map('intval', $turma_ids->fetchAll(PDO::FETCH_COLUMN));
$in = $turma_ids ? implode(',', $turma_ids) : '0';

// ── Ações ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    $acao = $_POST['acao'] ?? '';

    // Entrega de projeto
    if (in_array($acao, ['aprovar_entrega', 'revisar_entrega'], true)) {
        $entrega_id = (int)$_POST['entrega_id'];
        $en = $pdo->prepare(
            'SELECT en.*, pe.xp_etapa, pe.atividade_id, a.titulo, a.professor_id
             FROM projeto_entregas en
             JOIN projeto_etapas pe ON pe.id = en.etapa_id
             JOIN atividades a ON a.id = pe.atividade_id
             WHERE en.id = ? AND a.professor_id = ?'
        );
        $en->execute([$entrega_id, $prof_id]);
        $entrega = $en->fetch();

        if ($entrega) {
            if ($acao === 'aprovar_entrega') {
                $pdo->prepare("UPDATE projeto_entregas SET status = 'aprovada', comentario_prof = ? WHERE id = ?")
                    ->execute([trim($_POST['comentario'] ?? '') ?: null, $entrega_id]);

                // XP acumulado das etapas aprovadas vira o "resultado" da atividade
                $soma = $pdo->prepare(
                    "SELECT COALESCE(SUM(pe.xp_etapa),0) FROM projeto_entregas en
                     JOIN projeto_etapas pe ON pe.id = en.etapa_id
                     WHERE en.aluno_id = ? AND pe.atividade_id = ? AND en.status = 'aprovada'"
                );
                $soma->execute([(int)$entrega['aluno_id'], (int)$entrega['atividade_id']]);
                $xp_total = (int)$soma->fetchColumn();

                $pdo->prepare(
                    'INSERT INTO tentativas (aluno_id, atividade_id, numero, acertos, total_questoes, percentual, xp_ganho, concluida, finalizada_em)
                     VALUES (?, ?, 1, 1, 1, 100, ?, 1, NOW())'
                )->execute([(int)$entrega['aluno_id'], (int)$entrega['atividade_id'], $xp_total]);
                gamifica_registrar_resultado((int)$entrega['aluno_id'], (int)$entrega['atividade_id'], $xp_total, 'missao', $entrega['titulo'] . ' (etapa aprovada)');
                notificar((int)$entrega['aluno_id'], 'projeto', 'Etapa aprovada! 🔬', $entrega['titulo'] . ' — +' . $entrega['xp_etapa'] . ' XP');
                flash_set('Etapa aprovada e XP creditado! 🔬');
            } else {
                $pdo->prepare("UPDATE projeto_entregas SET status = 'revisar', comentario_prof = ? WHERE id = ?")
                    ->execute([trim($_POST['comentario'] ?? 'Dá para melhorar! Revise e reenvie.'), $entrega_id]);
                notificar((int)$entrega['aluno_id'], 'projeto', 'Etapa devolvida para melhorar 💬', $entrega['titulo'] . ' — leia o comentário e reenvie (nada foi perdido).');
                flash_set('Entrega devolvida para melhorar — sem punição, só evolução. 💬');
            }
        }
    }

    // Item de portfólio
    if (in_array($acao, ['aprovar_port', 'revisar_port'], true)) {
        $item_id = (int)$_POST['item_id'];
        $it = $pdo->query(
            "SELECT pi.* FROM portfolio_itens pi
             JOIN aluno_turma at2 ON at2.usuario_id = pi.aluno_id AND at2.ativo = 1
             WHERE pi.id = {$item_id} AND at2.turma_id IN ($in)"
        )->fetch();
        if ($it) {
            if ($acao === 'aprovar_port') {
                $pdo->prepare("UPDATE portfolio_itens SET status = 'aprovado', comentario_prof = ? WHERE id = ?")
                    ->execute([trim($_POST['comentario'] ?? '') ?: null, $item_id]);
                notificar((int)$it['aluno_id'], 'portfolio', 'Trabalho aprovado no portfólio! 🎨', $it['titulo']);
                desbloquear_conquista((int)$it['aluno_id'], 'artista_portfolio');
                flash_set('Trabalho aprovado! 🎨');
            } else {
                $pdo->prepare("UPDATE portfolio_itens SET status = 'revisar', comentario_prof = ? WHERE id = ?")
                    ->execute([trim($_POST['comentario'] ?? 'Capricha mais um pouco e reenvie!'), $item_id]);
                notificar((int)$it['aluno_id'], 'portfolio', 'Trabalho devolvido para melhorar 💬', $it['titulo']);
                flash_set('Devolvido para melhorar. 💬');
            }
        }
    }
    redirect(BASE_URL . '/professor/portfolio.php');
}

// ── Pendências ───────────────────────────────────────────────
$entregas = $pdo->prepare(
    "SELECT en.*, pe.titulo AS etapa_titulo, pe.ordem, pe.xp_etapa, a.titulo AS atividade_titulo, u.nome AS aluno_nome
     FROM projeto_entregas en
     JOIN projeto_etapas pe ON pe.id = en.etapa_id
     JOIN atividades a ON a.id = pe.atividade_id
     JOIN usuarios u ON u.id = en.aluno_id
     WHERE a.professor_id = ? AND en.status = 'enviada'
     ORDER BY en.criado_em ASC"
);
$entregas->execute([$prof_id]);
$entregas = $entregas->fetchAll();

$itens = $pdo->query(
    "SELECT pi.*, u.nome AS aluno_nome FROM portfolio_itens pi
     JOIN usuarios u ON u.id = pi.aluno_id
     JOIN aluno_turma at2 ON at2.usuario_id = pi.aluno_id AND at2.ativo = 1
     WHERE at2.turma_id IN ($in) AND pi.status = 'enviado'
     GROUP BY pi.id
     ORDER BY pi.criado_em ASC"
)->fetchAll();

pagina_inicio('Portfólio & Entregas', 'portfolio');
?>
<div class="alert alert-info">💜 Aqui não existe "reprovar": você <strong>aprova</strong> (credita XP / publica) ou <strong>devolve para melhorar</strong> com um comentário. O aluno reenvia quantas vezes precisar.</div>

<div class="section-label">🔬 Entregas de projeto aguardando revisão (<?= count($entregas) ?>)</div>
<?php if (empty($entregas)): ?>
  <div class="card" style="color:#aaa;font-weight:700;font-size:13px;">Nenhuma entrega pendente. ✨</div>
<?php endif; ?>
<?php foreach ($entregas as $en): ?>
<div class="card">
  <div class="card-title">
    <span>🔬 <?= e($en['aluno_nome']) ?> · <?= e($en['atividade_titulo']) ?> — Etapa <?= (int)$en['ordem'] ?>: <?= e($en['etapa_titulo']) ?></span>
    <span class="xp-badge">⭐ <?= (int)$en['xp_etapa'] ?> XP</span>
  </div>
  <p style="font-size:13px;font-weight:600;color:#444;background:#fafafa;border-radius:10px;padding:10px 12px;margin-bottom:10px;"><?= nl2br(e($en['texto'] ?? '')) ?></p>
  <form method="POST" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <?= csrf_input() ?>
    <input type="hidden" name="entrega_id" value="<?= (int)$en['id'] ?>">
    <input type="text" name="comentario" placeholder="Comentário para o aluno (opcional)" style="flex:1;min-width:200px;">
    <button type="submit" name="acao" value="aprovar_entrega" class="btn btn-primary" style="font-size:12px;">✅ Aprovar (+XP)</button>
    <button type="submit" name="acao" value="revisar_entrega" class="btn btn-outline" style="font-size:12px;">💬 Devolver p/ melhorar</button>
  </form>
</div>
<?php endforeach; ?>

<div class="section-label" style="margin-top:1.5rem;">🎨 Portfólio aguardando revisão (<?= count($itens) ?>)</div>
<?php if (empty($itens)): ?>
  <div class="card" style="color:#aaa;font-weight:700;font-size:13px;">Nenhum trabalho pendente. ✨</div>
<?php endif; ?>
<?php foreach ($itens as $it): ?>
<div class="card">
  <div class="card-title"><span>🎨 <?= e($it['aluno_nome']) ?> · <?= e($it['titulo']) ?></span></div>
  <?php if ($it['descricao']): ?><p style="font-size:13px;font-weight:600;color:#444;margin-bottom:10px;"><?= nl2br(e($it['descricao'])) ?></p><?php endif; ?>
  <form method="POST" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <?= csrf_input() ?>
    <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
    <input type="text" name="comentario" placeholder="Comentário para o aluno (opcional)" style="flex:1;min-width:200px;">
    <button type="submit" name="acao" value="aprovar_port" class="btn btn-primary" style="font-size:12px;">✅ Aprovar</button>
    <button type="submit" name="acao" value="revisar_port" class="btn btn-outline" style="font-size:12px;">💬 Devolver p/ melhorar</button>
  </form>
</div>
<?php endforeach; ?>
<?php pagina_fim(); ?>
