<?php
// ============================================================
//  config/google.php — Credenciais Google Workspace OAuth 2.0
//  Gamifica · itthrive.com.br/gamifica
// ============================================================
//
//  COMO OBTER AS CREDENCIAIS:
//  1. Acesse https://console.cloud.google.com/
//  2. Crie um projeto (Ex: "Gamifica Escola")
//  3. Menu: APIs e Serviços → Credenciais
//  4. Criar credencial → ID do cliente OAuth 2.0
//  5. Tipo: Aplicativo da Web
//  6. URIs de redirecionamento autorizados:
//       https://itthrive.com.br/gamifica/callback.php
//  7. Copie o Client ID e o Client Secret abaixo
// ============================================================

define('GOOGLE_CLIENT_ID',     'SEU_CLIENT_ID_AQUI.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'SEU_CLIENT_SECRET_AQUI');
define('GOOGLE_REDIRECT_URI',  BASE_URL . '/callback.php');

// Scopes necessários (e-mail e perfil básico)
define('GOOGLE_SCOPES', [
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/userinfo.profile',
    'openid',
]);

// Endpoint de autorização Google
define('GOOGLE_AUTH_URL',  'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USER_URL',  'https://www.googleapis.com/oauth2/v3/userinfo');

/**
 * Gera a URL de autorização do Google com state CSRF
 */
function google_auth_url(): string {
    $state = bin2hex(random_bytes(16));
    $_SESSION['google_state'] = $state;

    $params = http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => implode(' ', GOOGLE_SCOPES),
        'state'         => $state,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
        // Restringe ao domínio escolar (opcional mas recomendado)
        // 'hd'         => 'escola.edu.br',
    ]);

    return GOOGLE_AUTH_URL . '?' . $params;
}

/**
 * Troca o code pelo token de acesso
 */
function google_troca_code(string $code): ?array {
    $ch = curl_init(GOOGLE_TOKEN_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ]),
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

/**
 * Busca os dados do usuário usando o access_token
 */
function google_busca_usuario(string $access_token): ?array {
    $ch = curl_init(GOOGLE_USER_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $access_token],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
