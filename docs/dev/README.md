# Desenvolvimento

O ambiente inteiro atende por um comando: **`moodev`** — *Moodle Dev*. Ele
reúne o fluxo de worktrees, o devcontainer e o ferramental de desenvolvimento
(`phpcs`, `moosh`, `grunt`, o Chrome do Behat).

> Ele se chamava `cf`, de *courses-free*, quando só cuidava de worktrees deste
> projeto. `cf` segue funcionando como atalho.

| Documento | O que cobre |
|---|---|
| [`moodev-em-projeto-novo.md`](moodev-em-projeto-novo.md) | **montar tudo isto num fork novo do Moodle**, do bare repo ao primeiro teste |
| [`estrutura-worktrees.md`](estrutura-worktrees.md) | o bare repo e as worktrees: como montar e operar |
| [`moodev.md`](moodev.md) | o comando `moodev`: ambientes por worktree, referência completa |
| [`guia-worktrees.md`](guia-worktrees.md) | o fluxo: portas, `.env`, VS Code, a VPS, armadilhas |
| [`guia-desenvolvedor.md`](guia-desenvolvedor.md) | plugins, configuração, testes, domínio por vendedor |
| [`fluxo-de-contribuicao.md`](fluxo-de-contribuicao.md) | da worktree ao deploy: PR, CI, merge em `dev` |
| [`behat.md`](behat.md) | inicializar e rodar behat, e o que ele encontra que o phpunit não pega |
| [`portal-conferencia-visual.md`](portal-conferencia-visual.md) | medir o portal no Chrome contra o design system, e o que nem behat nem phpunit pegam |

Manual do sistema: `man moodev` — instalação em [`moodev.md`](moodev.md#manual-do-sistema).

## Em uma linha

```bash
moodev new minha-feature      # worktree nova, servida pelo container que já roda
```
