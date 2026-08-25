# Estado do marketplace e o que falta

Atualizado em 2026-08-25, depois da primeira compra real concluida em
`courses.leodg.dev`.

## O que ja funciona

Validado de ponta a ponta em producao: preferencia criada, checkout do Mercado
Pago, pagamento aprovado, webhook recebido, direito de acesso criado e aluno
matriculado.

| Componente | Estado |
|---|---|
| `local_marketplace` | empresa, membros, ofertas, direitos de acesso |
| `paygw_mercadopago` | OAuth com PKCE, vincular/desvincular, preferencia com `marketplace_fee`, webhook |
| `enrol_marketplace` | matricula por diferenca, a partir dos direitos vigentes |
| `availability_marketplace` | seccao liberada por oferta especifica, com botao de compra |
| Infra | VPS Oracle, deploy automatico no push para `dev`, TLS por certbot |

## O que NAO foi validado

Nao e o mesmo que "nao funciona" - e o que ninguem viu funcionar ainda.

**O split.** Vendedor e marketplace foram a mesma conta no teste, entao o
`marketplace_fee` nao transferiu nada. Precisa de uma segunda conta Mercado Pago
real. Ate la, os 25% sao codigo nao exercitado.

**Pix e boleto.** Pix exige chave registrada na conta do vendedor; boleto foi
excluido pelo `wallet_purchase` do modo de teste. Sao justamente os caminhos
assincronos, onde o webhook e a unica fonte da verdade.

**Expiracao de direito.** Ofertas com prazo (`accessdays`) nunca chegaram ao
vencimento num teste.

## Lacunas dentro das fases 1 e 2

Coisas que faltam no que ja foi dado como pronto.

### Nao existe tela para criar nada

Empresa, oferta e membro so nascem por CLI (`seed_demo.php`) ou por chamada
direta a `api::create_company()`. `set_offer.php` altera preco e situacao de
oferta existente, mas nao cria.

Isso bloqueia o requisito de auto-atendimento: "qualquer um se cadastra na
plataforma, e qualquer um pode cadastrar uma empresa vinculada a um perfil".

Falta:
- formulario de cadastro de empresa
- CRUD de oferta, incluindo quais cursos ela libera
- gestao de vendedores da empresa

### Regra do video externo e honra, nao sistema

`course_policy` guarda `hostingtype` por curso e calcula a comissao, e o papel de
vendedor tem `PROHIBIT` em `repository/upload:view`. Mas nada audita se um curso
marcado como `external` passou a hospedar video no `moodledata` por outro
caminho.

Sem uma tarefa que meca o tamanho por curso e alerte, a regra dos 25% depende de
boa fe.

### Assinatura nao renova

`accessmode=recurring` expira em `accessdays` e para. O Mercado Pago **nao tem
recorrencia com split**: `preapproval` nao aceita `marketplace_fee`, e o Checkout
Transparente com cartao salvo exige CVV a cada cobranca.

Hoje o aluno compra de novo. Decisao de negocio pendente: aviso de vencimento, ou
cobranca recorrente fora do split com repasse manual.

### Sem relatorio financeiro

A capability `local/marketplace:viewreport` existe e nao tem pagina. O vendedor
nao ve quanto vendeu, quanto o Mercado Pago reteve e quanto foi de comissao.

Lembrete para quando for feito: a ordem de deducao e fixa. A taxa do Mercado Pago
sai primeiro e os 25% incidem sobre o restante - um relatorio que calcule 25% do
bruto vai divergir do extrato.

### seed_demo cria vendedor sem senha

`user_create_user()` sem senha produz um usuario que ninguem consegue usar. Quem
precisa entrar como vendedor tem que definir a senha por fora.

## Fase 3 - dominio por vendedor

Nada construido.

O plano continua valido: mapa Host->wwwroot gerado em `$CFG->dataroot` e lido
pelo `config.php` antes do `lib/setup.php`. Nao consulta o banco ali porque `$DB`
ainda nao existe.

Falta:
- gerar o arquivo de mapa a cada mudanca de dominio
- trecho no `config.php` da VPS - lembrando que o deploy **preserva** o
  `config.php` existente, entao a mudanca exige apagar o arquivo uma vez
- `server` por dominio no nginx e emissao de certificado no onboarding
- validar que login em um dominio **nao vaza** para o outro

Decisao ja tomada: nao definir `$CFG->sessioncookiedomain`. A sessao passa a ser
por dominio, e isso precisa estar claro na UX de checkout.

## Fase 4 - captacao e relatorio

O tema de captacao foi **abandonado** por qualidade de codigo. Se voltar, comeca
do zero como tema filho do `boost_union`.

O relatorio financeiro esta descrito acima.

## Fase 5 - conteudo hospedado na plataforma

**Bloqueada por decisao de negocio**, nao por tecnica. Falta definir a cobranca
para conteudo hospedado: assinatura com cota de storage e banda mais comissao
menor, ou comissao maior com limite tecnico por curso.

`course_policy::validate_hostingtype()` recusa `platform` hoje, de proposito.

## Multi-pais

Fundacao pronta: `site_id` gravado por conta vinculada, moeda descoberta da
conta, dominio de autorizacao derivado do pais, trava recusando vendedor de pais
diferente.

Falta: um par `client_id`/`client_secret` por pais em vez de um unico, e a empresa
declarar em que pais opera. Ha trabalho iniciado no stash - `siteid` na oferta e
tabela `local_marketplace_account` - que nao foi commitado.

Restricao que decidiu o desenho: `core_payment::get_payable()` nao recebe o
usuario, entao valor, moeda e conta sao funcao pura do `itemid`. Uma oferta nao
pode ser BRL para um aluno e ARS para outro - o pais tem que estar na oferta.

## Divida tecnica conhecida

**`cachejs` amarrado ao debug.** Corrigido no codigo, mas exige apagar o
`config.php` da VPS uma vez para o template novo valer.

**AMD escrito a mao.** Nao ha grunt nem babel: `amd/src` e AMD de verdade e
`amd/build` e o mesmo arquivo com o `define` nomeado. Se alguem introduzir ES6 no
`src` sem um transpilador, o `requirejs.php` serve esse arquivo e o modulo quebra
com "No define call".

**Sem teste automatizado.** Nenhum PHPUnit ou Behat cobre o fluxo de compra.
