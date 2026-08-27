# Architecture Decision Records

Uma decisão por arquivo, com o contexto que a produziu e as consequências que ela
aceita. O objetivo não é registrar o que se escolheu — isso o código mostra — e
sim **por que**, e o que foi recusado no caminho.

## Convenção de nome

```
NNNN-titulo-em-kebab-case.md
```

Número sequencial, nunca reaproveitado. Um ADR não se apaga nem se reescreve:
quando a decisão muda, escreve-se um novo que **supera** o anterior, e o antigo
passa a `Superada por ADR-NNNN`.

## Situações

| Situação | Significa |
|---|---|
| `Proposta` | em discussão, ainda não vale |
| `Aceita` | é o que vale hoje |
| `Superada por ADR-NNNN` | valeu, e o novo explica por que deixou de valer |
| `Recusada` | foi considerada e descartada — vale registrar para não voltar |

Use [`0000-template.md`](0000-template.md) como ponto de partida.

## Decisões consolidadas

As decisões estruturais do marketplace nasceram num documento só, em
[`../architecture/decisoes-marketplace.md`](../architecture/decisoes-marketplace.md).
Cada linha daquela tabela é candidata a virar um ADR próprio — o que se ganha é
poder superar uma sem mexer nas outras. A §5 daquele documento, sobre o Mercado
Pago, já foi superada pelos ADRs abaixo.

## Índice

| # | Decisão | Situação |
|---|---|---|
| [0001](0001-gateways-alem-do-mercado-pago.md) | Mais de um gateway, e o núcleo sem saber o nome de nenhum | Aceita |
| [0002](0002-conta-de-pagamento-por-pais.md) | País ISO na oferta, e uma conta de pagamento por país | Aceita |
| [0003](0003-quem-cria-a-cobranca-emite-a-nota.md) | Quem cria a cobrança é o vendedor, porque é ele quem emite a nota | Aceita |
| [0004](0004-cohort-por-empresa-adiado.md) | Cohort por empresa: adiado, e derivado quando vier | Aceita |
