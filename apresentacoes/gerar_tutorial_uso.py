# -*- coding: utf-8 -*-
"""
Deck 2 — Tutorial de uso do Gamifica, passo a passo por perfil.
"""
from estilo_gamifica import *

prs = nova_apresentacao()
TOTAL = 16
n = 0

def cabecalho(s, titulo, etiqueta, cor_etiqueta=ROXO):
    logo(s, Inches(0.6), Inches(0.5))
    chip(s, Inches(9.3), Inches(0.55), Inches(3.4), Inches(0.5), etiqueta, cor_etiqueta, BRANCO, tam=13)
    texto(s, Inches(0.6), Inches(1.3), Inches(12.1), Inches(0.9), titulo, tam=30, cor=ESCURO, negrito=True)

def passo(s, x, y, w, numero, titulo, desc, cor=ROXO):
    circulo_emoji(s, x, y, Inches(0.75), "", cor)  # círculo com número
    tb = s.shapes[-1].text_frame
    p = tb.paragraphs[0]; p.alignment = PP_ALIGN.CENTER
    r = p.add_run(); r.text = str(numero); r.font.size = Pt(26); r.font.bold = True
    r.font.name = FONTE; r.font.color.rgb = BRANCO
    texto(s, x + Inches(0.95), y - Inches(0.05), w - Inches(1.0), Inches(0.5),
          titulo, tam=17, cor=ESCURO, negrito=True)
    texto(s, x + Inches(0.95), y + Inches(0.45), w - Inches(1.0), Inches(1.1),
          desc, tam=13, cor=CINZA, espaco=1.12)

# ══════════════════════════════════════════════════════════════
# SLIDE 1 — CAPA
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, ESCURO)
faixa_gradiente(s, Inches(-2), Inches(4.8), Inches(17), Inches(6), ESCURO, ROXO, arred=False)
chip(s, Inches(0.9), Inches(0.85), Inches(3.2), Inches(0.5), "MANUAL DE USO", ROXO, BRANCO, tam=13)
circulo_emoji(s, Inches(0.9), Inches(1.7), Inches(1.1), "📘", ROXO, tam=52)
texto(s, Inches(0.85), Inches(3.0), Inches(11.8), Inches(1.4),
      "Como usar o Gamifica", tam=58, cor=BRANCO, negrito=True)
texto(s, Inches(0.9), Inches(4.5), Inches(11.6), Inches(1.0),
      "Guia prático passo a passo para alunos, professores,\ncoordenação, administração e famílias.",
      tam=24, cor=CLARO, espaco=1.15)
chip(s, Inches(0.9), Inches(6.1), Inches(4.6), Inches(0.55),
     "Senha inicial padrão: Gamifica@2026", CLARO, ROXO, tam=14)

# ══════════════════════════════════════════════════════════════
# SLIDE 2 — OS 5 PERFIS
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
cabecalho(s, "5 perfis, cada um com sua área", "VISÃO GERAL")
texto(s, Inches(0.6), Inches(2.05), Inches(12), Inches(0.5),
      "Ao entrar, o sistema leva cada pessoa automaticamente para o painel do seu perfil:", tam=15, cor=CINZA)
perfis = [
    ("🎒", "Aluno", "Faz missões, ganha XP e moedas, sobe no ranking", ROXO),
    ("🧑‍🏫", "Professor", "Cria atividades, hospeda quiz ao vivo, acompanha a turma", ROXO2),
    ("🏫", "Coordenador", "Vê a escola inteira: engajamento, clima e destaques", VERDE),
    ("⚙️", "Administrador", "Gerencia usuários, módulos, loja e integrações", AMBAR),
    ("👨‍👩‍👧", "Responsável", "Acompanha o filho e fala com os professores", LILAS),
]
for i, (emo, t, desc, cor) in enumerate(perfis):
    y = Inches(2.7 + i * 0.9)
    retangulo(s, Inches(0.6), y, Inches(12.1), Inches(0.78), CLARO, arred=True)
    circulo_emoji(s, Inches(0.8), y + Inches(0.09), Inches(0.6), emo, cor, tam=22)
    texto(s, Inches(1.6), y + Inches(0.13), Inches(2.6), Inches(0.5), t, tam=17, cor=ESCURO, negrito=True)
    texto(s, Inches(4.3), y + Inches(0.16), Inches(8.2), Inches(0.5), desc, tam=14, cor=CINZA)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 3 — LOGIN
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
cabecalho(s, "Primeiro acesso: como entrar", "LOGIN")
passo(s, Inches(0.6), Inches(2.4), Inches(6.0), 1, "Abra o endereço da escola",
      "Acesse itthrive.com.br/gamifica no navegador do computador ou celular.")
passo(s, Inches(0.6), Inches(3.9), Inches(6.0), 2, "Escolha como entrar",
      "Botão \"Entrar com Google\" (conta da escola) ou e-mail e senha.")
passo(s, Inches(0.6), Inches(5.4), Inches(6.0), 3, "Troque a senha no 1º acesso",
      "Em \"Meu Perfil\", defina uma senha nova (mínimo 8 caracteres).")
# card lateral
retangulo(s, Inches(7.0), Inches(2.4), Inches(5.7), Inches(4.1), CLARO, arred=True)
circulo_emoji(s, Inches(7.4), Inches(2.75), Inches(0.9), "🔑", ROXO, tam=28)
texto(s, Inches(8.5), Inches(2.85), Inches(4.0), Inches(0.6), "Esqueceu a senha?", tam=18, cor=ESCURO, negrito=True)
bullets(s, Inches(7.4), Inches(3.9), Inches(5.0), Inches(2.4),
        ["Quem usa Google não precisa de senha — é só usar o botão.",
         "Quem usa e-mail/senha pede ao administrador da escola para redefinir.",
         "O administrador gera uma senha temporária em Usuários → 🔑."],
        tam=14, cor=ESCURO, cor_marcador=ROXO, espaco=1.15, gap_depois=10)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 4 — ALUNO: DASHBOARD E MISSÕES
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
cabecalho(s, "Aluno · painel e missões", "PERFIL: ALUNO", ROXO)
texto(s, Inches(0.6), Inches(2.05), Inches(12), Inches(0.5),
      "A tela inicial do aluno mostra o progresso e as missões disponíveis:", tam=15, cor=CINZA)
itens = [
    ("🏅", "Banner de XP", "Nível atual, XP total, moedas 🪙, streak 🔥 e posição na turma."),
    ("⚔️", "Missões", "Cartões de atividades. Clique em Iniciar para fazer."),
    ("♾️", "Tentativas ilimitadas", "Refaça quantas vezes quiser — só o melhor resultado conta."),
    ("💚", "Check-in do dia", "1 clique no emoji de humor = +5 XP. Só o professor vê o clima."),
]
for i, (emo, t, desc) in enumerate(itens):
    col = i % 2; row = i // 2
    x = Inches(0.6 + col * 6.15); y = Inches(2.7 + row * 1.95)
    retangulo(s, x, y, Inches(5.85), Inches(1.7), CLARO, arred=True)
    circulo_emoji(s, x + Inches(0.3), y + Inches(0.35), Inches(0.95), emo, ROXO, tam=28)
    texto(s, x + Inches(1.45), y + Inches(0.25), Inches(4.2), Inches(0.5), t, tam=17, cor=ESCURO, negrito=True)
    texto(s, x + Inches(1.45), y + Inches(0.78), Inches(4.2), Inches(0.85), desc, tam=13, cor=CINZA, espaco=1.1)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 5 — ALUNO: TIPOS DE MISSÃO
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
cabecalho(s, "Aluno · as 6 formas de jogar", "PERFIL: ALUNO", ROXO)
tipos = [
    ("❓", "Quiz", "Perguntas de múltipla escolha.\nXP proporcional aos acertos."),
    ("📖", "Leitura", "Lê o texto e responde às\nperguntas de compreensão."),
    ("🔬", "Projeto", "Etapas enviadas ao professor,\nque aprova cada uma."),
    ("🔤", "Caça-palavras", "Clica na 1ª e na última\nletra para marcar a palavra."),
    ("🤝", "Grupo", "Trabalho coletivo: todos do\ngrupo recebem o mesmo XP."),
    ("🎤", "Ao Vivo", "Entra com o PIN do telão\ne responde em tempo real."),
]
for i, (emo, t, desc) in enumerate(tipos):
    col = i % 3; row = i // 3
    x = Inches(0.6 + col * 4.08); y = Inches(2.15 + row * 2.15)
    retangulo(s, x, y, Inches(3.85), Inches(1.95), CLARO, arred=True)
    circulo_emoji(s, x + Inches(0.28), y + Inches(0.32), Inches(0.85), emo, ROXO, tam=24)
    texto(s, x + Inches(1.25), y + Inches(0.42), Inches(2.5), Inches(0.5), t,
          tam=17, cor=ESCURO, negrito=True)
    texto(s, x + Inches(0.32), y + Inches(1.15), Inches(3.3), Inches(0.75),
          desc, tam=12, cor=CINZA, espaco=1.08)
texto(s, Inches(0.6), Inches(6.7), Inches(12), Inches(0.5),
      "Em todas: nunca há desconto de XP. Errar é só mais uma tentativa.", tam=14, cor=ROXO, negrito=True)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 6 — ALUNO: LOJA, RANKING, MENTORIA
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
cabecalho(s, "Aluno · recompensas e comunidade", "PERFIL: ALUNO", ROXO)
blocos = [
    ("🛍️", "Loja", "Troca GamiCoins por molduras de avatar, privilégios e prêmios. Moedas nunca são retiradas — gastar é escolha.", ROXO),
    ("📊", "Ranking", "Três abas: turma, escola e Evolução (XP da semana, recomeça toda segunda).", ROXO2),
    ("🧭", "Mentoria", "Se você é mentor, ganha XP quando seu colega aprendiz melhora.", VERDE),
    ("🏆", "Conquistas", "Medalhas por dedicação: sequência de dias, projetos, check-ins e mais.", AMBAR),
]
for i, (emo, t, desc, cor) in enumerate(blocos):
    col = i % 2; row = i // 2
    x = Inches(0.6 + col * 6.15); y = Inches(2.4 + row * 2.15)
    retangulo(s, x, y, Inches(5.85), Inches(1.9), CLARO, arred=True)
    circulo_emoji(s, x + Inches(0.3), y + Inches(0.4), Inches(1.0), emo, cor, tam=28)
    texto(s, x + Inches(1.5), y + Inches(0.28), Inches(4.1), Inches(0.5), t, tam=18, cor=ESCURO, negrito=True)
    texto(s, x + Inches(1.5), y + Inches(0.82), Inches(4.15), Inches(1.0), desc, tam=13, cor=CINZA, espaco=1.1)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 7 — PROFESSOR: CRIAR ATIVIDADE
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
cabecalho(s, "Professor · criar uma atividade", "PERFIL: PROFESSOR", ROXO2)
passo(s, Inches(0.6), Inches(2.35), Inches(12.0), 1, "Menu Atividades → Nova atividade",
      "Escolha o tipo (quiz, leitura, projeto, caça-palavras ou grupo), a turma e a disciplina.", cor=ROXO2)
passo(s, Inches(0.6), Inches(3.75), Inches(12.0), 2, "Defina XP e tentativas",
      "Informe o XP da atividade (10–500) e o limite de tentativas (0 = ilimitadas, o padrão sem punição).", cor=ROXO2)
passo(s, Inches(0.6), Inches(5.15), Inches(12.0), 3, "Adicione o conteúdo e publique",
      "Cadastre as questões/etapas/palavras e clique em Publicar. A turma é notificada na hora.", cor=ROXO2)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 8 — PROFESSOR: MODO AO VIVO
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, ESCURO)
logo(s, Inches(0.6), Inches(0.5), escuro_fundo=True)
chip(s, Inches(9.3), Inches(0.55), Inches(3.4), Inches(0.5), "PERFIL: PROFESSOR", ROXO2, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.3), Inches(12), Inches(0.9), "Professor · quiz ao vivo (game show)", tam=30, cor=BRANCO, negrito=True)
passos_av = [
    ("1", "Menu Ao Vivo", "Escolha um quiz já publicado e clique em Gerar PIN."),
    ("2", "Projete o telão", "Um PIN de 6 dígitos aparece. Os alunos entram por ele."),
    ("3", "Conduza as questões", "Clique em Próxima questão; veja respostas e placar ao vivo."),
    ("4", "Encerre e dê XP", "Ao final, o XP entra para cada aluno — e só pode melhorar."),
]
for i, (num, t, desc) in enumerate(passos_av):
    x = Inches(0.6 + i * 3.12)
    faixa_gradiente(s, x, Inches(2.5), Inches(2.9), Inches(3.4), ROXO2, ESCURO)
    circulo_emoji(s, x + Inches(1.05), Inches(2.8), Inches(0.8), num, BRANCO, tam=26)
    tb = s.shapes[-1].text_frame; tb.paragraphs[0].runs[0].font.color.rgb = ROXO2
    texto(s, x + Inches(0.2), Inches(3.75), Inches(2.5), Inches(0.5), t, tam=16, cor=BRANCO, negrito=True, alinha=PP_ALIGN.CENTER)
    texto(s, x + Inches(0.25), Inches(4.25), Inches(2.4), Inches(1.4), desc, tam=13, cor=CLARO, alinha=PP_ALIGN.CENTER, espaco=1.15)
texto(s, Inches(0.6), Inches(6.2), Inches(12), Inches(0.5),
      "💡 É a nossa resposta ao Kahoot — com a diferença de que ninguém perde pontos.", tam=15, cor=LILAS, negrito=True)
rodape(s, n, TOTAL, escuro_fundo=True)

# ══════════════════════════════════════════════════════════════
# SLIDE 9 — PROFESSOR: METAS E MENTORIA
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
cabecalho(s, "Professor · metas e mentorias", "PERFIL: PROFESSOR", ROXO2)
# Metas
retangulo(s, Inches(0.6), Inches(2.3), Inches(5.9), Inches(4.3), CLARO, arred=True)
circulo_emoji(s, Inches(0.95), Inches(2.65), Inches(1.0), "🎯", ROXO2, tam=30)
texto(s, Inches(2.1), Inches(2.8), Inches(4.2), Inches(0.6), "Metas Coletivas", tam=20, cor=ESCURO, negrito=True)
bullets(s, Inches(1.0), Inches(3.85), Inches(5.2), Inches(2.6),
        ["Menu Metas → defina turma, tipo (XP, missões ou check-ins) e alvo.",
         "Escolha a recompensa que CADA aluno ganha ao atingir.",
         "Quando a turma bate a meta, todos recebem — automaticamente."],
        tam=14, cor=ESCURO, cor_marcador=ROXO2, espaco=1.15, gap_depois=10)
# Mentoria
retangulo(s, Inches(6.8), Inches(2.3), Inches(5.9), Inches(4.3), CLARO, arred=True)
circulo_emoji(s, Inches(7.15), Inches(2.65), Inches(1.0), "🤝", VERDE, tam=28)
texto(s, Inches(8.3), Inches(2.8), Inches(4.2), Inches(0.6), "Mentoria entre Pares", tam=20, cor=ESCURO, negrito=True)
bullets(s, Inches(7.2), Inches(3.85), Inches(5.2), Inches(2.6),
        ["Menu Mentorias → forme a dupla mentor + aprendiz da mesma turma.",
         "Defina o % da melhora do aprendiz que vira XP do mentor.",
         "Acompanhe quanto XP de bônus cada mentoria já gerou."],
        tam=14, cor=ESCURO, cor_marcador=VERDE, espaco=1.15, gap_depois=10)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 10 — PROFESSOR: PORTFÓLIO, FAMÍLIA, CLIMA
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
cabecalho(s, "Professor · acompanhamento", "PERFIL: PROFESSOR", ROXO2)
blocos = [
    ("🎨", "Portfólio & Entregas", "Revise trabalhos e etapas de projeto. Você aprova (credita XP) ou devolve para melhorar — nunca reprova."),
    ("👨‍👩‍👧", "Portal da Família", "Publique elogios no feed e converse por mensagem com os responsáveis. Tudo positivo."),
    ("💚", "Clima da turma", "Veja o humor médio dos check-ins e o Índice de Clima. Serve para acolher, nunca punir."),
    ("📈", "Relatórios", "Conclusão por atividade, alunos com baixo engajamento e exportação em CSV."),
]
for i, (emo, t, desc) in enumerate(blocos):
    col = i % 2; row = i // 2
    x = Inches(0.6 + col * 6.15); y = Inches(2.4 + row * 2.15)
    retangulo(s, x, y, Inches(5.85), Inches(1.9), CLARO, arred=True)
    circulo_emoji(s, x + Inches(0.3), y + Inches(0.4), Inches(1.0), emo, ROXO2, tam=26)
    texto(s, x + Inches(1.5), y + Inches(0.25), Inches(4.2), Inches(0.5), t, tam=17, cor=ESCURO, negrito=True)
    texto(s, x + Inches(1.5), y + Inches(0.78), Inches(4.15), Inches(1.05), desc, tam=13, cor=CINZA, espaco=1.1)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 11 — COORDENADOR
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
cabecalho(s, "Coordenador · a escola inteira", "PERFIL: COORDENADOR", VERDE)
texto(s, Inches(0.6), Inches(2.05), Inches(12), Inches(0.5),
      "O coordenador não cria atividades — ele acompanha e exporta:", tam=15, cor=CINZA)
blocos = [
    ("🏫", "Turmas", "Engajamento de cada turma, XP médio e professor responsável."),
    ("🧑‍🏫", "Professores", "Quantas atividades, metas e quizzes ao vivo cada um criou."),
    ("🌟", "Destaques", "Top alunos por XP total, por evolução da semana e por streak."),
    ("💚", "Clima", "Índice de Clima da escola e de cada turma (bem-estar dos alunos)."),
    ("📥", "Exportar CSV", "Baixe planilhas de alunos, turmas e clima para relatórios."),
]
for i, (emo, t, desc) in enumerate(blocos[:4]):
    col = i % 2; row = i // 2
    x = Inches(0.6 + col * 6.15); y = Inches(2.6 + row * 1.65)
    retangulo(s, x, y, Inches(5.85), Inches(1.45), CLARO, arred=True)
    circulo_emoji(s, x + Inches(0.3), y + Inches(0.3), Inches(0.85), emo, VERDE, tam=24)
    texto(s, x + Inches(1.35), y + Inches(0.2), Inches(4.3), Inches(0.5), t, tam=16, cor=ESCURO, negrito=True)
    texto(s, x + Inches(1.35), y + Inches(0.68), Inches(4.3), Inches(0.7), desc, tam=12, cor=CINZA, espaco=1.08)
# faixa CSV embaixo
retangulo(s, Inches(0.6), Inches(5.95), Inches(12.1), Inches(0.85), CLARO, arred=True)
circulo_emoji(s, Inches(0.85), Inches(6.05), Inches(0.65), "📥", VERDE, tam=20)
texto(s, Inches(1.7), Inches(6.15), Inches(10.8), Inches(0.5),
      "Exportar CSV: baixe planilhas de alunos, turmas e clima para seus relatórios oficiais.", tam=14, cor=ESCURO, negrito=True)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 12 — ADMIN
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
cabecalho(s, "Administrador · configuração", "PERFIL: ADMIN", AMBAR)
blocos = [
    ("👥", "Usuários", "Cadastre pessoas, importe alunos por CSV e vincule às turmas, professores e responsáveis."),
    ("🧩", "Módulos", "Ligue/desligue funções (loja, ao vivo, metas, família…) com um clique."),
    ("🛍️", "Loja", "Crie os itens que os alunos compram com GamiCoins e defina os preços."),
    ("🔗", "Integrações", "Configure o Google Classroom informando as credenciais da escola."),
    ("⚙️", "Sistema", "Ajuste XP máximo, bônus de streak, moedas por XP e outros parâmetros."),
    ("💾", "Backup", "Baixe uma cópia completa do banco de dados quando quiser."),
]
for i, (emo, t, desc) in enumerate(blocos):
    col = i % 3; row = i // 3
    x = Inches(0.6 + col * 4.08); y = Inches(2.4 + row * 2.15)
    retangulo(s, x, y, Inches(3.85), Inches(1.9), CLARO, arred=True)
    circulo_emoji(s, x + Inches(0.25), y + Inches(0.3), Inches(0.85), emo, AMBAR, tam=24)
    texto(s, x + Inches(1.2), y + Inches(0.28), Inches(2.5), Inches(0.5), t, tam=16, cor=ESCURO, negrito=True)
    texto(s, x + Inches(0.28), y + Inches(1.0), Inches(3.35), Inches(0.85), desc, tam=12, cor=CINZA, espaco=1.08)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 13 — RESPONSÁVEL
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
cabecalho(s, "Responsável · acompanhar o filho", "PERFIL: RESPONSÁVEL", LILAS)
passo(s, Inches(0.6), Inches(2.5), Inches(6.0), 1, "Veja o progresso",
      "Na tela inicial: XP, nível, sequência de dias e conquistas de cada filho.", cor=LILAS)
passo(s, Inches(0.6), Inches(4.0), Inches(6.0), 2, "Acompanhe o feed",
      "Receba elogios e avisos que o professor publica — sempre positivos.", cor=LILAS)
passo(s, Inches(0.6), Inches(5.5), Inches(6.0), 3, "Converse com a escola",
      "Menu Mensagens: fale diretamente com os professores do seu filho.", cor=LILAS)
retangulo(s, Inches(7.0), Inches(2.5), Inches(5.7), Inches(4.0), CLARO, arred=True)
circulo_emoji(s, Inches(9.35), Inches(2.85), Inches(1.0), "💜", LILAS, tam=30)
texto(s, Inches(7.2), Inches(4.0), Inches(5.3), Inches(2.3),
      "No Gamifica você nunca verá pontuação negativa do seu filho.\n\nTudo o que aparece é progresso — porque o sistema não registra punição.",
      tam=16, cor=ESCURO, alinha=PP_ALIGN.CENTER, espaco=1.2, anchor=MSO_ANCHOR.MIDDLE)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 14 — REGRAS DE OURO
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, ESCURO)
logo(s, Inches(0.6), Inches(0.5), escuro_fundo=True)
chip(s, Inches(9.7), Inches(0.55), Inches(3.0), Inches(0.5), "LEMBRE-SE", ROXO, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.35), Inches(12), Inches(0.9), "As regras de ouro do Gamifica", tam=30, cor=BRANCO, negrito=True)
regras = [
    ("🛡️", "O XP nunca diminui — está garantido na estrutura do sistema."),
    ("📈", "Só o melhor resultado conta. Refazer só melhora, nunca piora."),
    ("🪙", "Moedas só saem por escolha do aluno. Cancelou um resgate? Devolve."),
    ("👨‍👩‍👧", "Tudo que a família vê é positivo. Não existe registro de punição."),
    ("🚀", "O Ranking de Evolução zera toda semana — todos têm nova chance."),
]
for i, (emo, txt) in enumerate(regras):
    y = Inches(2.45 + i * 0.92)
    retangulo(s, Inches(0.6), y, Inches(12.1), Inches(0.78), ESCURO2, arred=True)
    circulo_emoji(s, Inches(0.8), y + Inches(0.09), Inches(0.6), emo, ROXO, tam=20)
    texto(s, Inches(1.65), y + Inches(0.15), Inches(10.8), Inches(0.5), txt, tam=15, cor=BRANCO, negrito=True)
rodape(s, n, TOTAL, escuro_fundo=True)

# ══════════════════════════════════════════════════════════════
# SLIDE 15 — DICAS RÁPIDAS
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
cabecalho(s, "Dicas para começar bem", "BOAS PRÁTICAS")
dicas = [
    ("🔔", "Fique de olho no sino", "As notificações no topo avisam sobre novas missões, conquistas e mensagens."),
    ("🎯", "Comece com uma meta simples", "Uma meta coletiva pequena engaja a turma logo na primeira semana."),
    ("💚", "Incentive o check-in diário", "Um clique por dia constrói o Índice de Clima e rende XP ao aluno."),
    ("🤝", "Forme mentorias cedo", "Duplas de mentoria ajudam quem tem dificuldade e valorizam quem ajuda."),
    ("📱", "Funciona no celular", "O sistema é responsivo — alunos podem jogar pelo próprio telefone."),
    ("🔑", "Troque a senha padrão", "Peça que todos alterem a senha inicial no primeiro acesso."),
]
for i, (emo, t, desc) in enumerate(dicas):
    col = i % 2; row = i // 2
    x = Inches(0.6 + col * 6.15); y = Inches(2.25 + row * 1.55)
    retangulo(s, x, y, Inches(5.85), Inches(1.35), CLARO, arred=True)
    circulo_emoji(s, x + Inches(0.28), y + Inches(0.25), Inches(0.85), emo, ROXO, tam=22)
    texto(s, x + Inches(1.3), y + Inches(0.18), Inches(4.35), Inches(0.5), t, tam=15, cor=ESCURO, negrito=True)
    texto(s, x + Inches(1.3), y + Inches(0.62), Inches(4.4), Inches(0.65), desc, tam=12, cor=CINZA, espaco=1.05)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 16 — ENCERRAMENTO
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, ESCURO)
faixa_gradiente(s, Inches(-2), Inches(-2), Inches(17), Inches(7), ESCURO, ROXO, arred=False)
circulo_emoji(s, Inches(5.85), Inches(1.6), Inches(1.5), "🎮", ROXO, tam=64)
texto(s, Inches(1.0), Inches(3.4), Inches(11.3), Inches(1.0),
      "Pronto para começar!", tam=44, cor=BRANCO, negrito=True, alinha=PP_ALIGN.CENTER)
texto(s, Inches(1.5), Inches(4.6), Inches(10.3), Inches(1.0),
      "Qualquer dúvida, fale direto com o criador do sistema.\nSuporte em português, sem ticket, sem robô.",
      tam=18, cor=CLARO, alinha=PP_ALIGN.CENTER, espaco=1.2)
chip(s, Inches(3.1), Inches(5.9), Inches(3.1), Inches(0.6), "itthrive.com.br/gamifica", CLARO, ROXO, tam=13)
chip(s, Inches(6.5), Inches(5.9), Inches(3.7), Inches(0.6), "prof.flavio.spina@gmail.com", CLARO, ROXO, tam=13)

import os
saida = os.path.join(os.path.dirname(__file__), "Gamifica_Tutorial_de_Uso.pptx")
prs.save(saida)
print("OK:", saida, "·", len(prs.slides._sldIdLst), "slides")
