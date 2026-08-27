# ADR-0001 — Mais de um gateway, e o núcleo sem saber o nome de nenhum

**Situação:** Aceita · **Data:** 2026-08-27

## Contexto

O split é o coração deste modelo de negócio e **nunca tinha sido visto
funcionar**. No Mercado Pago vendedor e marketplace foram a mesma conta no
teste, então o `marketplace_fee` não transferiu nada — e não houve erro, o que é
pior: o código parecia certo.

Tentar provar o split no Mercado Pago esbarrou em limites do próprio produto:

- `POST /preapproval` não aceita `marketplace_fee` — **não existe recorrência
  com split**
- o Transparente com cartão salvo exige CVV a cada cobrança
- o sandbox exige contas de teste dos dois lados, com regras de país

O levantamento em `../gateway-pay/levantamente.txt` apontou dois candidatos com
split nativo em Pix, cartão e boleto: **Pagar.me** e **Asaas**.

Havia também um problema estrutural, independente de fornecedor. O núcleo
conhecia o Mercado Pago pelo nome em quatro lugares:

| Onde | O quê |
|---|---|
| `api.php:135` | lia `defaultfeepercent` do namespace `paygw_mercadopago` |
| `company.php:134` | botão com `gateway=mercadopago` escrito no código |
| `report.php:115` | `SELECT` direto na tabela `paygw_mercadopago` |
| `mysubscriptions.php:160` | idem |

Com um segundo gateway, o relatório passaria a **mentir por omissão**: a venda
existiria, o aluno estaria matriculado, e o total simplesmente não contaria
aquele dinheiro. Sem erro e sem aviso.

## Decisão

Vamos ter **N gateways**, e o núcleo não vai saber o nome de nenhum.

Concretamente:

- a comissão padrão sai do namespace de um fornecedor e passa para as settings
  do `local_marketplace`
- os gateways passam a chamar `api::commission_for()` e `api::record_sale()` —
  pontos únicos, com o guarda de componente dentro, em vez de cada um repetir o
  mesmo bloco
- nasce `local_marketplace_sale`, neutra: guarda o que o `core_payment` não
  guarda — a comissão efetivamente retida e o id da transação — e o resto vem
  de `{payments}` por join
- a lista de meios de pagamento de um país é montada perguntando a cada gateway
  quais moedas e países ele atende, via `component_class_callback`

O primeiro gateway novo é o **Asaas**, e não o Pagar.me como se planejou. A razão
é técnica: no Asaas o split funciona em Pix e a cobrança devolve `invoiceUrl`,
uma página hospedada — o mesmo modelo de redirect que já existia. No Pagar.me o
split em Pix só existe no `POST /orders` transparente; o Link de Pagamento não
faz split em Pix nem em boleto, só em cartão. Começar por ele exigiria construir
página de QR Code e polling **antes** de qualquer split rodar.

## Alternativas consideradas

| Alternativa | Por que não |
|---|---|
| Ficar só no Mercado Pago | Não tem recorrência com split, e o sandbox torna a prova cara. O modelo inteiro depende de algo que não conseguíamos exercitar |
| Pagar.me primeiro, como planejado | Exigiria QR Code próprio e polling antes de a primeira prova de split existir. O Asaas chega ao mesmo lugar por redirect |
| Adicionar os gateways e refatorar o núcleo depois | O relatório mentiria em silêncio no intervalo, e "depois" costuma chegar quando alguém reclama de um número errado |
| Uma tabela de vendas por gateway, e o relatório somando todas | O relatório passaria a conhecer o nome de cada gateway — o problema que se quer resolver, com mais passos |
| Ler só `{payments}` do core, sem tabela nova | Falta lá a comissão retida e o id da transação. Recalcular 25% do bruto diverge do extrato, porque cada gateway deduz as próprias taxas numa ordem diferente |

## Consequências

**Fica mais fácil:** um gateway novo entra declarando moedas e países, sem que
nada no núcleo mude. O relatório soma tudo por construção. Trocar de fornecedor
deixa de ser reescrita.

**Fica mais difícil:**

- são três plugins de pagamento para manter, cada um com o próprio cliente HTTP,
  webhook e ciclo de credencial
- o CI passa de 5 para 7 instalações completas do Moodle por execução
- a comissão exibida no relatório depende de o gateway ter gravado o valor certo
  em `record_sale()` — um gateway que erre ali produz relatório errado sem que o
  núcleo tenha como perceber
- o upgrade precisa adotar o que já existe: conta sem vínculo por país e venda
  que só existia na tabela do Mercado Pago

## Como saber que erramos

Se um gateway novo exigir mudança em `local_marketplace` para funcionar, a
abstração não é a certa e este ADR precisa ser revisitado.

Se o total do relatório divergir da soma dos extratos dos gateways, o
`record_sale()` está sendo alimentado errado por alguém.
