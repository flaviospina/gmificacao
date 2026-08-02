# -*- coding: utf-8 -*-
"""
Estudo de Precificação do Gamifica — planilha de análise (calculadora viva).
Gera Gamifica_Estudo_Precificacao.xlsx com fórmulas para o sócio simular.
Azul = valor de entrada (editável) · Amarelo = premissa-chave · Preto = fórmula.
Valores em R$ são SUGESTÕES de mercado para validar (fontes na aba Benchmark).
"""
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter
import os

wb = openpyxl.Workbook()

# ── Estilos ───────────────────────────────────────────────────
ARIAL   = "Arial"
AZUL    = Font(name=ARIAL, size=10, color="0000FF")            # entrada editável
PRETO   = Font(name=ARIAL, size=10, color="000000")            # fórmula/texto
NEG     = Font(name=ARIAL, size=10, bold=True, color="000000")
TIT     = Font(name=ARIAL, size=15, bold=True, color="1A1A2E")
SUB     = Font(name=ARIAL, size=10, italic=True, color="666666")
CAB     = Font(name=ARIAL, size=10, bold=True, color="FFFFFF")
LILAS   = Font(name=ARIAL, size=10, bold=True, color="7C6EF0")
F_CAB   = PatternFill("solid", fgColor="7C6EF0")
F_CAB2  = PatternFill("solid", fgColor="1A1A2E")
F_YEL   = PatternFill("solid", fgColor="FFF3B0")   # premissa a validar
F_LILAS = PatternFill("solid", fgColor="EDE9FE")
F_VERDE = PatternFill("solid", fgColor="E7F7E1")
thin    = Side(style="thin", color="D9D9E3")
BORDA   = Border(left=thin, right=thin, top=thin, bottom=thin)
BRL     = 'R$ #,##0'
BRL2    = 'R$ #,##0.00'
PCT     = '0.0%'
CENTER  = Alignment(horizontal="center", vertical="center", wrap_text=True)
LEFTW   = Alignment(horizontal="left", vertical="center", wrap_text=True)
RIGHT   = Alignment(horizontal="right", vertical="center")

def titulo(ws, txt, sub=None):
    ws["A1"] = txt; ws["A1"].font = TIT
    if sub:
        ws["A2"] = sub; ws["A2"].font = SUB

def cabecalho(ws, linha, cols, larguras=None, fill=F_CAB):
    for i, c in enumerate(cols):
        cell = ws.cell(row=linha, column=i+1, value=c)
        cell.font = CAB; cell.fill = fill; cell.alignment = CENTER; cell.border = BORDA
    if larguras:
        for i, w in enumerate(larguras):
            ws.column_dimensions[get_column_letter(i+1)].width = w

def cel(ws, ref, val, font=PRETO, fmt=None, fill=None, al=None, borda=True):
    c = ws[ref]; c.value = val; c.font = font
    if fmt: c.number_format = fmt
    if fill: c.fill = fill
    if al: c.alignment = al
    if borda: c.border = BORDA
    return c

# ══════════════════════════════════════════════════════════════
# 1) LEIA-ME
# ══════════════════════════════════════════════════════════════
ws = wb.active; ws.title = "Leia-me"
titulo(ws, "Gamifica — Estudo de Precificação para Escolas",
       "Planilha de análise para decidir como cobrar. Todos os valores em R$ são sugestões de mercado, para validar.")
ws.column_dimensions["A"].width = 3
ws.column_dimensions["B"].width = 60
ws.column_dimensions["C"].width = 55
linhas = [
 ("", ""),
 ("COMO USAR", ""),
 ("Azul", "Célula de ENTRADA — pode alterar à vontade (nº de alunos, preços, etc.)."),
 ("Amarelo", "Premissa-chave / valor de mercado a validar com seu sócio."),
 ("Preto", "Fórmula — calculada automaticamente; não precisa editar."),
 ("", ""),
 ("ABAS", ""),
 ("Benchmark", "O que as principais plataformas cobram hoje (com fontes)."),
 ("Balões de Cobrança", "TODOS os itens que se pode cobrar de uma escola (único e recorrente)."),
 ("Modelo A — Por Aluno", "Calculadora do preço por aluno, com faixas por volume."),
 ("Modelo B — Pacote", "Calculadora do pacote fechado por faixa de tamanho da escola."),
 ("Por Aluno x Pacote", "Comparação lado a lado + ponto de equilíbrio (break-even)."),
 ("Público x Privado", "Como a cobrança muda entre escola privada e rede pública."),
 ("Projeção 3 anos", "Simulação de receita (implantação + recorrência) e margem."),
 ("", ""),
 ("RESUMO DA RECOMENDAÇÃO", ""),
 ("Modelo híbrido", "Pacote fechado por faixa para escolas pequenas/médias (venda simples) + preço por aluno para redes grandes e público (escala)."),
 ("Sempre cobrar 2 balões no mínimo", "1) Implantação (único) + 2) Licença recorrente (mensal/anual). Os demais balões são add-ons opcionais."),
 ("Ancoragem", "Cobrar por VALOR (engajamento, jogos exclusivos, sem punição), não por custo. A margem é alta: app leve, instância por escola."),
]
r = 4
for a, b in linhas:
    if a in ("COMO USAR","ABAS","RESUMO DA RECOMENDAÇÃO"):
        cel(ws, f"B{r}", a, font=LILAS, fill=F_LILAS, borda=False); ws[f"C{r}"].fill = F_LILAS
    else:
        cel(ws, f"B{r}", a, font=NEG, borda=False, al=LEFTW)
        cel(ws, f"C{r}", b, font=PRETO, borda=False, al=LEFTW)
    if a == "Azul": ws[f"B{r}"].fill = PatternFill("solid", fgColor="DCE6FF"); ws[f"B{r}"].font = AZUL
    if a == "Amarelo": ws[f"B{r}"].fill = F_YEL
    r += 1

# ══════════════════════════════════════════════════════════════
# 2) BENCHMARK
# ══════════════════════════════════════════════════════════════
ws = wb.create_sheet("Benchmark")
titulo(ws, "Benchmark — o que o mercado cobra",
       "Concorrentes internacionais têm preço público (por professor). Nacionais são 'sob consulta'. Câmbio editável abaixo.")
cel(ws, "F1", "Câmbio US$→R$", font=NEG, borda=False)
cel(ws, "G1", 5.40, font=AZUL, fmt=BRL2, fill=F_YEL)   # editável
cabecalho(ws, 4, ["Plataforma", "Modelo", "Preço (moeda de origem)", "≈ em R$", "Observação"],
          [22, 20, 30, 16, 40])
bench = [
 ("Kahoot!", "Por professor/mês", "US$ 10 a 49 (escola: a partir de US$ 29/prof/mês)", "=29*$G$1", "Cobra por professor, não por aluno."),
 ("Gimkit", "Por escola/ano", "US$ 1.000/ano (departamento US$ 650/ano)", "=1000*$G$1", "Licença de escola anual."),
 ("Quizizz (Wayground)", "Individual / escola", "Indiv. ~US$ 144/ano · escola sob consulta", "=144*$G$1", "Time até 30 ~US$ 50/mês."),
 ("ClassDojo", "Freemium", "Núcleo grátis · família US$ 110/ano", "=110*$G$1", "Monetiza a família, não a escola."),
 ("Classcraft", "Enterprise", "Sob consulta (grupo HMH)", None, "Venda corporativa/rede."),
 ("Educacross (BR)", "Por aluno", "Sob consulta", None, "Adaptativa+gamificada, pública e privada."),
 ("Gamefic (BR)", "Por escola", "Sob consulta", None, "Rede social gamificada da escola."),
]
r = 5
for nome, modelo, preco, brl_formula, obs in bench:
    cel(ws, f"A{r}", nome, font=NEG, al=LEFTW)
    cel(ws, f"B{r}", modelo, al=LEFTW)
    cel(ws, f"C{r}", preco, al=LEFTW)
    if brl_formula: cel(ws, f"D{r}", brl_formula, fmt=BRL, al=RIGHT)
    else: cel(ws, f"D{r}", "—", al=CENTER)
    cel(ws, f"E{r}", obs, al=LEFTW)
    r += 1
cel(ws, f"A{r+1}", "Leitura", font=NEG, borda=False)
cel(ws, f"B{r+1}",
    "A maioria cobra POR PROFESSOR. Para uma plataforma que o ALUNO usa todo dia, cobrar por aluno ou por pacote de escola captura muito mais valor.",
    font=PRETO, borda=False, al=LEFTW)
ws.merge_cells(f"B{r+1}:E{r+1}")
cel(ws, f"A{r+3}", "Fontes:", font=SUB, borda=False)
for i, src in enumerate([
  "Kahoot/Gimkit/ClassDojo/Quizizz: páginas de pricing e reviews 2025-2026 (makerstations, easternherald, capterra, saasworthy).",
  "Educacross e Gamefic: sites oficiais (sob consulta).",
]):
    cel(ws, f"B{r+3+i}", src, font=SUB, borda=False, al=LEFTW)

# ══════════════════════════════════════════════════════════════
# 3) BALÕES DE COBRANÇA
# ══════════════════════════════════════════════════════════════
ws = wb.create_sheet("Balões de Cobrança")
titulo(ws, "Balões de cobrança — todos os itens possíveis",
       "Menu completo do que se pode cobrar de uma escola. Combine conforme o cliente. Valores sugeridos para validar (amarelo).")
cabecalho(ws, 4, ["#", "Balão / item", "Tipo", "Faixa sugerida (R$)", "Quando aplicar"],
          [4, 30, 16, 26, 46])
baloes = [
 ("Implantação / Setup", "Único", "1.500 a 4.000", "Configurar a instância, importar alunos/turmas, aplicar marca. Principal receita inicial."),
 ("Taxa de adesão (alternativa leve)", "Único", "500 a 1.500", "Quando a escola resiste a setup alto; entrada menor para destravar a venda."),
 ("Licença POR ALUNO", "Recorrente", "4 a 9 /aluno/mês", "Modelo A. Escala com o tamanho da escola; ideal para redes e público."),
 ("Licença PACOTE FECHADO", "Recorrente", "690 / 1.490 / 2.490 /mês", "Modelo B. Faixas por tamanho; venda simples para privadas pequenas/médias."),
 ("Treinamento inicial (onboarding)", "Único", "800 a 2.500", "Formação dos professores no 1º mês. Pode ser incluído para fechar contrato anual."),
 ("Formação continuada / certificação", "Recorrente", "1.200 a 3.600 /ano", "Trilha de capacitação e certificado para os professores ao longo do ano."),
 ("Suporte premium / SLA", "Recorrente", "300 a 800 /mês", "Atendimento prioritário, gerente de conta, SLA de resposta. Básico já incluso."),
 ("Módulos premium / novos jogos", "Recorrente", "+10% a 25% da licença", "Jogos avançados (Sala de Enigmas, Laboratório), relatórios avançados, ao vivo."),
 ("Banco de conteúdo BNCC", "Único/Recorr.", "500 a 2.000", "Atividades prontas por ano e disciplina; atualização anual do banco."),
 ("White-label / marca própria", "Único + Recorr.", "3.000 setup + 300 /mês", "Domínio e identidade da escola/rede. Diferencial para grupos e franquias."),
 ("Integrações (Classroom, ERP, SSO)", "Único", "800 a 3.000 /integração", "Conectar ao Google Classroom ou ao sistema de gestão da escola."),
 ("Instância dedicada / uptime", "Recorrente", "incluído ou +200 /mês", "Isolamento de dados por escola (já é diferencial nosso) e garantia de disponibilidade."),
 ("Consultoria pedagógica / conteúdo sob medida", "Serviço (hora/pacote)", "150 a 300 /hora", "Criar conteúdo específico ou acompanhar o uso pedagógico."),
 ("Reajuste anual (cláusula)", "Recorrente", "IPCA / IGPM", "Reajuste automático no aniversário do contrato. Proteção de margem."),
 ("Licença por REDE / Secretaria (público)", "Recorrente", "2 a 6 /aluno/mês (escala)", "Contrato guarda-chuva por município; implantação e formação como itens do edital."),
 ("Relatórios institucionais / selo", "Add-on", "300 a 1.000", "Relatórios de engajamento para a escola usar em marketing e prestação de contas."),
]
r = 5
for i, (nome, tipo, faixa, quando) in enumerate(baloes, 1):
    cel(ws, f"A{r}", i, al=CENTER)
    cel(ws, f"B{r}", nome, font=NEG, al=LEFTW)
    tipo_fill = F_VERDE if "Recorr" in tipo else (F_LILAS if tipo=="Único" else None)
    cel(ws, f"C{r}", tipo, al=CENTER, fill=tipo_fill)
    cel(ws, f"D{r}", faixa, al=CENTER, fill=F_YEL)
    cel(ws, f"E{r}", quando, al=LEFTW)
    ws.row_dimensions[r].height = 30
    r += 1
cel(ws, f"B{r+1}", "Recomendação: no mínimo Implantação (único) + uma Licença recorrente. O resto é add-on para subir o ticket.",
    font=LILAS, borda=False, al=LEFTW)

# ══════════════════════════════════════════════════════════════
# 4) MODELO A — POR ALUNO
# ══════════════════════════════════════════════════════════════
ws = wb.create_sheet("Modelo A — Por Aluno")
titulo(ws, "Modelo A — Preço por aluno (com faixas por volume)",
       "Quanto mais alunos, menor o preço unitário. Edite os preços (amarelo) e o nº de alunos (azul).")
cabecalho(ws, 4, ["Faixa (min alunos)", "até", "Preço R$/aluno/mês"], [18, 10, 20])
faixas_a = [(1,150,8.00),(151,400,6.50),(401,800,5.00),(801,99999,4.00)]
r = 5
for mn, mx, preco in faixas_a:
    cel(ws, f"A{r}", mn, font=AZUL, al=CENTER)
    cel(ws, f"B{r}", ("9999+" if mx==99999 else mx), al=CENTER)
    cel(ws, f"C{r}", preco, font=AZUL, fmt=BRL2, fill=F_YEL, al=CENTER)
    r += 1
# Calculadora
cc = r + 1
cel(ws, f"A{cc}", "CALCULADORA", font=CAB, fill=F_CAB, borda=False)
cel(ws, f"A{cc+1}", "Nº de alunos da escola", font=NEG, al=LEFTW)
cel(ws, f"B{cc+1}", 300, font=AZUL, fill=F_YEL, al=CENTER)
cel(ws, f"A{cc+2}", "Preço aplicado (R$/aluno/mês)", font=NEG, al=LEFTW)
cel(ws, f"B{cc+2}", f"=INDEX(C5:C8,MATCH(B{cc+1},A5:A8,1))", fmt=BRL2, al=CENTER)
cel(ws, f"A{cc+3}", "Receita mensal", font=NEG, al=LEFTW)
cel(ws, f"B{cc+3}", f"=B{cc+1}*B{cc+2}", fmt=BRL, al=CENTER)
cel(ws, f"A{cc+4}", "Receita anual (x12)", font=NEG, al=LEFTW)
cel(ws, f"B{cc+4}", f"=B{cc+3}*12", fmt=BRL, al=CENTER)
ws.column_dimensions["A"].width = 26
# Guardar refs p/ Comparação
REF_A_MIN = "'Modelo A — Por Aluno'!$A$5:$A$8"
REF_A_PRC = "'Modelo A — Por Aluno'!$C$5:$C$8"

# ══════════════════════════════════════════════════════════════
# 5) MODELO B — PACOTE
# ══════════════════════════════════════════════════════════════
ws = wb.create_sheet("Modelo B — Pacote")
titulo(ws, "Modelo B — Pacote fechado por faixa de tamanho",
       "Preço fixo mensal por faixa de alunos. Simples de vender. Edite planos e preços (amarelo).")
cabecalho(ws, 4, ["Plano", "A partir de (alunos)", "Preço R$/mês", "Alunos de referência"], [16, 18, 16, 20])
faixas_b = [("Essencial",1,690,"até 150"),("Crescer",151,1490,"151 a 400"),
            ("Escola+",401,2490,"401 a 800"),("Rede",801,3990,"801+ (ou por aluno)")]
r = 5
for nome, mn, preco, ref in faixas_b:
    cel(ws, f"A{r}", nome, font=NEG, al=CENTER)
    cel(ws, f"B{r}", mn, font=AZUL, al=CENTER)
    cel(ws, f"C{r}", preco, font=AZUL, fmt=BRL, fill=F_YEL, al=CENTER)
    cel(ws, f"D{r}", ref, al=CENTER)
    r += 1
cc = r + 1
cel(ws, f"A{cc}", "CALCULADORA", font=CAB, fill=F_CAB, borda=False)
cel(ws, f"A{cc+1}", "Nº de alunos da escola", font=NEG, al=LEFTW)
cel(ws, f"B{cc+1}", 300, font=AZUL, fill=F_YEL, al=CENTER)
cel(ws, f"A{cc+2}", "Plano aplicado", font=NEG, al=LEFTW)
cel(ws, f"B{cc+2}", f"=INDEX(A5:A8,MATCH(B{cc+1},B5:B8,1))", al=CENTER)
cel(ws, f"A{cc+3}", "Preço mensal", font=NEG, al=LEFTW)
cel(ws, f"B{cc+3}", f"=INDEX(C5:C8,MATCH(B{cc+1},B5:B8,1))", fmt=BRL, al=CENTER)
cel(ws, f"A{cc+4}", "Preço anual (x12)", font=NEG, al=LEFTW)
cel(ws, f"B{cc+4}", f"=B{cc+3}*12", fmt=BRL, al=CENTER)
ws.column_dimensions["A"].width = 24
REF_B_MIN = "'Modelo B — Pacote'!$B$5:$B$8"
REF_B_PRC = "'Modelo B — Pacote'!$C$5:$C$8"

# ══════════════════════════════════════════════════════════════
# 6) POR ALUNO x PACOTE
# ══════════════════════════════════════════════════════════════
ws = wb.create_sheet("Por Aluno x Pacote")
titulo(ws, "Por aluno x Pacote — comparação e ponto de equilíbrio",
       "Para cada tamanho de escola: quanto sai em cada modelo e qual rende mais. Puxa preços dos Modelos A e B.")
cabecalho(ws, 4, ["Alunos", "Preço/aluno", "Por aluno (R$/mês)", "Pacote (R$/mês)", "Diferença", "Rende mais p/ nós"],
          [10, 14, 18, 16, 14, 20])
tamanhos = [50,100,150,200,300,400,500,600,800,1000]
r = 5
for n in tamanhos:
    cel(ws, f"A{r}", n, font=AZUL, al=CENTER)
    cel(ws, f"B{r}", f"=INDEX({REF_A_PRC},MATCH(A{r},{REF_A_MIN},1))", fmt=BRL2, al=CENTER)
    cel(ws, f"C{r}", f"=A{r}*B{r}", fmt=BRL, al=CENTER)
    cel(ws, f"D{r}", f"=INDEX({REF_B_PRC},MATCH(A{r},{REF_B_MIN},1))", fmt=BRL, al=CENTER)
    cel(ws, f"E{r}", f"=C{r}-D{r}", fmt=BRL, al=CENTER)
    cel(ws, f"F{r}", f'=IF(C{r}>D{r},"Por aluno","Pacote")', al=CENTER)
    r += 1
cel(ws, f"A{r+1}", "Como ler", font=NEG, borda=False)
cel(ws, f"B{r+1}",
    "Onde 'Por aluno' rende mais, ofereça por aluno (escolas grandes). Onde 'Pacote' rende mais, o pacote protege a receita (escolas pequenas). "
    "O ideal é listar os DOIS e a equipe comercial escolhe o melhor para cada caso.",
    font=PRETO, borda=False, al=LEFTW)
ws.merge_cells(f"B{r+1}:F{r+2}")

# ══════════════════════════════════════════════════════════════
# 7) PÚBLICO x PRIVADO
# ══════════════════════════════════════════════════════════════
ws = wb.create_sheet("Público x Privado")
titulo(ws, "Público x Privado — como muda a cobrança",
       "A lógica de venda e o modelo mudam entre a escola privada e a rede pública (licitação).")
cabecalho(ws, 4, ["Aspecto", "Escola privada", "Rede pública / Secretaria"], [24, 40, 40])
linhas = [
 ("Quem decide", "Diretor/mantenedor — venda rápida (semanas).", "Secretaria via licitação/pregão — ciclo longo (meses)."),
 ("Modelo ideal", "Pacote fechado por faixa (simples) ou por aluno.", "Por aluno em escala (volume alto, preço menor)."),
 ("Preço sugerido", "R$ 4 a 9/aluno/mês OU pacote R$ 690 a 2.490/mês.", "R$ 2 a 6/aluno/mês (grande volume, contrato anual)."),
 ("Implantação", "R$ 1.500 a 4.000 (único).", "Item do edital: implantação + formação + suporte on-site."),
 ("Treinamento", "Onboarding incluso ou add-on.", "Formação de professores geralmente obrigatória no edital."),
 ("Faturamento", "Mensal ou anual antecipado.", "Empenho/nota conforme cronograma da prefeitura."),
 ("Contrato", "12 meses com renovação automática.", "12 meses, prorrogável; sujeito à Lei 14.133/2021."),
 ("Risco", "Inadimplência baixa, decisão ágil.", "Ciclo e pagamento mais lentos, porém volume e previsibilidade altos."),
 ("Diferencial que pesa", "Sem punição, jogos exclusivos, portal família.", "Relatórios de rede, BNCC, e Índice de Clima por escola."),
]
r = 5
for asp, priv, pub in linhas:
    cel(ws, f"A{r}", asp, font=NEG, al=LEFTW)
    cel(ws, f"B{r}", priv, al=LEFTW)
    cel(ws, f"C{r}", pub, al=LEFTW)
    ws.row_dimensions[r].height = 30
    r += 1

# ══════════════════════════════════════════════════════════════
# 8) PROJEÇÃO 3 ANOS
# ══════════════════════════════════════════════════════════════
ws = wb.create_sheet("Projeção 3 anos")
titulo(ws, "Projeção de receita — 3 anos (simplificada)",
       "Edite as premissas (azul/amarelo). Modelo simplificado: recorrência = MRR do fim do ano x12.")
cabecalho(ws, 4, ["Premissa / Resultado", "Ano 1", "Ano 2", "Ano 3"], [34, 16, 16, 16])
def linha(ws, r, rot, vals, fmt=None, font=PRETO, fill=None, negrito=False):
    cel(ws, f"A{r}", rot, font=(NEG if negrito else PRETO), al=LEFTW)
    for i, v in enumerate(vals):
        col = get_column_letter(2+i)
        cel(ws, f"{col}{r}", v, font=font, fmt=fmt, fill=fill, al=CENTER)
# Entradas (azul/amarelo)
linha(ws, 5, "Escolas NOVAS no ano", [8, 15, 25], font=AZUL, fill=F_YEL)
linha(ws, 6, "Escolas acumuladas", ["=B5", "=B6+C5", "=C6+D5"])
linha(ws, 7, "Alunos médios por escola", [250, 250, 250], font=AZUL, fill=F_YEL)
linha(ws, 8, "Alunos totais", ["=B6*B7", "=C6*C7", "=D6*D7"])
linha(ws, 9, "Preço médio R$/aluno/mês", [6.00, 6.00, 6.00], fmt=BRL2, font=AZUL, fill=F_YEL)
linha(ws, 10, "MRR no fim do ano", ["=B8*B9", "=C8*C9", "=D8*D9"], fmt=BRL)
linha(ws, 11, "Receita recorrente no ano (MRRx12)", ["=B10*12", "=C10*12", "=D10*12"], fmt=BRL)
linha(ws, 12, "Ticket médio de implantação (único)", [2500, 2500, 2500], fmt=BRL, font=AZUL, fill=F_YEL)
linha(ws, 13, "Receita de implantação no ano", ["=B5*B12", "=C5*C12", "=D5*D12"], fmt=BRL)
linha(ws, 14, "RECEITA TOTAL no ano", ["=B11+B13", "=C11+C13", "=D11+D13"], fmt=BRL, negrito=True, fill=F_LILAS)
linha(ws, 15, "Margem bruta %", [0.75, 0.75, 0.75], fmt=PCT, font=AZUL, fill=F_YEL)
linha(ws, 16, "Lucro bruto no ano", ["=B14*B15", "=C14*C15", "=D14*D15"], fmt=BRL, negrito=True, fill=F_VERDE)
cel(ws, "A18", "Observações", font=NEG, borda=False)
for i, obs in enumerate([
  "Modelo simplificado: assume a recorrência do fim do ano aplicada aos 12 meses (otimista). Para conservador, use MRR médio.",
  "Margem alta porque o app é leve e a instância por escola tem custo marginal baixo; o maior custo é gente (implantação, suporte, conteúdo).",
  "Preço médio por aluno é o mix de privado (maior) e público (menor). Ajuste conforme sua meta de vendas.",
]):
    cel(ws, f"A{19+i}", "• " + obs, font=SUB, borda=False, al=LEFTW)
    ws.merge_cells(f"A{19+i}:D{19+i}")

# congela cabeçalhos e salva
for nome in wb.sheetnames:
    wb[nome].sheet_view.showGridLines = False

destino = os.path.join(os.path.dirname(__file__), "Gamifica_Estudo_Precificacao.xlsx")
wb.save(destino)
print("XLSX:", destino, "·", len(wb.sheetnames), "abas")
