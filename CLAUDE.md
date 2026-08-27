# Marketplace de cursos — contexto para agentes

Plataforma Moodle 5.2 onde qualquer pessoa publica curso gratuito ou pago, com
split de pagamento. Três gateways: Mercado Pago, Asaas e Pagar.me.

Este arquivo é carregado automaticamente a cada sessão. Leia antes de propor
qualquer coisa — várias decisões aqui parecem erradas até você conhecer a razão.

## O que já foi decidido, e não é para revisitar

| Decisão | Por quê |
|---|---|
| **Sem fork do Moodle** | IOMAD modifica 171 arquivos do core e remove 14. Prende o projeto à versão dele. Tudo que ele resolve, exceto domínio por vendedor, já existe no 5.2. |
| **Empresa = categoria de cursos** | Dá contexto para papel, tema e conta de pagamento. Não é escolha estética: o `core_payment` escopa conta por contexto. |
| **Direito de acesso é a fonte única da verdade** | Matrícula e liberação de seção leem `local_marketplace_entitlement`. Ninguém lê a venda para decidir acesso. |
| **Sem auto-atendimento para criar empresa** | Criar empresa cria uma **categoria**, objeto global. A parceria é fechada fora do sistema; o admin provisiona. |
| **Campos, não HTML livre, na vitrine** | HTML do vendedor A rodando no navegador do aluno da empresa B é XSS entre inquilinos. Quem quer página própria usa a API. |
| **Sem `$CFG->sessioncookiedomain`** | A sessão passa a ser por domínio. Login no domínio do vendedor não vale na plataforma — comportamento desejado. |

## Restrições externas que moldaram o desenho

Não são preferências. São limites de terceiros, verificados.

**`core_payment::get_payable()` não recebe o usuário.** Valor, moeda e conta são
função pura do `itemid`. Uma oferta não pode ser BRL para um aluno e ARS para
outro — por isso o país vive na oferta, e planos por país são ofertas separadas.

**O Mercado Pago não tem recorrência com split.** `preapproval` não aceita
`marketplace_fee`, e o Transparente com cartão salvo exige CVV a cada cobrança.
Assinatura aqui é acesso com prazo mais aviso de vencimento — não débito
automático. Foi o que motivou procurar outro gateway: ver `docs/adr/0001`.

**Quem cria a cobrança é o vendedor.** Não é escolha de arquitetura, é regra
fiscal: a plataforma não emite nota por outra empresa. A cobrança nasce na conta
dele, o líquido fica com ele, e o split leva só a comissão. Ver `docs/adr/0003`.

**A comissão incide sobre o líquido, não sobre o bruto.** Cada gateway deduz as
próprias taxas antes de dividir — no Asaas, R$ 100 viram R$ 97,52 e 25% disso
são R$ 24,38. Nunca recalcule no relatório: guarde o que o gateway devolveu.

**Baixa manual não prova split.** `receiveInCash` faz o split sair `CANCELLED`
com o valor certo na tela. Dinheiro que não passou pelo gateway não tem como ser
dividido.

**O split só ocorre entre contas do mesmo país.** A comissão cai na conta da
plataforma, e uma conta só guarda a moeda do próprio país. Não há câmbio no
caminho — por isso a oferta tem `country` em ISO, e a moeda é derivada dele.

**São três partes no split:** comprador, vendedor e a **aplicação**. Misturar
ambientes — aplicação de produção com vendedor de teste — é recusado com "uma das
partes é de teste". O `test_token` no OAuth resolve.

## Arquitetura em uma tela

```
Empresa (local_marketplace_company)
  ├── categoria de curso          → isolamento, contexto, tema
  ├── contas de pagamento         → UMA POR PAÍS (local_marketplace_account)
  ├── domínio próprio             → mapa Host→empresa lido pelo config.php
  └── ofertas (cada uma com country ISO)
        ├── direitos de acesso    → enrol + availability + block leem daqui
        └── vendas                → local_marketplace_sale, neutra de gateway
```

Seis plugins:

- `local_marketplace` — núcleo. Empresas, ofertas, direitos, vendas, relatórios,
  vitrine, telas de admin, `core_payment\service_provider`. **Não sabe o nome de
  gateway nenhum**: pergunta a cada um que moedas e países atende
- `paygw_mercadopago` — Checkout Pro com split. Todo HTTP passa por `mp_client`
- `paygw_asaas` — split em Pix, boleto e cartão. Credencial do vendedor cifrada,
  ambientes lado a lado, webhook autenticado
- `enrol_marketplace` — matrícula por diferença, a partir dos direitos
- `availability_marketplace` — libera seção mediante compra
- `block_marketplace` — assinaturas do aluno no Dashboard

Detalhes de tabela e campo: `docs/dev/guia-desenvolvedor.md`.

## Ambiente

Worktrees de um bare repo em `/home/leodg/localhost/gitworktree-bare-moodle/`.
A worktree de repouso é `dev`; as de trabalho nascem e morrem com as features.
Use `cf ls` para ver quais existem e qual está sendo servida — não presuma.

**Índice da documentação: `docs/README.md`.**

**Cada worktree tem o próprio ambiente, e vários rodam ao mesmo tempo.** O
comando é o `cf` (`.devcontainer/bin/cf`): `cf ls` mostra worktrees, offsets,
portas e status; `cf new <nome>` cria worktree, ambiente, dados e stack, e
ramifica de `origin/dev` por padrão. Cada worktree recebe um offset, e dele saem
o nome do stack e as portas — offset 0 é o principal (`courses-free`,
8080/8443/3307/9004), offset 1 soma 10 a cada uma.
Guia completo em `docs/dev/guia-worktrees.md`.

Não edite `.env` nem portas à mão: o `cf` gera esses arquivos e o `cf doctor`
reclama quando divergem do registro.

O `cf` do `PATH` é um symlink encadeado que resolve para
`dev/.devcontainer/bin/cf`. Ao editar o próprio `cf` numa branch, chame pelo
caminho (`./.devcontainer/bin/cf`) — `cf` puro executa a versão de `dev`.

Moodle 5.2 usa layout `public/` — os plugins ficam em `public/local/…`,
`public/payment/gateway/…`, e o `config.php` fica na raiz, fora do webroot.

**Fluxo:** commit no branch de feature → PR para `dev` → merge dispara deploy
automático para a VPS. Não há PR `dev`→`main` no caminho normal.

Container local: `courses-free-moodle-1` (Apache + PHP 8.4) e `courses-free-db-1`
(MariaDB 11.4).

## Comandos que funcionam

Rodar como `-u 1000:33` — uid do host, grupo `www-data`. Sem isso o PHPUnit não
escreve no dataroot.

```bash
# Testes
docker exec -u 1000:33 -e COMPOSER_HOME=/tmp/composer courses-free-moodle-1 \
  php /var/www/html/public/admin/tool/phpunit/cli/init.php
docker exec -u 1000:33 -w /var/www/html courses-free-moodle-1 \
  php vendor/bin/phpunit --testsuite local_marketplace_testsuite

# phpcs — LEIA O TOTAL, não corte a saída
docker exec -u 1000:33 courses-free-moodle-1 sh -c \
  'cd /tmp/cs && ./vendor/bin/phpcs --standard=moodle --report=summary <caminho>' \
  | grep -E "A TOTAL OF"

# CLI do marketplace, na VPS (o < /dev/null é obrigatório)
docker compose exec -T moodle \
  php /var/www/html/public/local/marketplace/cli/status.php < /dev/null
```

## Erros já cometidos aqui — não repita

**`tail -3` no phpcs esconde o relatório.** Reportei "zero violações" com 16
erros presentes; o CI reprovou. Sempre leia o total.

**Regex cego em comentários corrompeu o cabeçalho GPL de 74 arquivos.** Um padrão
que capitaliza `// texto` também pega a segunda linha de comentários
multi-linha. Corrija por arquivo e linha exatos.

**`cd` no Bash persiste entre chamadas.** Um `cd` numa etapa me fez concluir que
arquivos do core não existiam, e criar um diretório no lugar errado. Use caminho
absoluto ou confira o `pwd`.

**Backup dentro do diretório que o rsync sincroniza com `--delete` não é
backup.** O deploy seguinte apagou a cópia do `config.php` durante um incidente.

**Remoção e recriação em blocos separados abrem janela de indisponibilidade.**
Removi o `config.php`, o script morreu antes de recriar, e o instalador do Moodle
ficou exposto. Escrita de arquivo crítico: grave ao lado e mova.

**Automação cara para tarefa única.** Construí uma entrada de workflow para
substituir dois comandos manuais; custou commit, deploy, quinze minutos e o site
fora do ar. O usuário havia apontado isso antes.

**Strings de idioma têm ordem alfabética obrigatória.** Inserir por âncora quebra
o `phpcs`. Reordene o arquivo inteiro depois de acrescentar.

**Commits empurrados depois de o PR ser merjeado ficam órfãos.** Aconteceu quatro
vezes. Avise antes de o usuário merjear, ou segure o commit.

## Armadilhas do Moodle nesta base

| Sintoma | Causa |
|---|---|
| `Section error` | A seção do gateway é `paymentgateway<nome>`, não `paygw_<nome>` |
| Asaas recusa a cobrança inteira | Conta do vendedor sem o domínio **da plataforma** cadastrado em Minha Conta, ou aluno sem CPF no perfil |
| Webhook do Asaas respondendo 401 | Token vazio ou divergente entre o painel e a config do Moodle |
| `No define call` | `requirejs.php` serve `amd/src` quando não há `.map`. **Não há transpilador**: o `src` precisa ser AMD de verdade |
| Botão exige dois cliques | `cachejs` desligado faz cada módulo AMD virar uma requisição |
| Upgrade quebra em `messages.php` | `MESSAGE_DEFAULT_LOGGEDIN` não existe no 5.2. Use `MESSAGE_DEFAULT_ENABLED` |
| Empresa "sem meio de pagamento" após vincular | `account::is_available()` exige o gateway **habilitado**, não só o token |
| Filtro `branch=5.2` da API do diretório engana | Ele vai pelo `requires` (mínima). Confira `$plugin->supported` no `version.php` |

## Estado atual

**Funciona em produção:** compra completa validada — preferência, checkout,
webhook, matrícula. **105 testes** (63 no núcleo, 34 no Asaas, 8 no MP), phpcs
limpo, CI validando os plugins a cada push.

**O split foi provado** em 2026-08-27, no sandbox do Asaas, com duas contas
distintas: R$ 100 brutos, R$ 97,52 líquidos, split de 25% = R$ 24,38 atribuído à
carteira da plataforma. Primeira vez no projeto. Falta vê-lo chegar a `DONE` com
o saldo se movendo. Roteiro repetível em `docs/data-validation/asaas-sandbox.md`.

**Continua sem prova:** o split no Mercado Pago, e a compra pelo Moodle com
comissão maior que zero.

**Fase 3** tem a fundação no ar; falta apontar um domínio real.
**Fase 5** está bloqueada por decisão de negócio do usuário.

Detalhe completo: `docs/architecture/estado-e-proximas-fases.md`.

## Como o usuário trabalha

Prefere entender o porquê antes de aceitar a solução, e questiona premissas — em
mais de uma ocasião a objeção dele melhorou o desenho. Vale apresentar o
trade-off em vez de só a conclusão.

Não gosta de automação que exista só para evitar um comando manual, nem de
solução que dependa de a configuração estar certa para ser segura.

Escreve em português; o código e os comentários também, sem acentos. As strings
de idioma cobrem `en`, `pt_br` e `es`.
