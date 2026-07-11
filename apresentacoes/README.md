# Apresentações do Gamifica

Dois decks em PowerPoint (16:9), gerados por script com a identidade visual da plataforma.

| Arquivo | Slides | Uso |
|---|---|---|
| `Gamifica_Apresentacao_Comercial.pptx` | 13 | Venda/institucional. Gatilhos mentais (dor, exclusividade, prova, escassez, ação) focados nas 4 mecânicas que nenhum concorrente tem. |
| `Gamifica_Tutorial_de_Uso.pptx` | 16 | Manual passo a passo por perfil (aluno, professor, coordenador, admin, responsável). |

## Regerar / editar

Os `.pptx` são produzidos por scripts Python (`python-pptx`), então dá para versionar e editar o conteúdo no código:

```bash
pip install python-pptx
cd apresentacoes
python3 gerar_apresentacao_comercial.py   # -> Gamifica_Apresentacao_Comercial.pptx
python3 gerar_tutorial_uso.py             # -> Gamifica_Tutorial_de_Uso.pptx
```

- `estilo_gamifica.py` — kit de estilo compartilhado (paleta roxo #7C6EF0, componentes).
- `render_preview.py` — gera PNGs de conferência a partir de um `.pptx` (prévia de composição; o PowerPoint/Google Slides renderiza com mais fidelidade).

Os arquivos abrem normalmente no PowerPoint, Google Slides e Keynote.
