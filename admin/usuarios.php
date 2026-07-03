<?php
// admin/usuarios.php — CRUD de usuários + importação CSV + vínculos
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_admin();

$pdo = db();
$eid = escola_id();

$turmas = $pdo->prepare('SELECT id, nome FROM turmas WHERE escola_id = ? AND ativa = 1 ORDER BY nome');
$turmas->execute([$eid]);
$turmas = $turmas->fetchAll();

// ── Ações ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf_token'] ?? '')) {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        $nome   = trim($_POST['nome'] ?? '');
        $email  = strtolower(trim($_POST['email'] ?? ''));
        $perfil = in_array($_POST['perfil'] ?? '', PERFIS, true) ? $_POST['perfil'] : 'aluno';
        $senha  = $_POST['senha'] ?? '';

        if ($nome === '' || !email_valido($email) || mb_strlen($senha) < 8) {
            flash_set('Informe nome, e-mail válido e senha com 8+ caracteres.', 'erro');
        } else {
            try {
                $pdo->prepare('INSERT INTO usuarios (escola_id, nome, email, senha_hash, perfil) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$eid, $nome, $email, hash_senha($senha), $perfil]);
                $uid = (int)$pdo->lastInsertId();
                if ($perfil === 'aluno' && (int)($_POST['turma_id'] ?? 0)) {
                    $pdo->prepare('INSERT IGNORE INTO aluno_turma (usuario_id, turma_id) VALUES (?, ?)')
                        ->execute([$uid, (int)$_POST['turma_id']]);
                }
                flash_set('Usuário criado! ✅');
            } catch (PDOException $e) {
                flash_set('E-mail já cadastrado.', 'erro');
            }
        }
    }

    if ($acao === 'toggle') {
        $pdo->prepare('UPDATE usuarios SET ativo = 1 - ativo WHERE id = ? AND escola_id = ? AND id <> ?')
            ->execute([(int)$_POST['usuario_id'], $eid, usuario_id()]);
        flash_set('Status alterado.');
    }

    if ($acao === 'reset_senha') {
        $nova = bin2hex(random_bytes(4));
        $pdo->prepare('UPDATE usuarios SET senha_hash = ?, primeiro_acesso = 1 WHERE id = ? AND escola_id = ?')
            ->execute([hash_senha($nova), (int)$_POST['usuario_id'], $eid]);
        flash_set('Senha temporária: ' . $nova . ' — anote e repasse ao usuário.');
    }

    if ($acao === 'vincular_turma') {
        $pdo->prepare('INSERT IGNORE INTO aluno_turma (usuario_id, turma_id) VALUES (?, ?)')
            ->execute([(int)$_POST['usuario_id'], (int)$_POST['turma_id']]);
        flash_set('Aluno vinculado à turma.');
    }

    if ($acao === 'vincular_prof') {
        $disc = trim($_POST['disciplina'] ?? '');
        if ($disc !== '') {
            $pdo->prepare('INSERT IGNORE INTO professor_turma (professor_id, turma_id, disciplina) VALUES (?, ?, ?)')
                ->execute([(int)$_POST['usuario_id'], (int)$_POST['turma_id'], $disc]);
            flash_set('Professor vinculado à turma.');
        }
    }

    if ($acao === 'vincular_resp') {
        $pdo->prepare('INSERT IGNORE INTO responsavel_aluno (responsavel_id, aluno_id, parentesco) VALUES (?, ?, ?)')
            ->execute([(int)$_POST['usuario_id'], (int)$_POST['aluno_id'], trim($_POST['parentesco'] ?? '') ?: null]);
        flash_set('Responsável vinculado ao aluno.');
    }

    if ($acao === 'nova_turma') {
        $nome = trim($_POST['nome_turma'] ?? '');
        if ($nome !== '') {
            $pdo->prepare('INSERT INTO turmas (escola_id, nome, ano_letivo) VALUES (?, ?, YEAR(CURDATE()))')
                ->execute([$eid, $nome]);
            flash_set('Turma criada!');
        }
    }

    if ($acao === 'importar_csv' && !empty($_FILES['csv']['tmp_name'])) {
        $turma_id = (int)($_POST['turma_id'] ?? 0);
        $criados = 0; $ignorados = 0;
        $fh = fopen($_FILES['csv']['tmp_name'], 'r');
        while (($linha = fgetcsv($fh, 1000, str_contains(file_get_contents($_FILES['csv']['tmp_name'], false, null, 0, 200), ';') ? ';' : ',')) !== false) {
            $nome  = trim($linha[0] ?? '');
            $email = strtolower(trim($linha[1] ?? ''));
            $senha = trim($linha[2] ?? '') ?: bin2hex(random_bytes(4));
            if ($nome === '' || !email_valido($email)) { $ignorados++; continue; }
            try {
                $pdo->prepare('INSERT INTO usuarios (escola_id, nome, email, senha_hash, perfil) VALUES (?, ?, ?, ?, "aluno")')
                    ->execute([$eid, $nome, $email, hash_senha($senha)]);
                if ($turma_id) {
                    $pdo->prepare('INSERT IGNORE INTO aluno_turma (usuario_id, turma_id) VALUES (?, ?)')
                        ->execute([(int)$pdo->lastInsertId(), $turma_id]);
                }
                $criados++;
            } catch (PDOException $e) { $ignorados++; }
        }
        fclose($fh);
        flash_set("Importação: {$criados} aluno(s) criado(s), {$ignorados} linha(s) ignorada(s).");
    }

    redirect(BASE_URL . '/admin/usuarios.php' . (isset($_GET['perfil']) ? '?perfil=' . urlencode($_GET['perfil']) : ''));
}

$filtro = in_array($_GET['perfil'] ?? '', PERFIS, true) ? $_GET['perfil'] : '';
$sql = 'SELECT u.*, GROUP_CONCAT(DISTINCT t.nome SEPARATOR ", ") AS turmas_nomes
        FROM usuarios u
        LEFT JOIN aluno_turma at2 ON at2.usuario_id = u.id AND at2.ativo = 1
        LEFT JOIN turmas t ON t.id = at2.turma_id
        WHERE u.escola_id = ?';
$params = [$eid];
if ($filtro) { $sql .= ' AND u.perfil = ?'; $params[] = $filtro; }
$sql .= ' GROUP BY u.id ORDER BY u.perfil, u.nome LIMIT 300';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

$alunos_lista = $pdo->prepare("SELECT id, nome FROM usuarios WHERE escola_id = ? AND perfil = 'aluno' AND ativo = 1 ORDER BY nome");
$alunos_lista->execute([$eid]);
$alunos_lista = $alunos_lista->fetchAll();

pagina_inicio('Usuários', 'usuarios');
?>
<div class="two-col" style="grid-template-columns:1fr 320px;">
  <div>
    <div class="tabs">
      <a href="usuarios.php" class="tab <?= $filtro === '' ? 'on' : '' ?>" style="text-decoration:none;">Todos</a>
      <?php foreach (PERFIS as $p): ?>
        <a href="?perfil=<?= $p ?>" class="tab <?= $filtro === $p ? 'on' : '' ?>" style="text-decoration:none;"><?= ucfirst($p) ?></a>
      <?php endforeach; ?>
    </div>

    <div class="card" style="overflow-x:auto;">
      <table>
        <tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Turmas</th><th>Status</th><th>Ações</th></tr>
        <?php foreach ($usuarios as $u): ?>
        <tr>
          <td style="font-weight:800;"><?= e($u['nome']) ?></td>
          <td style="font-size:12px;"><?= e($u['email']) ?></td>
          <td><span class="badge bp"><?= ucfirst($u['perfil']) ?></span></td>
          <td style="font-size:12px;"><?= e($u['turmas_nomes'] ?? '—') ?></td>
          <td><span class="badge <?= $u['ativo'] ? 'bg' : 'br' ?>"><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
          <td>
            <form method="POST" style="display:inline-flex;gap:4px;">
              <?= csrf_input() ?>
              <input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>">
              <?php if ((int)$u['id'] !== usuario_id()): ?>
                <button type="submit" name="acao" value="toggle" class="btn btn-outline" style="font-size:10px;padding:4px 8px;"><?= $u['ativo'] ? 'Desativar' : 'Ativar' ?></button>
              <?php endif; ?>
              <button type="submit" name="acao" value="reset_senha" class="btn btn-outline" style="font-size:10px;padding:4px 8px;" onclick="return confirm('Gerar nova senha temporária?')">🔑</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-title">➕ Novo usuário</div>
      <form method="POST">
        <?= csrf_input() ?>
        <input type="hidden" name="acao" value="criar">
        <div class="fld"><label>Nome</label><input type="text" name="nome" required></div>
        <div class="fld"><label>E-mail</label><input type="email" name="email" required></div>
        <div class="fld"><label>Senha inicial (8+)</label><input type="text" name="senha" required minlength="8"></div>
        <div class="fld"><label>Perfil</label>
          <select name="perfil"><?php foreach (PERFIS as $p): ?><option value="<?= $p ?>"><?= ucfirst($p) ?></option><?php endforeach; ?></select>
        </div>
        <div class="fld"><label>Turma (se aluno)</label>
          <select name="turma_id"><option value="0">—</option><?php foreach ($turmas as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['nome']) ?></option><?php endforeach; ?></select>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Criar</button>
      </form>
    </div>

    <div class="card">
      <div class="card-title">📥 Importar alunos (CSV)</div>
      <p style="font-size:11px;color:#888;font-weight:600;margin-bottom:8px;">Formato: <code>nome;email;senha</code> (senha opcional — gerada se vazia).</p>
      <form method="POST" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <input type="hidden" name="acao" value="importar_csv">
        <div class="fld"><input type="file" name="csv" accept=".csv,text/csv" required></div>
        <div class="fld"><label>Turma</label>
          <select name="turma_id"><option value="0">Sem turma</option><?php foreach ($turmas as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['nome']) ?></option><?php endforeach; ?></select>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Importar</button>
      </form>
    </div>

    <div class="card">
      <div class="card-title">🔗 Vínculos</div>
      <form method="POST" style="margin-bottom:12px;">
        <?= csrf_input() ?>
        <input type="hidden" name="acao" value="vincular_turma">
        <label style="font-size:11px;font-weight:800;color:#aaa;text-transform:uppercase;">Aluno → turma</label>
        <div style="display:flex;gap:6px;margin-top:4px;">
          <select name="usuario_id" style="flex:1;"><?php foreach ($alunos_lista as $a): ?><option value="<?= (int)$a['id'] ?>"><?= e($a['nome']) ?></option><?php endforeach; ?></select>
          <select name="turma_id"><?php foreach ($turmas as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['nome']) ?></option><?php endforeach; ?></select>
          <button type="submit" class="btn btn-outline" style="font-size:11px;">OK</button>
        </div>
      </form>
      <form method="POST" style="margin-bottom:12px;">
        <?= csrf_input() ?>
        <input type="hidden" name="acao" value="vincular_prof">
        <label style="font-size:11px;font-weight:800;color:#aaa;text-transform:uppercase;">Professor → turma</label>
        <div style="display:flex;gap:6px;margin-top:4px;flex-wrap:wrap;">
          <select name="usuario_id" style="flex:1;min-width:110px;">
            <?php foreach ($usuarios as $u): if ($u['perfil'] !== 'professor') continue; ?><option value="<?= (int)$u['id'] ?>"><?= e($u['nome']) ?></option><?php endforeach; ?>
            <?php if ($filtro && $filtro !== 'professor'): ?><option value="0">Filtre por professor acima</option><?php endif; ?>
          </select>
          <select name="turma_id"><?php foreach ($turmas as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['nome']) ?></option><?php endforeach; ?></select>
          <input type="text" name="disciplina" placeholder="Disciplina" required style="flex:1;min-width:100px;">
          <button type="submit" class="btn btn-outline" style="font-size:11px;">OK</button>
        </div>
      </form>
      <form method="POST">
        <?= csrf_input() ?>
        <input type="hidden" name="acao" value="vincular_resp">
        <label style="font-size:11px;font-weight:800;color:#aaa;text-transform:uppercase;">Responsável → aluno</label>
        <div style="display:flex;gap:6px;margin-top:4px;flex-wrap:wrap;">
          <select name="usuario_id" style="flex:1;min-width:110px;">
            <?php foreach ($usuarios as $u): if ($u['perfil'] !== 'responsavel') continue; ?><option value="<?= (int)$u['id'] ?>"><?= e($u['nome']) ?></option><?php endforeach; ?>
            <?php if ($filtro && $filtro !== 'responsavel'): ?><option value="0">Filtre por responsável acima</option><?php endif; ?>
          </select>
          <select name="aluno_id"><?php foreach ($alunos_lista as $a): ?><option value="<?= (int)$a['id'] ?>"><?= e($a['nome']) ?></option><?php endforeach; ?></select>
          <input type="text" name="parentesco" placeholder="Parentesco" style="width:90px;">
          <button type="submit" class="btn btn-outline" style="font-size:11px;">OK</button>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="card-title">🏫 Nova turma</div>
      <form method="POST" style="display:flex;gap:6px;">
        <?= csrf_input() ?>
        <input type="hidden" name="acao" value="nova_turma">
        <input type="text" name="nome_turma" placeholder="Ex.: 7C" required style="flex:1;">
        <button type="submit" class="btn btn-primary" style="font-size:12px;">Criar</button>
      </form>
    </div>
  </div>
</div>
<?php pagina_fim(); ?>
