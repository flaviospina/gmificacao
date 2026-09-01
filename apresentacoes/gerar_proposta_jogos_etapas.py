# -*- coding: utf-8 -*-
"""
Proposta de Novos Jogos por ETAPA (Infantil, Fund. I, Fund. II, Médio).
Jogos que não existem nas plataformas de gamificação brasileiras.
Gera:
  - Gamifica_Proposta_Jogos_Etapas.pptx
  - Gamifica_Proposta_Jogos_Etapas.pdf
"""
import os, re, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from estilo_gamifica import *

AZUL   = RGBColor(0x1C, 0xB0, 0xF6)
TEAL   = RGBColor(0x0E, 0x8A, 0x77)
ROSA   = RGBColor(0xF0, 0x5A, 0x9E)
LARANJA= RGBColor(0xF2, 0x8C, 0x1E)

# ── Etapas (cor + descrição) e seus jogos ─────────────────────
# Cada jogo: emoji, nome, tag, oque, diferente, habilidade, exemplo
ETAPAS = [
 dict(nome="Educação Infantil", faixa="3 a 5 anos", cor=ROSA,
   lema="Sem leitura, muito toque, som e afeto. Foco no socioemocional e sensório — a maior lacuna do mercado BR.",
   jogos=[
     dict(emoji="🫧", nome="Bichinho dos Sentimentos", tag="Socioemocional (SEL)",
          oque="A criança cuida de um bichinho lendo a emoção dele (feliz, triste, com medo) e escolhe como acolher.",
          diferente="Jogo socioemocional digital para o Infantil é quase inexistente no Brasil — e é o que a pesquisa recente mais valida nessa idade.",
          habilidade="BNCC: 'O eu, o outro e o nós' — reconhecer e nomear emoções.",
          exemplo="O bichinho fica com medo do trovão → a criança escolhe abraçar, acender a luz ou chamar ajuda."),
     dict(emoji="🎵", nome="Orquestra dos Bichos", tag="Ritmo e padrões",
          oque="Repete e cria sequências de sons tocando os animais; depois inventa o próprio ritmo.",
          diferente="Trabalha escuta e padrão (base da matemática e da música), fugindo do quiz de resposta certa/errada.",
          habilidade="BNCC: escuta, imaginação e noções de sequência/padrão.",
          exemplo="Sapo–pato–sapo–? → a criança completa o padrão sonoro."),
     dict(emoji="🎬", nome="Teatrinho Mágico", tag="Narrativa criativa",
          oque="Arrasta personagens e cenários para montar uma historinha; o app narra em voz alta o que ela criou.",
          diferente="Criação livre (produção), não seleção — desenvolve oralidade e imaginação sem 'errar'.",
          habilidade="BNCC: oralidade, criatividade e linguagem.",
          exemplo="A criança monta 'a raposa na floresta à noite' e ouve a própria história ganhar vida."),
   ]),
 dict(nome="Fundamental I", faixa="6 a 10 anos", cor=ROXO,
   lema="Além dos jogos já propostos (enigmas, detetive, oficina): simulação viva, convivência e lógica.",
   jogos=[
     dict(emoji="🌱", nome="Guardião do Ecossistema", tag="Simulação de causa-efeito",
          oque="Equilibra um mini-mundo vivo (sol, água, plantas e bichos) e observa as consequências das escolhas.",
          diferente="Pensamento sistêmico e erro produtivo — o oposto do quiz; nenhuma plataforma BR faz simulação viva no Fund. I.",
          habilidade="Ciências: cadeias alimentares, ciclo da água, sustentabilidade.",
          exemplo="Tirar as abelhas → as flores somem → a criança descobre a interdependência."),
     dict(emoji="🤝", nome="Ilha da Convivência", tag="Socioemocional narrativo",
          oque="Resolve pequenas situações de convivência escolhendo atitudes e vendo o que acontece com o grupo.",
          diferente="Socioemocional aplicado (competências gerais da BNCC) — ausente nas plataformas gamificadas nacionais.",
          habilidade="BNCC: empatia, cooperação, resolução de conflitos.",
          exemplo="Um colega ficou de fora da brincadeira → escolher chamar, ignorar ou avisar o professor."),
     dict(emoji="🕹️", nome="Programe o Robô", tag="Pensamento computacional",
          oque="Monta sequências de comandos para o robô cumprir missões que aplicam o conteúdo da aula.",
          diferente="Lógica e algoritmo integrados ao currículo (e sem punição) — vai além do 'quiz gamificado' comum.",
          habilidade="Matemática/lógica: sequência, direção, resolução de problemas.",
          exemplo="Programar o robô para pegar as frações certas no caminho até a saída."),
   ]),
 dict(nome="Fundamental II", faixa="11 a 14 anos", cor=AZUL,
   lema="Adolescentes querem desafio real: argumentar, decidir e investigar com autonomia.",
   jogos=[
     dict(emoji="⚖️", nome="Arena do Debate", tag="Argumentação",
          oque="Recebe uma tese, monta argumentos com evidências, antecipa contra-argumentos e os colegas avaliam.",
          diferente="Competência argumentativa (núcleo da BNCC de Língua Portuguesa) — inexistente nas plataformas de gamificação BR.",
          habilidade="Língua Portuguesa: argumentação, repertório, escuta ativa.",
          exemplo="'Redes sociais deveriam ter idade mínima?' → montar defesa e refutar o outro lado."),
     dict(emoji="🏙️", nome="Prefeito por um Dia", tag="Simulação de gestão",
          oque="Administra uma cidade com orçamento limitado e decisões que afetam saúde, meio ambiente e educação.",
          diferente="Sim-management com trade-offs reais — pensamento sistêmico que nenhum concorrente nacional oferece.",
          habilidade="Geografia, Matemática e Cidadania: recursos, impacto, escolhas públicas.",
          exemplo="Investir em transporte ou em escola? Cada decisão muda os indicadores da cidade."),
     dict(emoji="🔬", nome="Laboratório de Investigação", tag="Método científico",
          oque="Formula hipótese, roda um experimento simulado, coleta dados e tira conclusões — como um cientista.",
          diferente="Investigação de verdade (hipótese→dado→conclusão), não pergunta de múltipla escolha.",
          habilidade="Ciências: método científico, análise de dados, argumentação.",
          exemplo="Testar o que faz o mofo crescer mais rápido variando umidade e luz."),
   ]),
 dict(nome="Ensino Médio", faixa="15 a 17 anos", cor=TEAL,
   lema="Protagonismo, interdisciplinaridade e preparação para o ENEM — com jogos que exigem pensar alto.",
   jogos=[
     dict(emoji="🌐", nome="Caçada Transmídia (ARG)", tag="Investigação interdisciplinar",
          oque="Um mistério que se desenrola por vários 'canais' ao longo de dias; a turma junta pistas e resolve o caso.",
          diferente="Alternate Reality Game educacional — praticamente nenhuma plataforma no mundo (e nenhuma no BR) oferece isso pronto.",
          habilidade="Interdisciplinar: pesquisa, análise crítica, colaboração (perfil ENEM).",
          exemplo="Uma 'notícia falsa' plantada leva os alunos a checar fontes em história, ciências e geografia."),
     dict(emoji="✍️", nome="Arquiteto da Redação", tag="Produção textual (ENEM)",
          oque="Monta a redação peça por peça — tese, argumentos, repertório e proposta — com feedback estruturado em cada parte.",
          diferente="Redação ENEM gamificada e guiada; as plataformas param no quiz e não treinam produção textual.",
          habilidade="Língua Portuguesa: dissertação-argumentativa, coesão, repertório.",
          exemplo="Encaixar um repertório sociocultural válido para sustentar a tese e ganhar XP por coerência."),
     dict(emoji="🏛️", nome="Cúpula das Nações", tag="Negociação e geopolítica",
          oque="Cada aluno representa um país e negocia acordos sobre clima, economia ou conflitos — um 'Modelo ONU' digital.",
          diferente="Role-play de negociação estruturada — não existe em plataforma de gamificação brasileira.",
          habilidade="História, Geografia e Sociologia: geopolítica, negociação, cidadania global.",
          exemplo="Negociar um acordo climático conciliando economia e meio ambiente entre países."),
   ]),
]

DIFERENCIAL = [
 ("Socioemocional (SEL) de verdade", "Só quiz e pontos"),
 ("Simulação e causa-efeito (erro produtivo)", "Resposta certa/errada"),
 ("Argumentação e debate", "Não trabalham"),
 ("ARG / investigação transmídia", "Inexistente"),
 ("Produção (mídia, redação ENEM)", "Só consumo/resposta"),
 ("Cobertura Infantil → Médio", "Foco em Fund. I / quiz"),
 ("Filosofia sem punição", "Rankings que punem"),
]

# ══════════════════════════════════════════════════════════════
#  PPTX
# ══════════════════════════════════════════════════════════════
prs = nova_apresentacao()
TOTAL = 3 + len(ETAPAS) + 2
n = 0

# 1 — CAPA
n += 1
s = slide_vazio(prs); fundo(s, ESCURO)
faixa_gradiente(s, Inches(-2), Inches(4.5), Inches(17), Inches(6), ESCURO, ROXO, arred=False)
chip(s, Inches(0.9), Inches(0.85), Inches(5.2), Inches(0.5), "NOVOS JOGOS · POR ETAPA DE ENSINO", ROXO, BRANCO, tam=13)
circulo_emoji(s, Inches(0.9), Inches(1.7), Inches(1.1), "🎮", ROXO, tam=52)
texto(s, Inches(0.85), Inches(3.0), Inches(11.8), Inches(1.3), "Jogos que o Brasil\nainda não tem", tam=48, cor=BRANCO, negrito=True, espaco=1.0)
texto(s, Inches(0.9), Inches(5.0), Inches(11.6), Inches(1.0),
      "Da Educação Infantil ao Ensino Médio — o diferencial\nfrente às plataformas de gamificação brasileiras.",
      tam=22, cor=CLARO, espaco=1.15)

# 2 — O GAP DO MERCADO
n += 1
s = slide_vazio(prs); fundo(s, BRANCO)
logo(s, Inches(0.6), Inches(0.5)); chip(s, Inches(9.4), Inches(0.55), Inches(3.3), Inches(0.5), "A LACUNA DO MERCADO BR", VERMELHO, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.4), Inches(12.1), Inches(0.9), "O que as plataformas brasileiras fazem — e não fazem", tam=28, cor=ESCURO, negrito=True)
# duas colunas
retangulo(s, Inches(0.6), Inches(2.4), Inches(5.9), Inches(4.1), CLARO, arred=True)
texto(s, Inches(0.9), Inches(2.6), Inches(5.3), Inches(0.5), "Já existe (Educacross, Khan, etc.)", tam=15, cor=CINZA, negrito=True)
bullets(s, Inches(0.9), Inches(3.2), Inches(5.3), Inches(3.0),
        ["Quiz e jogos de treino alinhados à BNCC","Trilhas, missões, pontos e medalhas","Ranking e relatórios de desempenho","Foco em Infantil / Fundamental I","Português, Matemática e alfabetização"],
        tam=14, cor=ESCURO, cor_marcador=CINZA, espaco=1.2, gap_depois=9)
faixa_gradiente(s, Inches(6.8), Inches(2.4), Inches(5.9), Inches(4.1), ROXO, ROXO2)
texto(s, Inches(7.1), Inches(2.6), Inches(5.3), Inches(0.5), "Onde ninguém está (nossa chance)", tam=15, cor=BRANCO, negrito=True)
bullets(s, Inches(7.1), Inches(3.2), Inches(5.3), Inches(3.0),
        ["Socioemocional (SEL) para o Infantil","Simulação e causa-efeito (erro produtivo)","Argumentação, debate e negociação","ARG / investigação transmídia","Produção: mídia e redação ENEM","Cobertura do Infantil ao Médio"],
        tam=14, cor=BRANCO, cor_marcador=CLARO, espaco=1.15, gap_depois=7)
rodape(s, n, TOTAL)

# 3 — VISÃO POR ETAPA
n += 1
s = slide_vazio(prs); fundo(s, ESCURO)
logo(s, Inches(0.6), Inches(0.5), escuro_fundo=True); chip(s, Inches(9.6), Inches(0.55), Inches(3.1), Inches(0.5), "POR ETAPA (BNCC)", VERDE, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.4), Inches(12), Inches(0.9), "12 jogos novos, um portfólio por idade", tam=30, cor=BRANCO, negrito=True)
for i, et in enumerate(ETAPAS):
    x = Inches(0.6 + i*3.12)
    faixa_gradiente(s, x, Inches(2.6), Inches(2.9), Inches(3.6), et["cor"], ESCURO)
    texto(s, x + Inches(0.2), Inches(2.8), Inches(2.5), Inches(0.9), et["nome"], tam=17, cor=BRANCO, negrito=True, alinha=PP_ALIGN.CENTER, espaco=0.95)
    texto(s, x + Inches(0.2), Inches(3.7), Inches(2.5), Inches(0.4), et["faixa"], tam=12, cor=CLARO, alinha=PP_ALIGN.CENTER)
    for k, j in enumerate(et["jogos"]):
        texto(s, x + Inches(0.25), Inches(4.2 + k*0.62), Inches(2.5), Inches(0.6), j["emoji"] + " " + j["nome"], tam=11.5, cor=BRANCO, negrito=True, alinha=PP_ALIGN.CENTER, espaco=0.9)
rodape(s, n, TOTAL, escuro_fundo=True)

# 4..7 — UMA ETAPA POR SLIDE (3 cards)
for et in ETAPAS:
    n += 1
    s = slide_vazio(prs); fundo(s, BRANCO)
    faixa_gradiente(s, Inches(0), Inches(0), Inches(13.333), Inches(1.75), et["cor"], ESCURO, arred=False)
    texto(s, Inches(0.6), Inches(0.35), Inches(9), Inches(0.7), et["nome"], tam=30, cor=BRANCO, negrito=True)
    chip(s, Inches(10.5), Inches(0.5), Inches(2.2), Inches(0.5), et["faixa"], BRANCO, et["cor"], tam=13)
    texto(s, Inches(0.6), Inches(1.15), Inches(12), Inches(0.5), et["lema"], tam=13, cor=CLARO, italico=True)
    for i, j in enumerate(et["jogos"]):
        x = Inches(0.6 + i*4.1)
        retangulo(s, x, Inches(2.05), Inches(3.85), Inches(4.9), CLARO, arred=True)
        circulo_emoji(s, x + Inches(1.35), Inches(2.25), Inches(1.1), j["emoji"], et["cor"], tam=34)
        texto(s, x + Inches(0.2), Inches(3.45), Inches(3.5), Inches(0.7), j["nome"], tam=16, cor=ESCURO, negrito=True, alinha=PP_ALIGN.CENTER, espaco=0.95)
        chip(s, x + Inches(0.75), Inches(3.05), Inches(2.35), Inches(0.38), j["tag"], et["cor"], BRANCO, tam=10)
        def mini(lbl, val, yy):
            texto(s, x + Inches(0.25), yy, Inches(3.4), Inches(0.3), lbl, tam=9.5, cor=et["cor"], negrito=True)
            texto(s, x + Inches(0.25), yy + Inches(0.26), Inches(3.4), Inches(0.9), val, tam=10.5, cor=ESCURO, espaco=1.05)
        mini("O QUE É", j["oque"], Inches(4.15))
        mini("POR QUE É INÉDITO", j["diferente"], Inches(5.35))
        mini("HABILIDADE", j["habilidade"], Inches(6.45)) if False else None
        texto(s, x + Inches(0.25), Inches(6.55), Inches(3.4), Inches(0.35), j["habilidade"], tam=9, cor=CINZA, italico=True, espaco=1.0)
    rodape(s, n, TOTAL)

# 8 — DIFERENCIAL COMPETITIVO
n += 1
s = slide_vazio(prs); fundo(s, BRANCO)
logo(s, Inches(0.6), Inches(0.5)); chip(s, Inches(9.7), Inches(0.55), Inches(3.0), Inches(0.5), "DIFERENCIAL", ESCURO, BRANCO, tam=13)
texto(s, Inches(0.6), Inches(1.35), Inches(12), Inches(0.9), "Gamifica vs. plataformas brasileiras", tam=28, cor=ESCURO, negrito=True)
y0 = Inches(2.35); alt = Inches(0.6)
cols = [Inches(6.4), Inches(3.1), Inches(3.2)]
retangulo(s, Inches(0.6), y0, sum(cols, Emu(0)), alt, ESCURO)
for k, c in enumerate(["Recurso", "Gamifica", "Concorrentes BR"]):
    cx = Inches(0.6) + sum(cols[:k], Emu(0))
    texto(s, cx + Inches(0.15), y0, cols[k]-Inches(0.2), alt, c, tam=13, cor=BRANCO, negrito=True, anchor=MSO_ANCHOR.MIDDLE)
for r, (rec, conc) in enumerate(DIFERENCIAL):
    y = y0 + (r+1)*alt
    if r % 2 == 0: retangulo(s, Inches(0.6), y, sum(cols, Emu(0)), alt, CLARO)
    texto(s, Inches(0.75), y, cols[0]-Inches(0.2), alt, rec, tam=12.5, cor=ESCURO, anchor=MSO_ANCHOR.MIDDLE)
    texto(s, Inches(0.6)+cols[0]+Inches(0.15), y, cols[1]-Inches(0.2), alt, "✓ Sim", tam=13, cor=VERDE, negrito=True, anchor=MSO_ANCHOR.MIDDLE, alinha=PP_ALIGN.CENTER)
    texto(s, Inches(0.6)+cols[0]+cols[1]+Inches(0.15), y, cols[2]-Inches(0.2), alt, conc, tam=12, cor=CINZA, anchor=MSO_ANCHOR.MIDDLE, alinha=PP_ALIGN.CENTER)
rodape(s, n, TOTAL)

# 9 — FECHAMENTO
n += 1
s = slide_vazio(prs); fundo(s, ESCURO)
faixa_gradiente(s, Inches(-2), Inches(-2), Inches(17), Inches(7), ESCURO, ROXO, arred=False)
circulo_emoji(s, Inches(5.85), Inches(1.6), Inches(1.5), "🎮", ROXO, tam=64)
texto(s, Inches(1.0), Inches(3.4), Inches(11.3), Inches(1.0), "Do Infantil ao Médio, o que ninguém tem.", tam=36, cor=BRANCO, negrito=True, alinha=PP_ALIGN.CENTER)
texto(s, Inches(1.0), Inches(4.6), Inches(11.3), Inches(0.9), "Escolham 1 jogo por etapa para um piloto e começamos.", tam=20, cor=LILAS, alinha=PP_ALIGN.CENTER)
chip(s, Inches(4.4), Inches(5.9), Inches(4.5), Inches(0.6), "itthrive.com.br/gamifica", CLARO, ROXO, tam=15)

saida_pptx = os.path.join(os.path.dirname(__file__), "Gamifica_Proposta_Jogos_Etapas.pptx")
prs.save(saida_pptx)
print("PPTX:", saida_pptx, "·", len(prs.slides._sldIdLst), "slides")

# ══════════════════════════════════════════════════════════════
#  PDF
# ══════════════════════════════════════════════════════════════
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm
from reportlab.lib import colors as rc
from reportlab.lib.styles import ParagraphStyle
from reportlab.platypus import (BaseDocTemplate, PageTemplate, Frame, Paragraph,
                                Spacer, Table, TableStyle, KeepTogether, CondPageBreak)

def hx(c): return rc.HexColor('#%02X%02X%02X' % (c[0], c[1], c[2]))
_EMO = re.compile("[\U0001F000-\U0001FAFF\U00002190-\U00002BFF\U00002600-\U000027BF\U0000FE00-\U0000FE0F\U0000200D★☆✓]", re.UNICODE)
def L(t): return re.sub(r"\s{2,}", " ", _EMO.sub("", t.replace("‑","-").replace("–","-"))).strip()

ESC=hx(ESCURO); CIN=rc.HexColor("#6B6B80"); LIN=rc.HexColor("#E5E9F2"); ZEB=rc.HexColor("#FAF9FF")
st_h1=ParagraphStyle("h1",fontName="Helvetica-Bold",fontSize=22,textColor=ESC,leading=25,spaceAfter=3)
st_sub=ParagraphStyle("sub",fontName="Helvetica",fontSize=10.5,textColor=CIN,leading=14,spaceAfter=4)
st_note=ParagraphStyle("note",fontName="Helvetica-Oblique",fontSize=8.5,textColor=CIN,leading=11)
st_p=ParagraphStyle("p",fontName="Helvetica",fontSize=10,textColor=rc.HexColor("#33333f"),leading=14,spaceAfter=3)
st_cab=ParagraphStyle("cab",fontName="Helvetica-Bold",fontSize=8.8,textColor=rc.white,leading=11)
st_cell=ParagraphStyle("cell",fontName="Helvetica",fontSize=8.8,textColor=rc.HexColor("#33333f"),leading=11)
st_cellb=ParagraphStyle("cellb",fontName="Helvetica-Bold",fontSize=8.8,textColor=ESC,leading=11)

def rodape(cnv,doc):
    cnv.saveState(); cnv.setFont("Helvetica",8); cnv.setFillColor(CIN)
    cnv.drawString(2*cm,1.1*cm,"Gamifica · Novos jogos por etapa · diferencial frente às plataformas BR")
    cnv.drawRightString(A4[0]-2*cm,1.1*cm,f"Página {doc.page}")
    cnv.restoreState()

pdf=os.path.join(os.path.dirname(__file__),"Gamifica_Proposta_Jogos_Etapas.pdf")
doc=BaseDocTemplate(pdf,pagesize=A4,leftMargin=1.8*cm,rightMargin=1.8*cm,topMargin=1.5*cm,bottomMargin=1.5*cm)
doc.addPageTemplates([PageTemplate(id="p",frames=[Frame(doc.leftMargin,doc.bottomMargin,doc.width,doc.height)],onPage=rodape)])
W=doc.width
def barra(txt, cor):
    t=Table([[Paragraph(L(txt),ParagraphStyle("b",fontName="Helvetica-Bold",fontSize=14,textColor=rc.white,leading=17))]],colWidths=[W],rowHeights=[0.85*cm])
    t.setStyle(TableStyle([("BACKGROUND",(0,0),(-1,-1),hx(cor)),("LEFTPADDING",(0,0),(-1,-1),12),("VALIGN",(0,0),(-1,-1),"MIDDLE"),("ROUNDEDCORNERS",[6,6,6,6])]))
    return t

flow=[]
flow.append(Paragraph("Gamifica — Novos Jogos por Etapa de Ensino", st_h1))
flow.append(Paragraph("Jogos inovadores que não existem nas plataformas de gamificação brasileiras — do Infantil ao Médio. "
                      "Continuação da proposta 'Além do quiz'.", st_sub))
flow.append(Paragraph("As plataformas nacionais (Educacross, Khan, etc.) concentram-se em quiz e jogos de treino alinhados à BNCC, "
                      "com trilhas, pontos, medalhas e ranking, focados em Infantil/Fundamental I. As lacunas — e a nossa chance — estão em: "
                      "<b>socioemocional no Infantil, simulação, argumentação/debate, ARG/investigação transmídia e produção (mídia e redação)</b>, "
                      "cobrindo todas as etapas e sempre <b>sem punição</b>.", st_p))
flow.append(Spacer(1,4))

for et in ETAPAS:
    flow.append(CondPageBreak(7*cm))
    flow.append(barra(f"{et['nome']}  ·  {et['faixa']}", et["cor"]))
    flow.append(Spacer(1,3))
    flow.append(Paragraph(L(et["lema"]), st_note))
    flow.append(Spacer(1,3))
    linhas=[]
    for j in et["jogos"]:
        linhas.append([j["nome"], j["oque"], j["diferente"], j["habilidade"]])
    dados=[[Paragraph(L(c),st_cab) for c in ["Jogo","O que é","Por que é inédito no BR","Habilidade (BNCC)"]]]
    for row in linhas:
        dados.append([Paragraph(L(str(x)), st_cellb if k==0 else st_cell) for k,x in enumerate(row)])
    t=Table(dados,colWidths=[0.18*W,0.30*W,0.30*W,0.22*W],repeatRows=1)
    stl=[("BACKGROUND",(0,0),(-1,0),hx(et["cor"])),("TOPPADDING",(0,0),(-1,-1),4),("BOTTOMPADDING",(0,0),(-1,-1),4),
         ("LEFTPADDING",(0,0),(-1,-1),5),("RIGHTPADDING",(0,0),(-1,-1),5),("VALIGN",(0,0),(-1,-1),"TOP"),("GRID",(0,0),(-1,-1),0.4,LIN)]
    for r in range(2,len(dados),2): stl.append(("BACKGROUND",(0,r),(-1,r),ZEB))
    t.setStyle(TableStyle(stl))
    flow.append(t); flow.append(Spacer(1,8))

flow.append(CondPageBreak(7*cm))
flow.append(Paragraph("Diferencial competitivo", st_h1))
dif=[[Paragraph(L(c),st_cab) for c in ["Recurso","Gamifica","Plataformas BR"]]]
for rec,conc in DIFERENCIAL:
    dif.append([Paragraph(L(rec),st_cellb),Paragraph("Sim",st_cell),Paragraph(L(conc),st_cell)])
t=Table(dif,colWidths=[0.5*W,0.2*W,0.3*W],repeatRows=1)
stl=[("BACKGROUND",(0,0),(-1,0),ESC),("TOPPADDING",(0,0),(-1,-1),5),("BOTTOMPADDING",(0,0),(-1,-1),5),
     ("LEFTPADDING",(0,0),(-1,-1),6),("VALIGN",(0,0),(-1,-1),"MIDDLE"),("GRID",(0,0),(-1,-1),0.4,LIN),
     ("TEXTCOLOR",(1,1),(1,-1),hx(VERDE)),("FONTNAME",(1,1),(1,-1),"Helvetica-Bold")]
for r in range(2,len(dif),2): stl.append(("BACKGROUND",(0,r),(-1,r),ZEB))
t.setStyle(TableStyle(stl)); flow.append(t)
flow.append(Spacer(1,10))
flow.append(Paragraph("Próximos passos", st_h1))
for tt in ["Escolher com o sócio 1 jogo por etapa para um piloto (4 no total).",
           "Priorizar os de maior diferenciação e menor esforço para validar rápido.",
           "Definir o roadmap de conteúdo por etapa (BNCC) junto com os professores.",
           "Rodar o piloto numa escola parceira e medir engajamento antes de escalar."]:
    flow.append(Paragraph("• " + L(tt), st_p))
flow.append(Spacer(1,8))
flow.append(Paragraph("Base de pesquisa: estudos 2024-2026 sobre jogos socioemocionais na primeira infância (Frontiers, ACM, Taylor & Francis), "
                      "Alternate Reality Games na educação (Learning Guild, ResearchGate, HCIL/UMD) e panorama das plataformas BR (Educacross, Khan Academy).", st_note))

doc.build(flow)
print("PDF:", pdf)
