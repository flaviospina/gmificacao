# -*- coding: utf-8 -*-
"""Estudo de Precificação do Gamifica — PDF de leitura (acompanha a planilha)."""
import os, re
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm
from reportlab.lib import colors as rc
from reportlab.lib.styles import ParagraphStyle
from reportlab.platypus import (BaseDocTemplate, PageTemplate, Frame, Paragraph,
                                Spacer, Table, TableStyle, KeepTogether, CondPageBreak)

ROXO=rc.HexColor("#7C6EF0"); ESC=rc.HexColor("#1A1A2E"); CINZA=rc.HexColor("#6B6B80")
CLARO=rc.HexColor("#F1EEFE"); LINHA=rc.HexColor("#E5E9F2"); VERDE=rc.HexColor("#16A34A")
AZUL=rc.HexColor("#1CB0F6"); AMBAR=rc.HexColor("#D97706"); ZEBRA=rc.HexColor("#FAF9FF")

_EMO=re.compile("[\U0001F000-\U0001FAFF\U00002190-\U00002BFF\U00002600-\U000027BF\U0000FE00-\U0000FE0F\U0000200D★☆]",re.UNICODE)
def L(t): return re.sub(r"\s{2,}"," ",_EMO.sub("",t.replace("‑","-").replace("–","-"))).strip()

st_h1=ParagraphStyle("h1",fontName="Helvetica-Bold",fontSize=23,textColor=ESC,leading=26,spaceAfter=3)
st_sub=ParagraphStyle("sub",fontName="Helvetica",fontSize=10.5,textColor=CINZA,leading=14,spaceAfter=4)
st_sec=ParagraphStyle("sec",fontName="Helvetica-Bold",fontSize=14,textColor=ROXO,leading=17,spaceBefore=10,spaceAfter=5)
st_p=ParagraphStyle("p",fontName="Helvetica",fontSize=10,textColor=rc.HexColor("#33333f"),leading=14.5,spaceAfter=4)
st_cab=ParagraphStyle("cab",fontName="Helvetica-Bold",fontSize=8.8,textColor=rc.white,leading=11)
st_cell=ParagraphStyle("cell",fontName="Helvetica",fontSize=8.8,textColor=rc.HexColor("#33333f"),leading=11)
st_cellb=ParagraphStyle("cellb",fontName="Helvetica-Bold",fontSize=8.8,textColor=ESC,leading=11)
st_note=ParagraphStyle("note",fontName="Helvetica-Oblique",fontSize=8.5,textColor=CINZA,leading=11)

def rodape(cnv,doc):
    cnv.saveState(); cnv.setFont("Helvetica",8); cnv.setFillColor(CINZA)
    cnv.drawString(2*cm,1.1*cm,"Gamifica · Estudo de precificação · valores sugeridos para validar")
    cnv.drawRightString(A4[0]-2*cm,1.1*cm,f"Página {doc.page}")
    cnv.restoreState()

pdf=os.path.join(os.path.dirname(__file__),"Gamifica_Estudo_Precificacao.pdf")
doc=BaseDocTemplate(pdf,pagesize=A4,leftMargin=1.8*cm,rightMargin=1.8*cm,topMargin=1.5*cm,bottomMargin=1.5*cm)
doc.addPageTemplates([PageTemplate(id="p",frames=[Frame(doc.leftMargin,doc.bottomMargin,doc.width,doc.height)],onPage=rodape)])
W=doc.width
def P(t,s=st_cell): return Paragraph(L(t),s)

def tabela(cabs, linhas, larguras, cor=ROXO, zebra=True):
    dados=[[Paragraph(L(c),st_cab) for c in cabs]]
    for row in linhas:
        dados.append([Paragraph(L(str(x)), st_cellb if j==0 else st_cell) for j,x in enumerate(row)])
    t=Table(dados,colWidths=[w*W for w in larguras],repeatRows=1)
    style=[("BACKGROUND",(0,0),(-1,0),cor),("TOPPADDING",(0,0),(-1,-1),4),("BOTTOMPADDING",(0,0),(-1,-1),4),
           ("LEFTPADDING",(0,0),(-1,-1),5),("RIGHTPADDING",(0,0),(-1,-1),5),("VALIGN",(0,0),(-1,-1),"MIDDLE"),
           ("GRID",(0,0),(-1,-1),0.4,LINHA)]
    if zebra:
        for r in range(2,len(dados),2): style.append(("BACKGROUND",(0,r),(-1,r),ZEBRA))
    t.setStyle(TableStyle(style)); return t

flow=[]
flow.append(Paragraph("Gamifica — Estudo de Precificação para Escolas", st_h1))
flow.append(Paragraph("Como cobrar das escolas (privadas e públicas): balões de cobrança, por aluno x pacote fechado e faixas de preço sugeridas. "
                      "Acompanha a planilha Gamifica_Estudo_Precificacao.xlsx (calculadora viva).", st_sub))
flow.append(Paragraph("Importante: os valores em reais são <b>sugestões de mercado para vocês validarem</b> — calibrados pelos preços públicos "
                      "dos concorrentes internacionais e pelas práticas do mercado educacional brasileiro. Não são cotações fechadas.", st_note))

flow.append(Paragraph("1. Benchmark de mercado", st_sec))
flow.append(Paragraph("A maioria das plataformas cobra <b>por professor</b> — o que subaproveita uma ferramenta que o <b>aluno</b> usa todo dia. "
                      "Cobrar por aluno ou por pacote de escola captura muito mais valor.", st_p))
flow.append(tabela(["Plataforma","Modelo","Preço praticado","Observação"],
 [["Kahoot!","Por professor/mês","US$ 10 a 49 · escola a partir de US$ 29/prof/mês","Não cobra por aluno."],
  ["Gimkit","Por escola/ano","US$ 1.000/ano (depto US$ 650/ano)","Licença anual de escola."],
  ["Quizizz (Wayground)","Individual/escola","Indiv. ~US$ 144/ano · escola sob consulta","Time até 30 ~US$ 50/mês."],
  ["ClassDojo","Freemium","Núcleo grátis · família US$ 110/ano","Monetiza a família."],
  ["Classcraft","Enterprise","Sob consulta (grupo HMH)","Venda por rede."],
  ["Educacross / Gamefic (BR)","Por aluno / escola","Sob consulta","Concorrentes nacionais diretos."]],
 [0.20,0.16,0.36,0.28]))
flow.append(Spacer(1,3))
flow.append(Paragraph("Câmbio de referência: US$ 1 ≈ R$ 5,40 (ajustável na planilha). Ex.: a licença de escola do Gimkit (US$ 1.000/ano) ≈ R$ 5.400/ano.", st_note))

flow.append(Paragraph("2. Balões de cobrança — o menu completo", st_sec))
flow.append(Paragraph("Tudo o que se pode cobrar de uma escola. O <b>mínimo</b> é Implantação (único) + uma Licença recorrente; o resto são add-ons que elevam o ticket.", st_p))
flow.append(tabela(["Balão / item","Tipo","Faixa sugerida (R$)","Quando aplicar"],
 [["Implantação / Setup","Único","1.500 a 4.000","Configurar instância, importar alunos, aplicar marca."],
  ["Taxa de adesão (leve)","Único","500 a 1.500","Entrada menor para destravar a venda."],
  ["Licença POR ALUNO","Recorrente","4 a 9 /aluno/mês","Escala com o tamanho; ideal p/ redes e público."],
  ["Licença PACOTE FECHADO","Recorrente","690 / 1.490 / 2.490 /mês","Venda simples p/ privadas pequenas/médias."],
  ["Treinamento inicial","Único","800 a 2.500","Onboarding dos professores no 1º mês."],
  ["Formação continuada","Recorrente","1.200 a 3.600 /ano","Capacitação e certificação ao longo do ano."],
  ["Suporte premium / SLA","Recorrente","300 a 800 /mês","Atendimento prioritário e gerente de conta."],
  ["Módulos premium / novos jogos","Recorrente","+10% a 25% da licença","Sala de Enigmas, relatórios avançados, ao vivo."],
  ["Banco de conteúdo BNCC","Único/Recorr.","500 a 2.000","Atividades prontas por ano e disciplina."],
  ["White-label / marca própria","Único+Recorr.","3.000 + 300 /mês","Domínio e identidade da escola/rede."],
  ["Integrações (Classroom, ERP)","Único","800 a 3.000 /integração","Conectar aos sistemas da escola."],
  ["Instância dedicada / uptime","Recorrente","incluído ou +200 /mês","Isolamento de dados e disponibilidade."],
  ["Consultoria pedagógica","Serviço","150 a 300 /hora","Conteúdo sob medida e acompanhamento."],
  ["Reajuste anual","Recorrente","IPCA / IGPM","Protege a margem no aniversário do contrato."],
  ["Licença por REDE (público)","Recorrente","2 a 6 /aluno/mês","Contrato por município via licitação."],
  ["Relatórios institucionais","Add-on","300 a 1.000","Relatórios de engajamento p/ a escola."]],
 [0.28,0.15,0.22,0.35]))

flow.append(CondPageBreak(6*cm))
flow.append(Paragraph("3. Por aluno x Pacote fechado", st_sec))
flow.append(tabela(["Modelo","Vantagens","Desvantagens","Melhor para"],
 [["Por aluno","Justo e escalável; cresce com a escola; previsível por cabeça.","Escola grande fica cara; exige contar alunos; sensível à evasão.","Redes grandes e setor público."],
  ["Pacote fechado","Simples de vender e de entender; receita previsível; bom p/ escola pequena.","Pode subprecificar escola grande; escolher as faixas é arte.","Privadas pequenas e médias."],
  ["Híbrido (recomendado)","Pacote por faixa + por aluno no excedente; melhor dos dois.","Exige tabela clara para o comercial.","Toda a operação."]],
 [0.16,0.30,0.30,0.24]))
flow.append(Spacer(1,4))
flow.append(Paragraph("<b>Ponto de equilíbrio (exemplo):</b> pacote Essencial de R$ 690/mês equivale a ~86 alunos a R$ 8/aluno. "
                      "Abaixo disso o <b>por aluno</b> é mais barato para a escola; acima, o <b>pacote</b> rende mais para nós. "
                      "A aba “Por Aluno x Pacote” da planilha mostra isso para cada tamanho de escola.", st_p))

flow.append(Paragraph("4. Público x Privado", st_sec))
flow.append(tabela(["Aspecto","Escola privada","Rede pública / Secretaria"],
 [["Quem decide","Diretor/mantenedor — venda em semanas.","Secretaria via licitação — ciclo de meses."],
  ["Modelo ideal","Pacote por faixa ou por aluno.","Por aluno em escala (volume alto)."],
  ["Preço sugerido","R$ 4 a 9/aluno OU pacote R$ 690 a 2.490/mês.","R$ 2 a 6/aluno/mês, contrato anual."],
  ["Implantação","R$ 1.500 a 4.000 (único).","Item do edital: setup + formação + suporte."],
  ["Faturamento","Mensal ou anual antecipado.","Empenho conforme cronograma (Lei 14.133/2021)."],
  ["Risco","Baixo; decisão ágil.","Pagamento mais lento, porém volume e previsibilidade."]],
 [0.18,0.40,0.42]))

flow.append(CondPageBreak(7*cm))
flow.append(Paragraph("5. Recomendação — tabela de preços sugerida", st_sec))
flow.append(Paragraph("Modelo <b>híbrido</b>: pacote fechado para privadas pequenas/médias (venda rápida) e por aluno para redes grandes e público (escala). "
                      "Sempre com Implantação + Licença recorrente; add-ons elevam o ticket.", st_p))
flow.append(tabela(["Plano","Alunos","Implantação (único)","Licença (recorrente)"],
 [["Essencial","até 150","R$ 1.500","R$ 690/mês  (ou R$ 8/aluno)"],
  ["Crescer","151 a 400","R$ 2.500","R$ 1.490/mês  (ou R$ 6,50/aluno)"],
  ["Escola+","401 a 800","R$ 3.500","R$ 2.490/mês  (ou R$ 5/aluno)"],
  ["Rede / Público","801+","edital / sob consulta","R$ 2 a 4/aluno/mês"]],
 [0.18,0.16,0.30,0.36], cor=VERDE))
flow.append(Spacer(1,4))
flow.append(Paragraph("<b>Por que a margem é alta:</b> o sistema é leve e cada escola tem instância isolada com custo marginal baixo; "
                      "o maior custo é gente (implantação, suporte e conteúdo). Por isso, precifique por <b>valor</b> (engajamento, jogos exclusivos, "
                      "sem punição, portal família), não por custo.", st_p))

flow.append(Paragraph("6. Projeção ilustrativa (3 anos)", st_sec))
flow.append(Paragraph("Cenário exemplo (edite na planilha): 8 / 15 / 25 novas escolas por ano, 250 alunos por escola, R$ 6/aluno/mês, "
                      "implantação média R$ 2.500, margem 75%.", st_p))
flow.append(tabela(["Indicador","Ano 1","Ano 2","Ano 3"],
 [["Escolas acumuladas","8","23","48"],
  ["Alunos totais","2.000","5.750","12.000"],
  ["Receita recorrente/ano","R$ 144 mil","R$ 414 mil","R$ 864 mil"],
  ["Receita de implantação","R$ 20 mil","R$ 37,5 mil","R$ 62,5 mil"],
  ["Receita total","R$ 164 mil","R$ 451,5 mil","R$ 926,5 mil"],
  ["Lucro bruto (75%)","R$ 123 mil","R$ 338,6 mil","R$ 694,9 mil"]],
 [0.34,0.22,0.22,0.22], cor=AMBAR))
flow.append(Paragraph("Números arredondados; a planilha recalcula ao mudar qualquer premissa. Modelo simplificado (recorrência do fim do ano x12).", st_note))

flow.append(Paragraph("7. Próximos passos", st_sec))
for t in ["Validar com o sócio as faixas de preço e os balões que entram na proposta padrão.",
          "Definir 2 a 3 planos “de prateleira” (Essencial / Crescer / Escola+) para acelerar a venda.",
          "Montar 1 proposta modelo para privada e 1 para rede pública (com os itens de edital).",
          "Rodar um piloto pago com 1 escola para calibrar preço e esforço de implantação."]:
    flow.append(Paragraph("• " + t, st_p))

doc.build(flow)
print("PDF:", pdf)
