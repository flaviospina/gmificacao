-- ============================================================
--  GAMIFICA — Script completo do banco de dados
--  Versão 2.0  |  MySQL 8.0 / MariaDB 10.6+  |  itthrive.com.br/gamifica
--
--  V2 = V1 corrigido + módulos competitivos:
--   · Ledger de XP e GamiCoins (fonte de verdade, sem triggers)
--   · Portal da Família (perfil responsavel, feed, mensagens)
--   · Portfólio do aluno
--   · Loja + avatar (economia 100% positiva)
--   · Modo Ao Vivo (quiz com PIN)
--   · Metas Coletivas de Turma        [inédito]
--   · Mentoria entre Pares            [inédito]
--   · Check-in de Bem-estar / Clima   [inédito]
--   · Ranking de Evolução semanal     [inédito]
--   · Notificações e Integrações (Google Classroom)
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
-- 2. USUÁRIOS  (V2: novo perfil "responsavel")
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    escola_id       INT UNSIGNED NOT NULL,
    nome            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    senha_hash      VARCHAR(255) NULL COMMENT 'NULL quando usa só Google',
    perfil          ENUM('aluno','professor','coordenador','admin','responsavel') NOT NULL DEFAULT 'aluno',
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
        '$2y$12$BCIOopAN38Vz0SJnBlBV6OMHKVrJUaGg6tSQaF6HLgqYFL40Cabwe', 'admin');

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
-- 4. ALUNO ↔ TURMA
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
-- 5. PROFESSOR ↔ TURMA
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
-- 6. RESPONSÁVEL ↔ ALUNO  [V2 · Portal da Família]
-- ============================================================
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

-- ============================================================
-- 7. MÓDULOS DO SISTEMA
-- ============================================================
CREATE TABLE IF NOT EXISTS modulos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave       VARCHAR(60) NOT NULL UNIQUE,
    nome        VARCHAR(100) NOT NULL,
    descricao   TEXT,
    ativo       TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO modulos (chave, nome, descricao, ativo) VALUES
('missoes',      'Missões e Atividades',    'Quizzes, projetos e leituras gamificadas', 1),
('xp_niveis',    'Sistema de XP e Níveis',  'Pontuação progressiva sem punição', 1),
('conquistas',   'Conquistas e Badges',     'Medalhas por desempenho e dedicação', 1),
('ranking',      'Ranking e Leaderboard',   'Classificação por turma e escola', 1),
('relatorios',   'Relatórios Avançados',    'Exportação e análises para coordenação', 1),
('colaborativo', 'Modo Colaborativo',       'Atividades em grupo com XP compartilhado', 1),
('notificacoes', 'Notificações',            'Alertas automáticos para todos os perfis', 1),
('loja',         'Loja e GamiCoins',        'Economia positiva: moedas, avatar e recompensas', 1),
('familia',      'Portal da Família',       'Feed positivo e mensagens com responsáveis', 1),
('portfolio',    'Portfólio do Aluno',      'Trabalhos publicados e revisados sem reprovação', 1),
('aovivo',       'Modo Ao Vivo',            'Quiz ao vivo com PIN, estilo game show', 1),
('metas',        'Metas Coletivas',         'Objetivos de turma com recompensa para todos', 1),
('mentoria',     'Mentoria entre Pares',    'Ajudar colegas vale XP', 1),
('bemestar',     'Termômetro de Bem-estar', 'Check-in diário de humor e Índice de Clima', 1),
('integracoes',  'Integrações Externas',    'Google Classroom / Microsoft 365', 0);

-- ============================================================
-- 8. CONFIGURAÇÕES DO SISTEMA
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
('checkin_xp_bonus',     '5',    'XP bônus pelo check-in diário de bem-estar'),
('moedas_por_xp',        '10',   'GamiCoins: 1 moeda a cada N XP ganhos'),
('mentor_pct_bonus',     '20',   '% da melhora do aprendiz que vira XP do mentor'),
('aovivo_tempo_questao', '30',   'Segundos por questão no Modo Ao Vivo'),
('google_client_id',     '',     'Client ID do Google OAuth 2.0'),
('google_client_secret', '',     'Client Secret do Google OAuth 2.0'),
('google_redirect_uri',  'https://itthrive.com.br/gamifica/callback.php', 'URI de retorno OAuth');

-- ============================================================
-- 9. NÍVEIS DE XP
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
(7,  'Desbravador', 2100),
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
-- 10. ATIVIDADES
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

-- 10c. Leitura: Texto (questões usam quiz_questoes da mesma atividade)
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

-- 10e. Projeto: Entregas de etapas pelos alunos [V2]
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

-- 10f. Caça-palavras: Configuração
CREATE TABLE IF NOT EXISTS cacapalavras_config (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    atividade_id    INT UNSIGNED NOT NULL UNIQUE,
    grid_linhas     TINYINT UNSIGNED DEFAULT 12,
    grid_colunas    TINYINT UNSIGNED DEFAULT 12,
    palavras        JSON NOT NULL COMMENT 'Array de palavras a encontrar',
    grade_gerada    LONGTEXT NULL COMMENT 'Grade JSON gerada pelo sistema',
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10g. Grupos: Integrantes
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
-- 11. TENTATIVAS
-- ============================================================
CREATE TABLE IF NOT EXISTS tentativas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id        INT UNSIGNED NOT NULL,
    atividade_id    INT UNSIGNED NOT NULL,
    origem          ENUM('normal','aovivo') DEFAULT 'normal',
    etapa           TINYINT UNSIGNED DEFAULT 1,
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
    xp_melhor       SMALLINT UNSIGNED DEFAULT 0,
    atualizado_em   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_aluno_atividade_xp (aluno_id, atividade_id),
    FOREIGN KEY (aluno_id)     REFERENCES usuarios(id)   ON DELETE CASCADE,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 13. LEDGER DE XP  [V2 · fonte de verdade — XP nunca negativo]
-- ============================================================
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

-- ============================================================
-- 14. LEDGER DE GAMICOINS  [V2 · economia 100% positiva]
--     'gasto' só existe por escolha do aluno (loja); estorno devolve
-- ============================================================
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

-- ============================================================
-- 15. PERFIL DE PROGRESSO (cache de totais — recalculado em PHP)
-- ============================================================
CREATE TABLE IF NOT EXISTS aluno_perfil (
    aluno_id        INT UNSIGNED PRIMARY KEY,
    xp_total        INT UNSIGNED DEFAULT 0,
    moedas          INT UNSIGNED DEFAULT 0,
    nivel_atual     TINYINT UNSIGNED DEFAULT 1,
    streak_dias     SMALLINT UNSIGNED DEFAULT 0,
    ultimo_acesso   DATE NULL,
    missoes_concluidas SMALLINT UNSIGNED DEFAULT 0,
    atualizado_em   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 16. CONQUISTAS — Catálogo (V1 + novas do V2)
-- ============================================================
CREATE TABLE IF NOT EXISTS conquistas (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave       VARCHAR(60) NOT NULL UNIQUE,
    nome        VARCHAR(100) NOT NULL,
    descricao   VARCHAR(200),
    icone       VARCHAR(10) NOT NULL COMMENT 'Emoji',
    criterio    VARCHAR(100),
    ativa       TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO conquistas (chave, nome, descricao, icone, criterio) VALUES
('raio_veloz',   'Raio Veloz',    '5 missões concluídas em 1 dia',        '⚡', 'missoes_dia>=5'),
('mira_certeira','Mira Certeira', '10 acertos consecutivos',              '🎯', 'acertos_seguidos>=10'),
('leitor_avido', 'Leitor Ávido',  '5 leituras concluídas',                '📚', 'leituras>=5'),
('campeao',      'Campeão',       '1° lugar na turma',                    '🏆', 'ranking_turma=1'),
('em_chamas',    'Em Chamas',     '7 dias de acesso seguidos',            '🔥', 'streak>=7'),
('colaborador',  'Colaborador',   'Trabalho em grupo concluído',          '🤝', 'grupos>=1'),
('superestrela', 'Superestrela',  '50 missões concluídas',                '🌟', 'missoes_total>=50'),
('cientista',    'Cientista',     '5 projetos finalizados',               '🔬', 'projetos>=5'),
('perfeito',     'Perfeito',      '100% de acerto em uma atividade',      '💯', 'percentual=100'),
('imparavel',    'Imparável',     '30 dias de acesso à plataforma',       '🚀', 'streak>=30'),
('diamante',     'Diamante',      'Atingir o nível 10',                   '💎', 'nivel>=10'),
('rei_escola',   'Rei da Escola', '1° lugar no ranking geral da escola',  '👑', 'ranking_escola=1'),
-- V2
('guia',         'Guia',          'Ajudou um colega como mentor',         '🧭', 'mentorias_bonus>=1'),
('foguete_semana','Foguete da Semana','1° no Ranking de Evolução da turma','🚀','evolucao_turma=1'),
('time_dos_sonhos','Time dos Sonhos','Meta coletiva da turma atingida',   '🎯', 'metas_atingidas>=1'),
('coracao_presente','Coração Presente','15 check-ins de bem-estar',       '❤️', 'checkins>=15'),
('estreante_aovivo','Estreia Ao Vivo','Participou de um quiz ao vivo',    '🎤', 'aovivo>=1'),
('artista_portfolio','Artista do Portfólio','Trabalho aprovado no portfólio','🎨','portfolio_aprovado>=1');

-- ============================================================
-- 17. CONQUISTAS DOS ALUNOS
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
-- 18. LOJA  [V2]
-- ============================================================
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

-- Itens de avatar do aluno (comprados; um equipado por vez)
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

-- ============================================================
-- 19. MODO AO VIVO  [V2 · resposta ao Kahoot]
-- ============================================================
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

-- ============================================================
-- 20. METAS COLETIVAS DE TURMA  [V2 · inédito]
-- ============================================================
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

-- ============================================================
-- 21. MENTORIA ENTRE PARES  [V2 · inédito]
-- ============================================================
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

-- ============================================================
-- 22. CHECK-IN DE BEM-ESTAR  [V2 · inédito]
-- ============================================================
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

-- ============================================================
-- 23. PORTFÓLIO  [V2]
-- ============================================================
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

-- ============================================================
-- 24. PORTAL DA FAMÍLIA  [V2]
-- ============================================================
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

-- ============================================================
-- 25. NOTIFICAÇÕES  [V2]
-- ============================================================
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

-- ============================================================
-- 26. INTEGRAÇÕES  [V2 · Google Classroom / Microsoft 365]
-- ============================================================
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

-- ============================================================
-- 27. LOGS DE ACESSO
-- ============================================================
CREATE TABLE IF NOT EXISTS logs_acesso (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NULL,
    email       VARCHAR(150) NULL,
    acao        VARCHAR(80) NOT NULL,
    ip          VARCHAR(45) NULL,
    user_agent  VARCHAR(300) NULL,
    detalhe     TEXT NULL,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_criado  (criado_em),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 28. VIEWS
-- ============================================================

-- Ranking por turma (V2: expõe turma_id)
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

-- [V2 · inédito] Ranking de Evolução — XP ganho na semana corrente
CREATE OR REPLACE VIEW vw_ranking_evolucao AS
SELECT
    u.id        AS aluno_id,
    u.nome      AS aluno_nome,
    u.avatar_url,
    u.escola_id,
    t.id        AS turma_id,
    t.nome      AS turma_nome,
    COALESCE(SUM(xe.xp), 0) AS xp_semana,
    RANK() OVER (
        PARTITION BY t.id
        ORDER BY COALESCE(SUM(xe.xp), 0) DESC
    ) AS posicao_evolucao
FROM usuarios u
JOIN aluno_turma at2 ON at2.usuario_id = u.id AND at2.ativo = 1
JOIN turmas t        ON t.id = at2.turma_id
LEFT JOIN xp_eventos xe ON xe.aluno_id = u.id
       AND YEARWEEK(xe.criado_em, 1) = YEARWEEK(CURDATE(), 1)
WHERE u.perfil = 'aluno' AND u.ativo = 1
GROUP BY u.id, u.nome, u.avatar_url, u.escola_id, t.id, t.nome;

-- [V2 · inédito] Clima por turma — humor médio + participação (últimos 7 dias)
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

-- Engajamento por turma (coordenador)
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

-- ============================================================
-- 29. ÍNDICES ADICIONAIS
-- ============================================================
CREATE INDEX idx_atividades_turma ON atividades(turma_id, publicada);
CREATE INDEX idx_tentativas_aluno ON tentativas(aluno_id, concluida);
CREATE INDEX idx_aluno_xp_total   ON aluno_perfil(xp_total DESC);
CREATE INDEX idx_logs_acao        ON logs_acesso(acao, criado_em);
CREATE INDEX idx_conquistas_aluno ON aluno_conquistas(aluno_id, desbloqueada_em);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- FIM — gamifica_v2.sql
-- Sem triggers: todo o motor de XP/moedas vive em includes/functions.php
-- (portável em hospedagem compartilhada e testável).
-- ============================================================
