-- ============================================================
--  GAMIFICA — Correção das views (compatível com MySQL 5.6/5.7)
--  Importe este arquivo no phpMyAdmin quando os dashboards de
--  aluno/professor/coordenador derem erro 500 (views ausentes,
--  pois a versão anterior usava RANK() OVER, exclusivo do MySQL 8).
--  Pode ser importado quantas vezes quiser (CREATE OR REPLACE).
-- ============================================================

SET NAMES utf8mb4;

CREATE OR REPLACE VIEW vw_ranking_turma AS
SELECT
    u.id                AS aluno_id,
    u.nome              AS aluno_nome,
    u.avatar_url,
    t.id                AS turma_id,
    t.nome              AS turma_nome,
    COALESCE(ap.xp_total, 0)            AS xp_total,
    COALESCE(ap.nivel_atual, 1)         AS nivel,
    COALESCE(ap.streak_dias, 0)         AS streak,
    COALESCE(ap.missoes_concluidas, 0)  AS missoes,
    (SELECT COUNT(*) + 1
       FROM aluno_turma at3
       JOIN usuarios u2 ON u2.id = at3.usuario_id AND u2.perfil = 'aluno' AND u2.ativo = 1
       LEFT JOIN aluno_perfil ap2 ON ap2.aluno_id = u2.id
      WHERE at3.turma_id = t.id AND at3.ativo = 1
        AND COALESCE(ap2.xp_total, 0) > COALESCE(ap.xp_total, 0)
    ) AS posicao_turma
FROM usuarios u
JOIN aluno_turma at2  ON at2.usuario_id = u.id AND at2.ativo = 1
JOIN turmas t         ON t.id = at2.turma_id
LEFT JOIN aluno_perfil ap ON ap.aluno_id = u.id
WHERE u.perfil = 'aluno' AND u.ativo = 1;

CREATE OR REPLACE VIEW vw_ranking_escola AS
SELECT
    u.id            AS aluno_id,
    u.nome          AS aluno_nome,
    u.avatar_url,
    u.escola_id,
    COALESCE(ap.xp_total, 0)            AS xp_total,
    COALESCE(ap.nivel_atual, 1)         AS nivel,
    COALESCE(ap.missoes_concluidas, 0)  AS missoes,
    (SELECT COUNT(*) + 1
       FROM usuarios u2
       LEFT JOIN aluno_perfil ap2 ON ap2.aluno_id = u2.id
      WHERE u2.escola_id = u.escola_id AND u2.perfil = 'aluno' AND u2.ativo = 1
        AND COALESCE(ap2.xp_total, 0) > COALESCE(ap.xp_total, 0)
    ) AS posicao_escola
FROM usuarios u
LEFT JOIN aluno_perfil ap ON ap.aluno_id = u.id
WHERE u.perfil = 'aluno' AND u.ativo = 1;

CREATE OR REPLACE VIEW vw_evolucao_semana AS
SELECT
    u.id        AS aluno_id,
    u.nome      AS aluno_nome,
    u.avatar_url,
    u.escola_id,
    t.id        AS turma_id,
    t.nome      AS turma_nome,
    COALESCE(SUM(xe.xp), 0) AS xp_semana
FROM usuarios u
JOIN aluno_turma at2 ON at2.usuario_id = u.id AND at2.ativo = 1
JOIN turmas t        ON t.id = at2.turma_id
LEFT JOIN xp_eventos xe ON xe.aluno_id = u.id
       AND YEARWEEK(xe.criado_em, 1) = YEARWEEK(CURDATE(), 1)
WHERE u.perfil = 'aluno' AND u.ativo = 1
GROUP BY u.id, u.nome, u.avatar_url, u.escola_id, t.id, t.nome;

CREATE OR REPLACE VIEW vw_ranking_evolucao AS
SELECT
    v.aluno_id, v.aluno_nome, v.avatar_url, v.escola_id, v.turma_id, v.turma_nome, v.xp_semana,
    (SELECT COUNT(*) + 1 FROM vw_evolucao_semana v2
      WHERE v2.turma_id = v.turma_id AND v2.xp_semana > v.xp_semana
    ) AS posicao_evolucao
FROM vw_evolucao_semana v;

CREATE OR REPLACE VIEW vw_clima_turma AS
SELECT
    t.id    AS turma_id,
    t.nome  AS turma_nome,
    t.escola_id,
    COUNT(DISTINCT at2.usuario_id) AS total_alunos,
    COUNT(DISTINCT cb.aluno_id)    AS alunos_checkin,
    ROUND(AVG(cb.humor), 2)        AS humor_medio,
    ROUND(
        100.0 * COUNT(DISTINCT cb.aluno_id)
        / NULLIF(COUNT(DISTINCT at2.usuario_id), 0)
    , 1) AS pct_participacao
FROM turmas t
JOIN aluno_turma at2 ON at2.turma_id = t.id AND at2.ativo = 1
LEFT JOIN checkins_bemestar cb ON cb.aluno_id = at2.usuario_id
       AND cb.data >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
WHERE t.ativa = 1
GROUP BY t.id, t.nome, t.escola_id;

CREATE OR REPLACE VIEW vw_engajamento_turmas AS
SELECT
    t.id            AS turma_id,
    t.nome          AS turma_nome,
    u_prof.nome     AS professor_nome,
    pt.disciplina,
    COUNT(DISTINCT at2.usuario_id)  AS total_alunos,
    COUNT(DISTINCT a.id)            AS total_atividades,
    ROUND(AVG(COALESCE(ap.xp_total, 0)), 0) AS xp_medio,
    ROUND(
        100.0 * COUNT(DISTINCT CASE WHEN ap.missoes_concluidas > 0 THEN at2.usuario_id END)
        / NULLIF(COUNT(DISTINCT at2.usuario_id), 0)
    , 1) AS pct_engajamento
FROM turmas t
LEFT JOIN professor_turma pt ON pt.turma_id = t.id AND pt.ativo = 1
LEFT JOIN usuarios u_prof    ON u_prof.id = pt.professor_id
LEFT JOIN aluno_turma at2    ON at2.turma_id = t.id AND at2.ativo = 1
LEFT JOIN aluno_perfil ap    ON ap.aluno_id = at2.usuario_id
LEFT JOIN atividades a       ON a.turma_id = t.id AND a.publicada = 1
WHERE t.ativa = 1
GROUP BY t.id, t.nome, u_prof.nome, pt.disciplina;
