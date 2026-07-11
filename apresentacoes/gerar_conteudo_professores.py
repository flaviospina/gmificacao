# -*- coding: utf-8 -*-
"""
Gera o material de revisão para os professores, a partir do MESMO conteúdo
usado na biblioteca (importa gerar_biblioteca_caca), garantindo que bate 100%
com o que está no sistema:
  - Gamifica_Cacapalavras_Conteudo.csv   (planilha para editar)
  - Gamifica_Cacapalavras_Conteudo.pdf   (para ler/imprimir)
"""
import os, csv
import sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from gerar_biblioteca_caca import CONTEUDO, DISC_ICON, norm

AQUI = os.path.dirname(__file__)
ORDEM_DISC = ["Matemática","Português","Inglês","Educação Física","Ciências","Artes","Geografia","História"]

# ── Paleta (mesma do app) ─────────────────────────────────────
from reportlab.lib import colors
ROXO   = colors.HexColor("#7C6EF0")
ROXO2  = colors.HexColor("#A855F7")
ESCURO = colors.HexColor("#1A1A2E")
CLARO  = colors.HexColor("#F1EEFE")
CINZA  = colors.HexColor("#6B6B80")
LINHA  = colors.HexColor("#E5E9F2")
ZEBRA  = colors.HexColor("#FAF9FF")

# ══════════════════════════════════════════════════════════════
# 1) CSV
# ══════════════════════════════════════════════════════════════
csv_path = os.path.join(AQUI, "Gamifica_Cacapalavras_Conteudo.csv")
with open(csv_path, "w", encoding="utf-8-sig", newline="") as f:
    w = csv.writer(f, delimiter=";")
    w.writerow(["Ano", "Disciplina", "Palavra (na grade)", "Dica"])
    for ano in range(1, 6):
        for disc in ORDEM_DISC:
            for palavra, dica in CONTEUDO[ano][disc]:
                w.writerow([f"{ano}º Ano", disc, norm(palavra), dica])
print("CSV:", csv_path)

# ══════════════════════════════════════════════════════════════
# 2) PDF
# ══════════════════════════════════════════════════════════════
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm, mm
from reportlab.lib.enums import TA_LEFT, TA_CENTER
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import (BaseDocTemplate, PageTemplate, Frame, Paragraph,
                                Spacer, Table, TableStyle, KeepTogether, PageBreak,
                                CondPageBreak)
import re

# O PDF (impressão) usa Helvetica, que não tem glifos de emoji.
# Removemos os emojis do texto — eles continuam no app e no CSV.
_EMOJI = re.compile(
    "[\U0001F000-\U0001FAFF\U00002190-\U00002BFF\U00002600-\U000027BF"
    "\U0001F1E6-\U0001F1FF\U0000FE00-\U0000FE0F\U00002B00-\U00002BFF\U0000200D]", flags=re.UNICODE)
def limpa(txt):
    return re.sub(r"\s{2,}", " ", _EMOJI.sub("", txt)).strip()

pdf_path = os.path.join(AQUI, "Gamifica_Cacapalavras_Conteudo.pdf")
FONT = "Helvetica"; FONTB = "Helvetica-Bold"

ss = getSampleStyleSheet()
st_titulo   = ParagraphStyle("t", parent=ss["Title"], fontName=FONTB, fontSize=24, textColor=ESCURO, spaceAfter=2, leading=27)
st_sub      = ParagraphStyle("s", fontName=FONT, fontSize=11, textColor=CINZA, spaceAfter=2, leading=15)
st_ano      = ParagraphStyle("a", fontName=FONTB, fontSize=16, textColor=colors.white, leading=20)
st_disc     = ParagraphStyle("d", fontName=FONTB, fontSize=11.5, textColor=ROXO, spaceBefore=6, spaceAfter=3, leading=14)
st_pal      = ParagraphStyle("p", fontName=FONTB, fontSize=10, textColor=ESCURO, leading=12)
st_dica     = ParagraphStyle("di", fontName=FONT, fontSize=10, textColor=colors.HexColor("#3b3b4f"), leading=12.5)
st_cab      = ParagraphStyle("cab", fontName=FONTB, fontSize=8.5, textColor=CINZA, leading=10)
st_intro    = ParagraphStyle("in", fontName=FONT, fontSize=10.5, textColor=colors.HexColor("#3b3b4f"), leading=15, spaceAfter=5)

def cabecalho_rodape(canvas, doc):
    canvas.saveState()
    # rodapé
    canvas.setFont(FONT, 8)
    canvas.setFillColor(CINZA)
    canvas.drawString(2*cm, 1.1*cm, "Gamifica · Caça-palavras do Fundamental I · conteúdo para revisão dos professores")
    canvas.drawRightString(A4[0]-2*cm, 1.1*cm, f"Página {doc.page}")
    canvas.restoreState()

def barra_ano(ano):
    """Faixa colorida com o nome do ano (tabela de 1 célula)."""
    t = Table([[Paragraph(f"{ano}º Ano", st_ano)]], colWidths=[A4[0]-4*cm], rowHeights=[0.85*cm])
    t.setStyle(TableStyle([
        ("BACKGROUND",(0,0),(-1,-1), ROXO),
        ("LEFTPADDING",(0,0),(-1,-1), 12),
        ("VALIGN",(0,0),(-1,-1),"MIDDLE"),
        ("ROUNDEDCORNERS",[6,6,6,6]),
    ]))
    return t

def tabela_disc(disc, itens):
    cabeca = Paragraph(disc, st_disc)
    linhas = [[Paragraph("PALAVRA", st_cab), Paragraph("DICA (o que o aluno lê)", st_cab)]]
    for palavra, dica in itens:
        linhas.append([Paragraph(norm(palavra), st_pal), Paragraph(limpa(dica), st_dica)])
    t = Table(linhas, colWidths=[3.2*cm, A4[0]-4*cm-3.2*cm-0.2*cm])
    style = [
        ("BACKGROUND",(0,0),(-1,0), CLARO),
        ("LINEBELOW",(0,0),(-1,0), 0.6, ROXO),
        ("TOPPADDING",(0,0),(-1,-1), 4),
        ("BOTTOMPADDING",(0,0),(-1,-1), 4),
        ("LEFTPADDING",(0,0),(-1,-1), 7),
        ("RIGHTPADDING",(0,0),(-1,-1), 7),
        ("VALIGN",(0,0),(-1,-1),"MIDDLE"),
        ("LINEBELOW",(0,1),(-1,-2), 0.4, LINHA),
        ("BOX",(0,0),(-1,-1), 0.6, LINHA),
    ]
    for r in range(1, len(linhas)):
        if r % 2 == 0:
            style.append(("BACKGROUND",(0,r),(-1,r), ZEBRA))
    t.setStyle(TableStyle(style))
    return KeepTogether([cabeca, t, Spacer(1, 5)])

# monta o documento
doc = BaseDocTemplate(pdf_path, pagesize=A4,
                      leftMargin=2*cm, rightMargin=2*cm, topMargin=1.6*cm, bottomMargin=1.6*cm)
frame = Frame(doc.leftMargin, doc.bottomMargin, doc.width, doc.height, id="f")
doc.addPageTemplates([PageTemplate(id="p", frames=[frame], onPage=cabecalho_rodape)])

flow = []
# Capa/cabeçalho
flow.append(Paragraph("Gamifica — Caça-palavras", st_titulo))
flow.append(Paragraph("Banco de conteúdo do Fundamental I · 5 anos × 8 disciplinas · 241 palavras com dicas", st_sub))
flow.append(Spacer(1, 8))
total = sum(len(CONTEUDO[a][d]) for a in CONTEUDO for d in CONTEUDO[a])
flow.append(Paragraph(
    "Este material lista todas as palavras e dicas dos caça-palavras já cadastrados no sistema. "
    "No jogo, o aluno lê a <b>dica</b> e procura a <b>palavra</b> escondida na grade — as tentativas são "
    "ilimitadas e só o melhor resultado conta. As palavras da grade não usam acentos (padrão de "
    "caça-palavras); as dicas mantêm a escrita correta em português. Use este documento para revisar, "
    "aprovar ou sugerir ajustes antes de aplicar em turma.", st_intro))
flow.append(Spacer(1, 6))

for ano in range(1, 6):
    # Garante espaço para a faixa do ano + a primeira tabela começarem juntas
    flow.append(CondPageBreak(6.5 * cm))
    flow.append(barra_ano(ano))
    flow.append(Spacer(1, 6))
    for disc in ORDEM_DISC:
        flow.append(tabela_disc(disc, CONTEUDO[ano][disc]))
    if ano < 5:
        flow.append(Spacer(1, 4))

doc.build(flow)
print("PDF:", pdf_path)
print("Total de palavras:", total)
