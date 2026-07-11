# -*- coding: utf-8 -*-
"""
Proposta de Novos Jogos do Gamifica — além do quiz.
Gera:
  - Gamifica_Proposta_Jogos.pptx  (apresentar ao sócio)
  - Gamifica_Proposta_Jogos.pdf   (leitura/impressão)
"""
import os, re, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from estilo_gamifica import *   # helpers + paleta

AZUL   = RGBColor(0x1C, 0xB0, 0xF6)
TEAL   = RGBColor(0x14, 0xA3, 0x8A)
ROSA   = RGBColor(0xF0, 0x5A, 0x9E)
LARANJA= RGBColor(0xF2, 0x8C, 0x1E)

# ── Conteúdo (fonte única para PPTX e PDF) ────────────────────
JOGOS = [
 dict(emoji="🔐", nome="Sala de Enigmas", cor=ROXO, fase="Fase 1", esforco="Médio‑alto", estrelas=5,
      tag="Escape room educativo — o carro‑chefe",
      oque="Uma sala temática com 3–5 cadeados. Cada cadeado abre ao resolver um enigma encadeado que exige conteúdo da matéria. Ao abrir todos, o aluno “escapa” e encontra o segredo. Tem história e pistas escondidas.",
      diferente="Não são perguntas soltas: é uma missão com narrativa e a sensação de “eu resolvi”. É o tipo com MAIOR evidência de eficácia no ensino fundamental.",
      ciencia="Desafio + narrativa + recordar em contexto; versão em dupla/turma aumenta a colaboração.",
      exemplo="Mat. 3º: cadeado 1 = some as idades e ache o código · cadeado 2 = complete a sequência · cadeado 3 = ache a figura escondida → código final.",
      casa="Casa com Metas Coletivas (a turma abre a sala junto) e Mentoria."),
 dict(emoji="🕵️", nome="Detetive Lógico", cor=AZUL, fase="Fase 1", esforco="Médio", estrelas=5,
      tag="Mistério dedutivo",
      oque="Um caso a resolver (“Quem comeu o bolo da festa?”). O aluno recebe pistas/evidências e, cruzando fatos da matéria com lógica de eliminação, descobre o culpado.",
      diferente="Desenvolve raciocínio dedutivo e inferência — quase nenhuma plataforma tem. Foge totalmente do decoreba.",
      ciencia="Pensamento lógico, eliminação de hipóteses e argumentação.",
      exemplo="Ciências 4º: “O suspeito é mamífero, vive na água e não põe ovos” → cruza características → golfinho.",
      casa="Pistas podem ser revistas quantas vezes quiser (sem punição)."),
 dict(emoji="🛠️", nome="Oficina do Criador", cor=VERDE, fase="Fase 1‑2", esforco="Médio‑alto", estrelas=4,
      tag="Construir, não escolher",
      oque="Em vez de marcar a resposta certa, o aluno CONSTRÓI algo válido arrastando peças: um prato equilibrado (Ciências), uma frase (Português), um orçamento/figura (Matemática), uma linha do tempo (História), um mapa rotulado (Geografia).",
      diferente="Criação em vez de seleção — produtivo e aberto, estilo “maker”/Minecraft, mas curricular.",
      ciencia="Aprendizagem por construção; junta exemplo + prática + feedback (menor carga cognitiva); erro produtivo.",
      exemplo="Ciências 2º: montar o prato saudável arrastando frutas, verduras e proteínas nas proporções certas.",
      casa="O professor define as peças e as regras de acerto."),
 dict(emoji="🗺️", nome="Jornada / Mundo", cor=LARANJA, fase="Fase 2", esforco="Médio", estrelas=4,
      tag="Mapa de aventura com narrativa",
      oque="Um mapa com nós de desafio. O aluno é um personagem que avança, desbloqueia regiões e escolhe caminhos, com uma história por trás (“A Expedição da Matemática”).",
      diferente="Transforma a lista de missões numa aventura visual com progressão e escolhas (modelo quest‑based).",
      ciencia="Persona, autonomia e metas curtas e claras — ótimo para foco de alunos neurodivergentes (TDAH, autismo).",
      exemplo="Geografia 5º: atravessar continentes; cada região pede um desafio para liberar a próxima.",
      casa="Os nós reaproveitam atividades já existentes; encaixa no nosso XP/níveis."),
 dict(emoji="🔬", nome="Laboratório Maluco", cor=TEAL, fase="Fase 2‑3", esforco="Alto", estrelas=5,
      tag="Simulador de causa‑e‑efeito",
      oque="Um sandbox onde o aluno mexe em variáveis e observa o resultado: regar/dar sol a uma planta, misturar cores, gerir uma barraquinha, mudar as estações num mapa.",
      diferente="Exploração e descoberta (“e se eu fizer isso?”) — o erro produtivo é a mecânica central. Quase inexistente nas plataformas.",
      ciencia="Ciclo hipótese → teste → observação; experimentação livre e segura.",
      exemplo="Ciências 3º: variar água e luz e ver a planta crescer, murchar ou florir.",
      casa="Sem punição por natureza: testar é o objetivo."),
 dict(emoji="📖", nome="Escolha Sua Aventura", cor=ROSA, fase="Fase 2", esforco="Médio", estrelas=3,
      tag="História ramificada",
      oque="Uma história interativa em que cada decisão aplica um conhecimento; escolhas “menos boas” levam a desvios engraçados — nunca a game over.",
      diferente="Narrativa + decisão significativa + socioemocional; reforça o pilar “sem punição” pela própria mecânica.",
      ciencia="Decisão com consequência imediata e engajamento narrativo.",
      exemplo="Português/História: conduzir um personagem por escolhas que exigem interpretar e aplicar o conteúdo.",
      casa="Ótimo para temas transversais: meio ambiente, convivência, valores."),
]

ROADMAP = [
 ("Fase 1 · agora", ROXO, [
    "🔐 Sala de Enigmas + 🕵️ Detetive Lógico (os dois mais diferenciados)",
    "Biblioteca inicial de casos/salas por ano e disciplina",
    "Integração com XP, Metas Coletivas e Mentoria já existentes",
 ]),
 ("Fase 2", AZUL, [
    "🛠️ Oficina do Criador (arrastar‑e‑soltar) e 🗺️ Jornada/Mundo",
    "📖 Escolha Sua Aventura para temas transversais",
    "Editor para o professor criar seus próprios enigmas e casos",
 ]),
 ("Fase 3", VERDE, [
    "🔬 Laboratório Maluco (simuladores por disciplina)",
    "Banco de conteúdo ampliado (1º ao 9º ano)",
    "Modo cooperativo em turma para os novos jogos",
 ]),
 ("Transversais · em paralelo", LARANJA, [
    "Refino visual concluído (tema Aventura, jogos coloridos)",
    "App/PWA para celular · integração Google Classroom",
    "Analytics avançado + validação com escola piloto",
 ]),
]

# ══════════════════════════════════════════════════════════════
#  PPTX
# ══════════════════════════════════════════════════════════════
prs = nova_apresentacao()
TOTAL = 14
n = 0

def estrelas_txt(k): return "★"*k + "☆"*(5-k)

# 1 — CAPA
n += 1
s = slide_vazio(prs); fundo(s, ESCURO)
faixa_gradiente(s, Inches(-2), Inches(4.4), Inches(17), Inches(6), ESCURO, ROXO, arred=False)
chip(s, Inches(0.9), Inches(0.85), Inches(4.6), Inches(0.5), "PROPOSTA DE NOVOS JOGOS", ROXO, BRANCO, tam=13)
circulo_emoji(s, Inches(0.9), Inches(1.7), Inches(1.1), "🎮", ROXO, tam=52)
texto(s, Inches(0.85), Inches(3.0), Inches(11.8), Inches(1.3), "Além do quiz", tam=60, cor=BRANCO, negrito=True)
texto(s, Inches(0.9), Inches(4.3), Inches(11.6), Inches(1.0),
      "Jogos diferentes, inovadores e desafiantes —\nque fazem o aluno pensar, não só responder.",
      tam=25, cor=CLARO, espaco=1.15)
chip(s, Inches(0.9), Inches(6.0), Inches(4.4), Inches(0.55), "Gamifica · para apresentar ao sócio", CLARO, ROXO, tam=14)

# 2 — INSIGHT
n += 1
s = slide_vazio(prs); fundo(s, BRANCO)
logo(s, Inches(0.6), Inches(0.5)); chip(s, Inches(10.1), Inches(0.55), Inches(2.6), Inches(0.5), "O INSIGHT", VERMELHO, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.5), Inches(12.1), Inches(1.6),
      "Os jogos “simples” viraram commodity.", tam=34, cor=ESCURO, negrito=True)
texto(s, Inches(0.6), Inches(2.7), Inches(12), Inches(0.6),
      "Quiz, caça‑palavras, forca, jogo da memória e associação estão em TODAS as plataformas:", tam=17, cor=CINZA)
simples = ["❓ Quiz", "🔤 Caça‑palavras", "🎯 Forca", "🧠 Memória", "🔗 Associação", "🃏 Flashcards"]
for i, t in enumerate(simples):
    x = Inches(0.6 + (i%3)*4.1); y = Inches(3.4 + (i//3)*1.0)
    chip(s, x, y, Inches(3.8), Inches(0.7), t, CLARO, ESCURO, tam=16)
texto(s, Inches(0.6), Inches(5.8), Inches(12.1), Inches(1.0),
      "Fazer “mais do mesmo” não diferencia o Gamifica. Precisamos de jogos que a concorrência não tem —\ne que a pesquisa mostra serem mais eficazes.",
      tam=17, cor=VERMELHO, negrito=True, espaco=1.2)
rodape(s, n, TOTAL)

# 3 — O QUE A PESQUISA DIZ
n += 1
s = slide_vazio(prs); fundo(s, ESCURO)
logo(s, Inches(0.6), Inches(0.5), escuro_fundo=True); chip(s, Inches(9.7), Inches(0.55), Inches(3.0), Inches(0.5), "BASE CIENTÍFICA", VERDE, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.9), "O que funciona MELHOR que o quiz", tam=32, cor=BRANCO, negrito=True)
achados = [
 ("🔓", "Escape / mistério", "Maior evidência no fundamental: colaboração, motivação e desafio."),
 ("🛠️", "Criação (construir)", "Aprender fazendo retém mais que só selecionar a resposta."),
 ("📖", "Narrativa com decisão", "Escolha com consequência engaja e dá sentido ao conteúdo."),
 ("🔁", "Erro produtivo", "Falhar e tentar de novo gera criatividade — combina com o nosso “sem punição”."),
]
for i, (emo, t, d) in enumerate(achados):
    y = Inches(2.5 + i*1.05)
    retangulo(s, Inches(0.6), y, Inches(12.1), Inches(0.9), ESCURO2, arred=True)
    circulo_emoji(s, Inches(0.8), y + Inches(0.15), Inches(0.6), emo, ROXO, tam=20)
    texto(s, Inches(1.6), y + Inches(0.1), Inches(3.4), Inches(0.7), t, tam=16, cor=BRANCO, negrito=True, anchor=MSO_ANCHOR.MIDDLE)
    texto(s, Inches(5.1), y + Inches(0.1), Inches(7.4), Inches(0.7), d, tam=14, cor=LILAS, anchor=MSO_ANCHOR.MIDDLE)
rodape(s, n, TOTAL, escuro_fundo=True)

# 4 — OVERVIEW dos 6 jogos
n += 1
s = slide_vazio(prs); fundo(s, BRANCO)
logo(s, Inches(0.6), Inches(0.5)); chip(s, Inches(9.9), Inches(0.55), Inches(2.8), Inches(0.5), "NOSSA APOSTA", ROXO, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.35), Inches(12), Inches(0.9), "6 jogos que ninguém oferece assim", tam=32, cor=ESCURO, negrito=True)
for i, j in enumerate(JOGOS):
    x = Inches(0.6 + (i%3)*4.1); y = Inches(2.4 + (i//3)*2.35)
    retangulo(s, x, y, Inches(3.85), Inches(2.1), CLARO, arred=True)
    circulo_emoji(s, x + Inches(0.28), y + Inches(0.28), Inches(0.85), j["emoji"], j["cor"], tam=24)
    texto(s, x + Inches(1.2), y + Inches(0.32), Inches(2.5), Inches(0.9), j["nome"], tam=16, cor=ESCURO, negrito=True, espaco=0.95)
    texto(s, x + Inches(0.3), y + Inches(1.25), Inches(3.3), Inches(0.8), j["tag"], tam=12, cor=CINZA, espaco=1.05)
    chip(s, x + Inches(0.3), y + Inches(1.62), Inches(1.7), Inches(0.36), j["fase"], j["cor"], BRANCO, tam=10)
rodape(s, n, TOTAL)

# 5..10 — UM SLIDE POR JOGO
for j in JOGOS:
    n += 1
    s = slide_vazio(prs); fundo(s, BRANCO)
    faixa_gradiente(s, Inches(0), Inches(0), Inches(4.5), Inches(7.5), j["cor"], ESCURO, arred=False)
    circulo_emoji(s, Inches(0.6), Inches(0.7), Inches(1.3), j["emoji"], BRANCO, tam=58)
    chip(s, Inches(0.6), Inches(2.2), Inches(2.5), Inches(0.5), j["fase"], BRANCO, j["cor"], tam=12)
    texto(s, Inches(0.6), Inches(2.9), Inches(3.4), Inches(1.6), j["nome"], tam=30, cor=BRANCO, negrito=True, espaco=1.0)
    texto(s, Inches(0.6), Inches(5.0), Inches(3.4), Inches(1.2), j["tag"], tam=15, cor=CLARO, italico=True, espaco=1.15)
    texto(s, Inches(0.6), Inches(6.4), Inches(3.4), Inches(0.6), "Inovação  " + estrelas_txt(j["estrelas"]), tam=14, cor=BRANCO, negrito=True)
    # direita
    def bloco(titulo, txt, y, cor_t=ESCURO):
        texto(s, Inches(5.0), y, Inches(7.8), Inches(0.4), titulo, tam=13, cor=j["cor"], negrito=True)
        texto(s, Inches(5.0), y + Inches(0.4), Inches(7.7), Inches(1.0), txt, tam=14, cor=cor_t, espaco=1.12)
    bloco("O QUE É", j["oque"], Inches(0.7))
    bloco("POR QUE É DIFERENTE", j["diferente"], Inches(2.25))
    bloco("CIÊNCIA POR TRÁS", j["ciencia"], Inches(3.7))
    bloco("EXEMPLO", j["exemplo"], Inches(4.95))
    box = retangulo(s, Inches(5.0), Inches(6.25), Inches(7.8), Inches(0.85), CLARO, arred=True)
    texto(s, Inches(5.25), Inches(6.35), Inches(7.3), Inches(0.7), "💡 " + j["casa"] + "   ·   Esforço: " + j["esforco"],
          tam=13, cor=j["cor"], negrito=True, anchor=MSO_ANCHOR.MIDDLE, espaco=1.05)
    rodape(s, n, TOTAL)

# 11 — COMPARATIVO
n += 1
s = slide_vazio(prs); fundo(s, BRANCO)
logo(s, Inches(0.6), Inches(0.5)); chip(s, Inches(10.0), Inches(0.55), Inches(2.7), Inches(0.5), "COMPARATIVO", ESCURO, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.35), Inches(12), Inches(0.9), "Visão geral das propostas", tam=30, cor=ESCURO, negrito=True)
cols = [Inches(4.3), Inches(2.2), Inches(3.6), Inches(2.0)]
cab = ["Jogo", "Inovação", "Desenvolve", "Esforço"]
linhas = [[j["emoji"]+" "+j["nome"], estrelas_txt(j["estrelas"]), j["tag"], j["esforco"]] for j in JOGOS]
y0 = Inches(2.35); alt = Inches(0.62)
retangulo(s, Inches(0.6), y0, sum(cols, Emu(0)), alt, ESCURO)
cx = Inches(0.6)
for k, c in enumerate(cab):
    texto(s, cx + Inches(0.12), y0, cols[k]-Inches(0.2), alt, c, tam=13, cor=BRANCO, negrito=True, anchor=MSO_ANCHOR.MIDDLE)
    cx += cols[k]
for r, row in enumerate(linhas):
    y = y0 + (r+1)*alt
    if r % 2 == 0: retangulo(s, Inches(0.6), y, sum(cols, Emu(0)), alt, CLARO)
    cx = Inches(0.6)
    for k, val in enumerate(row):
        cor = JOGOS[r]["cor"] if k == 0 else (AMBAR if k == 1 else ESCURO)
        texto(s, cx + Inches(0.12), y, cols[k]-Inches(0.2), alt, val, tam=12, cor=cor, negrito=(k<=1), anchor=MSO_ANCHOR.MIDDLE)
        cx += cols[k]
rodape(s, n, TOTAL)

# 12 — RECOMENDAÇÃO
n += 1
s = slide_vazio(prs); fundo(s, ESCURO)
logo(s, Inches(0.6), Inches(0.5), escuro_fundo=True); chip(s, Inches(9.6), Inches(0.55), Inches(3.1), Inches(0.5), "RECOMENDAÇÃO", VERDE, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.6), Inches(12.1), Inches(1.0), "Por onde começar (Fase 1)", tam=36, cor=BRANCO, negrito=True)
for i, j in enumerate(JOGOS[:2]):
    x = Inches(0.6 + i*6.15)
    faixa_gradiente(s, x, Inches(2.7), Inches(5.85), Inches(3.0), j["cor"], ESCURO)
    circulo_emoji(s, x + Inches(0.35), Inches(3.05), Inches(1.1), j["emoji"], BRANCO, tam=34)
    texto(s, x + Inches(1.6), Inches(3.15), Inches(4.0), Inches(0.9), j["nome"], tam=22, cor=BRANCO, negrito=True, espaco=0.95)
    texto(s, x + Inches(0.4), Inches(4.3), Inches(5.1), Inches(1.2), j["diferente"], tam=14, cor=CLARO, espaco=1.15)
texto(s, Inches(0.6), Inches(6.0), Inches(12.1), Inches(1.0),
      "São os dois de maior diferenciação, com esforço razoável, e ambos reforçam o nosso pilar “sem punição”.\nJuntos, posicionam o Gamifica como a plataforma dos jogos que fazem pensar.",
      tam=16, cor=LILAS, espaco=1.2)
rodape(s, n, TOTAL, escuro_fundo=True)

# 13 — ROADMAP
n += 1
s = slide_vazio(prs); fundo(s, BRANCO)
logo(s, Inches(0.6), Inches(0.5)); chip(s, Inches(9.3), Inches(0.55), Inches(3.4), Inches(0.5), "CONTINUAÇÃO DO PROJETO", ROXO, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.3), Inches(12), Inches(0.9), "Roadmap de continuação", tam=30, cor=ESCURO, negrito=True)
for i, (titulo, cor, itens) in enumerate(ROADMAP):
    x = Inches(0.6 + (i%2)*6.15); y = Inches(2.25 + (i//2)*2.35)
    retangulo(s, x, y, Inches(5.85), Inches(2.15), CLARO, arred=True)
    chip(s, x + Inches(0.25), y + Inches(0.22), Inches(3.4), Inches(0.5), titulo, cor, BRANCO, tam=13)
    bullets(s, x + Inches(0.3), y + Inches(0.85), Inches(5.3), Inches(1.2), itens, tam=12.5, cor=ESCURO, cor_marcador=cor, espaco=1.08, gap_depois=5)
rodape(s, n, TOTAL)

# 14 — FECHAMENTO
n += 1
s = slide_vazio(prs); fundo(s, ESCURO)
faixa_gradiente(s, Inches(-2), Inches(-2), Inches(17), Inches(7), ESCURO, ROXO, arred=False)
circulo_emoji(s, Inches(5.85), Inches(1.7), Inches(1.5), "🎮", ROXO, tam=64)
texto(s, Inches(1.0), Inches(3.5), Inches(11.3), Inches(1.0),
      "Jogos que fazem o aluno pensar.", tam=40, cor=BRANCO, negrito=True, alinha=PP_ALIGN.CENTER)
texto(s, Inches(1.0), Inches(4.7), Inches(11.3), Inches(0.9),
      "Escolham os jogos da Fase 1 e começamos a construir.", tam=20, cor=LILAS, alinha=PP_ALIGN.CENTER)
chip(s, Inches(4.4), Inches(5.9), Inches(4.5), Inches(0.6), "itthrive.com.br/gamifica", CLARO, ROXO, tam=15)

saida_pptx = os.path.join(os.path.dirname(__file__), "Gamifica_Proposta_Jogos.pptx")
prs.save(saida_pptx)
print("PPTX:", saida_pptx, "·", len(prs.slides._sldIdLst), "slides")

# ══════════════════════════════════════════════════════════════
#  PDF (reportlab) — mesmo conteúdo, texto limpo
# ══════════════════════════════════════════════════════════════
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm
from reportlab.lib import colors as rc
from reportlab.lib.styles import ParagraphStyle
from reportlab.platypus import (BaseDocTemplate, PageTemplate, Frame, Paragraph,
                                Spacer, Table, TableStyle, KeepTogether, CondPageBreak)

def hx(c): return rc.HexColor('#%02X%02X%02X' % (c[0], c[1], c[2]))
_EMO = re.compile("[\U0001F000-\U0001FAFF\U00002190-\U00002BFF\U00002600-\U000027BF\U0000FE00-\U0000FE0F\U0000200D⭐★☆]", re.UNICODE)
def limpa(t):
    t = t.replace("‑", "-").replace("‐", "-")  # hífens especiais → normal
    return re.sub(r"\s{2,}", " ", _EMO.sub("", t)).strip()

PROXO=hx(ROXO); PESC=hx(ESCURO); PCINZA=rc.HexColor("#6B6B80"); PCLARO=rc.HexColor("#F1EEFE"); PLINHA=rc.HexColor("#E5E9F2")
st_h1  = ParagraphStyle("h1", fontName="Helvetica-Bold", fontSize=24, textColor=PESC, leading=27, spaceAfter=3)
st_sub = ParagraphStyle("sub", fontName="Helvetica", fontSize=11, textColor=PCINZA, leading=15, spaceAfter=4)
st_p   = ParagraphStyle("p", fontName="Helvetica", fontSize=10.5, textColor=rc.HexColor("#33333f"), leading=15, spaceAfter=3)
st_jt  = ParagraphStyle("jt", fontName="Helvetica-Bold", fontSize=15, textColor=rc.white, leading=18)
st_lbl = ParagraphStyle("lbl", fontName="Helvetica-Bold", fontSize=8.5, textColor=PROXO, leading=11, spaceBefore=3)
st_val = ParagraphStyle("val", fontName="Helvetica", fontSize=10, textColor=rc.HexColor("#33333f"), leading=13)
st_sec = ParagraphStyle("sec", fontName="Helvetica-Bold", fontSize=13, textColor=PESC, leading=16, spaceBefore=6, spaceAfter=4)

def rodape_pdf(canvas, doc):
    canvas.saveState(); canvas.setFont("Helvetica", 8); canvas.setFillColor(PCINZA)
    canvas.drawString(2*cm, 1.1*cm, "Gamifica · Proposta de novos jogos · além do quiz")
    canvas.drawRightString(A4[0]-2*cm, 1.1*cm, f"Página {doc.page}")
    canvas.restoreState()

pdf_path = os.path.join(os.path.dirname(__file__), "Gamifica_Proposta_Jogos.pdf")
doc = BaseDocTemplate(pdf_path, pagesize=A4, leftMargin=2*cm, rightMargin=2*cm, topMargin=1.6*cm, bottomMargin=1.6*cm)
doc.addPageTemplates([PageTemplate(id="p", frames=[Frame(doc.leftMargin, doc.bottomMargin, doc.width, doc.height)], onPage=rodape_pdf)])
W = doc.width
flow = []
flow.append(Paragraph("Gamifica — Novos Jogos: além do quiz", st_h1))
flow.append(Paragraph("Proposta de jogos diferentes, inovadores e desafiantes · para apresentar ao sócio", st_sub))
flow.append(Spacer(1, 6))
flow.append(Paragraph("<b>O insight:</b> quiz, caça-palavras, forca, memória e associação são commodity — estão em todas as plataformas. "
                      "A pesquisa de aprendizagem baseada em jogos mostra que <b>escape/mistério, criação, narrativa com decisão e simulação</b> "
                      "engajam e retêm mais no ensino fundamental. É por aí que o Gamifica se diferencia.", st_p))
flow.append(Spacer(1, 8))

def barra(nome, emoji, cor):
    t = Table([[Paragraph(limpa(nome), st_jt)]], colWidths=[W], rowHeights=[0.8*cm])
    t.setStyle(TableStyle([("BACKGROUND",(0,0),(-1,-1),hx(cor)),("LEFTPADDING",(0,0),(-1,-1),12),("VALIGN",(0,0),(-1,-1),"MIDDLE"),("ROUNDEDCORNERS",[6,6,6,6])]))
    return t

st_tag = ParagraphStyle("tag", fontName="Helvetica-Oblique", fontSize=10, textColor=PCINZA, leading=13, spaceBefore=2, spaceAfter=2)

def linha_campo(lbl, val):
    return Table([[Paragraph(lbl, st_lbl), Paragraph(limpa(val), st_val)]], colWidths=[3.2*cm, W-3.2*cm],
                 style=TableStyle([("VALIGN",(0,0),(-1,-1),"TOP"),("TOPPADDING",(0,0),(-1,-1),2),("BOTTOMPADDING",(0,0),(-1,-1),2)]))

flow.append(Paragraph("As 6 propostas", st_sec))
for j in JOGOS:
    bloco = [barra(f"{j['nome']}   ·   {j['fase']}", j["emoji"], j["cor"]), Spacer(1,3),
             Paragraph(limpa(j["tag"]) + "  —  Inovação: " + str(j["estrelas"]) + "/5  ·  Esforço: " + limpa(j["esforco"]), st_tag),
             linha_campo("O que é", j["oque"]),
             linha_campo("Diferencial", j["diferente"]),
             linha_campo("Ciência", j["ciencia"]),
             linha_campo("Exemplo", j["exemplo"]),
             linha_campo("Encaixe", j["casa"]),
             Spacer(1,8)]
    flow.append(CondPageBreak(5.5*cm))
    flow.append(KeepTogether(bloco))

flow.append(CondPageBreak(6*cm))
flow.append(Paragraph("Recomendação — Fase 1", st_sec))
flow.append(Paragraph("Começar por <b>Sala de Enigmas</b> + <b>Detetive Lógico</b>: os dois de maior diferenciação, esforço razoável e "
                      "ambos reforçam o pilar “sem punição”. Juntos, posicionam o Gamifica como a plataforma dos jogos que fazem pensar.", st_p))
flow.append(Spacer(1, 8))
flow.append(Paragraph("Roadmap de continuação do projeto", st_sec))
for titulo, cor, itens in ROADMAP:
    flow.append(barra(titulo, "", cor)); flow.append(Spacer(1,3))
    for it in itens:
        flow.append(Paragraph("• " + limpa(it), st_p))
    flow.append(Spacer(1,6))

doc.build(flow)
print("PDF:", pdf_path)
