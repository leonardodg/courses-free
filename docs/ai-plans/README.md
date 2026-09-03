# Planos executados por agentes de IA

Registro de todo trabalho planejado e executado por agente de IA neste projeto.
Um arquivo por plano, em ordem cronológica.

## Por que isto existe

Um agente chega a cada sessão sem memória do que foi feito antes. O código conta
*o que* mudou; o commit conta *por quê* aquela mudança. Nenhum dos dois conta o
que foi **considerado e descartado**, quais premissas se mostraram falsas no
meio do caminho, ou o que ficou por fazer de propósito.

Sem esse registro, a sessão seguinte reabre discussões já encerradas — e às
vezes desfaz uma decisão sem saber que era decisão.

## Convenção de nome

```
AAAA-MM-DD-titulo-em-kebab-case.md
```

A data é a do **início** do trabalho.

## O que cada plano deve conter

| Seção | Para quê |
|---|---|
| **Contexto** | o problema, e como ele apareceu |
| **Decisões** | o que foi escolhido, com a alternativa recusada e o motivo |
| **O que mudou** | arquivos criados, alterados, removidos |
| **Descobertas** | o que se aprendeu no caminho — em geral a parte mais cara de redescobrir |
| **Verificação** | como se provou que funciona, com o resultado real |
| **Em aberto** | o que ficou de fora, e por quê |

Duas regras que fazem a diferença entre registro útil e ata de reunião:

**Registre o que foi descartado.** "Consideramos X e recusamos porque Y" evita
que a próxima sessão gaste horas chegando ao mesmo Y.

**Registre o resultado medido, não a intenção.** "Deve ficar mais rápido" não
ajuda ninguém; "abrir uma worktree existente leva ~1s, medido" ajuda.

## Índice

| Data | Plano | Resultado |
|---|---|---|
| 2026-08-26 | [`cf`, fluxo de worktrees](2026-08-26-cf-fluxo-de-worktrees.md) | entregue — PRs #50, #51, #52 |
| 2026-08-27 | [Gateways além do Mercado Pago](2026-08-27-gateways-asaas-e-pagarme.md) | parcial — Asaas entregue e **split provado**; Pagar.me em espera do CNPJ |
| 2026-09-03 | [Portal do aluno no `format_ldg`](2026-09-03-portal-do-aluno-format-ldg.md) | desenho aprovado; implementação não começou |
| 2026-09-03 | [Portal, plano 1: chrome e layout](2026-09-03-portal-plano-1-chrome-e-layout.md) | **executado** — 5 commits, testes verdes; planos 2 a 4 a escrever |
