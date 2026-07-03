<?php
// coordenador/professores.php — Engajamento por professor
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requer_coordenador();

$pdo = db();
$stmt = $pdo->prepare(
    "SELECT u.id, u.nome,
            COUNT(DISTINCT pt.turma_id) AS turmas,
            COUNT(DISTINCT a.id) AS atividades,
            COUNT(DISTINCT CASE WHEN a.criado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN a.id END) AS atividades_30d,
            COUNT(DISTINCT s.id) AS sessoes_aovivo,
            COUNT(DISTINCT m.id) AS metas_criadas
     FROM usuarios u
     LEFT JOIN professor_turma pt ON pt.professor_id = u.id AND pt.ativo = 1
     LEFT JOIN atividades a ON a.professor_id = u.id AND a.publicada = 1
     LEFT JOIN sessoes_ao_vivo s ON s.professor_id = u.id
     LEFT JOIN metas_turma m ON m.criador_id = u.id
     WHERE u.escola_id = ? AND u.perfil = 'professor' AND u.ativo = 1
     GROUP BY u.id, u.nome
     ORDER BY atividades DESC, u.nome"
);
$stmt->execute([escola_id()]);
$profs = $stmt->fetchAll();

pagina_inicio('Professores', 'professores');
?>
<div class="card" style="overflow-x:auto;">
  <div class="card-title">🧑‍🏫 Atividade dos professores</div>
  <?php if (empty($profs)): ?><div style="color:#aaa;font-size:13px;font-weight:700;">Nenhum professor cadastrado.</div><?php endif; ?>
  <table>
    <tr><th>Professor</th><th>Turmas</th><th>Atividades</th><th>Últimos 30 dias</th><th>Ao vivo 🎤</th><th>Metas 🎯</th></tr>
    <?php foreach ($profs as $p): ?>
    <tr>
      <td style="font-weight:800;"><?= e($p['nome']) ?></td>
      <td><?= (int)$p['turmas'] ?></td>
      <td><?= (int)$p['atividades'] ?></td>
      <td><?= (int)$p['atividades_30d'] ?></td>
      <td><?= (int)$p['sessoes_aovivo'] ?></td>
      <td><?= (int)$p['metas_criadas'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php pagina_fim(); ?>
