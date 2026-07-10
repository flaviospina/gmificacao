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
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
<style>
/* Estilos específicos da página de login */
body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;}
.login-box{background:#fff;border:2px solid #e5e9f2;border-bottom-width:6px;border-radius:28px;padding:2.5rem 2.4rem;width:100%;max-width:440px;position:relative;margin-top:40px;}
.login-mascote{position:absolute;top:-46px;left:50%;transform:translateX(-50%);font-size:64px;line-height:1;animation:flutuar 3s ease-in-out infinite;filter:drop-shadow(0 5px 0 rgba(0,0,0,.08));}
.logo{display:flex;align-items:center;gap:12px;justify-content:center;margin:14px 0 4px;}
.logo-ic{width:52px;height:52px;background:linear-gradient(135deg,#58cc02,#7be03a);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:26px;box-shadow:0 5px 0 #46a302;}
.logo-tx{font-size:34px;font-weight:800;letter-spacing:-1px;font-family:'Baloo 2',sans-serif;background:linear-gradient(90deg,#58cc02,#1cb0f6,#a560ff);-webkit-background-clip:text;background-clip:text;color:transparent;}
.sub{text-align:center;font-size:14px;color:#8a8aa3;margin-bottom:1.6rem;font-weight:800;}
.btn-google{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:13px;background:#fff;border:2px solid #e5e9f2;border-bottom-width:4px;border-radius:16px;font-size:14px;font-weight:900;color:#3b3b4f;cursor:pointer;transition:.15s;text-decoration:none;font-family:'Nunito',sans-serif;margin-bottom:18px;}
.btn-google:hover{border-color:#1cb0f6;background:#eef8ff;transform:translateY(-2px);}
.btn-google:active{transform:translateY(1px);border-bottom-width:2px;}
.btn-google svg{width:20px;height:20px;}
.divider{display:flex;align-items:center;gap:10px;margin-bottom:18px;}
.divider hr{flex:1;border:none;border-top:2px solid #eef1f8;}
.divider span{font-size:12px;color:#b9bed1;font-weight:800;}
.btn-entrar{width:100%;padding:15px;background:#58cc02;color:#fff;border:none;border-radius:18px;font-size:16px;font-weight:900;cursor:pointer;font-family:'Nunito',sans-serif;transition:.12s;box-shadow:0 5px 0 #46a302;letter-spacing:.3px;}
.btn-entrar:hover{filter:brightness(1.06);}
.btn-entrar:active{transform:translateY(4px);box-shadow:0 1px 0 #46a302;}
.links{text-align:center;margin-top:15px;font-size:12.5px;color:#8a8aa3;font-weight:700;}
.links a{color:#1899d6;cursor:pointer;text-decoration:none;font-weight:900;}
.alerta{padding:12px 15px;border-radius:14px;font-size:13px;font-weight:800;margin-bottom:16px;border:2px solid;}
.alerta.erro{background:#ffecec;color:#d43333;border-color:#ffc2c2;}
.alerta.info{background:#e8f9d8;color:#3f8f0a;border-color:#b8ea86;}
</style>
</head>
<body>
<div class="login-box">
  <div class="login-mascote">🦊</div>
  <div class="logo">
    <div class="logo-ic">🎮</div>
    <div class="logo-tx"><?= APP_NAME ?></div>
  </div>
  <div class="sub">Aprender virou aventura! 🚀</div>

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
