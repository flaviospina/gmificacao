<?php
// ============================================================
//  includes/functions.php — Funções auxiliares + MOTOR V2
//  Gamifica · itthrive.com.br/gamifica
//
//  O motor de gamificação vive aqui (sem triggers no banco):
//   · gamifica_registrar_resultado()  — tentativa → XP/moedas/mentor/metas/conquistas
//   · gamifica_bonus()                — qualquer XP extra (streak, check-in, meta...)
//   · recalcular_perfil()             — caches de aluno_perfil a partir dos ledgers
//  REGRA DE OURO: nenhuma função aqui subtrai XP. Nunca.
// ============================================================

/** Escapa string para saída HTML segura */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** Redireciona e encerra execução */
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

/** Lê uma configuração do sistema (com cache por requisição) */
function config(string $chave, string $padrao = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = db()->query('SELECT chave, valor FROM configuracoes')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $cache[$chave] ?? $padrao;
}

/** Verifica se um módulo do sistema está ativo */
function modulo_ativo(string $chave): bool {
    static $cache = null;
    if ($cache === null) {
        $cache = db()->query('SELECT chave, ativo FROM modulos')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return (bool)($cache[$chave] ?? false);
}

// ============================================================
//  MOTOR DE XP / MOEDAS / NÍVEL
// ============================================================

/**
 * Registra o resultado de uma tentativa concluída e propaga tudo:
 * melhor XP por atividade, ledger de XP, GamiCoins, bônus do mentor,
 * metas coletivas, perfil e conquistas.
 * Retorna o delta de XP efetivamente creditado (0 se não melhorou).
 */
function gamifica_registrar_resultado(int $aluno_id, int $atividade_id, int $xp_ganho, string $origem = 'missao', ?string $descricao = null): int {
    $pdo = db();

    // Melhor resultado anterior
    $stmt = $pdo->prepare('SELECT xp_melhor FROM aluno_xp WHERE aluno_id = ? AND atividade_id = ?');
    $stmt->execute([$aluno_id, $atividade_id]);
    $anterior = (int)($stmt->fetchColumn() ?: 0);

    $delta = max(0, $xp_ganho - $anterior); // nunca negativo: só o melhor conta

    // Upsert do melhor resultado (mesmo com delta 0, garante a linha p/ "missão concluída")
    $pdo->prepare(
        'INSERT INTO aluno_xp (aluno_id, atividade_id, xp_melhor) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE xp_melhor = GREATEST(xp_melhor, VALUES(xp_melhor))'
    )->execute([$aluno_id, $atividade_id, $xp_ganho]);

    if ($delta > 0) {
        // Ledger de XP (fonte de verdade da evolução semanal e das metas)
        $pdo->prepare(
            'INSERT INTO xp_eventos (aluno_id, origem, referencia_id, xp, descricao) VALUES (?, ?, ?, ?, ?)'
        )->execute([$aluno_id, $origem, $atividade_id, $delta, $descricao]);

        // GamiCoins proporcionais ao XP novo
        creditar_moedas($aluno_id, intdiv($delta, max(1, (int)config('moedas_por_xp', '10'))), $origem, $atividade_id);

        // Bônus do mentor (Mentoria entre Pares)
        aplicar_bonus_mentor($aluno_id, $delta);
    }

    recalcular_perfil($aluno_id);
    verificar_metas_turma($aluno_id);
    verificar_conquistas($aluno_id);

    return $delta;
}

/**
 * Credita XP de bônus (streak, check-in, meta, mentoria, conquista).
 * $xp deve ser positivo — a filosofia sem punição é inegociável.
 */
function gamifica_bonus(int $aluno_id, string $origem, int $xp, ?int $referencia_id = null, ?string $descricao = null, bool $recalcular = true): void {
    if ($xp <= 0) return;
    db()->prepare(
        'INSERT INTO xp_eventos (aluno_id, origem, referencia_id, xp, descricao) VALUES (?, ?, ?, ?, ?)'
    )->execute([$aluno_id, $origem, $referencia_id, $xp, $descricao]);

    creditar_moedas($aluno_id, intdiv($xp, max(1, (int)config('moedas_por_xp', '10'))), $origem, $referencia_id);

    if ($recalcular) {
        recalcular_perfil($aluno_id);
    }
}

/** Credita GamiCoins no ledger (só valores positivos) */
function creditar_moedas(int $aluno_id, int $moedas, string $origem, ?int $referencia_id = null): void {
    if ($moedas <= 0) return;
    db()->prepare(
        'INSERT INTO moeda_eventos (aluno_id, tipo, origem, referencia_id, moedas) VALUES (?, "ganho", ?, ?, ?)'
    )->execute([$aluno_id, $origem, $referencia_id, $moedas]);
}

/** Saldo de GamiCoins a partir do ledger */
function saldo_moedas(int $aluno_id): int {
    $stmt = db()->prepare(
        "SELECT COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN -moedas ELSE moedas END), 0)
         FROM moeda_eventos WHERE aluno_id = ?"
    );
    $stmt->execute([$aluno_id]);
    return max(0, (int)$stmt->fetchColumn());
}

/** Recalcula os caches de aluno_perfil a partir dos ledgers */
function recalcular_perfil(int $aluno_id): void {
    $pdo = db();

    $q = function (string $sql, array $params) use ($pdo): int {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    };
    $xp_total = $q('SELECT COALESCE(SUM(xp),0) FROM xp_eventos WHERE aluno_id = ?', [$aluno_id]);
    $missoes  = $q('SELECT COUNT(*) FROM aluno_xp WHERE aluno_id = ?', [$aluno_id]);
    $nivel    = $q('SELECT COALESCE(MAX(nivel),1) FROM niveis WHERE xp_minimo <= ?', [$xp_total]);
    $moedas   = saldo_moedas($aluno_id);

    $pdo->prepare(
        'INSERT INTO aluno_perfil (aluno_id, xp_total, moedas, nivel_atual, missoes_concluidas)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE xp_total = VALUES(xp_total), moedas = VALUES(moedas),
                                 nivel_atual = VALUES(nivel_atual), missoes_concluidas = VALUES(missoes_concluidas)'
    )->execute([$aluno_id, $xp_total, $moedas, $nivel, $missoes]);
}

// ============================================================
//  MENTORIA ENTRE PARES  [inédito]
// ============================================================

/** Quando o aprendiz melhora, o mentor ganha % da melhora como XP */
function aplicar_bonus_mentor(int $aprendiz_id, int $delta_xp): void {
    if (!modulo_ativo('mentoria') || $delta_xp <= 0) return;
    $pdo = db();
    $stmt = $pdo->prepare(
        "SELECT id, mentor_id, pct_bonus FROM mentorias WHERE aprendiz_id = ? AND status = 'ativa'"
    );
    $stmt->execute([$aprendiz_id]);
    foreach ($stmt->fetchAll() as $m) {
        $bonus = (int)floor($delta_xp * $m['pct_bonus'] / 100);
        if ($bonus <= 0) continue;
        gamifica_bonus((int)$m['mentor_id'], 'mentoria', $bonus, (int)$m['id'], 'Bônus de mentoria: seu colega evoluiu!');
        notificar((int)$m['mentor_id'], 'mentoria', 'Seu aprendiz evoluiu! 🧭', "Você ganhou +{$bonus} XP de bônus de mentoria.");
        verificar_conquistas((int)$m['mentor_id']);
    }
}

// ============================================================
//  METAS COLETIVAS DE TURMA  [inédito]
// ============================================================

/** Progresso atual de uma meta (a partir dos ledgers/check-ins) */
function progresso_meta(array $meta): int {
    $pdo = db();
    $ini = $meta['data_inicio'] . ' 00:00:00';
    $fim = $meta['data_fim']    . ' 23:59:59';
    $sql = match ($meta['tipo']) {
        'xp' => 'SELECT COALESCE(SUM(xe.xp),0) FROM xp_eventos xe
                 JOIN aluno_turma at2 ON at2.usuario_id = xe.aluno_id AND at2.turma_id = ? AND at2.ativo = 1
                 WHERE xe.criado_em BETWEEN ? AND ? AND xe.origem <> "meta"',
        'missoes' => 'SELECT COUNT(*) FROM tentativas t
                 JOIN aluno_turma at2 ON at2.usuario_id = t.aluno_id AND at2.turma_id = ? AND at2.ativo = 1
                 WHERE t.concluida = 1 AND t.finalizada_em BETWEEN ? AND ?',
        'checkins' => 'SELECT COUNT(*) FROM checkins_bemestar cb
                 JOIN aluno_turma at2 ON at2.usuario_id = cb.aluno_id AND at2.turma_id = ? AND at2.ativo = 1
                 WHERE cb.criado_em BETWEEN ? AND ?',
    };
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$meta['turma_id'], $ini, $fim]);
    return (int)$stmt->fetchColumn();
}

/** Verifica metas ativas das turmas do aluno; se atingidas, premia TODOS */
function verificar_metas_turma(int $aluno_id): void {
    if (!modulo_ativo('metas')) return;
    $pdo = db();
    $stmt = $pdo->prepare(
        "SELECT m.* FROM metas_turma m
         JOIN aluno_turma at2 ON at2.turma_id = m.turma_id AND at2.usuario_id = ? AND at2.ativo = 1
         WHERE m.status = 'ativa' AND m.data_fim >= CURDATE()"
    );
    $stmt->execute([$aluno_id]);

    foreach ($stmt->fetchAll() as $meta) {
        if (progresso_meta($meta) < (int)$meta['alvo']) continue;

        // Marca como atingida (update condicional evita premiar 2x em corrida)
        $upd = $pdo->prepare("UPDATE metas_turma SET status = 'atingida', atingida_em = NOW() WHERE id = ? AND status = 'ativa'");
        $upd->execute([$meta['id']]);
        if ($upd->rowCount() === 0) continue;

        // Recompensa para CADA aluno da turma
        $alunos = $pdo->prepare(
            "SELECT u.id FROM usuarios u
             JOIN aluno_turma at2 ON at2.usuario_id = u.id AND at2.turma_id = ? AND at2.ativo = 1
             WHERE u.perfil = 'aluno' AND u.ativo = 1"
        );
        $alunos->execute([$meta['turma_id']]);
        foreach ($alunos->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            if ((int)$meta['recompensa_xp'] > 0) {
                gamifica_bonus((int)$uid, 'meta', (int)$meta['recompensa_xp'], (int)$meta['id'], 'Meta da turma atingida: ' . $meta['titulo']);
            }
            if ((int)$meta['recompensa_moedas'] > 0) {
                creditar_moedas((int)$uid, (int)$meta['recompensa_moedas'], 'meta', (int)$meta['id']);
                recalcular_perfil((int)$uid);
            }
            notificar((int)$uid, 'meta', 'Meta da turma atingida! 🎯', $meta['titulo'] . ' — recompensa liberada para todos!');
            desbloquear_conquista((int)$uid, 'time_dos_sonhos');
        }
        notificar((int)$meta['criador_id'], 'meta', 'Sua turma atingiu a meta! 🎯', $meta['titulo']);
    }
}

// ============================================================
//  NOTIFICAÇÕES
// ============================================================

function notificar(int $usuario_id, string $tipo, string $titulo, ?string $mensagem = null, ?string $link = null): void {
    if (!modulo_ativo('notificacoes')) return;
    try {
        db()->prepare(
            'INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link) VALUES (?, ?, ?, ?, ?)'
        )->execute([$usuario_id, $tipo, $titulo, $mensagem, $link]);
    } catch (PDOException $e) {
        log_erro('Notificacao', $e->getMessage());
    }
}

/** Notifica todos os alunos ativos de uma turma */
function notificar_turma(int $turma_id, string $tipo, string $titulo, ?string $mensagem = null, ?string $link = null): void {
    $stmt = db()->prepare(
        "SELECT u.id FROM usuarios u
         JOIN aluno_turma at2 ON at2.usuario_id = u.id AND at2.turma_id = ? AND at2.ativo = 1
         WHERE u.perfil = 'aluno' AND u.ativo = 1"
    );
    $stmt->execute([$turma_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $uid) {
        notificar((int)$uid, $tipo, $titulo, $mensagem, $link);
    }
}

// ============================================================
//  CONQUISTAS
// ============================================================

/** Desbloqueia uma conquista específica (idempotente) e notifica */
function desbloquear_conquista(int $aluno_id, string $chave): void {
    $pdo = db();
    $c = $pdo->prepare('SELECT id, nome, icone FROM conquistas WHERE chave = ? AND ativa = 1');
    $c->execute([$chave]);
    $conquista = $c->fetch();
    if (!$conquista) return;

    $ins = $pdo->prepare('INSERT IGNORE INTO aluno_conquistas (aluno_id, conquista_id) VALUES (?, ?)');
    $ins->execute([$aluno_id, $conquista['id']]);
    if ($ins->rowCount() > 0) {
        notificar($aluno_id, 'conquista', 'Conquista desbloqueada! ' . $conquista['icone'], $conquista['nome']);
    }
}

/** Verifica e desbloqueia conquistas do aluno (chamada após eventos) */
function verificar_conquistas(int $aluno_id): void {
    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT ap.*,
                (SELECT COUNT(*) FROM tentativas t WHERE t.aluno_id = ? AND t.concluida = 1
                 AND DATE(t.finalizada_em) = CURDATE()) AS missoes_hoje,
                (SELECT COUNT(DISTINCT t.atividade_id) FROM tentativas t
                 JOIN atividades a ON a.id = t.atividade_id AND a.tipo = "leitura"
                 WHERE t.aluno_id = ? AND t.concluida = 1) AS total_leituras,
                (SELECT COUNT(DISTINCT t.atividade_id) FROM tentativas t
                 JOIN atividades a ON a.id = t.atividade_id AND a.tipo = "projeto"
                 WHERE t.aluno_id = ? AND t.concluida = 1) AS total_projetos,
                (SELECT COUNT(DISTINCT t.atividade_id) FROM tentativas t
                 JOIN atividades a ON a.id = t.atividade_id AND a.tipo = "grupo"
                 WHERE t.aluno_id = ? AND t.concluida = 1) AS total_grupos,
                (SELECT MAX(t.percentual) FROM tentativas t WHERE t.aluno_id = ? AND t.concluida = 1) AS max_pct,
                (SELECT COUNT(*) FROM checkins_bemestar cb WHERE cb.aluno_id = ?) AS total_checkins,
                (SELECT COUNT(*) FROM tentativas t WHERE t.aluno_id = ? AND t.origem = "aovivo") AS total_aovivo,
                (SELECT COUNT(*) FROM xp_eventos xe WHERE xe.aluno_id = ? AND xe.origem = "mentoria") AS bonus_mentor,
                (SELECT COUNT(*) FROM portfolio_itens pi WHERE pi.aluno_id = ? AND pi.status = "aprovado") AS portfolio_aprovados
         FROM aluno_perfil ap WHERE ap.aluno_id = ?'
    );
    $stmt->execute(array_fill(0, 10, $aluno_id));
    $ap = $stmt->fetch();
    if (!$ap) return;

    $rank_stmt = $pdo->prepare('SELECT MIN(posicao_turma) FROM vw_ranking_turma WHERE aluno_id = ?');
    $rank_stmt->execute([$aluno_id]);
    $posicao = (int)($rank_stmt->fetchColumn() ?: 99);

    $rank_esc = $pdo->prepare('SELECT MIN(posicao_escola) FROM vw_ranking_escola WHERE aluno_id = ?');
    $rank_esc->execute([$aluno_id]);
    $posicao_escola = (int)($rank_esc->fetchColumn() ?: 99);

    $rank_evo = $pdo->prepare('SELECT MIN(posicao_evolucao) FROM vw_ranking_evolucao WHERE aluno_id = ? AND xp_semana > 0');
    $rank_evo->execute([$aluno_id]);
    $posicao_evo = (int)($rank_evo->fetchColumn() ?: 99);

    $regras = [
        'raio_veloz'        => ($ap['missoes_hoje'] ?? 0) >= 5,
        'leitor_avido'      => ($ap['total_leituras'] ?? 0) >= 5,
        'em_chamas'         => ($ap['streak_dias'] ?? 0) >= 7,
        'colaborador'       => ($ap['total_grupos'] ?? 0) >= 1,
        'superestrela'      => ($ap['missoes_concluidas'] ?? 0) >= 50,
        'cientista'         => ($ap['total_projetos'] ?? 0) >= 5,
        'perfeito'          => ($ap['max_pct'] ?? 0) >= 100,
        'imparavel'         => ($ap['streak_dias'] ?? 0) >= 30,
        'diamante'          => ($ap['nivel_atual'] ?? 1) >= 10,
        'campeao'           => $posicao === 1,
        'rei_escola'        => $posicao_escola === 1,
        // V2
        'guia'              => ($ap['bonus_mentor'] ?? 0) >= 1,
        'foguete_semana'    => $posicao_evo === 1,
        'coracao_presente'  => ($ap['total_checkins'] ?? 0) >= 15,
        'estreante_aovivo'  => ($ap['total_aovivo'] ?? 0) >= 1,
        'artista_portfolio' => ($ap['portfolio_aprovados'] ?? 0) >= 1,
    ];

    foreach ($regras as $chave => $cumpriu) {
        if ($cumpriu) desbloquear_conquista($aluno_id, $chave);
    }
}

// ============================================================
//  CHECK-IN DE BEM-ESTAR  [inédito]
// ============================================================

/** Registra o check-in do dia. Retorna false se já fez hoje. */
function registrar_checkin(int $aluno_id, int $humor, ?string $comentario = null): bool {
    if ($humor < 1 || $humor > 5) return false;
    $ins = db()->prepare(
        'INSERT IGNORE INTO checkins_bemestar (aluno_id, data, humor, comentario) VALUES (?, CURDATE(), ?, ?)'
    );
    $ins->execute([$aluno_id, $humor, $comentario]);
    if ($ins->rowCount() === 0) return false;

    $bonus = (int)config('checkin_xp_bonus', '5');
    gamifica_bonus($aluno_id, 'checkin', $bonus, null, 'Check-in de bem-estar do dia');
    verificar_metas_turma($aluno_id);
    verificar_conquistas($aluno_id);
    return true;
}

function emoji_humor(int $humor): string {
    return match($humor) { 1 => '😢', 2 => '😕', 3 => '😐', 4 => '🙂', 5 => '😄', default => '❔' };
}

// ============================================================
//  HELPERS DE APRESENTAÇÃO
// ============================================================

/** Retorna XP/nível atual e próximo para um total de XP */
function xp_para_proximo_nivel(int $xp_total): array {
    $nivel = db()->prepare(
        'SELECT n1.nivel, n1.nome, n1.xp_minimo, n2.xp_minimo AS xp_proximo
         FROM niveis n1
         LEFT JOIN niveis n2 ON n2.nivel = n1.nivel + 1
         WHERE n1.xp_minimo <= ?
         ORDER BY n1.nivel DESC
         LIMIT 1'
    );
    $nivel->execute([$xp_total]);
    return $nivel->fetch() ?: ['nivel' => 1, 'nome' => 'Iniciante', 'xp_minimo' => 0, 'xp_proximo' => 100];
}

/** Moldura de avatar equipada (emoji da loja) ou string vazia */
function avatar_moldura(int $aluno_id): string {
    static $cache = [];
    if (!array_key_exists($aluno_id, $cache)) {
        $stmt = db()->prepare(
            'SELECT li.icone FROM aluno_avatar aa
             JOIN loja_itens li ON li.id = aa.item_id
             WHERE aa.aluno_id = ? AND aa.equipado = 1 LIMIT 1'
        );
        $stmt->execute([$aluno_id]);
        $cache[$aluno_id] = (string)($stmt->fetchColumn() ?: '');
    }
    return $cache[$aluno_id];
}

/** Formata número com separadores brasileiros */
function fmt_num(int|float $n, int $decimais = 0): string {
    return number_format($n, $decimais, ',', '.');
}

/** Retorna emoji de medalha pelo lugar */
function medalha(int $pos): string {
    return match($pos) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => (string)$pos };
}

/** Retorna a cor da barra de progresso pelo percentual */
function cor_progresso(float $pct): string {
    if ($pct >= 80) return '#16a34a';
    if ($pct >= 50) return '#f59e0b';
    if ($pct >= 30) return '#d97706';
    return '#dc2626';
}

/** Retorna badge CSS pelo percentual de engajamento */
function badge_engajamento(float $pct): string {
    if ($pct >= 70) return '<span class="badge bg">' . round($pct) . '%</span>';
    if ($pct >= 50) return '<span class="badge ba">' . round($pct) . '%</span>';
    return '<span class="badge br">' . round($pct) . '%</span>';
}

/** Sanitiza e valida e-mail */
function email_valido(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/** Gera hash de senha */
function hash_senha(string $senha): string {
    return password_hash($senha, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
}

/** Retorna ícone emoji por tipo de atividade */
function icone_tipo(string $tipo): string {
    return match($tipo) {
        'quiz'         => '❓',
        'leitura'      => '📖',
        'projeto'      => '🔬',
        'cacapalavras' => '🔤',
        'grupo'        => '🤝',
        default        => '📋',
    };
}

/** Loga erro sem expor ao usuário */
function log_erro(string $contexto, string $msg): void {
    error_log('[Gamifica][' . $contexto . '] ' . $msg);
}

// ============================================================
//  CAÇA-PALAVRAS — geração da grade
// ============================================================

/**
 * Gera (ou retorna a já gerada) grade do caça-palavras.
 * Retorna ['grade' => [[letras]], 'palavras' => [...]].
 */
function cacapalavras_grade(int $atividade_id): ?array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM cacapalavras_config WHERE atividade_id = ?');
    $stmt->execute([$atividade_id]);
    $cfg = $stmt->fetch();
    if (!$cfg) return null;

    $palavras = array_values(array_filter(array_map(
        fn($p) => mb_strtoupper(trim($p)),
        json_decode($cfg['palavras'], true) ?: []
    )));
    if (!$palavras) return null;

    if (!empty($cfg['grade_gerada'])) {
        $grade = json_decode($cfg['grade_gerada'], true);
        if ($grade) return ['grade' => $grade, 'palavras' => $palavras];
    }

    $lin = max(8, (int)$cfg['grid_linhas']);
    $col = max(8, (int)$cfg['grid_colunas']);
    $grade = array_fill(0, $lin, array_fill(0, $col, ''));
    $dirs = [[0,1],[1,0],[1,1]]; // → ↓ ↘

    foreach ($palavras as $palavra) {
        $letras = preg_split('//u', $palavra, -1, PREG_SPLIT_NO_EMPTY);
        $n = count($letras);
        for ($tent = 0; $tent < 200; $tent++) {
            [$dl, $dc] = $dirs[array_rand($dirs)];
            $maxL = $lin - ($dl ? $n : 1);
            $maxC = $col - ($dc ? $n : 1);
            if ($maxL < 0 || $maxC < 0) continue;
            $l0 = random_int(0, $maxL);
            $c0 = random_int(0, $maxC);
            $ok = true;
            for ($i = 0; $i < $n; $i++) {
                $cel = $grade[$l0 + $dl*$i][$c0 + $dc*$i];
                if ($cel !== '' && $cel !== $letras[$i]) { $ok = false; break; }
            }
            if (!$ok) continue;
            for ($i = 0; $i < $n; $i++) {
                $grade[$l0 + $dl*$i][$c0 + $dc*$i] = $letras[$i];
            }
            break;
        }
    }

    $alfabeto = ['A','B','C','D','E','F','G','H','I','J','L','M','N','O','P','R','S','T','U','V'];
    foreach ($grade as $l => $linha) {
        foreach ($linha as $c => $cel) {
            if ($cel === '') $grade[$l][$c] = $alfabeto[array_rand($alfabeto)];
        }
    }

    $pdo->prepare('UPDATE cacapalavras_config SET grade_gerada = ? WHERE atividade_id = ?')
        ->execute([json_encode($grade), $atividade_id]);

    return ['grade' => $grade, 'palavras' => $palavras];
}
