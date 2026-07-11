# 🎮 Gamifica — Plataforma de Gamificação Escolar (V2)

Gamificação escolar **sem punição**: tentativas ilimitadas, só o melhor resultado conta, XP nunca diminui.

- **Especificação V1:** [ESPECIFICACAO.md](ESPECIFICACAO.md)
- **Modelo V2 (expansão competitiva):** [MODELO_V2.md](MODELO_V2.md) — mapa de lacunas frente a ClassDojo/Classcraft/Kahoot + os 4 diferenciais inéditos (Ranking de Evolução, Metas Coletivas, Mentoria entre Pares, Termômetro de Bem-estar).

## Stack

PHP 8.2+ · MySQL 8 / MariaDB 10.6+ · Apache (Hostgator compartilhado) — sem frameworks, sem Composer obrigatório, sem triggers no banco (motor de XP em `includes/functions.php`).

## Instalação (produção — Hostgator)

1. Envie os arquivos para `public_html/gamifica/`.
2. Crie o banco no cPanel e importe **`sql/gamifica_v2.sql`** (instalação nova).
   Opcional: importe `sql/dados_demo.sql` para dados de demonstração.
   Opcional: importe `sql/cacapalavras_biblioteca.sql` para 40 caça-palavras prontos
   (1º ao 5º ano × 8 disciplinas, cada palavra com dica). Requer o professor demo.
3. Edite `config/db.php` com host, banco, usuário e senha do cPanel.
4. Acesse `/gamifica/login.php` — admin padrão: `admin@escola.edu.br` / `Gamifica@2026` (troque no primeiro acesso).
5. (Opcional) Configure o Google OAuth em `config/google.php` (instruções no próprio arquivo).

### Migração de uma instalação V1

Importe, nesta ordem: `sql/gamifica_v2_tabelas_novas.sql` e depois `sql/migracao_v1_v2.sql` (removendo a linha `SOURCE`, já atendida pelo primeiro arquivo). O histórico de XP vira ledger e as moedas retroativas são creditadas.

## Rodando localmente (desenvolvimento/testes)

```bash
mysql -e "CREATE DATABASE gamifica CHARACTER SET utf8mb4"
mysql gamifica < sql/gamifica_v2.sql
mysql gamifica < sql/dados_demo.sql

GAMIFICA_DB_HOST=127.0.0.1 GAMIFICA_DB_NAME=gamifica \
GAMIFICA_DB_USER=root GAMIFICA_DB_PASS= \
php -S 127.0.0.1:8088
```

`BASE_URL` é detectada automaticamente em `localhost`/`php -S`; em produção usa o valor fixo de `config/app.php` (ou a env `GAMIFICA_BASE_URL`).

### Usuários de demonstração (senha de todos: `Gamifica@2026`)

| Perfil | E-mail |
|---|---|
| Admin | admin@escola.edu.br |
| Professora | ana@escola.edu.br |
| Coordenador | carlos@escola.edu.br |
| Alunos | bia@ · davi@ · enzo@ · gabi@ · hugo@ · iris@escola.edu.br |
| Responsável | rosa@familia.com |

## Módulos (V2)

| Módulo | Onde | Resumo |
|---|---|---|
| Missões (quiz, leitura, projeto, caça-palavras, grupo) | aluno/professor | Tentativas ilimitadas, melhor resultado conta |
| **Modo Ao Vivo** 🎤 | professor hospeda, aluno entra com PIN | Quiz estilo game show; no fim o XP **só melhora** |
| **Loja + GamiCoins** 🪙 | aluno/professor/admin | Economia positiva; estorno automático em cancelamento |
| **Portal da Família** 👨‍👩‍👧 | responsável/professor | Feed 100% positivo + mensagens diretas |
| **Portfólio** 🎨 | aluno/professor/família | Aprova ou devolve para melhorar — nunca reprova |
| **Metas Coletivas** 🎯 *(inédito)* | professor/aluno | A turma vence junta; recompensa para todos |
| **Mentoria entre Pares** 🧭 *(inédito)* | professor/aluno | Ajudar colega vale XP (% da melhora do aprendiz) |
| **Termômetro de Bem-estar** 💚 *(inédito)* | aluno/professor/coordenador | Check-in diário de humor → Índice de Clima |
| **Ranking de Evolução** 🚀 *(inédito)* | todos | XP da semana; a disputa recomeça toda segunda |
| Notificações 🔔 | todos | Sino no topo de todas as páginas |
| Relatórios/CSV 📥 | professor/coordenador | Conclusão, engajamento, clima |
| Integrações 🔗 | admin | Estrutura pronta p/ Google Classroom (requer credenciais) |

## Estrutura

```
├── config/            db.php · app.php · google.php
├── includes/          bootstrap · auth · functions (motor de XP) · layout
├── aluno/  professor/  coordenador/  admin/  responsavel/
├── api/               aovivo.php (polling) · notificacoes.php
├── assets/            css/style.css · js/main.js
└── sql/               gamifica_v2.sql · dados_demo.sql · migracao_v1_v2.sql
                       gamifica_v2_tabelas_novas.sql · gamifica_v1.sql (referência)
```

## Regras de ouro (invariantes do sistema)

1. XP nunca diminui — `xp_eventos.xp` é `UNSIGNED`, o banco rejeita valor negativo.
2. Melhor resultado sempre conta (`aluno_xp.xp_melhor = GREATEST(...)`).
3. Moedas só saem por escolha do aluno; cancelamento estorna.
4. Tudo que a família vê é positivo.
5. O Ranking de Evolução zera semanalmente — todo aluno tem chance de aparecer.
