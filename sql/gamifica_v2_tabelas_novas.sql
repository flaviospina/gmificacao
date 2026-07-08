-- ============================================================
--  GAMIFICA — Tabelas e views novas do V2 (companheiro da migração)
--  Gerado a partir do gamifica_v2.sql — rode junto com migracao_v1_v2.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS responsavel_aluno (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    responsavel_id  INT UNSIGNED NOT NULL,
    aluno_id        INT UNSIGNED NOT NULL,
    parentesco      VARCHAR(40) NULL COMMENT 'mãe, pai, avó...',
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_resp_aluno (responsavel_id, aluno_id),
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id)       REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS projeto_entregas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etapa_id        INT UNSIGNED NOT NULL,
    aluno_id        INT UNSIGNED NOT NULL,
    texto           TEXT NULL,
    status          ENUM('enviada','aprovada','revisar') DEFAULT 'enviada',
    comentario_prof TEXT NULL,
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_etapa_aluno (etapa_id, aluno_id),
    FOREIGN KEY (etapa_id) REFERENCES projeto_etapas(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS xp_eventos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id        INT UNSIGNED NOT NULL,
    origem          ENUM('missao','aovivo','streak','checkin','mentoria','meta','conquista','ajuste') NOT NULL,
    referencia_id   INT UNSIGNED NULL COMMENT 'id da atividade/meta/mentoria conforme origem',
    xp              SMALLINT UNSIGNED NOT NULL COMMENT 'UNSIGNED: o banco rejeita XP negativo',
    descricao       VARCHAR(200) NULL,
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_xpe_aluno_data (aluno_id, criado_em),
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS moeda_eventos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id        INT UNSIGNED NOT NULL,
    tipo            ENUM('ganho','gasto','estorno') NOT NULL,
    origem          VARCHAR(40) NOT NULL COMMENT 'missao, meta, loja, conquista...',
    referencia_id   INT UNSIGNED NULL,
    moedas          SMALLINT UNSIGNED NOT NULL,
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_me_aluno (aluno_id, criado_em),
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS loja_itens (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    escola_id       INT UNSIGNED NOT NULL,
    nome            VARCHAR(100) NOT NULL,
    descricao       VARCHAR(255) NULL,
    icone           VARCHAR(10) NOT NULL DEFAULT '🎁',
    categoria       ENUM('avatar','privilegio','material') NOT NULL,
    preco_moedas    SMALLINT UNSIGNED NOT NULL,
    requer_aprovacao TINYINT(1) DEFAULT 1 COMMENT 'privilegios/materiais: professor aprova',
    ativo           TINYINT(1) DEFAULT 1,
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (escola_id) REFERENCES escolas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS loja_resgates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id         INT UNSIGNED NOT NULL,
    aluno_id        INT UNSIGNED NOT NULL,
    moedas_gastas   SMALLINT UNSIGNED NOT NULL,
    status          ENUM('pendente','aprovado','entregue','cancelado') DEFAULT 'pendente',
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_resgate_status (status),
    FOREIGN KEY (item_id)  REFERENCES loja_itens(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aluno_avatar (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id    INT UNSIGNED NOT NULL,
    item_id     INT UNSIGNED NOT NULL,
    equipado    TINYINT(1) DEFAULT 0,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_aluno_item (aluno_id, item_id),
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id)   ON DELETE CASCADE,
    FOREIGN KEY (item_id)  REFERENCES loja_itens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sessoes_ao_vivo (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    atividade_id        INT UNSIGNED NOT NULL COMMENT 'atividade tipo quiz',
    professor_id        INT UNSIGNED NOT NULL,
    pin                 CHAR(6) NOT NULL,
    status              ENUM('lobby','questao','revisao','encerrada') DEFAULT 'lobby',
    questao_atual       TINYINT UNSIGNED DEFAULT 0 COMMENT 'ordem da questão em exibição',
    questao_iniciada_em DATETIME NULL,
    tempo_questao       TINYINT UNSIGNED DEFAULT 30,
    criado_em           DATETIME DEFAULT CURRENT_TIMESTAMP,
    encerrada_em        DATETIME NULL,
    INDEX idx_sessao_pin (pin, status),
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sessao_participantes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sessao_id   INT UNSIGNED NOT NULL,
    aluno_id    INT UNSIGNED NOT NULL,
    pontos      INT UNSIGNED DEFAULT 0,
    entrou_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sessao_aluno (sessao_id, aluno_id),
    FOREIGN KEY (sessao_id) REFERENCES sessoes_ao_vivo(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id)  REFERENCES usuarios(id)        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sessao_respostas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sessao_id       INT UNSIGNED NOT NULL,
    questao_id      INT UNSIGNED NOT NULL,
    aluno_id        INT UNSIGNED NOT NULL,
    alternativa_id  INT UNSIGNED NOT NULL,
    correta         TINYINT(1) DEFAULT 0,
    tempo_ms        INT UNSIGNED DEFAULT 0,
    pontos          SMALLINT UNSIGNED DEFAULT 0,
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_resp (sessao_id, questao_id, aluno_id),
    FOREIGN KEY (sessao_id)      REFERENCES sessoes_ao_vivo(id)  ON DELETE CASCADE,
    FOREIGN KEY (questao_id)     REFERENCES quiz_questoes(id)    ON DELETE CASCADE,
    FOREIGN KEY (aluno_id)       REFERENCES usuarios(id)         ON DELETE CASCADE,
    FOREIGN KEY (alternativa_id) REFERENCES quiz_alternativas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS metas_turma (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    turma_id          INT UNSIGNED NOT NULL,
    criador_id        INT UNSIGNED NOT NULL,
    titulo            VARCHAR(150) NOT NULL,
    descricao         VARCHAR(255) NULL,
    tipo              ENUM('xp','missoes','checkins') NOT NULL DEFAULT 'xp',
    alvo              INT UNSIGNED NOT NULL COMMENT 'XP somado / nº missões / nº check-ins da turma',
    recompensa_xp     SMALLINT UNSIGNED DEFAULT 0 COMMENT 'XP para CADA aluno ao atingir',
    recompensa_moedas SMALLINT UNSIGNED DEFAULT 0,
    data_inicio       DATE NOT NULL,
    data_fim          DATE NOT NULL,
    status            ENUM('ativa','atingida','expirada') DEFAULT 'ativa',
    atingida_em       DATETIME NULL,
    criado_em         DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (turma_id)   REFERENCES turmas(id)   ON DELETE CASCADE,
    FOREIGN KEY (criador_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mentorias (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    turma_id     INT UNSIGNED NOT NULL,
    mentor_id    INT UNSIGNED NOT NULL,
    aprendiz_id  INT UNSIGNED NOT NULL,
    disciplina   VARCHAR(80) NULL,
    criador_id   INT UNSIGNED NOT NULL COMMENT 'professor que formou a dupla',
    pct_bonus    TINYINT UNSIGNED DEFAULT 20 COMMENT '% da melhora do aprendiz que vira XP do mentor',
    status       ENUM('ativa','encerrada') DEFAULT 'ativa',
    criado_em    DATETIME DEFAULT CURRENT_TIMESTAMP,
    encerrada_em DATETIME NULL,
    INDEX idx_mentoria_aprendiz (aprendiz_id, status),
    FOREIGN KEY (turma_id)    REFERENCES turmas(id)   ON DELETE CASCADE,
    FOREIGN KEY (mentor_id)   REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aprendiz_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (criador_id)  REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS checkins_bemestar (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id    INT UNSIGNED NOT NULL,
    data        DATE NOT NULL,
    humor       TINYINT UNSIGNED NOT NULL COMMENT '1=😢 2=😕 3=😐 4=🙂 5=😄',
    comentario  VARCHAR(200) NULL,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_checkin_dia (aluno_id, data),
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS portfolio_itens (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id        INT UNSIGNED NOT NULL,
    atividade_id    INT UNSIGNED NULL,
    titulo          VARCHAR(150) NOT NULL,
    descricao       TEXT NULL,
    status          ENUM('enviado','aprovado','revisar') DEFAULT 'enviado' COMMENT 'sem reprovação: volta para melhorar',
    comentario_prof VARCHAR(255) NULL,
    visivel_familia TINYINT(1) DEFAULT 1,
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id)     REFERENCES usuarios(id)   ON DELETE CASCADE,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS feed_familia (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    escola_id   INT UNSIGNED NOT NULL,
    turma_id    INT UNSIGNED NULL COMMENT 'NULL = escola inteira',
    aluno_id    INT UNSIGNED NULL COMMENT 'NULL = post para a turma',
    autor_id    INT UNSIGNED NOT NULL,
    tipo        ENUM('elogio','aviso','conquista') NOT NULL DEFAULT 'elogio',
    titulo      VARCHAR(150) NOT NULL,
    mensagem    TEXT NULL,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feed_turma (turma_id, criado_em),
    FOREIGN KEY (escola_id) REFERENCES escolas(id)  ON DELETE CASCADE,
    FOREIGN KEY (turma_id)  REFERENCES turmas(id)   ON DELETE CASCADE,
    FOREIGN KEY (aluno_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (autor_id)  REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mensagens (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    de_id       INT UNSIGNED NOT NULL,
    para_id     INT UNSIGNED NOT NULL,
    aluno_id    INT UNSIGNED NULL COMMENT 'aluno contexto da conversa',
    texto       TEXT NOT NULL,
    lida        TINYINT(1) DEFAULT 0,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_msg_para (para_id, lida),
    INDEX idx_msg_conversa (de_id, para_id, criado_em),
    FOREIGN KEY (de_id)    REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (para_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notificacoes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NOT NULL,
    tipo        VARCHAR(40) NOT NULL COMMENT 'atividade, conquista, meta, mensagem, resgate...',
    titulo      VARCHAR(150) NOT NULL,
    mensagem    VARCHAR(255) NULL,
    link        VARCHAR(255) NULL,
    lida        TINYINT(1) DEFAULT 0,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_usuario (usuario_id, lida, criado_em),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS integracoes (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    escola_id     INT UNSIGNED NOT NULL,
    servico       ENUM('google_classroom','microsoft_teams') NOT NULL,
    client_id     VARCHAR(255) NULL,
    client_secret VARCHAR(255) NULL,
    ativo         TINYINT(1) DEFAULT 0,
    criado_em     DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_escola_servico (escola_id, servico),
    FOREIGN KEY (escola_id) REFERENCES escolas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS turma_vinculos (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    turma_id     INT UNSIGNED NOT NULL,
    servico      ENUM('google_classroom','microsoft_teams') NOT NULL,
    externo_id   VARCHAR(120) NOT NULL COMMENT 'id do curso no serviço externo',
    externo_nome VARCHAR(150) NULL,
    ultima_sync  DATETIME NULL,
    UNIQUE KEY uq_turma_servico (turma_id, servico),
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sessoes (
    id            VARCHAR(128) PRIMARY KEY,
    dados         MEDIUMTEXT,
    usuario_id    INT UNSIGNED NULL,
    ip            VARCHAR(45) NULL,
    expira_em     DATETIME NOT NULL,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sessao_expira (expira_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Views (recriadas/corrigidas — compatíveis com MySQL 5.6/5.7)
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

SET FOREIGN_KEY_CHECKS = 1;
