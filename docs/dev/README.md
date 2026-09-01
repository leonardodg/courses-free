# Desenvolvimento

| Documento | O que cobre |
|---|---|
| [`estrutura-worktrees.md`](estrutura-worktrees.md) | o bare repo e as worktrees: como montar e operar |
| [`cf.md`](cf.md) | o comando `cf`: ambientes por worktree, referência completa |
| [`guia-worktrees.md`](guia-worktrees.md) | o fluxo: portas, `.env`, VS Code, a VPS, armadilhas |
| [`guia-desenvolvedor.md`](guia-desenvolvedor.md) | plugins, configuração, testes, domínio por vendedor |
| [`fluxo-de-contribuicao.md`](fluxo-de-contribuicao.md) | da worktree ao deploy: PR, CI, merge em `dev` |
| [`behat.md`](behat.md) | inicializar e rodar behat, e o que ele encontra que o phpunit não pega |

Manual do sistema: `man cf` — instalação em [`cf.md`](cf.md#manual-do-sistema).

## Em uma linha

```bash
cf new minha-feature      # worktree nova, servida pelo container que já roda
```
