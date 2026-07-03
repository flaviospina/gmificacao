-- ============================================================
--  GAMIFICA — Migração V1 → V2
--  Rode em um banco que já tem o gamifica.sql (V1) aplicado.
--  Instalações novas devem usar direto o gamifica_v2.sql.
-- ============================================================

SET NAMES utf8mb4;

-- 1. Novo perfil "responsavel"
ALTER TABLE usuarios
    MODIFY perfil ENUM('aluno','professor','coordenador','admin','responsavel') NOT NULL DEFAULT 'aluno';

-- 2. GamiCoins no perfil + streak maior
ALTER TABLE aluno_perfil
    ADD COLUMN moedas INT UNSIGNED DEFAULT 0 AFTER xp_total,
    MODIFY streak_dias SMALLINT UNSIGNED DEFAULT 0;

-- 3. Origem da tentativa (normal | ao vivo)
ALTER TABLE tentativas
    ADD COLUMN origem ENUM('normal','aovivo') DEFAULT 'normal' AFTER atividade_id;

-- 4. Remove triggers do V1 — o motor agora é PHP (includes/functions.php)
DROP TRIGGER IF EXISTS tg_atualiza_xp_apos_tentativa;
DROP TRIGGER IF EXISTS tg_atualiza_streak;

-- 5. Corrige nome de nível duplicado do V1
UPDATE niveis SET nome = 'Desbravador' WHERE nivel = 7;

-- 6. Tabelas novas — copie do gamifica_v2.sql as seções:
--    6, 10e, 13, 14, 18, 19, 20, 21, 22, 23, 24, 25, 26
--    (responsavel_aluno, projeto_entregas, xp_eventos, moeda_eventos,
--     loja_*, aluno_avatar, sessoes_ao_vivo, sessao_*, metas_turma,
--     mentorias, checkins_bemestar, portfolio_itens, feed_familia,
--     mensagens, notificacoes, integracoes, turma_vinculos)
SOURCE gamifica_v2_tabelas_novas.sql;  -- (no cliente mysql; via phpMyAdmin, importe o arquivo separadamente ANTES dos passos 7-11)

-- 7. Popular o ledger a partir do histórico existente
INSERT INTO xp_eventos (aluno_id, origem, referencia_id, xp, descricao, criado_em)
SELECT ax.aluno_id, 'missao', ax.atividade_id, ax.xp_melhor,
       'Migração V1: melhor resultado consolidado', ax.atualizado_em
FROM aluno_xp ax
WHERE ax.xp_melhor > 0;

-- 8. Moedas retroativas (1 a cada 10 XP já conquistados)
INSERT INTO moeda_eventos (aluno_id, tipo, origem, moedas, criado_em)
SELECT ap.aluno_id, 'ganho', 'migracao', FLOOR(ap.xp_total / 10), NOW()
FROM aluno_perfil ap
WHERE ap.xp_total >= 10;

UPDATE aluno_perfil ap
SET ap.moedas = FLOOR(ap.xp_total / 10);

-- 9. Novos módulos e configurações
INSERT IGNORE INTO modulos (chave, nome, descricao, ativo) VALUES
('loja',        'Loja e GamiCoins',        'Economia positiva: moedas, avatar e recompensas', 1),
('familia',     'Portal da Família',       'Feed positivo e mensagens com responsáveis', 1),
('portfolio',   'Portfólio do Aluno',      'Trabalhos publicados e revisados sem reprovação', 1),
('aovivo',      'Modo Ao Vivo',            'Quiz ao vivo com PIN, estilo game show', 1),
('metas',       'Metas Coletivas',         'Objetivos de turma com recompensa para todos', 1),
('mentoria',    'Mentoria entre Pares',    'Ajudar colegas vale XP', 1),
('bemestar',    'Termômetro de Bem-estar', 'Check-in diário de humor e Índice de Clima', 1),
('integracoes', 'Integrações Externas',    'Google Classroom / Microsoft 365', 0);

UPDATE modulos SET ativo = 1 WHERE chave IN ('relatorios','notificacoes');

INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES
('checkin_xp_bonus',     '5',  'XP bônus pelo check-in diário de bem-estar'),
('moedas_por_xp',        '10', 'GamiCoins: 1 moeda a cada N XP ganhos'),
('mentor_pct_bonus',     '20', '% da melhora do aprendiz que vira XP do mentor'),
('aovivo_tempo_questao', '30', 'Segundos por questão no Modo Ao Vivo');

-- 10. Novas conquistas
INSERT IGNORE INTO conquistas (chave, nome, descricao, icone, criterio) VALUES
('guia',             'Guia',                'Ajudou um colega como mentor',           '🧭', 'mentorias_bonus>=1'),
('foguete_semana',   'Foguete da Semana',   '1° no Ranking de Evolução da turma',     '🚀', 'evolucao_turma=1'),
('time_dos_sonhos',  'Time dos Sonhos',     'Meta coletiva da turma atingida',        '🎯', 'metas_atingidas>=1'),
('coracao_presente', 'Coração Presente',    '15 check-ins de bem-estar',              '❤️', 'checkins>=15'),
('estreante_aovivo', 'Estreia Ao Vivo',     'Participou de um quiz ao vivo',          '🎤', 'aovivo>=1'),
('artista_portfolio','Artista do Portfólio','Trabalho aprovado no portfólio',         '🎨', 'portfolio_aprovado>=1');

-- 11. Views novas/corrigidas — já incluídas no gamifica_v2_tabelas_novas.sql (passo 6)
