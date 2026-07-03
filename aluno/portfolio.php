<?php
// aluno/portfolio.php — Portfólio do aluno (sem reprovação: aprovado ou "melhorar")
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_aluno();
if (!modulo_ativo('portfolio')) { flash_set('O portfólio está desativado.', 'erro'); redirect(BASE_URL . '/aluno/'); }

$pdo      = db();
$aluno_id = usuario_id();

// ── Publicar/atualizar item ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    $titulo = trim($_POST['titulo'] ?? '');
    $desc   = trim($_POST['descricao'] ?? '');
    $edit   = (int)($_POST['item_id'] ?? 0);
    if ($titulo !== '') {
        if ($edit > 0) {
            $pdo->prepare(
                "UPDATE portfolio_itens SET titulo = ?, descricao = ?, status = 'enviado', comentario_prof = NULL
                 WHERE id = ? AND aluno_id = ?"
            )->execute([$titulo, $desc, $edit, $aluno_id]);
            flash_set('Trabalho atualizado e reenviado para revisão! 🎨');
        } else {
            $pdo->prepare(
                'INSERT INTO portfolio_itens (aluno_id, titulo, descricao, visivel_familia) VALUES (?, ?, ?, ?)'
            )->execute([$aluno_id, $titulo, $desc, isset($_POST['visivel_familia']) ? 1 : 0]);
            flash_set('Trabalho publicado no portfólio! Seu professor vai revisar. 🎨');
        }
    }
    redirect(BASE_URL . '/aluno/portfolio.php');
}

$itens = $pdo->prepare('SELECT * FROM portfolio_itens WHERE aluno_id = ? ORDER BY criado_em DESC');
$itens->execute([$aluno_id]);
$itens = $itens->fetchAll();

$st = [
    'enviado'  => ['ba', '📨 Em revisão'],
    'aprovado' => ['bg', '✅ Aprovado'],
    'revisar'  => ['bb', '💬 Melhorar e reenviar'],
];

pagina_inicio('Portfólio', 'portfolio');
?>
<div class="card">
  <div class="card-title">➕ Publicar novo trabalho</div>
  <form method="POST">
    <?= csrf_input() ?>
    <div class="fld"><label>Título</label><input type="text" name="titulo" required maxlength="150" placeholder="Ex.: Maquete do sistema solar"></div>
    <div class="fld"><label>Descrição</label><textarea name="descricao" rows="3" placeholder="Conte o que você fez e o que aprendeu..."></textarea></div>
    <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;margin-bottom:12px;">
      <input type="checkbox" name="visivel_familia" checked style="width:auto;"> Mostrar para minha família quando aprovado
    </label>
    <button type="submit" class="btn btn-primary">Publicar →</button>
  </form>
</div>

<?php if (empty($itens)): ?>
  <div class="card" style="text-align:center;padding:2rem;color:#aaa;">Seu portfólio está esperando o primeiro trabalho! 🎨</div>
<?php endif; ?>

<?php foreach ($itens as $it): [$cls, $rotulo] = $st[$it['status']]; ?>
<div class="card">
  <div class="card-title">
    <span>🎨 <?= e($it['titulo']) ?></span>
    <span class="badge <?= $cls ?>"><?= $rotulo ?></span>
  </div>
  <?php if ($it['descricao']): ?><p style="font-size:13px;font-weight:600;color:#555;margin-bottom:8px;"><?= nl2br(e($it['descricao'])) ?></p><?php endif; ?>
  <?php if ($it['status'] === 'revisar' && $it['comentario_prof']): ?>
    <div class="alert alert-info" style="margin-bottom:10px;">💬 Professor: <?= e($it['comentario_prof']) ?></div>
    <details>
      <summary style="font-size:12px;font-weight:800;color:#7c6ef0;cursor:pointer;">Melhorar e reenviar</summary>
      <form method="POST" style="margin-top:10px;">
        <?= csrf_input() ?>
        <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
        <div class="fld"><label>Título</label><input type="text" name="titulo" required value="<?= e($it['titulo']) ?>"></div>
        <div class="fld"><label>Descrição</label><textarea name="descricao" rows="3"><?= e($it['descricao'] ?? '') ?></textarea></div>
        <button type="submit" class="btn btn-primary">Reenviar →</button>
      </form>
    </details>
  <?php endif; ?>
  <div style="font-size:11px;color:#aaa;font-weight:700;margin-top:6px;"><?= date('d/m/Y', strtotime($it['criado_em'])) ?><?= $it['visivel_familia'] ? ' · 👨‍👩‍👧 visível para a família' : '' ?></div>
</div>
<?php endforeach; ?>
<?php pagina_fim(); ?>
