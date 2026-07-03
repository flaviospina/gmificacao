# 🎮 Gamifica — Modelo V2 (Expansão Competitiva)

**Base:** ESPECIFICACAO.md (v1) + Relatório Comparativo de Mercado (julho/2026)
**Objetivo:** cobrir todas as lacunas identificadas frente a ClassDojo, Classcraft, Kahoot!, Quizizz, Blooket e Gimkit — e adicionar mecânicas que **nenhum concorrente possui**, mantendo o pilar central: **sem punição, tentativas ilimitadas, nada é perdido**.

---

## 1. Mapa de lacunas → módulos novos

| # | Lacuna (quem tem no mercado) | Módulo novo no Gamifica | Como fica no nosso modelo |
|---|---|---|---|
| 1 | Comunicação escola‑família (ClassDojo — núcleo do produto) | **Portal da Família** | Novo perfil `responsavel`, feed 100% positivo da turma/aluno, mensagens diretas professor ↔ responsável. Diferente do ClassDojo: o responsável **nunca vê pontuação negativa**, porque ela não existe. |
| 2 | Portfólio do aluno (ClassDojo Portfolio) | **Portfólio** | Aluno publica trabalhos; professor revisa (aprova ou devolve para melhorar — nunca "reprova"); itens aprovados aparecem para a família. |
| 3 | Economia virtual + loja (Gimkit/Blooket) | **GamiCoins + Loja** | Moedas ganhas junto com XP (nunca retiradas por comportamento). Loja com itens de avatar, privilégios de sala e recompensas definidas pela escola. Gasto é escolha, estorno em cancelamento — o schema **não aceita débito punitivo**. |
| 4 | Customização de avatar/RPG (Classcraft) | **Avatar** | Itens de avatar compráveis com GamiCoins e equipáveis, sem HP, sem morte, sem perda. |
| 5 | Quiz ao vivo com PIN (Kahoot!/Quizizz) | **Modo Ao Vivo** | Professor projeta, alunos entram com PIN de 6 dígitos, pontos por acerto + velocidade. O resultado vira uma tentativa normal: **só melhora o XP do aluno, nunca reduz** — resposta direta ao "já usamos Kahoot". |
| 6 | Analytics avançado / school climate index (Classcraft) | **Índice de Clima + Relatórios** | Índice de Clima por turma (bem‑estar × engajamento), série temporal de XP via ledger de eventos, exportação CSV. Painéis para professor e coordenador. |
| 7 | Notificações/alertas (ambos concorrentes diretos) | **Notificações** | Sino no topo: nova atividade, conquista, meta atingida, mensagem, resgate aprovado. |
| 8 | Integração Google Classroom / Microsoft 365 (ambos) | **Integrações** | Estrutura pronta (credenciais por escola + vínculo turma ↔ curso + importação de alunos). Ativação requer credenciais do Google Cloud da escola. |

## 2. Diferenciais inéditos (ninguém no mercado tem)

| # | Mecânica | Descrição | Por que ninguém tem |
|---|---|---|---|
| 1 | **Ranking de Evolução** | Ranking semanal por **XP ganho na semana**, não por XP acumulado. O aluno que mais cresceu aparece no topo — mesmo sendo o último no ranking geral. | ClassDojo/Classcraft rankeiam por total absoluto: quem começa atrás desiste. Aqui todo aluno recomeça a disputa toda segunda‑feira. |
| 2 | **Metas Coletivas de Turma** | Professor define meta para a turma inteira (ex.: 5.000 XP juntos até sexta). Se a turma atinge, **todos** ganham a recompensa. | Concorrentes só têm competição individual ou entre equipes (que gera perdedores). Aqui a turma vence junta ou apenas ainda‑não‑venceu. |
| 3 | **Mentoria entre Pares** | Professor pareia mentor e aprendiz. Quando o aprendiz melhora a pontuação, o mentor ganha bônus de XP (% da melhora). Ajudar o colega vale XP. | Nenhuma plataforma recompensa academicamente o ato de ajudar. Transforma os melhores alunos em aliados dos que têm dificuldade. |
| 4 | **Termômetro de Bem‑estar** | Check‑in diário de humor (1 clique, 5 emojis, +5 XP). Professor/coordenador veem o clima agregado da turma — nunca para punir, sempre para acolher. Alimenta o Índice de Clima. | Classcraft mede "clima" a partir de comportamento registrado pelo professor; aqui o dado vem do próprio aluno, sem consequência negativa possível. |

## 3. Regras de ouro do modelo (invariantes)

1. **XP nunca diminui.** O ledger `xp_eventos` usa coluna `UNSIGNED` — o banco rejeita XP negativo.
2. **Melhor resultado sempre conta.** Toda forma de jogar (missão, ao vivo) converge para `aluno_xp.xp_melhor = GREATEST(anterior, novo)`.
3. **Moedas não são instrumento disciplinar.** Só saem do saldo por escolha do aluno (compra na loja); cancelamento estorna.
4. **Todo dado exposto à família é positivo** (feed, conquistas, progresso). Não existe registro de "comportamento negativo" no schema.
5. **Rankings têm porta de entrada para todos:** o Ranking de Evolução zera semanalmente.

## 4. Arquitetura do modelo de dados V2

### 4.1 Fonte de verdade: ledger de eventos
- `xp_eventos` — todo XP ganho (missão, ao vivo, streak, check‑in, mentoria, meta, conquista). Permite série temporal, ranking de evolução e progresso de metas sem tabelas extras.
- `moeda_eventos` — ganhos e gastos de GamiCoins.
- `aluno_perfil` guarda apenas caches (`xp_total`, `moedas`, `nivel_atual`, `streak_dias`), recalculados pelo motor PHP (`gamifica_registrar_resultado()` / `recalcular_perfil()`).
- **Triggers do V1 foram removidos** — toda a lógica vive em PHP (`includes/functions.php`), mais portável em hospedagem compartilhada e mais fácil de testar.

### 4.2 Tabelas novas (17)
`xp_eventos`, `moeda_eventos`, `responsavel_aluno`, `feed_familia`, `mensagens`, `portfolio_itens`, `loja_itens`, `loja_resgates`, `aluno_avatar`, `sessoes_ao_vivo`, `sessao_participantes`, `sessao_respostas`, `metas_turma`, `mentorias`, `checkins_bemestar`, `notificacoes`, `integracoes` (+ `turma_vinculos`).

### 4.3 Views novas
- `vw_ranking_evolucao` — XP da semana corrente por aluno/turma (via ledger).
- `vw_clima_turma` — média de humor + participação de check‑in por turma/semana.
- Views do V1 mantidas e corrigidas (`vw_ranking_turma` agora expõe `turma_id`).

### 4.4 Perfis
`aluno · professor · coordenador · admin · responsavel` (novo). Cada perfil tem seu diretório e menu próprios.

## 5. Módulos por perfil (V2)

| Perfil | Novidades V2 |
|---|---|
| **Aluno** | Loja + GamiCoins, avatar, portfólio, check‑in de bem‑estar, ranking de evolução, meta da turma no dashboard, mentoria, entrar em quiz ao vivo por PIN, notificações |
| **Professor** | Hospedar quiz ao vivo, criar metas coletivas, criar mentorias, revisar portfólio, feed da família + mensagens, aprovar resgates da loja, painel de clima da turma |
| **Coordenador** | Índice de Clima da escola, ranking de evolução entre turmas, exportação CSV ampliada |
| **Responsável** *(novo)* | Progresso dos filhos (XP, nível, conquistas), feed positivo, mensagens com professores |
| **Admin** | Gestão da loja, integrações (Google Classroom), módulos novos ativáveis/desativáveis |

## 6. Sequenciamento

- **Agora (esta entrega):** operacionalidade 100% — schema V2, motor de XP/moedas, todas as páginas funcionais dos 5 perfis, seeds de demonstração.
- **Próxima etapa (combinada):** camada visual — refinamento de UI/UX sobre as páginas já funcionais.
- **Depois:** ativação da integração Google Classroom com credenciais reais da escola piloto.
