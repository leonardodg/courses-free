# Modelo de dados do marketplace

As tabelas dos cinco plugins, o que cada campo resolve, e as relações entre elas.

> Extraído de [`../dev/guia-desenvolvedor.md`](../dev/guia-desenvolvedor.md), que
> continua sendo o documento de referência para configuração, testes, domínio por
> vendedor e armadilhas.

---

## Modelo de dados

```mermaid
erDiagram
    company ||--o{ member : "N vendedores"
    company ||--o{ offer : publica
    company ||--o{ entitlement : vendeu
    company ||--o{ course_policy : define
    offer ||--o{ offer_course : libera
    offer ||--o{ entitlement : origina
    company {
        int id PK
        string shortname UK
        int categoryid FK
        string themename
        string hostname UK
        number commissionpct
        string status
    }
    member {
        int id PK
        int companyid FK
        int userid FK
        string memberrole
    }
    offer {
        int id PK
        int companyid FK
        string offertype
        number price
        string currency
        string accessmode
        int accessdays
        int billingdays
        int maxcycles
        string status
    }
    offer_course {
        int id PK
        int offerid FK
        int courseid FK
    }
    entitlement {
        int id PK
        int userid FK
        int offerid FK
        int companyid FK
        int timestart
        int timeend
        string status
        int cycles
        int norenew
    }
    course_policy {
        int id PK
        int courseid FK
        int companyid FK
        string hostingtype
        number commissionpct
    }
```

> **A credencial de pagamento não vive aqui.** Ela fica em
> `payment_gateways.config` do core, na conta de pagamento criada no contexto da
> categoria da empresa. Guardar uma cópia criaria duas fontes de verdade para uma
> credencial financeira — a tabela `local_marketplace_mpaccount` existiu e foi
> removida por isso.

## Tabelas, campo a campo

### `local_marketplace_company`

A empresa vendedora. Uma empresa = uma categoria de cursos.

| Campo | Para que serve |
|---|---|
| `shortname` | Único. Vira o `idnumber` da categoria e entra nas URLs da vitrine e do painel. **Não é editável** — trocar quebraria links já divulgados. |
| `categoryid` | Categoria provisionada na criação. É o contexto onde vivem o papel de vendedor e a conta de pagamento. |
| `themename` | Tema da categoria. Depende de `$CFG->allowcategorythemes`, garantido pelo `install.php`. Vazio limpa e volta ao tema do site. |
| `hostname` | Único. Domínio próprio do vendedor. Resolve pelo mapa gerado — ver *Domínio por vendedor* abaixo. |
| `commissionpct` | Comissão negociada, de 0 a 100. **Nulo herda o padrão do site** — nulo e zero são coisas diferentes. |
| `status` | `active` ou `suspended`. Não há aprovação manual: os portões são a conta de pagamento e o papel restrito. |

### `local_marketplace_member`

N vendedores por empresa; uma pessoa pode estar em várias.

| Campo | Para que serve |
|---|---|
| `memberrole` | `owner` ou `seller`. As capabilities são as mesmas — a distinção existe para impedir que a empresa fique sem responsável pela conta de pagamento. |

> **O vínculo são duas coisas inseparáveis.** Gravar a linha e atribuir o papel
> `marketplaceseller` no contexto da categoria. Só a linha produz alguém que
> consta como vendedor e não consegue fazer nada; só o papel produz alguém
> invisível ao marketplace. Use sempre `api::add_member()` e
> `api::remove_member()`.

### `local_marketplace_offer`

A unidade de **venda**. O curso deixa de ser o que se vende e passa a ser o que
se libera.

| Campo | Para que serve |
|---|---|
| `offertype` | `single` um curso · `bundle` conjunto escolhido · `catalog` tudo da categoria, com cursos novos entrando sozinhos. |
| `price` | Zero torna gratuita, e ofertas gratuitas não passam por gateway nenhum. |
| `country` | ISO-3166 alpha-2. Decide a conta que recebe, a moeda e quais gateways aparecem no checkout. Vive aqui e não na empresa porque `get_payable()` resolve tudo pelo `itemid`, sem saber quem compra. |
| `currency` | **Derivada** do `country`, em `before_validate()`. Não é escolha: uma conta do país X só recebe na moeda de X, e não há câmbio no caminho do split. |
| `accessmode` | `lifetime` · `days` · `recurring`. |
| `accessdays` | Quanto acesso **cada pagamento** libera. |
| `billingdays` | De quanto em quanto tempo se espera o próximo pagamento. Só em `recurring`. |
| `maxcycles` | Quantas cobranças a assinatura admite. `0` = sem limite. |

> **Por que `accessdays` e `billingdays` são separados.** Permitem carência:
> cobrar a cada 30 dias liberando 35 dá margem para o pagamento atrasar sem
> cortar o aluno. Se fossem o mesmo campo — como eram — a carência não existiria.
> E `maxcycles` é o que distingue "mensal por 12 meses" de "mensal enquanto
> quiser".

### `local_marketplace_account`

Conta de pagamento da empresa **em cada país**. Existe porque o `core_payment`
escopa conta por contexto, e todas as contas de uma empresa vivem no mesmo — o da
categoria dela. Sem uma chave por país, `account::get_record(['contextid' => …])`
devolvia *a primeira que aparecesse*, e uma empresa que vende no Brasil e na
Argentina passava a receber em ordem de id.

| Campo | Para que serve |
|---|---|
| `country` | ISO-3166 alpha-2. Único junto com `companyid`. |
| `accountid` | `payment_accounts.id` do core. A conta em si continua sendo dele — aqui mora só o vínculo. |

Ver [ADR-0002](../adr/0002-conta-de-pagamento-por-pais.md).

### `local_marketplace_sale`

O que o marketplace precisa saber de uma venda e o `core_payment` não guarda.
Valor, moeda, gateway, comprador e data ficam em `{payments}`, que é a fonte da
verdade — duplicar daria duas versões do mesmo número financeiro.

| Campo | Para que serve |
|---|---|
| `paymentid` | `payments.id` do core, único. Uma venda por pagamento. |
| `feeamount` | Comissão **efetivamente** enviada, em moeda. Não recalcule a partir do percentual: cada gateway deduz as próprias taxas numa ordem diferente. |
| `externalid` | Id da transação no gateway, para conciliar com o extrato dele. |
| `companyid` | Desnormalizado: o relatório filtra por empresa a cada página. |

Existe porque o relatório lia a tabela do `paygw_mercadopago` direto. Com um
segundo gateway aquilo passaria a **mentir por omissão** — a venda existiria, o
aluno estaria matriculado, e o total simplesmente não contaria o dinheiro.
Ver [ADR-0001](../adr/0001-gateways-alem-do-mercado-pago.md).

### `local_marketplace_entitlement`

O que o aluno comprou. Fonte da verdade do acesso.

| Campo | Para que serve |
|---|---|
| `companyid` | Desnormalizado de propósito: o acesso ao catálogo consulta por empresa a cada página. |
| `timeend` | `0` = vitalício. A vigência confere **a data além do status**: o status só muda quando o cron roda, e confiar nele daria acesso grátis na janela entre o vencimento e a próxima execução. |
| `status` | `active` · `expired` · `cancelled`. `cancelled` é **revogação imediata**, para estorno e fraude. |
| `cycles` | Quantas cobranças já foram pagas. Contado aqui e não na tabela do gateway, porque o limite é regra do marketplace: trocar de meio de pagamento não pode zerar a contagem. |
| `norenew` | O aluno cancelou a assinatura. **Não revoga** — o acesso corre até `timeend`, porque ele pagou por aquele período. Só interrompe os avisos. |

### `local_marketplace_course`

Política de hospedagem e comissão, por curso.

| Campo | Para que serve |
|---|---|
| `hostingtype` | `external` ou `platform`. Hoje só `external` é aceito — `platform` é recusado na validação até a decisão de negócio da Fase 5. |
| `commissionpct` | Percentual retido. Só se aplica em oferta de **curso único**. |

### `paygw_mercadopago`

Transações. Vive no plugin do gateway, não no marketplace.

| Campo | Para que serve |
|---|---|
| `externalreference` | A chave de ligação. O ID do pagamento só existe **depois** que o aluno paga, então precisamos de algo nosso acompanhando desde a criação da preferência. |
| `accountid` | Conta que recebe. É dela que sai o token para consultar o pagamento. |
| `feeamount` | `marketplace_fee` enviado. **Não é 25% do bruto**: a taxa do Mercado Pago sai antes e a comissão incide sobre o resto. |
| `status` | Espelha o Mercado Pago: `pending` · `approved` · `rejected` · `refunded` · `cancelled`. |

### `paygw_asaas`

Transações do Asaas. Mesma ideia, com duas diferenças que valem.

| Campo | Para que serve |
|---|---|
| `environment` | `sandbox` ou `production`. Guardado **na linha** porque uma cobrança criada em homologação não pode ser consultada com a chave de produção: as bases não compartilham dados. |
| `feepercent` | Percentual resolvido no momento da compra. Guardado porque a comissão da empresa pode mudar depois, e a venda antiga precisa continuar explicável. |
| `feeamount` | Comissão em moeda, calculada sobre o valor **bruto**: R$ 100 a 25% são R$ 25,00. Vai aos gateways como valor absoluto (`fixedValue` no Asaas, `marketplace_fee` no Mercado Pago), e não como percentual — o `percentualValue` do Asaas incidiria sobre o `netValue` e devolveria menos. Depois da criação vale o que o gateway devolveu. |
| `billingtype` | `UNDEFINED` deixa o aluno escolher · `PIX` · `BOLETO` · `CREDIT_CARD`. |

A credencial do vendedor **não** fica aqui: vive cifrada no
`payment_gateways.config` do core, por ambiente. Ver
[ADR-0003](../adr/0003-quem-cria-a-cobranca-emite-a-nota.md).
