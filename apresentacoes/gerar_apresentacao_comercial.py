# -*- coding: utf-8 -*-
"""
Deck 1 — Apresentação comercial do Gamifica com gatilhos mentais,
focada no conteúdo EXCLUSIVO que nenhuma plataforma de gamificação tem.
"""
from estilo_gamifica import *

prs = nova_apresentacao()
TOTAL = 13
n = 0

# ══════════════════════════════════════════════════════════════
# SLIDE 1 — CAPA (gatilho: exclusividade + curiosidade)
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, ESCURO)
faixa_gradiente(s, Inches(-2), Inches(4.6), Inches(17), Inches(6), ESCURO, ROXO, arred=False)
# selo topo
chip(s, Inches(0.9), Inches(0.8), Inches(4.4), Inches(0.5),
     "★  ÚNICO NO MERCADO MUNDIAL  ★", ROXO, BRANCO, tam=13)
circulo_emoji(s, Inches(0.9), Inches(1.7), Inches(1.1), "🎮", ROXO, tam=54)
texto(s, Inches(0.85), Inches(3.0), Inches(11.5), Inches(1.4),
      "Gamifica", tam=72, cor=BRANCO, negrito=True)
texto(s, Inches(0.9), Inches(4.35), Inches(11.6), Inches(1.0),
      "A única plataforma de gamificação escolar que\nmotiva SEM PUNIR — e faz o que ninguém mais faz.",
      tam=26, cor=BRANCO, negrito=True, espaco=1.1)
chip(s, Inches(0.9), Inches(5.9), Inches(3.0), Inches(0.55),
     "itthrive.com.br/gamifica", CLARO, ROXO, tam=14)
texto(s, Inches(9.2), Inches(6.75), Inches(3.6), Inches(0.5),
      "Apresentação institucional · 2026", tam=12, cor=LILAS, alinha=PP_ALIGN.RIGHT)

# ══════════════════════════════════════════════════════════════
# SLIDE 2 — A DOR (gatilho: dor / problema)
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
logo(s, Inches(0.6), Inches(0.5))
chip(s, Inches(10.0), Inches(0.55), Inches(2.7), Inches(0.5), "O PROBLEMA", VERMELHO, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.5), Inches(12), Inches(1.0),
      "A gamificação que existe hoje PUNE o aluno.", tam=34, cor=ESCURO, negrito=True)
texto(s, Inches(0.6), Inches(2.5), Inches(12), Inches(0.6),
      "Os dois líderes mundiais dependem estruturalmente de tirar pontos de quem já conquistou:",
      tam=17, cor=CINZA)

card = retangulo(s, Inches(0.6), Inches(3.35), Inches(5.9), Inches(2.6), CLARO, arred=True)
circulo_emoji(s, Inches(0.95), Inches(3.7), Inches(0.9), "💔", RGBColor(0xFE,0xE2,0xE2), tam=30)
texto(s, Inches(2.0), Inches(3.75), Inches(4.3), Inches(0.6), "ClassDojo", tam=22, cor=ESCURO, negrito=True)
texto(s, Inches(2.0), Inches(4.3), Inches(4.3), Inches(0.5), "180+ países", tam=13, cor=CINZA)
bullets(s, Inches(1.0), Inches(4.9), Inches(5.2), Inches(1.0),
        ["Registra pontos NEGATIVOS de comportamento",
         "A criança perde na frente da turma toda"],
        tam=13, cor=ESCURO, cor_marcador=VERMELHO, marcador="✕")

card = retangulo(s, Inches(6.85), Inches(3.35), Inches(5.9), Inches(2.6), CLARO, arred=True)
circulo_emoji(s, Inches(7.2), Inches(3.7), Inches(0.9), "🗡️", RGBColor(0xFE,0xE2,0xE2), tam=28)
texto(s, Inches(8.25), Inches(3.75), Inches(4.3), Inches(0.6), "Classcraft", tam=22, cor=ESCURO, negrito=True)
texto(s, Inches(8.25), Inches(4.3), Inches(4.3), Inches(0.5), "8 milhões de usuários", tam=13, cor=CINZA)
bullets(s, Inches(7.25), Inches(4.9), Inches(5.2), Inches(1.0),
        ["O avatar PERDE vida (HP) por mau comportamento",
         "Errar e falhar viram castigo dentro do jogo"],
        tam=13, cor=ESCURO, cor_marcador=VERMELHO, marcador="✕")

texto(s, Inches(0.6), Inches(6.25), Inches(12.1), Inches(0.6),
      "Resultado: quem mais precisa de estímulo é justamente quem mais é penalizado — e desiste.",
      tam=15, cor=VERMELHO, negrito=True)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 3 — A VIRADA (gatilho: prazer / alívio + razão)
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, ESCURO)
logo(s, Inches(0.6), Inches(0.5), escuro_fundo=True)
chip(s, Inches(10.2), Inches(0.55), Inches(2.5), Inches(0.5), "A VIRADA", VERDE, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.7), Inches(12), Inches(1.6),
      "E se o erro fosse\nsó mais uma tentativa?", tam=44, cor=BRANCO, negrito=True, espaco=1.0)
texto(s, Inches(0.6), Inches(3.7), Inches(11.8), Inches(1.0),
      "No Gamifica, o aluno tenta quantas vezes quiser. Só o melhor resultado conta.\nO XP nunca diminui — está escrito na estrutura do sistema, não é promessa de marketing.",
      tam=18, cor=LILAS, espaco=1.2)
# três selos
selos = [("♾️", "Tentativas\nilimitadas"), ("📈", "Só o melhor\nresultado conta"), ("🛡️", "XP nunca\ndiminui")]
for i, (emo, t) in enumerate(selos):
    x = Inches(0.6 + i * 4.15)
    faixa_gradiente(s, x, Inches(5.1), Inches(3.85), Inches(1.55), ROXO, ROXO2)
    circulo_emoji(s, x + Inches(0.3), Inches(5.35), Inches(1.0), emo, BRANCO, tam=30)
    texto(s, x + Inches(1.45), Inches(5.35), Inches(2.3), Inches(1.1), t,
          tam=17, cor=BRANCO, negrito=True, anchor=MSO_ANCHOR.MIDDLE, espaco=1.0)
rodape(s, n, TOTAL, escuro_fundo=True)

# ══════════════════════════════════════════════════════════════
# SLIDE 4 — GATILHO DE EXCLUSIVIDADE (o "abre-alas" das 4 mecânicas)
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
logo(s, Inches(0.6), Inches(0.5))
chip(s, Inches(9.5), Inches(0.55), Inches(3.2), Inches(0.5), "EXCLUSIVIDADE", ROXO, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.5), Inches(12.1), Inches(1.6),
      "4 mecânicas que NENHUMA outra\nplataforma de gamificação tem.", tam=36, cor=ESCURO, negrito=True, espaco=1.0)
texto(s, Inches(0.6), Inches(3.15), Inches(12), Inches(0.6),
      "Pesquisamos ClassDojo, Classcraft, Kahoot!, Quizizz, Blooket e Gimkit. Nenhum oferece o que vem a seguir:",
      tam=15, cor=CINZA)
mini = [("🚀", "Ranking de Evolução", ROXO), ("🎯", "Metas Coletivas", ROXO2),
        ("🤝", "Mentoria entre Pares", VERDE), ("💚", "Termômetro de Bem-estar", AMBAR)]
for i, (emo, t, cor) in enumerate(mini):
    x = Inches(0.6 + i * 3.12)
    card = retangulo(s, x, Inches(4.0), Inches(2.9), Inches(2.5), CLARO, arred=True)
    circulo_emoji(s, x + Inches(0.9), Inches(4.35), Inches(1.1), emo, cor, tam=34)
    texto(s, x + Inches(0.15), Inches(5.6), Inches(2.6), Inches(0.9), t,
          tam=16, cor=ESCURO, negrito=True, alinha=PP_ALIGN.CENTER, espaco=1.0, anchor=MSO_ANCHOR.TOP)
    chip(s, x + Inches(0.75), Inches(4.05), Inches(1.4), Inches(0.4), f"#{i+1}", cor, BRANCO, tam=12)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDES 5-8 — as 4 mecânicas inéditas, uma por slide
# ══════════════════════════════════════════════════════════════
def slide_mecanica(numero, emoji, titulo, tagline, cor, dor_concorrente, como_funciona, porque_unico):
    global n
    n += 1
    s = slide_vazio(prs)
    fundo(s, BRANCO)
    # coluna lateral colorida
    faixa_gradiente(s, Inches(0), Inches(0), Inches(4.5), Inches(7.5), cor, ESCURO, arred=False)
    circulo_emoji(s, Inches(0.6), Inches(0.7), Inches(1.3), emoji, BRANCO, tam=58)
    chip(s, Inches(0.6), Inches(2.2), Inches(2.9), Inches(0.5), f"INÉDITO · #{numero}", BRANCO, cor, tam=13)
    texto(s, Inches(0.6), Inches(2.95), Inches(3.4), Inches(2.0), titulo,
          tam=32, cor=BRANCO, negrito=True, espaco=1.0)
    texto(s, Inches(0.6), Inches(5.3), Inches(3.4), Inches(1.6), tagline,
          tam=15, cor=CLARO, italico=True, espaco=1.15)
    # conteúdo direito
    texto(s, Inches(5.0), Inches(0.75), Inches(7.8), Inches(0.5),
          "❌  O que o mercado faz", tam=15, cor=VERMELHO, negrito=True)
    texto(s, Inches(5.0), Inches(1.25), Inches(7.7), Inches(0.9), dor_concorrente,
          tam=15, cor=CINZA, espaco=1.15)
    texto(s, Inches(5.0), Inches(2.5), Inches(7.8), Inches(0.5),
          "⚙️  Como funciona no Gamifica", tam=15, cor=ESCURO, negrito=True)
    bullets(s, Inches(5.0), Inches(3.05), Inches(7.7), Inches(2.2), como_funciona,
            tam=15, cor=ESCURO, cor_marcador=cor, espaco=1.1, gap_depois=9)
    box = retangulo(s, Inches(5.0), Inches(5.7), Inches(7.7), Inches(1.25), CLARO, arred=True)
    texto(s, Inches(5.3), Inches(5.85), Inches(7.2), Inches(1.0),
          "💡  " + porque_unico, tam=15, cor=cor, negrito=True, anchor=MSO_ANCHOR.MIDDLE, espaco=1.1)
    rodape(s, n, TOTAL)

slide_mecanica(
    1, "🚀", "Ranking de\nEvolução",
    "\"Toda segunda-feira, todo aluno recomeça a disputa.\"",
    ROXO,
    "O ranking conta o XP acumulado. Quem começa atrás nunca alcança — e desiste de competir.",
    ["Ranking semanal pelo XP GANHO na semana, não pelo total.",
     "O aluno que mais evoluiu aparece no topo — mesmo sendo o último no geral.",
     "Zera toda segunda: cada semana é uma chance nova de brilhar."],
    "Premia esforço e progresso, não a largada. Ninguém fica para trás por definição.")

slide_mecanica(
    2, "🎯", "Metas\nColetivas",
    "\"A turma vence junta — ou ainda-não-venceu junta.\"",
    ROXO2,
    "A competição é individual ou entre equipes: sempre cria perdedores dentro da sala.",
    ["O professor define uma meta para a turma inteira (ex.: 5.000 XP juntos).",
     "Se a turma atinge, TODOS ganham a recompensa.",
     "Se não atinge, ninguém perde nada — só continua tentando."],
    "Transforma colegas em aliados. Cooperação no lugar de rivalidade.")

slide_mecanica(
    3, "🤝", "Mentoria\nentre Pares",
    "\"Ajudar o colega passa a valer XP de verdade.\"",
    VERDE,
    "Nenhuma plataforma recompensa academicamente o ato de um aluno ajudar o outro.",
    ["O professor forma duplas de mentor e aprendiz.",
     "Quando o aprendiz melhora a nota, o mentor ganha % dessa melhora em XP.",
     "Os melhores alunos viram aliados de quem tem dificuldade."],
    "O bom aluno lucra ensinando. O que tem dificuldade ganha um parceiro.")

slide_mecanica(
    4, "💚", "Termômetro\nde Bem-estar",
    "\"O aluno diz como está — e a escola escuta.\"",
    AMBAR,
    "O Classcraft mede 'clima' a partir do comportamento que o professor registra (e pune).",
    ["Check-in diário de humor em 1 clique (5 emojis), valendo XP.",
     "Professor e coordenador veem o clima AGREGADO da turma.",
     "O dado vem do próprio aluno — nunca gera punição, só acolhimento."],
    "Um Índice de Clima construído para cuidar, não para vigiar.")

# ══════════════════════════════════════════════════════════════
# SLIDE 9 — COMPARATIVO (gatilho: autoridade + prova lógica)
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
logo(s, Inches(0.6), Inches(0.5))
chip(s, Inches(9.7), Inches(0.55), Inches(3.0), Inches(0.5), "PROVA DIRETA", ESCURO, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.35), Inches(12), Inches(0.9),
      "Gamifica vs. os líderes de mercado", tam=32, cor=ESCURO, negrito=True)

linhas = [
    ("Critério", "Gamifica", "ClassDojo", "Classcraft", True),
    ("Filosofia de pontuação", "Sem punição", "Pontos negativos", "Perde vida/HP", False),
    ("Ranking de Evolução semanal", "SIM", "Não", "Não", False),
    ("Metas coletivas de turma", "SIM", "Não", "Não", False),
    ("Mentoria entre pares com XP", "SIM", "Não", "Não", False),
    ("Termômetro de bem-estar", "SIM", "Não", "Não", False),
    ("Portal da família positivo", "SIM", "Parcial", "Não", False),
    ("Suporte nativo em português", "SIM", "Traduzido", "Traduzido", False),
]
x0, y0 = Inches(0.6), Inches(2.4)
larguras = [Inches(4.3), Inches(2.9), Inches(2.55), Inches(2.55)]
alt_linha = Inches(0.52)
for r, (c0, c1, c2, c3, cab) in enumerate(linhas):
    y = y0 + r * alt_linha
    if cab:
        retangulo(s, x0, y, sum(larguras, Emu(0)), alt_linha, ESCURO)
    elif r % 2 == 0:
        retangulo(s, x0, y, sum(larguras, Emu(0)), alt_linha, CLARO)
    # destaque coluna Gamifica
    if not cab:
        retangulo(s, x0 + larguras[0], y, larguras[1], alt_linha, RGBColor(0xED,0xE9,0xFE))
    vals = [c0, c1, c2, c3]
    cx = x0
    for ci, val in enumerate(vals):
        cor = BRANCO if cab else (ROXO if ci == 1 else (CINZA if val == "Não" else ESCURO))
        neg = cab or ci == 1
        al = PP_ALIGN.LEFT if ci == 0 else PP_ALIGN.CENTER
        pref = "✓ " if (val == "SIM") else ""
        texto(s, cx + Inches(0.15), y, larguras[ci] - Inches(0.2), alt_linha,
              (pref + val) if val != "SIM" else "✓ SIM",
              tam=13, cor=(VERDE if val == "SIM" and not cab else cor),
              negrito=neg or val == "SIM", alinha=al, anchor=MSO_ANCHOR.MIDDLE)
        cx += larguras[ci]
texto(s, Inches(0.6), Inches(6.75), Inches(12), Inches(0.5),
      "Fonte: relatório comparativo de mercado (julho/2026).", tam=11, cor=CINZA, italico=True)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 10 — PROVA SOCIAL / OCEANO AZUL (gatilho: escassez de concorrência)
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, ESCURO)
logo(s, Inches(0.6), Inches(0.5), escuro_fundo=True)
chip(s, Inches(9.4), Inches(0.55), Inches(3.3), Inches(0.5), "ESPAÇO DESOCUPADO", ROXO, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.6), Inches(12.1), Inches(1.6),
      "Um espaço que ninguém ocupa.", tam=40, cor=BRANCO, negrito=True)
texto(s, Inches(0.6), Inches(3.0), Inches(11.9), Inches(1.2),
      "Nenhum concorrente — nacional ou internacional — assume a filosofia \"sem punição\" como pilar central.\nNo Brasil, não há concorrente direto dedicado a esse nicho.",
      tam=18, cor=LILAS, espaco=1.2)
stats = [("0", "concorrentes\nsem punição"), ("4", "mecânicas\ninéditas"), ("5", "perfis\nintegrados"), ("100%", "em português,\ncom o criador")]
for i, (num, lb) in enumerate(stats):
    x = Inches(0.6 + i * 3.12)
    faixa_gradiente(s, x, Inches(4.6), Inches(2.9), Inches(2.0), ROXO, ROXO2)
    texto(s, x + Inches(0.15), Inches(4.8), Inches(2.6), Inches(0.9), num,
          tam=40, cor=BRANCO, negrito=True, alinha=PP_ALIGN.CENTER)
    texto(s, x + Inches(0.15), Inches(5.75), Inches(2.6), Inches(0.8), lb,
          tam=13, cor=CLARO, alinha=PP_ALIGN.CENTER, espaco=1.0)
rodape(s, n, TOTAL, escuro_fundo=True)

# ══════════════════════════════════════════════════════════════
# SLIDE 11 — TODOS GANHAM (gatilho: pertencimento / benefício por público)
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
logo(s, Inches(0.6), Inches(0.5))
chip(s, Inches(10.1), Inches(0.55), Inches(2.6), Inches(0.5), "TODOS GANHAM", VERDE, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.35), Inches(12), Inches(0.9),
      "Uma plataforma, quatro vitórias.", tam=32, cor=ESCURO, negrito=True)
publicos = [
    ("🎒", "Aluno", "Aprende sem medo de errar. Cada tentativa só melhora — nunca perde.", ROXO),
    ("🧑‍🏫", "Professor", "Menos correção manual, mais engajamento. Vê quem precisa de ajuda na hora.", ROXO2),
    ("🏫", "Coordenação", "Índice de Clima e relatórios prontos para acompanhar a escola inteira.", VERDE),
    ("👨‍👩‍👧", "Família", "Recebe só notícias positivas do filho. Fala direto com o professor.", AMBAR),
]
for i, (emo, t, desc, cor) in enumerate(publicos):
    col = i % 2
    row = i // 2
    x = Inches(0.6 + col * 6.15)
    y = Inches(2.5 + row * 2.15)
    retangulo(s, x, y, Inches(5.85), Inches(1.9), CLARO, arred=True)
    circulo_emoji(s, x + Inches(0.35), y + Inches(0.45), Inches(1.0), emo, cor, tam=30)
    texto(s, x + Inches(1.55), y + Inches(0.3), Inches(4.0), Inches(0.5), t,
          tam=20, cor=ESCURO, negrito=True)
    texto(s, x + Inches(1.55), y + Inches(0.85), Inches(4.1), Inches(0.95), desc,
          tam=13, cor=CINZA, espaco=1.1)
rodape(s, n, TOTAL)

# ══════════════════════════════════════════════════════════════
# SLIDE 12 — CTA (gatilho: ação + antecipação)
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, ESCURO)
faixa_gradiente(s, Inches(-2), Inches(-2), Inches(17), Inches(7), ESCURO, ROXO, arred=False)
circulo_emoji(s, Inches(0.9), Inches(0.9), Inches(1.1), "🎮", ROXO, tam=52)
texto(s, Inches(0.9), Inches(2.3), Inches(11.8), Inches(1.6),
      "Vamos gamificar sua escola\nsem punir ninguém?", tam=44, cor=BRANCO, negrito=True, espaco=1.0)
texto(s, Inches(0.9), Inches(4.3), Inches(11.5), Inches(0.9),
      "Instância dedicada por escola · dados isolados · suporte direto com o criador.",
      tam=18, cor=LILAS, espaco=1.15)
chip(s, Inches(0.9), Inches(5.5), Inches(4.4), Inches(0.7),
     "itthrive.com.br/gamifica", CLARO, ROXO, tam=17)
chip(s, Inches(5.5), Inches(5.5), Inches(4.6), Inches(0.7),
     "prof.flavio.spina@gmail.com", CLARO, ROXO, tam=15)
texto(s, Inches(0.9), Inches(6.6), Inches(11.5), Inches(0.5),
      "Agende uma demonstração e veja as 4 mecânicas exclusivas funcionando ao vivo.",
      tam=14, cor=BRANCO, negrito=True)

# ══════════════════════════════════════════════════════════════
# SLIDE 13 — FECHAMENTO (bordão)
# ══════════════════════════════════════════════════════════════
n += 1
s = slide_vazio(prs)
fundo(s, BRANCO)
circulo_emoji(s, Inches(5.85), Inches(1.9), Inches(1.6), "🎮", ROXO, tam=70)
texto(s, Inches(1.0), Inches(3.7), Inches(11.3), Inches(1.2),
      "Errar faz parte de aprender.", tam=40, cor=ESCURO, negrito=True, alinha=PP_ALIGN.CENTER)
texto(s, Inches(1.0), Inches(4.8), Inches(11.3), Inches(0.9),
      "Punir, não.", tam=40, cor=ROXO, negrito=True, alinha=PP_ALIGN.CENTER)
texto(s, Inches(1.0), Inches(6.0), Inches(11.3), Inches(0.6),
      "Gamifica · gamificação escolar sem punição", tam=15, cor=CINZA, alinha=PP_ALIGN.CENTER)

import os
saida = os.path.join(os.path.dirname(__file__), "Gamifica_Apresentacao_Comercial.pptx")
prs.save(saida)
print("OK:", saida, "·", len(prs.slides.__iter__.__self__._sldIdLst), "slides")
