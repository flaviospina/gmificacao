# -*- coding: utf-8 -*-
"""
Gera sql/cacapalavras_biblioteca.sql — 40 caça-palavras (5 anos × 8 disciplinas)
para o Fundamental I, cada palavra com sua dica. Conteúdo alinhado à faixa etária.

Cria (de forma idempotente) 5 turmas "1º Ano".."5º Ano", vincula o professor
demo (Ana Souza) e publica 40 atividades do tipo 'cacapalavras'.
"""
import json, unicodedata, os

def norm(p):
    p = unicodedata.normalize('NFD', p.upper())
    p = ''.join(c for c in p if unicodedata.category(c) != 'Mn')
    return ''.join(c for c in p if 'A' <= c <= 'Z')

# CONTEUDO[ano][disciplina] = [(palavra, dica), ...]
CONTEUDO = {
1: {
 "Matemática": [
   ("TRES","Quantas rodas tem um triciclo? 🚲"),
   ("QUATRO","Número de patas de um cachorro 🐕"),
   ("CINCO","Quantos dedos tem uma mão? ✋"),
   ("SEIS","A metade de uma dúzia (12 dividido por 2)"),
   ("ZERO","Número que quer dizer 'nenhum': 0"),
   ("BOLA","Brinquedo redondo que rola e quica ⚽"),
 ],
 "Português": [
   ("VOGAL","A, E, I, O e U são as letras chamadas de ___"),
   ("LETRA","Cada sinalzinho do alfabeto: A, B, C..."),
   ("GATO","Bichinho de estimação que faz 'miau' 🐱"),
   ("BOLO","Doce gostoso que tem no aniversário 🎂"),
   ("CASA","Lugar onde a gente mora 🏠"),
   ("PATO","Ave que faz 'quá-quá' e adora nadar 🦆"),
 ],
 "Inglês": [
   ("CAT","'Gato' em inglês 🐱"),
   ("DOG","'Cachorro' em inglês 🐶"),
   ("RED","A cor 'vermelho' em inglês 🔴"),
   ("BLUE","A cor 'azul' em inglês 🔵"),
   ("SUN","O 'sol' em inglês ☀️"),
   ("BOOK","'Livro' em inglês 📖"),
 ],
 "Educação Física": [
   ("PULAR","O que fazemos com a corda, dando saltinhos"),
   ("CORRER","Mover-se bem rápido com as pernas 🏃"),
   ("BOLA","Usada no futebol e no basquete ⚽"),
   ("NADAR","Mover o corpo dentro da água 🏊"),
   ("PIQUE","Brincadeira de pega-pega: pique-___"),
   ("DANCAR","Mexer o corpo no ritmo da música 💃"),
 ],
 "Ciências": [
   ("SOL","Estrela que ilumina e aquece o dia ☀️"),
   ("AGUA","Bebemos quando estamos com sede 💧"),
   ("FLOR","Parte colorida e cheirosa da planta 🌸"),
   ("ARVORE","Planta grande, com tronco e folhas 🌳"),
   ("PEIXE","Animal que vive na água e tem escamas 🐟"),
   ("MICO","Macaquinho pequeno que pula nas árvores 🐒"),
 ],
 "Artes": [
   ("COR","Azul, vermelho e amarelo são tipos de ___ 🎨"),
   ("LAPIS","Usamos para desenhar e escrever ✏️"),
   ("TINTA","Líquido colorido que usamos para pintar"),
   ("PINCEL","Ferramenta de cerdas para passar tinta 🖌️"),
   ("AZUL","A cor do céu num dia bonito 💙"),
   ("PAPEL","Onde desenhamos e escrevemos 📄"),
 ],
 "Geografia": [
   ("RUA","Caminho por onde passam carros e pessoas"),
   ("ESCOLA","Lugar onde estudamos e brincamos 🏫"),
   ("CIDADE","Lugar cheio de casas, ruas e prédios 🏙️"),
   ("MAPA","Desenho que mostra onde ficam os lugares 🗺️"),
   ("CAMPO","Lugar do interior, com muito verde 🌾"),
   ("PRAIA","Lugar com areia e mar 🏖️"),
 ],
 "História": [
   ("FAMILIA","Papai, mamãe, irmãos e você 👨‍👩‍👧"),
   ("ONTEM","O dia que passou, antes de hoje"),
   ("HOJE","O dia de agora, em que estamos"),
   ("VOVO","O pai do seu pai ou da sua mãe 👴"),
   ("NOME","Como cada pessoa é chamada"),
   ("FESTA","Comemoração com bolo, música e alegria 🎉"),
 ],
},
2: {
 "Matemática": [
   ("DEZ","Número que vem depois do nove: 10"),
   ("DEZENA","Um grupo de dez unidades"),
   ("DOBRO","O ___ de 4 é 8 (é multiplicar por 2)"),
   ("METADE","A ___ de 10 é 5"),
   ("SOMAR","Juntar quantidades; o mesmo que adicionar"),
   ("REGUA","Serve para medir e traçar linhas retas 📏"),
 ],
 "Português": [
   ("SILABA","Pedacinho da palavra: CA-SA tem duas"),
   ("ACENTO","Sinalzinho sobre a letra, como no 'á' de café"),
   ("PLURAL","Mais de um; o ___ de gato é gatos"),
   ("FRASE","Conjunto de palavras que tem sentido"),
   ("PONTO","Sinal que encerra a frase: ."),
   ("MASCULINO","Gênero das palavras 'o menino', 'o cão'"),
 ],
 "Inglês": [
   ("GREEN","A cor 'verde' em inglês 🟢"),
   ("APPLE","A fruta 'maçã' em inglês 🍎"),
   ("HOUSE","'Casa' em inglês 🏠"),
   ("HELLO","Como se diz 'olá' em inglês 👋"),
   ("WATER","'Água' em inglês 💧"),
   ("THREE","O número 'três' em inglês"),
 ],
 "Educação Física": [
   ("FUTEBOL","Esporte jogado com os pés e uma bola ⚽"),
   ("EQUIPE","Grupo de jogadores do mesmo time"),
   ("AMARELINHA","Brincadeira de pular casas numeradas no chão"),
   ("ALONGAR","Esticar o corpo antes dos exercícios 🤸"),
   ("CORRIDA","Prova de quem chega mais rápido 🏃"),
   ("PETECA","Brinquedo de penas que batemos com a mão"),
 ],
 "Ciências": [
   ("PLANTA","Ser vivo que nasce da semente e tem raiz 🌱"),
   ("SEMENTE","Dela nasce uma nova planta"),
   ("INSETO","Animal de seis patas, como a formiga 🐜"),
   ("CHUVA","Água que cai das nuvens 🌧️"),
   ("DENTES","Usamos para mastigar; escovamos todo dia 🦷"),
   ("RAIZ","Parte da planta que fica embaixo da terra"),
 ],
 "Artes": [
   ("PINTURA","Obra feita com tinta e pincel 🖼️"),
   ("MUSICA","Arte feita com sons e ritmo 🎵"),
   ("TEATRO","Arte de representar histórias no palco 🎭"),
   ("COLAGEM","Arte de colar papéis para formar figuras ✂️"),
   ("CIRCULO","Forma bem redonda, como a roda ⭕"),
   ("VERDE","Cor que sai da mistura de azul com amarelo 💚"),
 ],
 "Geografia": [
   ("BAIRRO","Parte da cidade onde fica a sua casa"),
   ("RIO","Curso de água doce que corre pela terra 🏞️"),
   ("NORTE","Um dos pontos do mapa (o oposto do sul)"),
   ("PLACA","Sinal na rua que informa e orienta 🚸"),
   ("PONTE","Passagem construída por cima de um rio 🌉"),
   ("HORTA","Lugar onde se plantam verduras e legumes 🥕"),
 ],
 "História": [
   ("PASSADO","O tempo que já aconteceu, antes de hoje"),
   ("CALENDARIO","Mostra os dias, semanas e meses 📅"),
   ("ANTIGO","Muito velho, de muito tempo atrás"),
   ("SEMANA","Período de sete dias seguidos"),
   ("MEMORIA","Onde guardamos nossas lembranças 🧠"),
   ("BRINQUEDO","Antigamente eram de madeira; hoje há muitos"),
 ],
},
3: {
 "Matemática": [
   ("CENTENA","Um grupo de cem unidades"),
   ("DIVIDIR","Repartir em partes iguais"),
   ("TRIANGULO","Figura que tem três lados 🔺"),
   ("QUADRADO","Figura com quatro lados iguais ⬛"),
   ("SUBTRAIR","Tirar; o mesmo que diminuir"),
   ("METRO","Unidade usada para medir comprimento 📏"),
 ],
 "Português": [
   ("SUBSTANTIVO","Palavra que dá nome às coisas (casa, gato)"),
   ("ALFABETO","Conjunto de todas as letras, de A a Z 🔤"),
   ("RIMA","Sons parecidos no fim dos versos (gato/pato)"),
   ("LEITURA","O ato de ler um texto ou um livro 📖"),
   ("DITADO","Atividade de escrever aquilo que se ouve"),
   ("MAIUSCULA","Letra grande, usada no começo dos nomes"),
 ],
 "Inglês": [
   ("YELLOW","A cor 'amarelo' em inglês 🟡"),
   ("SCHOOL","'Escola' em inglês 🏫"),
   ("FRIEND","'Amigo' em inglês 🤝"),
   ("FAMILY","'Família' em inglês 👨‍👩‍👧"),
   ("ORANGE","A fruta 'laranja' em inglês 🍊"),
   ("TEACHER","'Professor' em inglês 👩‍🏫"),
 ],
 "Educação Física": [
   ("BASQUETE","Esporte de arremessar a bola na cesta 🏀"),
   ("GINASTICA","Esporte de saltos, giros e equilíbrio 🤸"),
   ("VOLEI","Esporte com a bola por cima da rede 🏐"),
   ("CAPOEIRA","Arte brasileira que mistura luta e dança"),
   ("ARBITRO","Pessoa que apita e marca as faltas no jogo"),
   ("SALTO","Impulso do corpo para cima ou para a frente"),
 ],
 "Ciências": [
   ("OXIGENIO","Gás do ar que respiramos para viver"),
   ("VERTEBRADO","Animal que tem coluna com vértebras"),
   ("HABITAT","Lugar natural onde um animal vive"),
   ("PULMAO","Órgão que usamos para respirar 🫁"),
   ("ESQUELETO","Todos os ossos do corpo juntos 💀"),
   ("MAMIFERO","Animal que mama leite quando é filhote 🐄"),
 ],
 "Artes": [
   ("PAISAGEM","Desenho ou pintura de um lugar 🏞️"),
   ("ESCULTURA","Arte feita em barro, pedra ou madeira 🗿"),
   ("MELODIA","Sequência de sons que forma a música 🎶"),
   ("RETRATO","Desenho ou foto do rosto de alguém 🖼️"),
   ("MOSAICO","Figura formada por várias pecinhas coloridas"),
   ("PRIMARIA","Cor ___: azul, vermelho e amarelo"),
 ],
 "Geografia": [
   ("ESTADO","São Paulo e Bahia são exemplos de ___"),
   ("CAPITAL","Cidade principal de um estado ou país"),
   ("OCEANO","Enorme massa de água salgada 🌊"),
   ("MONTANHA","Elevação muito alta do terreno ⛰️"),
   ("BUSSOLA","Instrumento que sempre aponta o norte 🧭"),
   ("FLORESTA","Lugar com muitíssimas árvores juntas 🌳"),
 ],
 "História": [
   ("INDIGENA","Foram os primeiros habitantes do Brasil 🏹"),
   ("COLONIA","O Brasil já foi uma ___ de Portugal"),
   ("MUSEU","Lugar que guarda e expõe objetos antigos 🏛️"),
   ("CULTURA","Costumes, festas e danças de um povo"),
   ("PORTUGAL","País da Europa que colonizou o Brasil"),
   ("CARAVELA","Navio antigo usado nas grandes viagens ⛵"),
 ],
},
4: {
 "Matemática": [
   ("FRACAO","Uma parte de um inteiro, como 1/2"),
   ("PERIMETRO","A soma de todos os lados de uma figura"),
   ("DECIMAL","Número com vírgula, como 2,5"),
   ("ANGULO","Abertura entre duas linhas que se encontram 📐"),
   ("MILHAR","Um grupo de mil unidades"),
   ("SIMETRIA","Quando os dois lados ficam iguais, como no espelho"),
   ("GRAFICO","Desenho que mostra dados em barras 📊"),
 ],
 "Português": [
   ("ADJETIVO","Palavra que descreve algo (bonito, grande)"),
   ("VERBO","Palavra de ação (correr, pular, ler)"),
   ("SINONIMO","Palavra de sentido igual (feliz e contente)"),
   ("ANTONIMO","Palavra de sentido oposto (alto e baixo)"),
   ("NARRATIVA","Texto que conta uma história 📚"),
   ("PARAGRAFO","Trecho do texto que começa em nova linha"),
 ],
 "Inglês": [
   ("MONDAY","'Segunda-feira' em inglês 📅"),
   ("WINTER","A estação 'inverno' em inglês ❄️"),
   ("PURPLE","A cor 'roxo' em inglês 🟣"),
   ("MOTHER","'Mãe' em inglês 👩"),
   ("NUMBER","'Número' em inglês 🔢"),
   ("SUMMER","A estação 'verão' em inglês ☀️"),
 ],
 "Educação Física": [
   ("HANDEBOL","Esporte de arremessar a bola com as mãos"),
   ("ATLETISMO","Provas de corrida, salto e arremesso 🏅"),
   ("XADREZ","Jogo de tabuleiro com rei, rainha e peões ♟️"),
   ("COOPERAR","Trabalhar junto com os colegas do time 🤝"),
   ("EQUILIBRIO","Habilidade de não cair, como na trave"),
   ("ARREMESSO","Lançamento da bola em direção ao alvo"),
 ],
 "Ciências": [
   ("DIGESTAO","Processo que transforma o alimento no corpo"),
   ("MICROBIO","Ser vivo minúsculo, só visto no microscópio 🔬"),
   ("ENERGIA","O Sol é a nossa maior fonte de ___ ⚡"),
   ("RECICLAR","Transformar o lixo em algo novo e útil ♻️"),
   ("NUTRIENTE","Substância dos alimentos que dá saúde 🥗"),
   ("PLANETA","A Terra é o nosso ___ 🌍"),
 ],
 "Artes": [
   ("AQUARELA","Tinta que se mistura e dilui com água 🎨"),
   ("SECUNDARIA","Cor ___: verde, laranja e roxo"),
   ("FOTOGRAFIA","Imagem capturada por uma câmera 📷"),
   ("HARMONIA","Combinação agradável de cores ou sons"),
   ("ORIGAMI","Arte japonesa de dobrar papel 🦢"),
   ("PERSPECTIVA","Técnica que dá profundidade ao desenho"),
 ],
 "Geografia": [
   ("RELEVO","Formas da terra: montanhas, planícies, vales"),
   ("CLIMA","Pode ser quente, frio, chuvoso ou seco 🌦️"),
   ("REGIAO","O Brasil tem cinco: Norte, Sul, Nordeste..."),
   ("POPULACAO","O conjunto de pessoas que vivem num lugar"),
   ("FRONTEIRA","Linha que separa dois países ou estados"),
   ("HEMISFERIO","Cada metade da Terra: Norte ou Sul 🌐"),
 ],
 "História": [
   ("IMIGRANTE","Pessoa que veio morar aqui vinda de outro país"),
   ("REPUBLICA","Forma de governo com um presidente eleito"),
   ("IMPERIO","Época em que o Brasil teve um imperador 👑"),
   ("BANDEIRA","Símbolo do país, verde e amarela 🇧🇷"),
   ("CIDADANIA","Direitos e deveres de quem vive em sociedade"),
   ("ESCRAVIDAO","Trabalho forçado de pessoas no passado do Brasil"),
 ],
},
5: {
 "Matemática": [
   ("PORCENTAGEM","Uma parte em cada cem, como 50%"),
   ("POLIGONO","Figura fechada de vários lados retos"),
   ("VOLUME","O espaço que um objeto ocupa 📦"),
   ("DIVISOR","Número pelo qual se divide outro"),
   ("MEDIA","Soma dos valores dividida pela quantidade"),
   ("DIAMETRO","Linha que corta o círculo passando pelo centro"),
 ],
 "Português": [
   ("PRONOME","Palavra que substitui o nome (ele, ela, nós)"),
   ("ORTOGRAFIA","O modo certo de escrever as palavras ✍️"),
   ("ADVERBIO","Palavra que indica modo, tempo ou lugar"),
   ("CONECTIVO","Palavra que liga ideias (mas, porque, então)"),
   ("CRONICA","Texto curto sobre acontecimentos do dia a dia"),
   ("PARLENDA","Versinho popular de brincar, como 'um, dois, feijão'"),
 ],
 "Inglês": [
   ("BREAKFAST","A refeição 'café da manhã' em inglês 🍳"),
   ("WEEKEND","O 'fim de semana' em inglês"),
   ("WEATHER","O 'tempo' (clima) em inglês 🌦️"),
   ("COUNTRY","'País' em inglês 🌎"),
   ("LIBRARY","'Biblioteca' em inglês 📚"),
   ("KITCHEN","'Cozinha' em inglês 🍽️"),
 ],
 "Educação Física": [
   ("FUTSAL","Futebol de salão, jogado numa quadra"),
   ("TATICA","O plano de jogo para tentar vencer a partida"),
   ("OLIMPIADA","A maior competição esportiva do mundo 🏅"),
   ("ADVERSARIO","O time ou jogador contra quem se disputa"),
   ("MODALIDADE","Cada tipo de esporte (natação, judô, vôlei)"),
   ("FLEXIBILIDADE","Capacidade de dobrar o corpo com facilidade"),
 ],
 "Ciências": [
   ("CIRCULACAO","O caminho que o sangue faz pelo corpo ❤️"),
   ("EVAPORACAO","Passagem da água de líquido para vapor 💨"),
   ("ECOSSISTEMA","Conjunto dos seres vivos e do ambiente 🌳"),
   ("RESPIRACAO","Puxar oxigênio e soltar gás carbônico 🫁"),
   ("PUBERDADE","Fase de mudanças do corpo na adolescência"),
   ("GRAVIDADE","Força que puxa tudo para o chão 🍎"),
 ],
 "Artes": [
   ("ABSTRATO","Arte sem figuras reais, só formas e cores"),
   ("GRAFITE","Arte urbana feita em muros com spray 🎨"),
   ("PARTITURA","Papel onde se escrevem as notas musicais 🎼"),
   ("COREOGRAFIA","A sequência de passos de uma dança 💃"),
   ("CONTRASTE","Diferença forte entre cores, como claro e escuro"),
   ("RENASCIMENTO","Período de grandes artistas, como Leonardo da Vinci"),
 ],
 "Geografia": [
   ("LATITUDE","Linhas horizontais do mapa, paralelas ao Equador"),
   ("URBANO","Relativo à cidade (o oposto de rural) 🏙️"),
   ("MIGRACAO","Movimento de pessoas de um lugar para outro"),
   ("ATMOSFERA","Camada de ar que envolve toda a Terra"),
   ("HIDROGRAFIA","Estudo dos rios, lagos e mares de uma região 🌊"),
   ("COORDENADA","Par de números que localiza um ponto no mapa"),
 ],
 "História": [
   ("CIVILIZACAO","Sociedade organizada, como a egípcia ou a romana"),
   ("REVOLUCAO","Uma grande mudança na sociedade ou no governo"),
   ("DEMOCRACIA","Governo em que o povo escolhe seus líderes 🗳️"),
   ("PATRIMONIO","Bens históricos que devem ser preservados 🏛️"),
   ("CONSTITUICAO","A lei principal que organiza um país 📜"),
   ("ABOLICAO","O fim da escravidão no Brasil, em 1888"),
 ],
},
}

DISC_ICON = {
 "Matemática":"➗","Português":"📚","Inglês":"🔤","Educação Física":"⚽",
 "Ciências":"🔬","Artes":"🎨","Geografia":"🌍","História":"📜",
}

def sql_str(s):
    return "'" + s.replace("\\","\\\\").replace("'","\\'") + "'"

out = []
out.append("-- ============================================================")
out.append("--  GAMIFICA — Biblioteca de Caça-palavras (Fundamental I)")
out.append("--  40 jogos: 5 anos (1º a 5º) x 8 disciplinas, cada palavra com dica.")
out.append("--  Idempotente: pode ser importado mais de uma vez sem duplicar.")
out.append("--  Requer o professor demo (Ana Souza). Se não existir, ajuste o e-mail abaixo.")
out.append("-- ============================================================")
out.append("SET NAMES utf8mb4;")
out.append("")
out.append("-- Professor e escola de referência (professor demo)")
out.append("SET @prof := (SELECT id FROM usuarios WHERE email = 'ana@escola.edu.br' LIMIT 1);")
out.append("SET @esc  := (SELECT escola_id FROM usuarios WHERE id = @prof LIMIT 1);")
out.append("")
out.append("-- Turmas 1º a 5º Ano (cria se não existirem)")
for ano in range(1,6):
    nome = f"{ano}º Ano"
    out.append(f"INSERT INTO turmas (escola_id, nome, ano_letivo) "
               f"SELECT @esc, {sql_str(nome)}, YEAR(CURDATE()) "
               f"FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE escola_id = @esc AND nome = {sql_str(nome)});")
out.append("")

total_palavras = 0
for ano in range(1,6):
    nome_turma = f"{ano}º Ano"
    out.append(f"-- ═══════════════ {nome_turma} ═══════════════")
    out.append(f"SET @turma := (SELECT id FROM turmas WHERE escola_id = @esc AND nome = {sql_str(nome_turma)} LIMIT 1);")
    # Vincula professor à turma (todas as disciplinas)
    for disc in CONTEUDO[ano]:
        out.append(f"INSERT INTO professor_turma (professor_id, turma_id, disciplina) "
                   f"SELECT @prof, @turma, {sql_str(disc)} FROM DUAL "
                   f"WHERE NOT EXISTS (SELECT 1 FROM professor_turma WHERE professor_id=@prof AND turma_id=@turma AND disciplina={sql_str(disc)});")
    for disc, itens in CONTEUDO[ano].items():
        maior = max(len(norm(p)) for p,_ in itens)
        lado = max(9, min(15, maior + 2))
        titulo = f"{DISC_ICON[disc]} Caça-palavras de {disc} · {ano}º Ano"
        desc = f"Leia cada dica e ache a palavra escondida na grade. Tentativas ilimitadas — só o seu melhor resultado conta!"
        xp = 50 + ano * 10   # 60..100
        arr = [{"p": norm(p), "d": d} for p, d in itens]
        total_palavras += len(arr)
        js = json.dumps(arr, ensure_ascii=False)
        # Atividade (idempotente por titulo+turma)
        out.append(
            "INSERT INTO atividades (professor_id, turma_id, titulo, descricao, tipo, disciplina, xp_recompensa, max_tentativas, publicada) "
            f"SELECT @prof, @turma, {sql_str(titulo)}, {sql_str(desc)}, 'cacapalavras', {sql_str(disc)}, {xp}, 0, 1 "
            f"FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM atividades WHERE turma_id=@turma AND titulo={sql_str(titulo)});")
        out.append(f"SET @atv := (SELECT id FROM atividades WHERE turma_id=@turma AND titulo={sql_str(titulo)} LIMIT 1);")
        out.append(
            "INSERT INTO cacapalavras_config (atividade_id, grid_linhas, grid_colunas, palavras, grade_gerada) "
            f"SELECT @atv, {lado}, {lado}, {sql_str(js)}, NULL "
            f"FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cacapalavras_config WHERE atividade_id=@atv);")
    out.append("")

out.append(f"-- Total: 40 caça-palavras · {total_palavras} palavras com dicas.")

destino = os.path.join(os.path.dirname(__file__), "..", "sql", "cacapalavras_biblioteca.sql")
destino = os.path.abspath(destino)
with open(destino, "w", encoding="utf-8") as f:
    f.write("\n".join(out) + "\n")
print("Gerado:", destino)
print("40 atividades ·", total_palavras, "palavras")
