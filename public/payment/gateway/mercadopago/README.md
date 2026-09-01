# paygw_mercadopago

Gateway Mercado Pago para o `core_payment`, com Checkout Pro e `marketplace_fee`.

## Para que serve

O vendedor autoriza a nossa aplicação por **OAuth**, e é essa autorização que
permite o `marketplace_fee` voltar para a plataforma. A preferência de pagamento
é criada com o token do vendedor: o dinheiro nasce na conta dele, e a comissão é
retida na origem.

O webhook manda **só o ID**, nunca o status — e isso é de propósito do Mercado
Pago. Confiar no corpo da notificação permitiria a qualquer um POSTar "aprovado"
no nosso endpoint; o status é sempre consultado na API.

## Dependências

Nenhuma declarada. Com o `local_marketplace` instalado a comissão vem dele; sem
ele, cai no padrão de fábrica.

## O que precisa configurar

`/admin/settings.php?section=paymentgatewaymercadopago`

| Configuração | O que faz |
|---|---|
| `clientid` / `clientsecret` | credenciais **da aplicação**, não da conta |
| `platformsite` | site do Mercado Pago da plataforma (MLB, MLA…) |
| `testmode` | usa `test_token` no OAuth |
| `defaultfeepercent` | comissão de fallback quando não há marketplace |

No painel do Mercado Pago, a integração precisa ser declarada como **"API de
Preferências"** — o `marketplace_fee` vai na preferência, e não no pagamento.

## A comissão aqui é sempre sobre o bruto, e não por escolha

O `marketplace_fee` é **valor absoluto**, e a taxa do Mercado Pago só é conhecida
depois que o pagamento acontece. Não há como cobrar um percentual de um número
que ainda não existe.

Consequência: quando o marketplace está configurado com base **líquida**, a venda
por aqui sai sobre o **bruto** e a linha grava `feebase = 'gross'`. A
configuração diz a intenção, a venda diz o fato — expor a divergência é melhor
que gravar a intenção e deixar o relatório mentir. Ver
[ADR-0007](../../../../docs/adr/0007-comissao-sobre-o-bruto.md).

**Não confunda com a ordem de dedução.** A taxa do Mercado Pago sai primeiro, do
lado do vendedor, e o `marketplace_fee` sai do que sobra. Isso não muda quanto a
plataforma recebe — muda quem absorve a taxa. Se não sobrar saldo para o
`marketplace_fee`, quem recusa é o Mercado Pago.

## Limitações conhecidas do gateway

**Não há recorrência com split.** `preapproval` não aceita `marketplace_fee`, e o
Transparente com cartão salvo exige CVV a cada cobrança. Assinatura neste projeto
é **acesso com prazo mais aviso de vencimento**, nunca débito automático. Foi o
que motivou procurar um segundo gateway — ver
[ADR-0001](../../../../docs/adr/0001-gateways-alem-do-mercado-pago.md).

**São três partes no split:** comprador, vendedor e a **aplicação**. Misturar
ambientes — aplicação de produção com vendedor de teste — é recusado com "uma das
partes é de teste". O `test_token` no OAuth resolve.

## Armadilhas

**Empresa aparece "sem meio de pagamento" mesmo após vincular:**
`account::is_available()` exige o gateway **habilitado**, e não só o token
presente.

**A taxa do MP não é reportada de volta.** Ela varia por meio de pagamento e
prazo de repasse. O relatório não a exibe, e não deve inventá-la: o líquido do
vendedor é o do extrato dele.

**O split no Mercado Pago continua sem prova.** Diferente do Asaas, nunca foi
visto transferindo entre duas contas distintas neste projeto. Antes de confiar,
repita o roteiro do Asaas aqui — e desconfie de sucesso sem erro: foi exatamente
assim que o `marketplace_fee` pareceu funcionar sem transferir nada, quando
vendedor e marketplace eram a mesma conta.

## Testes

```bash
docker exec -u 1000:33 -w /var/www/html courses-free-moodle-1 \
  php vendor/bin/phpunit --testsuite paygw_mercadopago_testsuite
```

8 testes, cobrindo PKCE, moeda por país e a URL de autorização.

A cobertura é menor que a do Asaas por um motivo estrutural: o `mp_client`
instancia curl inline, então a montagem de corpo e o mapeamento de erro só seriam
exercitáveis batendo na API de verdade. O `asaas_client` tem a costura
`make_curl()` e por isso é testável sem rede — vale replicar aqui quando este
arquivo for mexido.
