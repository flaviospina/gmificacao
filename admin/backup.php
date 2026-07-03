<?php
// admin/backup.php — Exportação SQL do banco (dump via PHP, sem exec)
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_admin();

$pdo = db();

if (isset($_GET['baixar'])) {
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="gamifica_backup_' . date('Ymd_His') . '.sql"');

    echo "-- Gamifica — backup gerado em " . date('d/m/Y H:i:s') . "\n";
    echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

    $tabelas = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
    foreach ($tabelas as [$tabela]) {
        $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '', $tabela) . '`')->fetch(PDO::FETCH_NUM);
        echo "DROP TABLE IF EXISTS `{$tabela}`;\n" . $create[1] . ";\n\n";

        $rows = $pdo->query('SELECT * FROM `' . str_replace('`', '', $tabela) . '`');
        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            $cols = '`' . implode('`,`', array_keys($row)) . '`';
            $vals = implode(',', array_map(
                fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v),
                array_values($row)
            ));
            echo "INSERT INTO `{$tabela}` ({$cols}) VALUES ({$vals});\n";
        }
        echo "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS = 1;\n";
    log_acesso(usuario_id(), $_SESSION['email'] ?? '', 'backup_baixado', $_SERVER['REMOTE_ADDR'] ?? '');
    exit;
}

$tamanhos = $pdo->query(
    "SELECT table_name AS tabela, table_rows AS linhas
     FROM information_schema.tables WHERE table_schema = DATABASE()
     ORDER BY table_rows DESC LIMIT 15"
)->fetchAll();

pagina_inicio('Backup', 'backup');
?>
<div class="card" style="text-align:center;">
  <div style="font-size:44px;margin-bottom:8px;">💾</div>
  <div style="font-size:16px;font-weight:800;margin-bottom:6px;">Backup completo do banco de dados</div>
  <p style="font-size:13px;color:#888;font-weight:600;margin-bottom:14px;">Gera um arquivo .sql com estrutura e dados de todas as tabelas. Guarde em local seguro.</p>
  <a href="?baixar=1" class="btn btn-primary" style="font-size:15px;padding:12px 26px;">📥 Baixar backup agora</a>
</div>

<div class="card">
  <div class="card-title">📊 Tabelas (estimativa de linhas)</div>
  <table>
    <tr><th>Tabela</th><th>Linhas</th></tr>
    <?php foreach ($tamanhos as $t): ?>
    <tr><td><?= e($t['tabela']) ?></td><td><?= fmt_num((int)$t['linhas']) ?></td></tr>
    <?php endforeach; ?>
  </table>
</div>
<?php pagina_fim(); ?>
