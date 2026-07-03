<?php
// ============================================================
//  callback.php — Retorno do OAuth 2.0 do Google
//  Gamifica · itthrive.com.br/gamifica
// ============================================================
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/google.php';

// ── Verifica se houve erro no OAuth ──────────────────────────
if (isset($_GET['error'])) {
    error_log('[Gamifica][OAuth] Erro: ' . $_GET['error']);
    header('Location: ' . BASE_URL . '/login.php?msg=erro_google');
    exit;
}

// ── Verifica o state CSRF ─────────────────────────────────────
$state_recebido  = $_GET['state'] ?? '';
$state_esperado  = $_SESSION['google_state'] ?? '';

if (empty($state_recebido) || !hash_equals($state_esperado, $state_recebido)) {
    error_log('[Gamifica][OAuth] State CSRF inválido.');
    header('Location: ' . BASE_URL . '/login.php?msg=erro_google');
    exit;
}
unset($_SESSION['google_state']);

// ── Troca o code pelo token ───────────────────────────────────
$code = $_GET['code'] ?? '';
if (empty($code)) {
    header('Location: ' . BASE_URL . '/login.php?msg=erro_google');
    exit;
}

$token_data = google_troca_code($code);
if (empty($token_data['access_token'])) {
    error_log('[Gamifica][OAuth] Token vazio: ' . json_encode($token_data));
    header('Location: ' . BASE_URL . '/login.php?msg=erro_google');
    exit;
}

// ── Busca dados do usuário Google ─────────────────────────────
$google_user = google_busca_usuario($token_data['access_token']);
if (empty($google_user['email'])) {
    header('Location: ' . BASE_URL . '/login.php?msg=erro_google');
    exit;
}

// ── Tenta fazer login ─────────────────────────────────────────
if (login_google($google_user)) {
    $destino = REDIRECT_PERFIL[usuario_perfil()] ?? BASE_URL;
    header('Location: ' . $destino);
} else {
    // Usuário Google autenticado mas não cadastrado no sistema
    header('Location: ' . BASE_URL . '/login.php?msg=nao_cadastrado');
}
exit;
