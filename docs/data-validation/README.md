# Validação

Como se verifica que o sistema funciona — e o que ainda não foi visto funcionar.

| Documento | O que cobre |
|---|---|
| [`painel-de-testes.md`](painel-de-testes.md) | caminhos de gestão, CLI, cartões de teste e o que falta provar |
| [`asaas-sandbox.md`](asaas-sandbox.md) | provar o **split** no Asaas: contas, webhook, script e passo a passo com `curl` |
| [`asaas-assinatura.md`](asaas-assinatura.md) | provar o **ciclo** da assinatura no Asaas: cobranca automatica, corte por falta de pagamento e volta ao pagar a atrasada |
| [`mercadopago-split.md`](mercadopago-split.md) | provar o **split** no Mercado Pago: as três contas, painel, túnel e as três rodadas |
| [`local-partners-layout.md`](local-partners-layout.md) | provar o **layout** da landing e do cadastro nas cinco larguras e nos três temas |

Scripts em [`scripts/`](scripts/). Credenciais **nunca** entram aqui: ficam em
`.devcontainer/secrets/`, coberto pelo `.gitignore`. Este repositório está no
GitHub, e chave de API em markdown versionado é um caminho sem volta.

A distinção que importa neste projeto:

- **sem prova** — o código existe, ninguém viu funcionar
- **falta construir** — decidido, não feito
- **bloqueado** — parado por decisão de negócio

O split de 25% está **provado nos dois gateways**: Asaas em 2026-08-27, Mercado
Pago em 2026-09-08 (R$ 5,00 → R$ 1,25 de `application_fee`, com `collector_id`
diferente do dono da aplicação). Antes disso, no Mercado Pago vendedor e
marketplace eram a mesma conta e o `marketplace_fee` não transferia nada — sem
erro nenhum, que é o pior tipo de falso positivo.

No Mercado Pago o vendedor daquela rodada era **pessoa física** — o CNPJ é
exigido da plataforma, não de quem vende.

A **compra pelo Moodle com comissão maior que zero** foi provada em 2026-09-08,
pela vitrine e com o webhook chegando sozinho: R$ 5,00 → R$ 1,25 de comissão,
`feesource = company`, direito de 30 dias e matrícula.

Continua **sem prova**: o vendedor pessoa jurídica no Mercado Pago.
