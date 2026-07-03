# 🎮 Gamifica — Especificação Técnica Completa
**Domínio:** itthrive.com.br/gamifica  
**Stack:** PHP 8.2 + MySQL 8.0 + Hostgator Compartilhado  
**Autenticação:** Google Workspace (OAuth 2.0) + fallback e-mail/senha  

---

## 1. MÓDULO DO ALUNO

### 1.1 Login / Autenticação
- Login via **Google Workspace** (OAuth 2.0 — e-mail @escola.edu.br)
- Fallback: e-mail + senha (para escolas sem Google Workspace)
- Sessão PHP com timeout de 8h
- Redirecionamento automático pelo perfil (aluno / professor / coordenador / admin)

### 1.2 Dashboard do Aluno
Ao entrar, o aluno vê:
- **Banner de XP:** nível atual, XP total, XP para próximo nível, barra de progresso
- **Sequência de dias** (streak) — ícone de fogo
- **Posição no ranking** da turma
- **Missões disponíveis** (máx. 4 em destaque)
- **Conquistas recentes** (últimas 3)
- **Ranking rápido** (top 5 da turma)

### 1.3 Missões
Tipos de atividade no lançamento:
| Tipo | Descrição | XP base |
|------|-----------|---------|
| Quiz múltipla escolha | Questões com 4 alternativas, 1 correta | 10–500 XP |
| Leitura + questionário | Texto + perguntas de compreensão | 10–500 XP |
| Projeto por etapas | Atividade dividida em N etapas sequenciais | 10–500 XP |
| Caça-palavras | Grade de letras com termos ocultos | 10–500 XP |
| Trabalho em grupo | Atividade colaborativa com XP compartilhado | 10–500 XP |

**Regras de tentativas:**
- Padrão: **ilimitadas** (sem punição)
- Professor pode configurar limite (3x, 5x, 10x, ilimitadas)
- **Nunca há desconto de XP** — apenas o melhor resultado conta
- Aluno pode refazer para melhorar o próprio score

**Cálculo de XP:**
- XP = (acertos / total) × XP_da_atividade
- Apenas o **maior XP** de todas as tentativas é salvo no perfil
- Tentativas extras não reduzem o XP já conquistado

### 1.4 Sistema de Níveis
| Nível | Nome | XP necessário |
|-------|------|---------------|
| 1 | Iniciante | 0 |
| 2 | Curioso | 100 |
| 3 | Aprendiz | 300 |
| 4 | Explorador | 600 |
| 5 | Aventureiro | 1.000 |
| 6 | Desafiante | 1.500 |
| 7 | Explorador | 2.100 |
| 8 | Mestre | 2.800 |
| 9 | Guardião | 3.600 |
| 10 | Campeão | 4.500 |
| 11–20 | (avançados) | +1.000 por nível |

### 1.5 Conquistas (Badges)
| Conquista | Critério | Ícone |
|-----------|----------|-------|
| Raio Veloz | 5 missões em 1 dia | ⚡ |
| Mira Certeira | 10 acertos consecutivos | 🎯 |
| Leitor Ávido | 5 leituras concluídas | 📚 |
| Campeão | 1° lugar por 1 semana | 🏆 |
| Em Chamas | 7 dias de acesso seguidos | 🔥 |
| Colaborador | 1 trabalho em grupo concluído | 🤝 |
| Superestrela | 50 missões concluídas | 🌟 |
| Cientista | 5 projetos finalizados | 🔬 |
| Perfeito | 100% em uma atividade | 💯 |
| Imparável | 30 dias de acesso | 🚀 |
| Diamante | Atingir nível 10 | 💎 |
| Rei da Escola | 1° lugar geral | 👑 |

### 1.6 Ranking
- **Por turma:** ranking dos alunos da mesma turma
- **Por escola:** ranking geral de todos os alunos
- **Por matéria:** top alunos em cada disciplina
- Atualizado em tempo real a cada missão concluída
- Aluno vê sua posição destacada

### 1.7 Matérias
- Progresso do aluno por disciplina (XP ganho / XP disponível)
- Listagem de atividades por matéria
- Histórico de tentativas por atividade

---

## 2. MÓDULO DO PROFESSOR

- Criar / editar / encerrar atividades
- Ver progresso de cada aluno
- Ver alunos com baixo engajamento (< 30%)
- Relatório de conclusão por atividade
- Criar atividades com tentativas ilimitadas (padrão)

---

## 3. MÓDULO DO COORDENADOR

- Visão geral por turma e escola
- Engajamento por professor
- Top alunos da escola
- Exportar relatório CSV
- Não cria atividades — apenas visualiza

---

## 4. MÓDULO DO ADMIN

- CRUD completo de usuários
- Importar alunos via CSV
- Ativar / desativar módulos
- Logs de acesso
- Backup do banco de dados
- Configurações do sistema (XP máximo, nível máximo, etc.)
- Integração Google Workspace OAuth

---

## 5. AUTENTICAÇÃO GOOGLE WORKSPACE

**Fluxo:**
1. Usuário clica "Entrar com Google"
2. OAuth 2.0 redireciona para accounts.google.com
3. Google retorna token com e-mail (@escola.edu.br)
4. Sistema verifica se e-mail existe no banco
5. Se sim → login + cria sessão com perfil correto
6. Se não → mensagem "Conta não cadastrada, procure o admin"

**Biblioteca:** `league/oauth2-google` via Composer  
**Credenciais:** Google Cloud Console (Client ID + Secret)

---

## 6. ESTRUTURA DE PASTAS DO PROJETO

```
gamifica/
├── index.php              ← redireciona para login
├── login.php              ← tela de login
├── logout.php             ← encerra sessão
├── callback.php           ← retorno OAuth Google
├── .htaccess              ← regras Apache / segurança
├── composer.json          ← dependências PHP
├── config/
│   ├── db.php             ← conexão MySQL PDO
│   ├── google.php         ← credenciais OAuth
│   └── app.php            ← constantes do sistema
├── includes/
│   ├── header.php         ← cabeçalho HTML comum
│   ├── sidebar.php        ← menu lateral por perfil
│   ├── footer.php         ← rodapé HTML
│   ├── auth.php           ← funções de sessão/segurança
│   └── functions.php      ← funções auxiliares globais
├── aluno/
│   ├── index.php          ← dashboard do aluno
│   ├── missoes.php        ← lista de missões
│   ├── missao.php         ← fazer uma missão (quiz etc)
│   ├── conquistas.php     ← badges do aluno
│   ├── ranking.php        ← ranking turma e escola
│   └── materias.php       ← progresso por matéria
├── professor/
│   ├── index.php          ← dashboard professor
│   ├── atividades.php     ← listar atividades
│   ├── nova_atividade.php ← criar atividade
│   ├── editar.php         ← editar atividade
│   ├── turma.php          ← alunos da turma
│   └── relatorios.php     ← relatórios
├── coordenador/
│   ├── index.php
│   ├── turmas.php
│   ├── professores.php
│   ├── destaques.php
│   └── relatorio.php
├── admin/
│   ├── index.php
│   ├── usuarios.php
│   ├── novo_usuario.php
│   ├── modulos.php
│   ├── sistema.php
│   ├── logs.php
│   └── backup.php
├── assets/
│   ├── css/style.css      ← estilos globais
│   ├── js/main.js         ← scripts globais
│   └── img/              ← logos e ícones
└── sql/
    └── gamifica.sql       ← script completo do banco
```
