-- ============================================================
--  GAMIFICA V2 — Dados de demonstração
--  Rode APÓS o gamifica_v2.sql.
--  Senha de todos os usuários demo: Gamifica@2026
-- ============================================================
SET NAMES utf8mb4;

-- ── Turmas ────────────────────────────────────────────────────
INSERT INTO turmas (escola_id, nome, ano_letivo) VALUES
(1, '9A', 2026),
(1, '8B', 2026);

-- ── Usuários (admin id=1 já existe) ──────────────────────────
INSERT INTO usuarios (escola_id, nome, email, senha_hash, perfil) VALUES
(1, 'Ana Souza',    'ana@escola.edu.br',    '$2y$12$BCIOopAN38Vz0SJnBlBV6OMHKVrJUaGg6tSQaF6HLgqYFL40Cabwe', 'professor'),   -- id 2
(1, 'Carlos Lima',  'carlos@escola.edu.br', '$2y$12$BCIOopAN38Vz0SJnBlBV6OMHKVrJUaGg6tSQaF6HLgqYFL40Cabwe', 'coordenador'), -- id 3
(1, 'Bia Martins',  'bia@escola.edu.br',    '$2y$12$BCIOopAN38Vz0SJnBlBV6OMHKVrJUaGg6tSQaF6HLgqYFL40Cabwe', 'aluno'),       -- id 4
(1, 'Davi Rocha',   'davi@escola.edu.br',   '$2y$12$BCIOopAN38Vz0SJnBlBV6OMHKVrJUaGg6tSQaF6HLgqYFL40Cabwe', 'aluno'),       -- id 5
(1, 'Enzo Pereira', 'enzo@escola.edu.br',   '$2y$12$BCIOopAN38Vz0SJnBlBV6OMHKVrJUaGg6tSQaF6HLgqYFL40Cabwe', 'aluno'),       -- id 6
(1, 'Gabi Nunes',   'gabi@escola.edu.br',   '$2y$12$BCIOopAN38Vz0SJnBlBV6OMHKVrJUaGg6tSQaF6HLgqYFL40Cabwe', 'aluno'),       -- id 7
(1, 'Hugo Alves',   'hugo@escola.edu.br',   '$2y$12$BCIOopAN38Vz0SJnBlBV6OMHKVrJUaGg6tSQaF6HLgqYFL40Cabwe', 'aluno'),       -- id 8
(1, 'Iris Costa',   'iris@escola.edu.br',   '$2y$12$BCIOopAN38Vz0SJnBlBV6OMHKVrJUaGg6tSQaF6HLgqYFL40Cabwe', 'aluno'),       -- id 9
(1, 'Rosa Martins', 'rosa@familia.com',     '$2y$12$BCIOopAN38Vz0SJnBlBV6OMHKVrJUaGg6tSQaF6HLgqYFL40Cabwe', 'responsavel');-- id 10

-- ── Vínculos ─────────────────────────────────────────────────
INSERT INTO aluno_turma (usuario_id, turma_id) VALUES
(4,1),(5,1),(6,1),(7,1),(8,2),(9,2);

INSERT INTO professor_turma (professor_id, turma_id, disciplina) VALUES
(2, 1, 'Matemática'),
(2, 1, 'Português'),
(2, 2, 'Ciências');

INSERT INTO responsavel_aluno (responsavel_id, aluno_id, parentesco) VALUES
(10, 4, 'mãe');

-- ── Atividades (turma 9A) ────────────────────────────────────
-- 1: Quiz de Frações (Matemática)
INSERT INTO atividades (professor_id, turma_id, titulo, descricao, tipo, disciplina, xp_recompensa, publicada) VALUES
(2, 1, 'Quiz de Frações', 'Revisão de frações equivalentes e operações.', 'quiz', 'Matemática', 100, 1);

INSERT INTO quiz_questoes (atividade_id, ordem, enunciado) VALUES
(1, 1, 'Qual fração é equivalente a 1/2?'),
(1, 2, 'Quanto é 1/4 + 1/4?'),
(1, 3, 'Qual fração é maior?');

INSERT INTO quiz_alternativas (questao_id, texto, correta, ordem) VALUES
(1, '2/4', 1, 1), (1, '2/3', 0, 2), (1, '1/3', 0, 3), (1, '3/5', 0, 4),
(2, '2/8', 0, 1), (2, '1/2', 1, 2), (2, '2/4 de 2', 0, 3), (2, '1/8', 0, 4),
(3, '1/3', 0, 1), (3, '1/4', 0, 2), (3, '3/4', 1, 3), (3, '2/5', 0, 4);

-- 2: Leitura (Português)
INSERT INTO atividades (professor_id, turma_id, titulo, descricao, tipo, disciplina, xp_recompensa, publicada) VALUES
(2, 1, 'Leitura: O Pequeno Príncipe', 'Leia o trecho e responda às perguntas.', 'leitura', 'Português', 80, 1);

INSERT INTO leitura_conteudo (atividade_id, texto) VALUES
(2, 'Tu te tornas eternamente responsável por aquilo que cativas. — Antoine de Saint-Exupéry.\n\nNo capítulo XXI, a raposa ensina ao Pequeno Príncipe o valor dos laços: "cativar" significa criar vínculos. Para a raposa, o tempo dedicado à sua rosa é o que a torna única no mundo. O essencial é invisível aos olhos: só se vê bem com o coração.');

INSERT INTO quiz_questoes (atividade_id, ordem, enunciado) VALUES
(2, 1, 'Segundo a raposa, o que significa "cativar"?'),
(2, 2, 'Para o texto, o que torna a rosa única?');

INSERT INTO quiz_alternativas (questao_id, texto, correta, ordem) VALUES
(4, 'Criar vínculos', 1, 1), (4, 'Prender alguém', 0, 2), (4, 'Viajar entre planetas', 0, 3), (4, 'Colecionar flores', 0, 4),
(5, 'O tempo dedicado a ela', 1, 1), (5, 'Sua cor', 0, 2), (5, 'Seu perfume', 0, 3), (5, 'Seus espinhos', 0, 4);

-- 3: Caça-palavras (Ciências, 9A)
INSERT INTO atividades (professor_id, turma_id, titulo, descricao, tipo, disciplina, xp_recompensa, publicada) VALUES
(2, 1, 'Caça-palavras: Sistema Solar', 'Encontre os planetas escondidos na grade.', 'cacapalavras', 'Ciências', 60, 1);

INSERT INTO cacapalavras_config (atividade_id, grid_linhas, grid_colunas, palavras) VALUES
(3, 12, 12, '["TERRA","MARTE","VENUS","JUPITER","SATURNO","LUA"]');

-- 4: Projeto por etapas
INSERT INTO atividades (professor_id, turma_id, titulo, descricao, tipo, disciplina, xp_recompensa, total_etapas, publicada) VALUES
(2, 1, 'Projeto: Horta na Escola', 'Planeje, plante e registre o crescimento.', 'projeto', 'Ciências', 200, 3, 1);

INSERT INTO projeto_etapas (atividade_id, ordem, titulo, descricao, xp_etapa) VALUES
(4, 1, 'Planejamento', 'Descreva o que será plantado e onde.', 60),
(4, 2, 'Plantio', 'Registre como foi o plantio.', 60),
(4, 3, 'Relatório final', 'Conte o resultado e o que aprendeu.', 80);

-- 5: Trabalho em grupo
INSERT INTO atividades (professor_id, turma_id, titulo, descricao, tipo, disciplina, xp_recompensa, publicada) VALUES
(2, 1, 'Mural Colaborativo de Poesia', 'Em grupo, montem um mural com 5 poemas autorais.', 'grupo', 'Português', 150, 1);

INSERT INTO grupo_membros (atividade_id, aluno_id, lider) VALUES
(5, 4, 1), (5, 5, 0), (5, 6, 0), (5, 7, 0);

-- 6: Quiz para a 8B
INSERT INTO atividades (professor_id, turma_id, titulo, descricao, tipo, disciplina, xp_recompensa, publicada) VALUES
(2, 2, 'Quiz: Estados da Matéria', 'Sólido, líquido e gasoso.', 'quiz', 'Ciências', 100, 1);

INSERT INTO quiz_questoes (atividade_id, ordem, enunciado) VALUES
(6, 1, 'A passagem do estado líquido para o gasoso chama-se:'),
(6, 2, 'O gelo é a água em qual estado?');

INSERT INTO quiz_alternativas (questao_id, texto, correta, ordem) VALUES
(6, 'Evaporação', 1, 1), (6, 'Fusão', 0, 2), (6, 'Solidificação', 0, 3), (6, 'Condensação', 0, 4),
(7, 'Sólido', 1, 1), (7, 'Líquido', 0, 2), (7, 'Gasoso', 0, 3), (7, 'Plasma', 0, 4);

-- ── Histórico consistente (tentativas → aluno_xp → ledger → perfil) ──
-- Bia (4): quiz 2/3 = 67 XP · leitura 2/2 = 80 XP  → 147 XP, 14 moedas, nível 2
INSERT INTO tentativas (aluno_id, atividade_id, numero, acertos, total_questoes, percentual, xp_ganho, concluida, finalizada_em) VALUES
(4, 1, 1, 2, 3, 66.67, 67, 1, NOW()),
(4, 2, 1, 2, 2, 100.00, 80, 1, NOW());
INSERT INTO aluno_xp (aluno_id, atividade_id, xp_melhor) VALUES (4, 1, 67), (4, 2, 80);
INSERT INTO xp_eventos (aluno_id, origem, referencia_id, xp, descricao) VALUES
(4, 'missao', 1, 67, 'Quiz de Frações'),
(4, 'missao', 2, 80, 'Leitura: O Pequeno Príncipe');
INSERT INTO moeda_eventos (aluno_id, tipo, origem, referencia_id, moedas) VALUES
(4, 'ganho', 'missao', 1, 6), (4, 'ganho', 'missao', 2, 8);
INSERT INTO aluno_perfil (aluno_id, xp_total, moedas, nivel_atual, streak_dias, ultimo_acesso, missoes_concluidas) VALUES
(4, 147, 14, 2, 2, CURDATE(), 2);

-- Davi (5): quiz 1/3 = 33 XP → 33 XP, 3 moedas, nível 1
INSERT INTO tentativas (aluno_id, atividade_id, numero, acertos, total_questoes, percentual, xp_ganho, concluida, finalizada_em) VALUES
(5, 1, 1, 1, 3, 33.33, 33, 1, NOW());
INSERT INTO aluno_xp (aluno_id, atividade_id, xp_melhor) VALUES (5, 1, 33);
INSERT INTO xp_eventos (aluno_id, origem, referencia_id, xp, descricao) VALUES
(5, 'missao', 1, 33, 'Quiz de Frações');
INSERT INTO moeda_eventos (aluno_id, tipo, origem, referencia_id, moedas) VALUES
(5, 'ganho', 'missao', 1, 3);
INSERT INTO aluno_perfil (aluno_id, xp_total, moedas, nivel_atual, streak_dias, ultimo_acesso, missoes_concluidas) VALUES
(5, 33, 3, 1, 1, CURDATE(), 1);

-- Enzo (6): quiz 3/3 = 100 XP · leitura 2/2 = 80 XP · streak +10 → 190 XP, 19 moedas, nível 2
INSERT INTO tentativas (aluno_id, atividade_id, numero, acertos, total_questoes, percentual, xp_ganho, concluida, finalizada_em) VALUES
(6, 1, 1, 3, 3, 100.00, 100, 1, NOW()),
(6, 2, 1, 2, 2, 100.00, 80, 1, NOW());
INSERT INTO aluno_xp (aluno_id, atividade_id, xp_melhor) VALUES (6, 1, 100), (6, 2, 80);
INSERT INTO xp_eventos (aluno_id, origem, referencia_id, xp, descricao) VALUES
(6, 'missao', 1, 100, 'Quiz de Frações'),
(6, 'missao', 2, 80, 'Leitura: O Pequeno Príncipe'),
(6, 'streak', NULL, 10, 'Bônus de sequência de acesso');
INSERT INTO moeda_eventos (aluno_id, tipo, origem, referencia_id, moedas) VALUES
(6, 'ganho', 'missao', 1, 10), (6, 'ganho', 'missao', 2, 8), (6, 'ganho', 'streak', NULL, 1);
INSERT INTO aluno_perfil (aluno_id, xp_total, moedas, nivel_atual, streak_dias, ultimo_acesso, missoes_concluidas) VALUES
(6, 190, 19, 2, 3, CURDATE(), 2);

-- Conquista: Enzo tirou 100%
INSERT INTO aluno_conquistas (aluno_id, conquista_id)
SELECT 6, id FROM conquistas WHERE chave = 'perfeito';

-- ── Check-ins de bem-estar ───────────────────────────────────
INSERT INTO checkins_bemestar (aluno_id, data, humor) VALUES
(4, CURDATE(), 5),
(5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 3),
(6, CURDATE(), 4);

-- ── Loja ──────────────────────────────────────────────────────
INSERT INTO loja_itens (escola_id, nome, descricao, icone, categoria, preco_moedas, requer_aprovacao) VALUES
(1, 'Moldura Estrela',        'Estrela dourada ao lado do seu nome',        '⭐', 'avatar',     10, 0),
(1, 'Moldura Foguete',        'Foguete ao lado do seu nome',                '🚀', 'avatar',     15, 0),
(1, 'Moldura Coroa',          'Coroa real ao lado do seu nome',             '👑', 'avatar',     40, 0),
(1, 'Escolher lugar na sala', 'Escolha onde sentar por uma semana',         '🪑', 'privilegio', 30, 1),
(1, 'DJ da turma',            'Escolha a música do intervalo',              '🎧', 'privilegio', 25, 1),
(1, 'Kit de adesivos',        'Cartela de adesivos da escola',              '✨', 'material',   20, 1);

-- ── Meta coletiva ativa (9A) ─────────────────────────────────
INSERT INTO metas_turma (turma_id, criador_id, titulo, descricao, tipo, alvo, recompensa_xp, recompensa_moedas, data_inicio, data_fim) VALUES
(1, 2, 'Turma 9A rumo aos 1.000 XP!', 'Juntos, vamos somar 1.000 XP até o fim da quinzena.', 'xp', 1000, 50, 5,
 CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY));

-- ── Mentoria ativa: Enzo ajuda Davi em Matemática ────────────
INSERT INTO mentorias (turma_id, mentor_id, aprendiz_id, disciplina, criador_id) VALUES
(1, 6, 5, 'Matemática', 2);

-- ── Feed da família ──────────────────────────────────────────
INSERT INTO feed_familia (escola_id, turma_id, aluno_id, autor_id, tipo, titulo, mensagem) VALUES
(1, 1, NULL, 2, 'aviso',  'Semana da Horta começa segunda!', 'Turma 9A iniciará o projeto da horta. Tragam garrafas PET recicláveis.'),
(1, 1, 4,    2, 'elogio', 'Bia mandou muito bem na leitura!', 'Bia tirou 100% no questionário de O Pequeno Príncipe. Parabéns!');

-- ── Notificação de boas-vindas ───────────────────────────────
INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem)
SELECT id, 'sistema', 'Bem-vindo ao Gamifica V2! 🎉', 'Conheça a Loja, o Modo Ao Vivo, as Metas da Turma e muito mais.'
FROM usuarios;
