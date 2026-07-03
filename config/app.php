<?php
// ============================================================
//  config/app.php — Constantes globais da aplicação
//  Gamifica V2 · itthrive.com.br/gamifica
// ============================================================

// URL base: fixa em produção; detectada automaticamente em
// desenvolvimento (php -S, localhost) para o sistema rodar em
// qualquer ambiente sem editar este arquivo.
if (getenv('GAMIFICA_BASE_URL')) {
    define('BASE_URL', rtrim(getenv('GAMIFICA_BASE_URL'), '/'));
} elseif (PHP_SAPI === 'cli-server' || in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('BASE_URL', $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
} else {
    define('BASE_URL', 'https://itthrive.com.br/gamifica');
}

// Caminhos físicos
define('ROOT_PATH',     dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ASSETS_URL',    BASE_URL . '/assets');

// Nome da aplicação
define('APP_NAME',    'Gamifica');
define('APP_VERSION', '2.0.0');

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
