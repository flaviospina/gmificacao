<?php
// professor/relatorios.php — Relatórios de conclusão + exportação CSV
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_professor();

$pdo     = db();
$prof_id = usuario_id();

// ── Exportar CSV de uma atividade ────────────────────────────
if (isset($_GET['csv'])) {
    $atv_id = (int)$_GET['csv'];
    $chk = $pdo->prepare('SELECT titulo FROM atividades WHERE id = ? AND professor_id = ?');
    $chk->execute([$atv_id, $prof_id]);
    $titulo = $chk->fetchColumn();
    if ($titulo) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_atividade_' . $atv_id . '.csv"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM p/ Excel
        fputcsv($out, ['Aluno', 'Melhor XP', 'Tentativas', 'Melhor %', 'Última tentativa'], ';', '"', '\\');
        $rows = $pdo->prepare(
            'SELECT u.nome, COALESCE(ax.xp_melhor, 0) AS xp,
                    COUNT(t.id) AS tentativas, COALESCE(MAX(t.percentual), 0) AS pct, MAX(t.finalizada_em) AS ultima
             FROM atividades a
             JOIN aluno_turma at2 ON at2.turma_id = a.turma_id AND at2.ativo = 1
             JOIN usuarios u ON u.id = at2.usuario_id AND u.perfil = "aluno" AND u.ativo = 1
             LEFT JOIN aluno_xp ax ON ax.atividade_id = a.id AND ax.aluno_id = u.id
             LEFT JOIN tentativas t ON t.atividade_id = a.id AND t.aluno_id = u.id AND t.concluida = 1
             WHERE a.id = ?
             GROUP BY u.id, u.nome, ax.xp_melhor
             ORDER BY xp DESC, u.nome'
        );
        $rows->execute([$atv_id]);
        foreach ($rows->fetchAll() as $r) {
            fputcsv($out, [$r['nome'], $r['xp'], $r['tentativas'], $r['pct'], $r['ultima'] ?? '—'], ';', '"', '\\');
        }
        fclose($out);
        exit;
    }
}

$atividades = $pdo->prepare(
    'SELECT a.id, a.titulo, a.tipo, a.disciplina, a.xp_recompensa, t.nome AS turma_nome,
            (SELECT COUNT(*) FROM aluno_turma at2 WHERE at2.turma_id = a.turma_id AND at2.ativo = 1) AS total_alunos,
            (SELECT COUNT(DISTINCT ax.aluno_id) FROM aluno_xp ax WHERE ax.atividade_id = a.id) AS concluiram,
            (SELECT ROUND(AVG(ax.xp_melhor), 0) FROM aluno_xp ax WHERE ax.atividade_id = a.id) AS xp_medio,
            (SELECT COUNT(*) FROM tentativas tt WHERE tt.atividade_id = a.id AND tt.concluida = 1) AS total_tentativas
     FROM atividades a
     JOIN turmas t ON t.id = a.turma_id
     WHERE a.professor_id = ? AND a.publicada = 1
     ORDER BY a.criado_em DESC'
);
$atividades->execute([$prof_id]);
$atividades = $atividades->fetchAll();

pagina_inicio('Relatórios', 'relatorios');
?>
<?php if (empty($atividades)): ?>
  <div class="card" style="text-align:center;padding:2rem;color:#aaa;">Publique atividades para ver relatórios.</div>
<?php endif; ?>

<div class="card" style="overflow-x:auto;">
  <div class="card-title">📈 Conclusão por atividade</div>
  <table>
    <tr><th>Atividade</th><th>Turma</th><th>Concluíram</th><th>XP médio</th><th>Tentativas (total)</th><th>CSV</th></tr>
    <?php foreach ($atividades as $a):
      $pct = $a['total_alunos'] ? round(100 * $a['concluiram'] / $a['total_alunos']) : 0; ?>
    <tr>
      <td style="font-weight:800;"><?= icone_tipo($a['tipo']) ?> <?= e($a['titulo']) ?><br><span style="font-size:11px;color:#aaa;"><?= e($a['disciplina']) ?></span></td>
      <td><?= e($a['turma_nome']) ?></td>
      <td><?= (int)$a['concluiram'] ?>/<?= (int)$a['total_alunos'] ?> <?= badge_engajamento($pct) ?></td>
      <td><?= $a['xp_medio'] !== null ? fmt_num((int)$a['xp_medio']) . ' / ' . (int)$a['xp_recompensa'] : '—' ?></td>
      <td><?= (int)$a['total_tentativas'] ?></td>
      <td><a href="?csv=<?= (int)$a['id'] ?>" class="btn btn-outline" style="font-size:11px;">📥 Baixar</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php pagina_fim(); ?>
