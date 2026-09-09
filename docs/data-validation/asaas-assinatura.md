# Provar o ciclo da assinatura no Asaas

Roteiro do débito automático: assinatura criada, ciclo cobrado, acesso cortado
por falta de pagamento e devolvido ao pagar a atrasada — tudo sem esperar mês
nenhum.

O irmão deste documento é [`asaas-sandbox.md`](asaas-sandbox.md), que prova o
split da cobrança avulsa. Aqui o que se prova é o **ciclo**.

## O que torna isto possível sem esperar

**O Asaas gera várias cobranças de uma vez.** Uma assinatura semanal nasce com
quatro ou cinco cobranças `PENDING`, com vencimentos espaçados. Dá para pagar uma
por vez, na ordem que quiser, e cada pagamento dispara o webhook de verdade.

Duas consequências, e as duas custaram correção:

**A lista volta da mais distante para a mais próxima.** Pegar a primeira do array
mandava o aluno pagar a fatura do mês seguinte, deixando a de hoje vencer atrás
dele. Por isso existe `payment_processor::earliest_charge()`.

**Não existe ciclo diário.** `DAILY` é recusado — o Asaas responde *"O parâmetro
cycle deve ser informado"*, tratando o valor como ausente. O mais curto é
`WEEKLY`. Como o marketplace conta acesso em dias, a tradução é lossy, e o
**empate vai para o ciclo maior**: cobrar mais cedo que o combinado tira do aluno
dinheiro que ele não contratou.

## Preparar

Sandbox não liquida Pix. O que processa na hora é **cartão fictício**, então a
oferta de teste usa `CREDIT_CARD`.

```bash
# Configuracao do site
moodev cli cfg.php --component=paygw_asaas --name=environment --set=sandbox
moodev cli cfg.php --component=paygw_asaas --name=billingtype --set=CREDIT_CARD
moodev cli cfg.php --component=paygw_asaas --name=usecallback --set=0
```

`usecallback=0` evita a checagem de domínio: o `returnUrl` exige que o endereço
da plataforma esteja cadastrado na conta do vendedor, e para esta prova o webhook
basta — ele não passa por essa validação.

O aluno precisa de **CPF num campo de perfil**, apontado em `documentfield`. Sem
ele o Asaas recusa a cobrança com *"é necessário preencher o CPF ou CNPJ"*, e a
falha só aparece no meio do checkout.

Webhook, pela API em vez do painel:

```bash
curl -s -X POST "$ASAAS_BASE_URL/webhooks" \
  -H "access_token: $ASAAS_SELLER_PJ_API_KEY" -H "Content-Type: application/json" \
  -d '{"name":"Moodle","url":"<site>/payment/gateway/asaas/webhook.php",
       "email":"...","enabled":true,"authToken":"'"$ASAAS_WEBHOOK_TOKEN"'",
       "sendType":"SEQUENTIALLY","events":["PAYMENT_RECEIVED","PAYMENT_CONFIRMED"]}'
```

A oferta: `accessmode = recurring`, `billingdays = 7`, `accessdays = 7`.

## O roteiro

1. **Assinar.** A compra cria a assinatura e manda o aluno para a cobrança que
   vence primeiro.
2. **Pagar o ciclo 1** com `POST /payments/{id}/payWithCreditCard`.
3. **Ver o aviso**, movendo o `timeend` do direito para dentro de cada marco e
   rodando `notify_expiring`.
4. **Não pagar:** `timeend` para o passado e `sync_entitlements` — o acesso cai.
5. **Pagar a atrasada** com o mesmo `payWithCreditCard` numa cobrança seguinte —
   o acesso volta sozinho, pelo webhook.

Cartão do sandbox: `5162306219378829`, `05/2029`, CCV `318`.

## Resultado — 09/09/2026

Assinatura `sub_3of118zoofc4al9x`, R$ 10,00 por ciclo, comissão 25% sobre o bruto
(`fixedValue: 2.50`).

| Etapa | O que aconteceu |
|---|---|
| Assinatura criada | 4 cobranças `PENDING`, e o plugin escolheu a de vencimento **mais próximo** |
| Ciclo 1 pago | webhook entregou sozinho: direito ativo, 4 matrículas, venda com `feesource company` |
| Avisos | 2 mensagens, uma por marco, com textos diferentes |
| Não pagou | `sync_entitlements` suspendeu **as 4** matrículas |
| Pagou a atrasada | ciclo adotado, acesso de volta, **1 direito** com `cycles = 3` |

Três linhas em `paygw_asaas`, uma por ciclo, `fee 2.50` em cada — e três vendas
em `local_marketplace_sale`. É isso que faz o relatório mostrar quanto entrou em
cada mês.

## O bug que este roteiro encontrou

**Pagar a atrasada criava um segundo direito em vez de reviver o primeiro.**

O `deliver_order()` procurava direito **ativo** para estender. Quem deixa vencer
tem o direito marcado como `expired` pelo cron — então nada era encontrado, e um
novo nascia ao lado.

O estrago não é visual: `cycles` voltava a `1`, e com ele o `maxcycles` de uma
assinatura de doze meses nunca terminaria; a tela do aluno listava a oferta duas
vezes; e todo `get_record` que espera um só quebrava com *"found more than one
record"*.

Corrigido procurando **ativo ou vencido**. `cancelled` fica de fora de propósito:
revogar é decisão de negócio, e um pagamento novo não pode desfazê-la em
silêncio — nesse caso nasce direito novo e a revogação continua no histórico.

O período novo conta **a partir de agora** quando o vencimento já passou: quem
ficou dois dias sem pagar não ganha os dois dias de volta.

## Armadilhas

**Direito de outra oferta segura o acesso.** Na primeira tentativa o corte
suspendeu zero matrículas — o aluno ainda tinha direitos de outras ofertas
cobrindo os mesmos cursos. É o sync trabalhando por diferença, e está certo; para
demonstrar o corte, isole a assinatura.

**Baixa manual não prova nada.** `receiveInCash` cancela o split e zera os
saldos. Ver `asaas-sandbox.md`.

**O webhook fica na conta do VENDEDOR**, não na da plataforma: é ele quem cria as
cobranças.
