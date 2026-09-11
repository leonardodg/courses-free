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

Não há campo de comissão aqui. A comissão é regra do marketplace, e vive em
`local_marketplace/defaultfeepercent`; sem o marketplace instalado, o plugin cai
num padrão de fábrica de 25%. Existiu um `defaultfeepercent` nesta tela até
08/09/2026, mas **nenhuma linha de código o lia** — o valor já havia sido
migrado, e o texto de ajuda ainda dizia que a comissão incidia sobre o líquido,
o contrário do que a seção abaixo explica.

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

**O split foi provado em 08/09/2026**, com duas contas distintas e dinheiro real.
Pagamento `178004552586`, Pix: R$ 5,00 brutos − R$ 0,05 de taxa do Mercado Pago −
R$ 1,25 de `application_fee` = R$ 3,70 para o vendedor, e os R$ 1,25 no extrato
da plataforma. O `collector_id` foi o vendedor, e não o dono da aplicação — que é
a condição sem a qual o número não significa nada. Roteiro em
[`docs/data-validation/mercadopago-split.md`](../../../../docs/data-validation/mercadopago-split.md).

**O vendedor não precisa de CNPJ.** A conta que vendeu naquela rodada é pessoa
física — `/users/me` devolve `identification.type` vazio e sem a tag `business`.
Quem precisa ser pessoa jurídica é a **plataforma**, dona da aplicação, porque é
ela que recebe a comissão. Continua sem prova o caso inverso, vendedor pessoa
jurídica, que é o convencional e não aparenta risco.

Foi essa rodada que confirmou a ordem de dedução descrita acima: a taxa do MP
saiu primeiro, e o `marketplace_fee` saiu do que sobrou.

**O sandbox não serve para provar isto.** Com `purpose: wallet_purchase` o
Checkout Pro entra em loop de redirecionamento no login de carteira; sem ele, a
tela devolve erro. Nos dois casos `payments/search` devolve zero pagamentos — não
há o que conferir. Não perca tempo ali: a prova é com conta real e valor mínimo.

**Continue desconfiando de sucesso sem erro.** O `marketplace_fee` já pareceu
funcionar sem transferir nada, quando vendedor e marketplace eram a mesma conta.
A pergunta que decide é sempre `collector_id != dono da aplicacao`, e é por isso
que o script da prova aborta sozinho quando os dois coincidem.

## Testes

```bash
docker exec -u 1000:33 -w /var/www/html ldg-courses-moodle-1 \
  php vendor/bin/phpunit --testsuite paygw_mercadopago_testsuite
```

24 testes: PKCE, moeda por país, URL de autorização, a camada HTTP e o
`marketplace_fee`.

A costura `make_curl()` foi replicada do `asaas_client` em 08/09/2026, e é o que
tornou testável o que antes só era exercitável batendo na API: o corpo enviado, o
mapeamento de erro e o `test_token` do OAuth. Ela é **estática**, diferente da do
Asaas, porque o `post_json()` do fluxo OAuth também é — e a chamada usa
`static::`, senão o late static binding não alcança a subclasse falsa e o teste
volta a bater na rede sem avisar.

Junto veio a extração de `fee_for()` e `build_preference_body()` do
`start_payment()`. O `marketplace_fee` é o único número deste plugin que move
dinheiro, e ficou sem teste enquanto só existia dentro de um método que precisa
de banco, sessão e rede para rodar.

Continua fora do teste automatizado: `process_notification()`,
`locate_transaction()` e as três páginas de OAuth.
