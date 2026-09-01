# local_marketplace

O núcleo da plataforma. Empresas, ofertas, direitos de acesso, vendas,
relatórios, vitrine e a resolução da comissão.

**Este plugin não sabe o nome de nenhum gateway.** Ele pergunta a cada gateway
instalado que moedas e países atende, e é isso que permite acrescentar um
terceiro sem tocar no núcleo. Se você se pegar escrevendo `if ($gateway ===
'asaas')` aqui dentro, o desenho foi violado.

## Para que serve

Uma plataforma onde qualquer pessoa publica curso, gratuito ou pago, e a
plataforma retém uma comissão sobre as vendas.

```
Empresa (local_marketplace_company)
  ├── categoria de curso          → isolamento, contexto, tema
  ├── contas de pagamento         → UMA POR PAÍS
  ├── domínio próprio             → opcional
  └── ofertas (cada uma com country ISO)
        ├── direitos de acesso    → enrol + availability + block leem daqui
        └── vendas                → neutras de gateway
```

**Empresa é uma categoria de cursos.** Não é escolha estética: o `core_payment`
escopa conta de pagamento por **contexto**, e a categoria é o que dá contexto,
papel e tema à empresa.

**O direito de acesso é a fonte única da verdade.** Matrícula e liberação de
seção leem `local_marketplace_entitlement`. Ninguém lê a venda para decidir
acesso — venda e acesso são coisas diferentes, e confundi-las quebra estorno,
prazo e renovação de uma vez só.

## Dependências

Nenhuma além do Moodle 5.2 (`requires 2026042000`). É o plugin do qual os
outros dependem, e não o contrário.

Precisa de **pelo menos um gateway** instalado para vender: `paygw_asaas` ou
`paygw_mercadopago`. Sem gateway ele continua funcionando para curso gratuito.

## O que precisa configurar

`/admin/settings.php?section=local_marketplace_settings`

| Configuração | Padrão | O que faz |
|---|---|---|
| `defaultfeepercent` | `25` | Comissão do site, último degrau da cadeia |
| `commissionbase` | `gross` | Base de cálculo: sobre o bruto ou sobre o líquido |
| `defaultcountry` | — | País da primeira conta de uma empresa nova |

### A cadeia da comissão

Resolvida por `api::resolve_commission()`, do mais específico ao mais genérico:

```
1. course_policy    (só em oferta de curso único)
2. company          (comissão negociada com esta empresa)
3. plan             (plano contratado pela empresa)
4. padrão do site
```

**A base sai do mesmo degrau que deu a taxa.** Resolver as duas em cadeias
independentes produziria "taxa do plano com base do site" — combinação que
nenhum contrato tem, e que o parceiro não conseguiria conferir.

Coluna `commissionbase` **nula** significa "este contrato não define a base,
herda a do site". É diferente de escolher `gross`: gravar o valor congelaria a
base ali para sempre, e depois não haveria como distinguir quem escolheu de quem
herdou. Vale a mesma lógica para `commissionpct` nula na empresa — "não
negociamos nada" e "negociamos zero" precisam ser distinguíveis.

Detalhe em [ADR-0007](../../../docs/adr/0007-comissao-sobre-o-bruto.md).

### Capacidades

| Capacidade | Para quem |
|---|---|
| `local/marketplace:manageall` | administrador da plataforma |
| `local/marketplace:createcompany` | quem provisiona empresa |
| `local/marketplace:managecompany` | dono da empresa, no contexto dela |
| `local/marketplace:managepayment` | quem vincula conta de recebimento |
| `local/marketplace:publishcourse` | quem publica oferta |
| `local/marketplace:viewreport` | quem vê o relatório da empresa |

O papel da empresa é criado no provisionamento e atribuído no **contexto da
categoria** — é o que impede a empresa A de enxergar a B.

## Telas

| URL | O que é |
|---|---|
| `/local/marketplace/admin/companies.php` | empresas, com a comissão efetiva e de onde ela veio |
| `/local/marketplace/admin/plans.php` | planos comerciais e as faixas de resolução |
| `/local/marketplace/report.php?company=<shortname>` | vendas, cursos, alunos e assinaturas |

O relatório é filtrado por `company` **shortname**, não por id.

## Armadilhas

**Não recalcule a comissão no relatório.** Guarde o que o gateway devolveu.
Estorno parcial e split recusado mudam o valor depois da criação, e um relatório
que discorda do extrato é pior que relatório nenhum. Por isso `feepercent`,
`feebase` e `feesource` são fotografados em `local_marketplace_sale`: mudar a
configuração não pode reescrever o passado.

**Dinheiro é `TYPE="number"` com `DECIMALS`**, nunca float, no XMLDB.

**`get_payable()` do core não recebe o usuário.** Valor, moeda e conta são função
pura do `itemid` — por isso o país vive na oferta, e "o mesmo curso em BRL para
um aluno e ARS para outro" é impossível por construção. Plano em outro país é
outra oferta.

**O split só ocorre entre contas do mesmo país.** Uma conta guarda só a moeda do
próprio país, e não há câmbio no caminho.

**`allowcategorythemes`** precisa estar ligado para o tema por empresa funcionar.
O `db/install.php` garante; num ambiente que veio de antes, confira.

## Testes

```bash
docker exec -u 1000:33 -w /var/www/html courses-free-moodle-1 \
  php vendor/bin/phpunit --testsuite local_marketplace_testsuite
```

102 testes. Os que mais importam: `commission_test.php` (a cadeia e a base) e
`sale_test.php` (a foto dos termos resistindo a mudança de configuração).
