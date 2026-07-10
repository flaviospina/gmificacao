<?php
// ============================================================
//  includes/layout.php — Cabeçalho, sidebar e rodapé comuns
//  Gamifica V2 — layout funcional (refinamento visual: próxima etapa)
// ============================================================

/** Mensagem flash (1 exibição) */
function flash_set(string $msg, string $tipo = 'ok'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'tipo' => $tipo];
}
function flash_render(): string {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $cor = $f['tipo'] === 'erro' ? 'flash-erro' : 'flash-ok';
    return '<div class="flash ' . $cor . '">' . e($f['msg']) . '</div>';
}

/** Itens de menu por perfil: [url, icone, rotulo, chave] */
function menu_itens(string $perfil): array {
    $b = BASE_URL;
    return match ($perfil) {
        'aluno' => [
            ["$b/aluno/",              '⚔️', 'Missões',     'missoes'],
            ["$b/aluno/aovivo.php",    '🎤', 'Ao Vivo',     'aovivo'],
            ["$b/aluno/conquistas.php",'🏆', 'Conquistas',  'conquistas'],
            ["$b/aluno/ranking.php",   '📊', 'Ranking',     'ranking'],
            ["$b/aluno/materias.php",  '📚', 'Matérias',    'materias'],
            ["$b/aluno/loja.php",      '🛍️', 'Loja',        'loja'],
            ["$b/aluno/portfolio.php", '🎨', 'Portfólio',   'portfolio'],
            ["$b/aluno/mentoria.php",  '🧭', 'Mentoria',    'mentoria'],
            ["$b/aluno/perfil.php",    '👤', 'Meu Perfil',  'perfil'],
        ],
        'professor' => [
            ["$b/professor/",               '🏠', 'Início',      'inicio'],
            ["$b/professor/atividades.php", '📝', 'Atividades',  'atividades'],
            ["$b/professor/aovivo.php",     '🎤', 'Ao Vivo',     'aovivo'],
            ["$b/professor/metas.php",      '🎯', 'Metas',       'metas'],
            ["$b/professor/mentoria.php",   '🧭', 'Mentorias',   'mentoria'],
            ["$b/professor/portfolio.php",  '🎨', 'Portfólio',   'portfolio'],
            ["$b/professor/familia.php",    '👨‍👩‍👧', 'Família',     'familia'],
            ["$b/professor/loja.php",       '🛍️', 'Resgates',    'loja'],
            ["$b/professor/clima.php",      '💚', 'Clima',       'clima'],
            ["$b/professor/relatorios.php", '📈', 'Relatórios',  'relatorios'],
        ],
        'coordenador' => [
            ["$b/coordenador/",                '🏠', 'Visão Geral', 'inicio'],
            ["$b/coordenador/turmas.php",      '🏫', 'Turmas',      'turmas'],
            ["$b/coordenador/professores.php", '🧑‍🏫', 'Professores', 'professores'],
            ["$b/coordenador/destaques.php",   '🌟', 'Destaques',   'destaques'],
            ["$b/coordenador/clima.php",       '💚', 'Clima',       'clima'],
            ["$b/coordenador/relatorio.php",   '📥', 'Exportar CSV','relatorio'],
        ],
        'admin' => [
            ["$b/admin/",                '🏠', 'Início',      'inicio'],
            ["$b/admin/usuarios.php",    '👥', 'Usuários',    'usuarios'],
            ["$b/admin/modulos.php",     '🧩', 'Módulos',     'modulos'],
            ["$b/admin/loja.php",        '🛍️', 'Loja',        'loja'],
            ["$b/admin/integracoes.php", '🔗', 'Integrações', 'integracoes'],
            ["$b/admin/sistema.php",     '⚙️', 'Sistema',     'sistema'],
            ["$b/admin/logs.php",        '📜', 'Logs',        'logs'],
            ["$b/admin/backup.php",      '💾', 'Backup',      'backup'],
        ],
        'responsavel' => [
            ["$b/responsavel/",              '🏠', 'Meus Filhos', 'inicio'],
            ["$b/responsavel/mensagens.php", '💬', 'Mensagens',   'mensagens'],
        ],
        default => [],
    };
}

/** Abre a página: <head>, sidebar e topo do <main> */
function pagina_inicio(string $titulo, string $menu_ativo = ''): void {
    $perfil = usuario_perfil();
    $rotulos = [
        'aluno' => 'Aluno', 'professor' => 'Professor', 'coordenador' => 'Coordenador',
        'admin' => 'Admin', 'responsavel' => 'Responsável',
    ];

    // Notificações não lidas (sino)
    $notifs = [];
    $nao_lidas = 0;
    try {
        $stmt = db()->prepare('SELECT id, titulo, mensagem, lida, criado_em FROM notificacoes WHERE usuario_id = ? ORDER BY criado_em DESC LIMIT 8');
        $stmt->execute([usuario_id()]);
        $notifs = $stmt->fetchAll();
        $stmt = db()->prepare('SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND lida = 0');
        $stmt->execute([usuario_id()]);
        $nao_lidas = (int)$stmt->fetchColumn();
    } catch (PDOException $e) { log_erro('Layout', $e->getMessage()); }

    // XP na sidebar (só aluno)
    $xp_box = null;
    if ($perfil === 'aluno') {
        try {
            $stmt = db()->prepare(
                'SELECT ap.xp_total, ap.moedas, ap.nivel_atual, n.nome AS nivel_nome, n.xp_minimo, n2.xp_minimo AS xp_proximo
                 FROM aluno_perfil ap
                 JOIN niveis n ON n.nivel = ap.nivel_atual
                 LEFT JOIN niveis n2 ON n2.nivel = ap.nivel_atual + 1
                 WHERE ap.aluno_id = ?'
            );
            $stmt->execute([usuario_id()]);
            $xp_box = $stmt->fetch() ?: null;
        } catch (PDOException $e) { log_erro('Layout', $e->getMessage()); }
    }
    $moldura = $perfil === 'aluno' ? avatar_moldura(usuario_id()) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo) ?> — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
</head>
<body>
<div class="dash">
  <aside class="sidebar">
    <div class="sb-logo">
      <div class="sb-logo-i">🎮</div>
      <div class="sb-logo-t"><?= APP_NAME ?></div>
    </div>
    <div class="sb-user">
      <?php if (!empty($_SESSION['avatar_url'])): ?>
        <img src="<?= e($_SESSION['avatar_url']) ?>" class="sb-ava" style="object-fit:cover;" alt="Avatar">
      <?php else: ?>
        <div class="sb-ava"><?= e(mb_strtoupper(mb_substr(usuario_nome(), 0, 2))) ?></div>
      <?php endif; ?>
      <div>
        <div class="sb-name"><?= $moldura ? $moldura . ' ' : '' ?><?= e(usuario_nome()) ?></div>
        <div class="sb-role"><?= $rotulos[$perfil] ?? '' ?></div>
      </div>
    </div>
    <div class="sb-sec">menu</div>
    <?php foreach (menu_itens($perfil) as [$url, $ic, $rotulo, $chave]): ?>
      <a class="sb-item <?= $chave === $menu_ativo ? 'on' : '' ?>" href="<?= e($url) ?>"><em><?= $ic ?></em> <?= e($rotulo) ?></a>
    <?php endforeach; ?>
    <div class="sb-sec">conta</div>
    <a class="sb-item" href="<?= BASE_URL ?>/logout.php"><em>🚪</em> Sair</a>

    <?php if ($perfil === 'aluno'): ?>
      <div class="sb-mascote" title="Gami, seu companheiro de aventuras!">🦊</div>
    <?php endif; ?>

    <?php if ($xp_box): ?>
    <div class="sb-xp"<?= $perfil === 'aluno' ? '' : ' style="margin-top:auto;"' ?>>
      <div class="sb-xp-label">Nível <?= (int)$xp_box['nivel_atual'] ?> — <?= e($xp_box['nivel_nome']) ?> · 🪙 <?= fmt_num((int)$xp_box['moedas']) ?></div>
      <?php
        $base = (int)$xp_box['xp_minimo'];
        $prox = $xp_box['xp_proximo'] !== null ? (int)$xp_box['xp_proximo'] : null;
        $pct  = $prox ? min(100, round(100 * ((int)$xp_box['xp_total'] - $base) / max(1, $prox - $base))) : 100;
      ?>
      <div class="sb-xp-bar"><div class="sb-xp-fill" style="width:<?= $pct ?>%"></div></div>
      <div class="sb-xp-num"><?= fmt_num((int)$xp_box['xp_total']) ?> / <?= $prox ? fmt_num($prox) : '—' ?> XP</div>
    </div>
    <?php endif; ?>
  </aside>

  <main class="main">
    <div class="topbar">
      <h1 class="page-title"><?= e($titulo) ?></h1>
      <details class="notif">
        <summary class="notif-bell">🔔<?php if ($nao_lidas): ?><span class="notif-count"><?= $nao_lidas ?></span><?php endif; ?></summary>
        <div class="notif-drop">
          <?php if (empty($notifs)): ?>
            <div class="notif-item"><span style="color:#aaa;">Nenhuma notificação.</span></div>
          <?php endif; ?>
          <?php foreach ($notifs as $n): ?>
            <div class="notif-item <?= $n['lida'] ? '' : 'notif-nova' ?>">
              <div class="notif-titulo"><?= e($n['titulo']) ?></div>
              <?php if ($n['mensagem']): ?><div class="notif-msg"><?= e($n['mensagem']) ?></div><?php endif; ?>
              <div class="notif-data"><?= date('d/m H:i', strtotime($n['criado_em'])) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if ($nao_lidas): ?>
            <form method="POST" action="<?= BASE_URL ?>/api/notificacoes.php" style="padding:8px;text-align:center;">
              <?= csrf_input() ?>
              <button type="submit" class="btn btn-outline" style="font-size:12px;">Marcar todas como lidas</button>
            </form>
          <?php endif; ?>
        </div>
      </details>
    </div>
    <?= flash_render() ?>
<?php
}

/** Fecha a página */
function pagina_fim(): void {
?>
  </main>
</div>
</body>
</html>
<?php
}
