<?php
// aluno/perfil.php — Meu perfil + troca de senha
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_aluno();

$pdo      = db();
$aluno_id = usuario_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    $atual = $_POST['senha_atual'] ?? '';
    $nova  = $_POST['senha_nova'] ?? '';
    $conf  = $_POST['senha_conf'] ?? '';

    $u = $pdo->prepare('SELECT senha_hash FROM usuarios WHERE id = ?');
    $u->execute([$aluno_id]);
    $hash = $u->fetchColumn();

    if (mb_strlen($nova) < 8) {
        flash_set('A nova senha precisa ter pelo menos 8 caracteres.', 'erro');
    } elseif ($nova !== $conf) {
        flash_set('A confirmação não confere com a nova senha.', 'erro');
    } elseif ($hash && !password_verify($atual, $hash)) {
        flash_set('Senha atual incorreta.', 'erro');
    } else {
        $pdo->prepare('UPDATE usuarios SET senha_hash = ?, primeiro_acesso = 0 WHERE id = ?')
            ->execute([hash_senha($nova), $aluno_id]);
        flash_set('Senha alterada com sucesso! 🔒');
    }
    redirect(BASE_URL . '/aluno/perfil.php');
}

$perfil = $pdo->prepare(
    'SELECT ap.*, n.nome AS nivel_nome FROM aluno_perfil ap
     JOIN niveis n ON n.nivel = ap.nivel_atual WHERE ap.aluno_id = ?'
);
$perfil->execute([$aluno_id]);
$ap = $perfil->fetch() ?: ['xp_total' => 0, 'moedas' => 0, 'nivel_atual' => 1, 'nivel_nome' => 'Iniciante', 'streak_dias' => 0, 'missoes_concluidas' => 0];

$hist = $pdo->prepare(
    'SELECT origem, xp, descricao, criado_em FROM xp_eventos WHERE aluno_id = ? ORDER BY criado_em DESC LIMIT 15'
);
$hist->execute([$aluno_id]);
$hist = $hist->fetchAll();

$origem_icone = ['missao' => '⚔️', 'aovivo' => '🎤', 'streak' => '🔥', 'checkin' => '💚', 'mentoria' => '🧭', 'meta' => '🎯', 'conquista' => '🏅', 'ajuste' => '⚙️'];

pagina_inicio('Meu Perfil', 'perfil');
?>
<div class="stats">
  <div class="stat"><div class="stat-v"><?= fmt_num((int)$ap['xp_total']) ?></div><div class="stat-l">XP total</div><div class="stat-s">Nível <?= (int)$ap['nivel_atual'] ?> — <?= e($ap['nivel_nome']) ?></div></div>
  <div class="stat"><div class="stat-v">🪙 <?= fmt_num((int)$ap['moedas']) ?></div><div class="stat-l">GamiCoins</div></div>
  <div class="stat"><div class="stat-v">🔥 <?= (int)$ap['streak_dias'] ?></div><div class="stat-l">Dias seguidos</div></div>
  <div class="stat"><div class="stat-v"><?= (int)$ap['missoes_concluidas'] ?></div><div class="stat-l">Missões concluídas</div></div>
</div>

<div class="two-col">
  <div class="card">
    <div class="card-title">📜 Histórico de XP — nada aqui é negativo 😉</div>
    <?php if (empty($hist)): ?><div style="color:#aaa;font-size:13px;font-weight:700;text-align:center;padding:1rem;">Complete missões para ver seu histórico.</div><?php endif; ?>
    <?php foreach ($hist as $h): ?>
    <div class="rank-row">
      <span style="font-size:16px;"><?= $origem_icone[$h['origem']] ?? '✨' ?></span>
      <div style="flex:1;">
        <div style="font-size:13px;font-weight:700;"><?= e($h['descricao'] ?: ucfirst($h['origem'])) ?></div>
        <div style="font-size:11px;color:#aaa;"><?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?></div>
      </div>
      <span style="font-size:13px;font-weight:800;color:#16a34a;">+<?= (int)$h['xp'] ?> XP</span>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-title">🔒 Trocar senha</div>
    <form method="POST">
      <?= csrf_input() ?>
      <div class="fld"><label>Senha atual</label><input type="password" name="senha_atual" autocomplete="current-password"></div>
      <div class="fld"><label>Nova senha (mín. 8)</label><input type="password" name="senha_nova" required minlength="8" autocomplete="new-password"></div>
      <div class="fld"><label>Confirmar nova senha</label><input type="password" name="senha_conf" required minlength="8" autocomplete="new-password"></div>
      <button type="submit" class="btn btn-primary">Salvar →</button>
    </form>
  </div>
</div>
<?php pagina_fim(); ?>
