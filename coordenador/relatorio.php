<?php
// coordenador/relatorio.php — Exportação CSV da escola
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_coordenador();

$pdo = db();
$eid = escola_id();

$tipo = $_GET['baixar'] ?? '';
if (in_array($tipo, ['alunos', 'turmas', 'clima'], true)) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="gamifica_' . $tipo . '_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");

    if ($tipo === 'alunos') {
        fputcsv($out, ['Aluno', 'Turma', 'XP total', 'XP na semana', 'Nível', 'Streak', 'Missões', 'Posição na turma'], ';', '"', '\\');
        $stmt = $pdo->prepare(
            'SELECT r.aluno_nome, r.turma_nome, r.xp_total, COALESCE(re.xp_semana, 0) AS xp_semana,
                    r.nivel, r.streak, r.missoes, r.posicao_turma
             FROM vw_ranking_turma r
             LEFT JOIN vw_ranking_evolucao re ON re.aluno_id = r.aluno_id AND re.turma_id = r.turma_id
             JOIN turmas t ON t.id = r.turma_id
             WHERE t.escola_id = ?
             ORDER BY r.turma_nome, r.posicao_turma'
        );
        $stmt->execute([$eid]);
    } elseif ($tipo === 'turmas') {
        fputcsv($out, ['Turma', 'Professor', 'Disciplina', 'Alunos', 'Atividades', 'XP médio', '% engajamento'], ';', '"', '\\');
        $stmt = $pdo->prepare(
            'SELECT turma_nome, professor_nome, disciplina, total_alunos, total_atividades, xp_medio, pct_engajamento
             FROM vw_engajamento_turmas WHERE turma_id IN (SELECT id FROM turmas WHERE escola_id = ?)'
        );
        $stmt->execute([$eid]);
    } else {
        fputcsv($out, ['Turma', 'Alunos', 'Alunos com check-in (7d)', 'Humor médio', '% participação'], ';', '"', '\\');
        $stmt = $pdo->prepare(
            'SELECT turma_nome, total_alunos, alunos_checkin, humor_medio, pct_participacao
             FROM vw_clima_turma WHERE escola_id = ?'
        );
        $stmt->execute([$eid]);
    }
    foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row) fputcsv($out, $row, ';', '"', '\\');
    fclose($out);
    exit;
}

pagina_inicio('Exportar Relatórios', 'relatorio');
?>
<div class="loja-grid">
  <div class="loja-item">
    <div class="loja-icone">👥</div>
    <div style="font-weight:800;">Alunos</div>
    <div style="font-size:12px;color:#888;font-weight:600;margin:4px 0 10px;">XP, nível, evolução semanal, streak e posição de cada aluno.</div>
    <a href="?baixar=alunos" class="btn btn-primary">📥 Baixar CSV</a>
  </div>
  <div class="loja-item">
    <div class="loja-icone">🏫</div>
    <div style="font-weight:800;">Turmas</div>
    <div style="font-size:12px;color:#888;font-weight:600;margin:4px 0 10px;">Engajamento, atividades e XP médio por turma.</div>
    <a href="?baixar=turmas" class="btn btn-primary">📥 Baixar CSV</a>
  </div>
  <div class="loja-item">
    <div class="loja-icone">💚</div>
    <div style="font-weight:800;">Clima</div>
    <div style="font-size:12px;color:#888;font-weight:600;margin:4px 0 10px;">Humor médio e participação nos check-ins (7 dias).</div>
    <a href="?baixar=clima" class="btn btn-primary">📥 Baixar CSV</a>
  </div>
</div>
<?php pagina_fim(); ?>
