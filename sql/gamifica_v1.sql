-- ============================================================
--  GAMIFICA — Script completo do banco de dados
--  Versão 1.0  |  MySQL 8.0  |  itthrive.com.br/gamifica
--  Criado em: 2026
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. ESCOLA
-- ============================================================
CREATE TABLE IF NOT EXISTS escolas (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(150) NOT NULL,
    dominio     VARCHAR(100) NOT NULL COMMENT 'Ex: escola.edu.br (Google Workspace)',
    ativo       TINYINT(1) DEFAULT 1,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO escolas (nome, dominio) VALUES ('Escola Demo', 'escola.edu.br');

-- ============================================================
-- 2. USUÁRIOS
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    escola_id       INT UNSIGNED NOT NULL,
    nome            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    senha_hash      VARCHAR(255) NULL COMMENT 'NULL quando usa só Google',
    perfil          ENUM('aluno','professor','coordenador','admin') NOT NULL DEFAULT 'aluno',
    google_id       VARCHAR(100) NULL UNIQUE COMMENT 'sub do token Google',
    avatar_url      VARCHAR(255) NULL,
    ativo           TINYINT(1) DEFAULT 1,
    primeiro_acesso TINYINT(1) DEFAULT 1 COMMENT '1 = deve trocar senha',
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (escola_id) REFERENCES escolas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin padrão (senha: Gamifica@2026 — altere após o primeiro acesso)
INSERT INTO usuarios (escola_id, nome, email, senha_hash, perfil)
VALUES (1, 'Administrador', 'admin@escola.edu.br',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ============================================================
-- 3. TURMAS
-- ============================================================
CREATE TABLE IF NOT EXISTS turmas (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    escola_id   INT UNSIGNED NOT NULL,
    nome        VARCHAR(50) NOT NULL COMMENT 'Ex: 9A, 8B',
    ano_letivo  YEAR NOT NULL DEFAULT 2026,
    ativa       TINYINT(1) DEFAULT 1,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (escola_id) REFERENCES escolas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. ALUNO ↔ TURMA (um aluno pode trocar de turma ao longo do ano)
-- ============================================================
CREATE TABLE IF NOT EXISTS aluno_turma (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NOT NULL,
    turma_id    INT UNSIGNED NOT NULL,
    ativo       TINYINT(1) DEFAULT 1,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_aluno_turma (usuario_id, turma_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id)   REFERENCES turmas(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. PROFESSOR ↔ TURMA (professor pode ter várias turmas)
-- ============================================================
CREATE TABLE IF NOT EXISTS professor_turma (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    professor_id    INT UNSIGNED NOT NULL,
    turma_id        INT UNSIGNED NOT NULL,
    disciplina      VARCHAR(80) NOT NULL,
    ativo           TINYINT(1) DEFAULT 1,
    UNIQUE KEY uq_prof_turma_disc (professor_id, turma_id, disciplina),
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id)     REFERENCES turmas(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. MÓDULOS DO SISTEMA (ativados/desativados pelo admin)
-- ============================================================
CREATE TABLE IF NOT EXISTS modulos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave       VARCHAR(60) NOT NULL UNIQUE COMMENT 'Ex: missoes, xp, conquistas',
    nome        VARCHAR(100) NOT NULL,
    descricao   TEXT,
    ativo       TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO modulos (chave, nome, descricao, ativo) VALUES
('missoes',      'Missões e Atividades',   'Quizzes, projetos e leituras gamificadas', 1),
('xp_niveis',   'Sistema de XP e Níveis', 'Pontuação progressiva sem punição', 1),
('conquistas',  'Conquistas e Badges',    'Medalhas por desempenho e dedicação', 1),
('ranking',     'Ranking e Leaderboard',  'Classificação por turma e escola', 1),
('relatorios',  'Relatórios Avançados',   'Exportação e análises para coordenação', 0),
('colaborativo','Modo Colaborativo',      'Atividades em grupo com XP compartilhado', 1),
('notificacoes','Notificações',           'Alertas automáticos para alunos e professores', 0);

-- ============================================================
-- 7. CONFIGURAÇÕES DO SISTEMA
-- ============================================================
CREATE TABLE IF NOT EXISTS configuracoes (
    chave   VARCHAR(80) PRIMARY KEY,
    valor   VARCHAR(255) NOT NULL,
    descricao VARCHAR(200)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO configuracoes (chave, valor, descricao) VALUES
('xp_maximo_atividade',  '500',  'XP máximo que uma atividade pode valer'),
('nivel_maximo',         '20',   'Nível máximo do sistema'),
('tentativas_padrao',    '0',    '0 = ilimitadas'),
('streak_xp_bonus',      '10',   'XP bônus por dia de acesso consecutivo'),
('google_client_id',     '',     'Client ID do Google OAuth 2.0'),
('google_client_secret', '',     'Client Secret do Google OAuth 2.0'),
('google_redirect_uri',  'https://itthrive.com.br/gamifica/callback.php', 'URI de retorno OAuth');

-- ============================================================
-- 8. NÍVEIS DE XP
-- ============================================================
CREATE TABLE IF NOT EXISTS niveis (
    nivel       INT UNSIGNED PRIMARY KEY,
    nome        VARCHAR(60) NOT NULL,
    xp_minimo   INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO niveis (nivel, nome, xp_minimo) VALUES
(1,  'Iniciante',   0),
(2,  'Curioso',     100),
(3,  'Aprendiz',    300),
(4,  'Explorador',  600),
(5,  'Aventureiro', 1000),
(6,  'Desafiante',  1500),
(7,  'Explorador+', 2100),
(8,  'Mestre',      2800),
(9,  'Guardião',    3600),
(10, 'Campeão',     4500),
(11, 'Lendário I',  5500),
(12, 'Lendário II', 6600),
(13, 'Épico I',     7800),
(14, 'Épico II',    9100),
(15, 'Mítico I',    10500),
(16, 'Mítico II',   12000),
(17, 'Supremo I',   13600),
(18, 'Supremo II',  15300),
(19, 'Imortal',     17100),
(20, 'Transcendente',19000);

-- ============================================================
-- 9. ATIVIDADES (criadas pelo professor)
-- ============================================================
CREATE TABLE IF NOT EXISTS atividades (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    professor_id    INT UNSIGNED NOT NULL,
    turma_id        INT UNSIGNED NOT NULL,
    titulo          VARCHAR(200) NOT NULL,
    descricao       TEXT,
    tipo            ENUM('quiz','leitura','projeto','cacapalavras','grupo') NOT NULL,
    disciplina      VARCHAR(80) NOT NULL,
    xp_recompensa   SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    max_tentativas  TINYINT UNSIGNED DEFAULT 0 COMMENT '0 = ilimitadas',
    total_etapas    TINYINT UNSIGNED DEFAULT 1 COMMENT 'Para projetos por etapas',
    data_inicio     DATETIME NULL,
    data_fim        DATETIME NULL,
    publicada       TINYINT(1) DEFAULT 0,
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id),
    FOREIGN KEY (turma_id)     REFERENCES turmas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 10. CONTEÚDO DAS ATIVIDADES
-- ============================================================

-- 10a. Quiz: Questões
CREATE TABLE IF NOT EXISTS quiz_questoes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    atividade_id    INT UNSIGNED NOT NULL,
    ordem           TINYINT UNSIGNED DEFAULT 1,
    enunciado       TEXT NOT NULL,
    imagem_url      VARCHAR(255) NULL,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10b. Quiz: Alternativas
CREATE TABLE IF NOT EXISTS quiz_alternativas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    questao_id      INT UNSIGNED NOT NULL,
    texto           VARCHAR(500) NOT NULL,
    correta         TINYINT(1) DEFAULT 0,
    ordem           TINYINT UNSIGNED DEFAULT 1,
    FOREIGN KEY (questao_id) REFERENCES quiz_questoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10c. Leitura: Texto + Questões abertas ou múltipla escolha
CREATE TABLE IF NOT EXISTS leitura_conteudo (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    atividade_id    INT UNSIGNED NOT NULL UNIQUE,
    texto           LONGTEXT NOT NULL,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10d. Projeto: Etapas
CREATE TABLE IF NOT EXISTS projeto_etapas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    atividade_id    INT UNSIGNED NOT NULL,
    ordem           TINYINT UNSIGNED NOT NULL,
    titulo          VARCHAR(150) NOT NULL,
    descricao       TEXT,
    xp_etapa        SMALLINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10e. Caça-palavras: Configuração da grade
CREATE TABLE IF NOT EXISTS cacapalavras_config (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    atividade_id    INT UNSIGNED NOT NULL UNIQUE,
    grid_linhas     TINYINT UNSIGNED DEFAULT 15,
    grid_colunas    TINYINT UNSIGNED DEFAULT 15,
    palavras        JSON NOT NULL COMMENT 'Array de palavras a encontrar',
    grade_gerada    LONGTEXT NULL COMMENT 'Grade JSON gerada pelo sistema',
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10f. Grupos: Integrantes
CREATE TABLE IF NOT EXISTS grupo_membros (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    atividade_id    INT UNSIGNED NOT NULL,
    aluno_id        INT UNSIGNED NOT NULL,
    lider           TINYINT(1) DEFAULT 0,
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_grupo_membro (atividade_id, aluno_id),
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id)     REFERENCES usuarios(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 11. TENTATIVAS DOS ALUNOS
-- ============================================================
CREATE TABLE IF NOT EXISTS tentativas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id        INT UNSIGNED NOT NULL,
    atividade_id    INT UNSIGNED NOT NULL,
    etapa           TINYINT UNSIGNED DEFAULT 1 COMMENT 'Para projetos por etapas',
    numero          TINYINT UNSIGNED DEFAULT 1,
    acertos         TINYINT UNSIGNED DEFAULT 0,
    total_questoes  TINYINT UNSIGNED DEFAULT 0,
    percentual      DECIMAL(5,2) DEFAULT 0.00,
    xp_ganho        SMALLINT UNSIGNED DEFAULT 0,
    concluida       TINYINT(1) DEFAULT 0,
    iniciada_em     DATETIME DEFAULT CURRENT_TIMESTAMP,
    finalizada_em   DATETIME NULL,
    INDEX idx_aluno_atividade (aluno_id, atividade_id),
    FOREIGN KEY (aluno_id)     REFERENCES usuarios(id)    ON DELETE CASCADE,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 12. XP DO ALUNO (melhor resultado por atividade)
-- ============================================================
CREATE TABLE IF NOT EXISTS aluno_xp (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id        INT UNSIGNED NOT NULL,
    atividade_id    INT UNSIGNED NOT NULL,
    xp_melhor       SMALLINT UNSIGNED DEFAULT 0 COMMENT 'Maior XP obtido em todas as tentativas',
    atualizado_em   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_aluno_atividade_xp (aluno_id, atividade_id),
    FOREIGN KEY (aluno_id)     REFERENCES usuarios(id)   ON DELETE CASCADE,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 13. PERFIL DE PROGRESSO DO ALUNO (cache de totais)
-- ============================================================
CREATE TABLE IF NOT EXISTS aluno_perfil (
    aluno_id        INT UNSIGNED PRIMARY KEY,
    xp_total        INT UNSIGNED DEFAULT 0,
    nivel_atual     TINYINT UNSIGNED DEFAULT 1,
    streak_dias     TINYINT UNSIGNED DEFAULT 0,
    ultimo_acesso   DATE NULL,
    missoes_concluidas SMALLINT UNSIGNED DEFAULT 0,
    atualizado_em   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 14. CONQUISTAS (BADGES) — Catálogo
-- ============================================================
CREATE TABLE IF NOT EXISTS conquistas (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave       VARCHAR(60) NOT NULL UNIQUE,
    nome        VARCHAR(100) NOT NULL,
    descricao   VARCHAR(200),
    icone       VARCHAR(10) NOT NULL COMMENT 'Emoji',
    criterio    VARCHAR(100) COMMENT 'Ex: missoes_dia>=5',
    ativa       TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO conquistas (chave, nome, descricao, icone, criterio) VALUES
('raio_veloz',   'Raio Veloz',    '5 missões concluídas em 1 dia',       '⚡', 'missoes_dia>=5'),
('mira_certeira','Mira Certeira', '10 acertos consecutivos',              '🎯', 'acertos_seguidos>=10'),
('leitor_avido', 'Leitor Ávido',  '5 leituras concluídas',               '📚', 'leituras>=5'),
('campeao',      'Campeão',       '1° lugar na turma por 1 semana',      '🏆', 'ranking1_semana>=1'),
('em_chamas',    'Em Chamas',     '7 dias de acesso seguidos',           '🔥', 'streak>=7'),
('colaborador',  'Colaborador',   'Trabalho em grupo concluído',         '🤝', 'grupos>=1'),
('superestrela', 'Superestrela',  '50 missões concluídas',               '🌟', 'missoes_total>=50'),
('cientista',    'Cientista',     '5 projetos finalizados',              '🔬', 'projetos>=5'),
('perfeito',     'Perfeito',      '100% de acerto em uma atividade',     '💯', 'percentual=100'),
('imparavel',    'Imparável',     '30 dias de acesso à plataforma',      '🚀', 'streak>=30'),
('diamante',     'Diamante',      'Atingir o nível 10',                  '💎', 'nivel>=10'),
('rei_escola',   'Rei da Escola', '1° lugar no ranking geral da escola', '👑', 'ranking_escola=1');

-- ============================================================
-- 15. CONQUISTAS DOS ALUNOS
-- ============================================================
CREATE TABLE IF NOT EXISTS aluno_conquistas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id        INT UNSIGNED NOT NULL,
    conquista_id    INT UNSIGNED NOT NULL,
    desbloqueada_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_aluno_conquista (aluno_id, conquista_id),
    FOREIGN KEY (aluno_id)     REFERENCES usuarios(id)    ON DELETE CASCADE,
    FOREIGN KEY (conquista_id) REFERENCES conquistas(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 16. LOGS DE ACESSO
-- ============================================================
CREATE TABLE IF NOT EXISTS logs_acesso (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NULL,
    email       VARCHAR(150) NULL COMMENT 'Registra mesmo se login falhou',
    acao        VARCHAR(80) NOT NULL COMMENT 'login_ok, login_fail, logout, missao_concluida...',
    ip          VARCHAR(45) NULL,
    user_agent  VARCHAR(300) NULL,
    detalhe     TEXT NULL,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_criado  (criado_em),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 17. SESSÕES PHP (armazenadas no banco — mais seguro no host compartilhado)
-- ============================================================
CREATE TABLE IF NOT EXISTS sessoes (
    id          VARCHAR(128) PRIMARY KEY,
    usuario_id  INT UNSIGNED NULL,
    dados       TEXT,
    ip          VARCHAR(45),
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expira_em   DATETIME NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 18. VIEWS ÚTEIS
-- ============================================================

-- Ranking por turma
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
    RANK() OVER (
        PARTITION BY t.id
        ORDER BY COALESCE(ap.xp_total, 0) DESC
    ) AS posicao_turma
FROM usuarios u
JOIN aluno_turma at2  ON at2.usuario_id = u.id AND at2.ativo = 1
JOIN turmas t         ON t.id = at2.turma_id
LEFT JOIN aluno_perfil ap ON ap.aluno_id = u.id
WHERE u.perfil = 'aluno' AND u.ativo = 1;

-- Ranking geral da escola
CREATE OR REPLACE VIEW vw_ranking_escola AS
SELECT
    u.id            AS aluno_id,
    u.nome          AS aluno_nome,
    u.avatar_url,
    u.escola_id,
    COALESCE(ap.xp_total, 0)            AS xp_total,
    COALESCE(ap.nivel_atual, 1)         AS nivel,
    COALESCE(ap.missoes_concluidas, 0)  AS missoes,
    RANK() OVER (
        PARTITION BY u.escola_id
        ORDER BY COALESCE(ap.xp_total, 0) DESC
    ) AS posicao_escola
FROM usuarios u
LEFT JOIN aluno_perfil ap ON ap.aluno_id = u.id
WHERE u.perfil = 'aluno' AND u.ativo = 1;

-- Engajamento por turma (para coordenador)
CREATE OR REPLACE VIEW vw_engajamento_turmas AS
SELECT
    t.id            AS turma_id,
    t.nome          AS turma_nome,
    u_prof.nome     AS professor_nome,
    pt.disciplina,
    COUNT(DISTINCT at2.usuario_id)  AS total_alunos,
    COUNT(DISTINCT a.id)            AS total_atividades,
    ROUND(
        AVG(COALESCE(ap.xp_total, 0))
    , 0)                             AS xp_medio,
    ROUND(
        100.0 * COUNT(DISTINCT CASE WHEN ap.missoes_concluidas > 0 THEN at2.usuario_id END)
        / NULLIF(COUNT(DISTINCT at2.usuario_id), 0)
    , 1)                             AS pct_engajamento
FROM turmas t
LEFT JOIN professor_turma pt ON pt.turma_id = t.id AND pt.ativo = 1
LEFT JOIN usuarios u_prof    ON u_prof.id = pt.professor_id
LEFT JOIN aluno_turma at2    ON at2.turma_id = t.id AND at2.ativo = 1
LEFT JOIN aluno_perfil ap    ON ap.aluno_id = at2.usuario_id
LEFT JOIN atividades a       ON a.turma_id = t.id AND a.publicada = 1
WHERE t.ativa = 1
GROUP BY t.id, t.nome, u_prof.nome, pt.disciplina;

-- ============================================================
-- 19. TRIGGERS
-- ============================================================

DELIMITER $$

-- Após inserir/atualizar tentativa, recalcula xp_melhor e atualiza perfil
CREATE TRIGGER tg_atualiza_xp_apos_tentativa
AFTER UPDATE ON tentativas
FOR EACH ROW
BEGIN
    DECLARE v_xp_melhor SMALLINT UNSIGNED;
    DECLARE v_xp_total  INT UNSIGNED;
    DECLARE v_nivel     TINYINT UNSIGNED;

    IF NEW.concluida = 1 THEN
        -- Atualiza o melhor XP para esta atividade
        INSERT INTO aluno_xp (aluno_id, atividade_id, xp_melhor)
        VALUES (NEW.aluno_id, NEW.atividade_id, NEW.xp_ganho)
        ON DUPLICATE KEY UPDATE
            xp_melhor = GREATEST(xp_melhor, NEW.xp_ganho);

        -- Recalcula XP total
        SELECT COALESCE(SUM(xp_melhor), 0)
        INTO v_xp_total
        FROM aluno_xp
        WHERE aluno_id = NEW.aluno_id;

        -- Calcula nível atual
        SELECT COALESCE(MAX(nivel), 1)
        INTO v_nivel
        FROM niveis
        WHERE xp_minimo <= v_xp_total;

        -- Atualiza perfil do aluno
        INSERT INTO aluno_perfil (aluno_id, xp_total, nivel_atual, missoes_concluidas)
        VALUES (NEW.aluno_id, v_xp_total, v_nivel,
            (SELECT COUNT(DISTINCT atividade_id) FROM aluno_xp WHERE aluno_id = NEW.aluno_id)
        )
        ON DUPLICATE KEY UPDATE
            xp_total = v_xp_total,
            nivel_atual = v_nivel,
            missoes_concluidas = (
                SELECT COUNT(DISTINCT atividade_id)
                FROM aluno_xp WHERE aluno_id = NEW.aluno_id
            );
    END IF;
END$$

-- Atualiza streak ao registrar acesso
CREATE TRIGGER tg_atualiza_streak
BEFORE UPDATE ON aluno_perfil
FOR EACH ROW
BEGIN
    IF NEW.ultimo_acesso = CURDATE() AND OLD.ultimo_acesso = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN
        SET NEW.streak_dias = OLD.streak_dias + 1;
    ELSEIF NEW.ultimo_acesso = CURDATE() AND OLD.ultimo_acesso != CURDATE() THEN
        SET NEW.streak_dias = 1;
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- 20. ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ============================================================
CREATE INDEX idx_atividades_turma     ON atividades(turma_id, publicada);
CREATE INDEX idx_tentativas_aluno     ON tentativas(aluno_id, concluida);
CREATE INDEX idx_aluno_xp_total       ON aluno_perfil(xp_total DESC);
CREATE INDEX idx_logs_acao            ON logs_acesso(acao, criado_em);
CREATE INDEX idx_conquistas_aluno     ON aluno_conquistas(aluno_id, desbloqueada_em);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- FIM DO SCRIPT — gamifica.sql
-- Total de tabelas: 20
-- Total de views:    3
-- Total de triggers: 2
-- ============================================================
