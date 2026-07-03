<?php
// ============================================================
//  config/db.php — Conexão PDO com MySQL
//  Gamifica V2 · itthrive.com.br/gamifica
// ============================================================

// ⚠️  PREENCHA com os dados do seu banco na Hostgator (cPanel).
//     Variáveis de ambiente GAMIFICA_DB_* têm prioridade (útil
//     para desenvolvimento/testes sem editar este arquivo).
define('DB_HOST',    getenv('GAMIFICA_DB_HOST') ?: 'localhost');
define('DB_NAME',    getenv('GAMIFICA_DB_NAME') ?: 'seuusuario_gamifica');
define('DB_USER',    getenv('GAMIFICA_DB_USER') ?: 'seuusuario_dbuser');
define('DB_PASS',    getenv('GAMIFICA_DB_PASS') !== false ? getenv('GAMIFICA_DB_PASS') : 'SuaSenhaAqui');
define('DB_CHARSET', 'utf8mb4');

// Singleton — retorna sempre a mesma conexão durante a requisição
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            // Alinha NOW()/CURDATE() do MySQL ao fuso do PHP (America/Sao_Paulo)
            $pdo->exec("SET time_zone = '" . date('P') . "'");
        } catch (PDOException $e) {
            error_log('[Gamifica][DB] ' . $e->getMessage());
            http_response_code(500);
            die('<h2>Erro de conexão com o banco de dados. Tente novamente em instantes.</h2>');
        }
    }
    return $pdo;
}
