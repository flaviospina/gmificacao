<?php
// ============================================================
//  includes/auth.php — Autenticação, sessão e controle de acesso
//  Gamifica V2 · itthrive.com.br/gamifica
// ============================================================

require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/db.php';

// Inicia sessão segura (uma única vez)
function session_iniciar(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $url = parse_url(BASE_URL);
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT,
            'path'     => ($url['path'] ?? '') !== '' ? $url['path'] : '/',
            'secure'   => ($url['scheme'] ?? 'https') === 'https',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// ──────────────────────────────────────────────────────────
//  Login por e-mail + senha
// ──────────────────────────────────────────────────────────
function login_email(string $email, string $senha): bool {
    $pdo  = db();
    $stmt = $pdo->prepare(
        'SELECT id, nome, email, senha_hash, perfil, ativo, escola_id, avatar_url
         FROM usuarios WHERE email = ? LIMIT 1'
    );
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user || !$user['ativo']) {
        log_acesso(null, $email, 'login_fail_usuario', $_SERVER['REMOTE_ADDR'] ?? '');
        return false;
    }

    if (empty($user['senha_hash']) || !password_verify($senha, $user['senha_hash'])) {
        log_acesso($user['id'], $email, 'login_fail_senha', $_SERVER['REMOTE_ADDR'] ?? '');
        return false;
    }

    criar_sessao($user);
    atualizar_ultimo_acesso($user['id'], $user['perfil']);
    log_acesso($user['id'], $email, 'login_ok', $_SERVER['REMOTE_ADDR'] ?? '');
    return true;
}

// ──────────────────────────────────────────────────────────
//  Login via Google OAuth (chamado pelo callback.php)
// ──────────────────────────────────────────────────────────
function login_google(array $google_user): bool {
    $email     = strtolower(trim($google_user['email'] ?? ''));
    $google_id = $google_user['sub'] ?? '';
    $avatar    = $google_user['picture'] ?? null;

    if (empty($email) || empty($google_id)) return false;

    $pdo  = db();
    $stmt = $pdo->prepare(
        'SELECT id, nome, email, perfil, ativo, escola_id, avatar_url, google_id
         FROM usuarios
         WHERE google_id = ? OR email = ?
         LIMIT 1'
    );
    $stmt->execute([$google_id, $email]);
    $user = $stmt->fetch();

    if (!$user || !$user['ativo']) {
        log_acesso(null, $email, 'login_google_nao_cadastrado', $_SERVER['REMOTE_ADDR'] ?? '');
        return false;
    }

    if (empty($user['google_id'])) {
        $pdo->prepare('UPDATE usuarios SET google_id = ?, avatar_url = ? WHERE id = ?')
            ->execute([$google_id, $avatar, $user['id']]);
    }

    $user['avatar_url'] = $avatar ?? $user['avatar_url'];
    criar_sessao($user);
    atualizar_ultimo_acesso($user['id'], $user['perfil']);
    log_acesso($user['id'], $email, 'login_google_ok', $_SERVER['REMOTE_ADDR'] ?? '');
    return true;
}

// ──────────────────────────────────────────────────────────
//  Cria a sessão após login bem-sucedido
// ──────────────────────────────────────────────────────────
function criar_sessao(array $user): void {
    session_regenerate_id(true); // previne session fixation
    $_SESSION['usuario_id']  = $user['id'];
    $_SESSION['nome']        = $user['nome'];
    $_SESSION['email']       = $user['email'];
    $_SESSION['perfil']      = $user['perfil'];
    $_SESSION['escola_id']   = $user['escola_id'];
    $_SESSION['avatar_url']  = $user['avatar_url'] ?? null;
    $_SESSION['logado_em']   = time();
}

// ──────────────────────────────────────────────────────────
//  Logout
// ──────────────────────────────────────────────────────────
function logout(): void {
    session_iniciar();
    if (!empty($_SESSION['usuario_id'])) {
        log_acesso($_SESSION['usuario_id'], $_SESSION['email'] ?? '', 'logout', $_SERVER['REMOTE_ADDR'] ?? '');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }
    session_destroy();
}

// ──────────────────────────────────────────────────────────
//  Verificação de acesso — use no início de cada página
// ──────────────────────────────────────────────────────────
function requer_perfil(array $perfis_permitidos): void {
    session_iniciar();

    if (
        empty($_SESSION['usuario_id']) ||
        (time() - ($_SESSION['logado_em'] ?? 0)) > SESSION_TIMEOUT
    ) {
        logout();
        header('Location: ' . BASE_URL . '/login.php?msg=sessao_expirada');
        exit;
    }

    if (!in_array($_SESSION['perfil'], $perfis_permitidos, true)) {
        header('Location: ' . BASE_URL . '/login.php?msg=acesso_negado');
        exit;
    }
}

// Atalhos por perfil
function requer_aluno():       void { requer_perfil(['aluno']); }
function requer_professor():   void { requer_perfil(['professor']); }
function requer_coordenador(): void { requer_perfil(['coordenador', 'admin']); }
function requer_admin():       void { requer_perfil(['admin']); }
function requer_responsavel(): void { requer_perfil(['responsavel']); }

// ──────────────────────────────────────────────────────────
//  Helpers de sessão
// ──────────────────────────────────────────────────────────
function usuario_id():     int    { return (int)($_SESSION['usuario_id'] ?? 0); }
function usuario_nome():   string { return $_SESSION['nome'] ?? ''; }
function usuario_perfil(): string { return $_SESSION['perfil'] ?? ''; }
function escola_id():      int    { return (int)($_SESSION['escola_id'] ?? 0); }
function esta_logado():    bool   { return !empty($_SESSION['usuario_id']); }

// ──────────────────────────────────────────────────────────
//  Último acesso + streak (V2: calculado em PHP, com bônus)
// ──────────────────────────────────────────────────────────
function atualizar_ultimo_acesso(int $id, string $perfil = 'aluno'): void {
    if ($perfil !== 'aluno') return;
    $pdo  = db();
    $hoje = date('Y-m-d');

    $stmt = $pdo->prepare('SELECT ultimo_acesso, streak_dias FROM aluno_perfil WHERE aluno_id = ?');
    $stmt->execute([$id]);
    $ap = $stmt->fetch();

    if (!$ap) {
        $pdo->prepare('INSERT INTO aluno_perfil (aluno_id, ultimo_acesso, streak_dias) VALUES (?, ?, 1)')
            ->execute([$id, $hoje]);
        return;
    }

    if ($ap['ultimo_acesso'] === $hoje) return; // já contou hoje

    $ontem  = date('Y-m-d', strtotime('-1 day'));
    $streak = ($ap['ultimo_acesso'] === $ontem) ? ((int)$ap['streak_dias'] + 1) : 1;

    $pdo->prepare('UPDATE aluno_perfil SET ultimo_acesso = ?, streak_dias = ? WHERE aluno_id = ?')
        ->execute([$hoje, $streak, $id]);

    // Bônus de sequência (nunca é retirado — só somado)
    if ($streak > 1) {
        $bonus = (int)config('streak_xp_bonus', '10');
        if ($bonus > 0) {
            gamifica_bonus($id, 'streak', $bonus, null, "Sequência de {$streak} dias de acesso");
        }
    }
    verificar_conquistas($id);
}

// ──────────────────────────────────────────────────────────
//  Log de acesso
// ──────────────────────────────────────────────────────────
function log_acesso(?int $usuario_id, string $email, string $acao, string $ip): void {
    try {
        db()->prepare(
            'INSERT INTO logs_acesso (usuario_id, email, acao, ip, user_agent)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $usuario_id,
            $email,
            $acao,
            $ip,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
        ]);
    } catch (PDOException $e) {
        error_log('[Gamifica][Log] ' . $e->getMessage());
    }
}

// ──────────────────────────────────────────────────────────
//  Token CSRF
// ──────────────────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_valido(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function csrf_input(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}
