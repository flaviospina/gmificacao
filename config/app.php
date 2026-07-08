<?php
// ============================================================
//  config/app.php — Constantes globais da aplicação
//  Gamifica V2 · itthrive.com.br/gamifica
// ============================================================

// URL base — detectada automaticamente a partir da requisição real
// (esquema + host + subpasta), para funcionar com www e sem www,
// http ou https, em qualquer domínio/subpasta, sem editar nada.
// A env GAMIFICA_BASE_URL tem prioridade; o valor fixo abaixo é
// usado apenas fora de uma requisição web (ex.: scripts CLI).
if (getenv('GAMIFICA_BASE_URL')) {
    define('BASE_URL', rtrim(getenv('GAMIFICA_BASE_URL'), '/'));
} elseif (!empty($_SERVER['HTTP_HOST'])) {
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
           || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $https ? 'https' : 'http';

    // Subpasta da aplicação (ex.: /gamifica) derivada da posição real
    // dos arquivos em relação ao document root do servidor.
    $raiz    = str_replace('\\', '/', dirname(__DIR__));
    $docroot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    if ($docroot !== '' && str_starts_with($raiz, $docroot)) {
        $subpasta = rtrim(substr($raiz, strlen($docroot)), '/');
    } else {
        // Fallback: deduz a subpasta pelo caminho do script atual
        $script   = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
        $subpasta = preg_replace('#/(aluno|professor|coordenador|admin|responsavel|api)?/?[^/]*\.php$#', '', $script);
        $subpasta = rtrim($subpasta, '/');
    }

    define('BASE_URL', $scheme . '://' . $_SERVER['HTTP_HOST'] . $subpasta);
} else {
    define('BASE_URL', 'https://itthrive.com.br/gamifica');
}

// Caminhos físicos
define('ROOT_PATH',     dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ASSETS_URL',    BASE_URL . '/assets');

// Nome da aplicação
define('APP_NAME',    'Gamifica');
define('APP_VERSION', '2.0.1');

// Configurações de sessão
define('SESSION_TIMEOUT', 60 * 60 * 8); // 8 horas
define('SESSION_NAME',    'gamifica_sess');

// Perfis válidos (enum espelhando o banco) — V2: + responsavel
define('PERFIS', ['aluno', 'professor', 'coordenador', 'admin', 'responsavel']);

// Páginas de entrada por perfil
define('REDIRECT_PERFIL', [
    'aluno'       => BASE_URL . '/aluno/',
    'professor'   => BASE_URL . '/professor/',
    'coordenador' => BASE_URL . '/coordenador/',
    'admin'       => BASE_URL . '/admin/',
    'responsavel' => BASE_URL . '/responsavel/',
]);

// Segurança
define('BCRYPT_COST', 12);

// Fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Exibir erros apenas em desenvolvimento
define('APP_DEBUG', (bool) getenv('GAMIFICA_DEBUG'));
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
