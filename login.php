<?php
// ============================================================
//  login.php — Tela de login do Gamifica
//  Suporta: Google Workspace OAuth + e-mail/senha fallback
// ============================================================
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/google.php';

// Se já estiver logado, redireciona para o módulo correto
if (esta_logado()) {
    $destino = REDIRECT_PERFIL[usuario_perfil()] ?? BASE_URL;
    header('Location: ' . $destino);
    exit;
}

$erro = '';
$msg  = '';

// Mensagens da query string
$msgs = [
    'sessao_expirada' => 'Sua sessão expirou. Faça login novamente.',
    'acesso_negado'   => 'Você não tem permissão para acessar essa área.',
    'nao_cadastrado'  => 'Sua conta Google não está cadastrada. Procure o administrador.',
    'erro_google'     => 'Erro na autenticação com o Google. Tente novamente.',
    'logout'          => 'Você saiu com sucesso.',
];
if (isset($_GET['msg']) && array_key_exists($_GET['msg'], $msgs)) {
    $msg = $msgs[$_GET['msg']];
}

// ──────────────────────────────────────────────────────────
//  POST: login por e-mail + senha
// ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido($_POST['csrf_token'] ?? '')) {
        $erro = 'Token de segurança inválido. Recarregue a página.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (empty($email) || empty($senha)) {
            $erro = 'Preencha e-mail e senha.';
        } elseif (login_email($email, $senha)) {
            $destino = REDIRECT_PERFIL[usuario_perfil()] ?? BASE_URL;
            header('Location: ' . $destino);
            exit;
        } else {
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}

// URL do botão Google
$google_url = google_auth_url();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
<style>
/* Estilos específicos da página de login */
body{background:linear-gradient(135deg,#1a1a2e,#16213e,#0f3460);min-height:100vh;display:flex;align-items:center;justify-content:center;}
.login-box{background:#fff;border-radius:24px;padding:2.75rem 2.5rem;width:100%;max-width:430px;box-shadow:0 24px 80px rgba(0,0,0,.35);}
.logo{display:flex;align-items:center;gap:12px;justify-content:center;margin-bottom:6px;}
.logo-ic{width:52px;height:52px;background:linear-gradient(135deg,#7c6ef0,#a855f7);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:26px;box-shadow:0 8px 24px #7c6ef040;}
.logo-tx{font-size:30px;font-weight:800;color:#1a1a2e;letter-spacing:-1px;}
.sub{text-align:center;font-size:13px;color:#aaa;margin-bottom:1.75rem;font-weight:600;}
.btn-google{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:13px;background:#fff;border:2px solid #e0e0e0;border-radius:12px;font-size:14px;font-weight:800;color:#1a1a2e;cursor:pointer;transition:.2s;text-decoration:none;font-family:'Nunito',sans-serif;margin-bottom:18px;}
.btn-google:hover{border-color:#7c6ef0;background:#f5f3ff;}
.btn-google svg{width:20px;height:20px;}
.divider{display:flex;align-items:center;gap:10px;margin-bottom:18px;}
.divider hr{flex:1;border:none;border-top:1px solid #f0eff5;}
.divider span{font-size:12px;color:#ccc;font-weight:700;}
.fld{margin-bottom:14px;}
.fld label{font-size:11px;font-weight:800;color:#aaa;display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.7px;}
.fld input{width:100%;padding:13px 15px;border:2px solid #e8e8f0;border-radius:12px;font-size:14px;font-family:'Nunito',sans-serif;outline:none;transition:.2s;color:#1a1a2e;font-weight:600;box-sizing:border-box;}
.fld input:focus{border-color:#7c6ef0;box-shadow:0 0 0 4px #7c6ef015;}
.btn-entrar{width:100%;padding:14px;background:linear-gradient(135deg,#7c6ef0,#a855f7);color:#fff;border:none;border-radius:14px;font-size:15px;font-weight:800;cursor:pointer;font-family:'Nunito',sans-serif;transition:.15s;box-shadow:0 6px 20px #7c6ef040;}
.btn-entrar:hover{transform:translateY(-2px);box-shadow:0 10px 28px #7c6ef050;}
.links{text-align:center;margin-top:14px;font-size:12px;color:#aaa;font-weight:600;}
.links a{color:#7c6ef0;cursor:pointer;text-decoration:none;}
.alerta{padding:11px 14px;border-radius:10px;font-size:13px;font-weight:700;margin-bottom:16px;}
.alerta.erro{background:#fef2f2;color:#dc2626;border:1.5px solid #fecaca;}
.alerta.info{background:#f0fdf4;color:#16a34a;border:1.5px solid #bbf7d0;}
</style>
</head>
<body>
<div class="login-box">
  <div class="logo">
    <div class="logo-ic">🎮</div>
    <div class="logo-tx"><?= APP_NAME ?></div>
  </div>
  <div class="sub">Plataforma de Gamificação Escolar</div>

  <?php if ($erro): ?>
    <div class="alerta erro">⚠️ <?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>
  <?php if ($msg): ?>
    <div class="alerta info">✅ <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- Botão Google -->
  <a href="<?= htmlspecialchars($google_url) ?>" class="btn-google">
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
      <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
      <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
      <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
    </svg>
    Entrar com Google Workspace
  </a>

  <div class="divider">
    <hr><span>ou acesse com e-mail e senha</span><hr>
  </div>

  <!-- Form e-mail/senha -->
  <form method="POST" action="">
    <?= csrf_input() ?>
    <div class="fld">
      <label for="email">E-mail institucional</label>
      <input type="email" id="email" name="email"
             placeholder="seunome@escola.edu.br"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
             autocomplete="email" required>
    </div>
    <div class="fld">
      <label for="senha">Senha</label>
      <input type="password" id="senha" name="senha"
             placeholder="••••••••"
             autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn-entrar">Entrar na plataforma →</button>
  </form>

  <div class="links">
    <a href="recuperar.php">Esqueci minha senha</a> &nbsp;·&nbsp;
    <a href="mailto:admin@escola.edu.br">Primeiro acesso? Fale com o admin</a>
  </div>
</div>
</body>
</html>
