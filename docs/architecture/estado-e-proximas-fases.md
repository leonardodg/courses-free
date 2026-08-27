# Estado do marketplace e o que falta

Atualizado em 2026-08-25.

## O que ja funciona

Validado de ponta a ponta em producao: preferencia criada, checkout do Mercado
Pago, pagamento aprovado, webhook recebido, direito de acesso criado e aluno
matriculado.

| Componente | Estado |
|---|---|
| `local_marketplace` | empresas, vendedores, ofertas, direitos, relatorios, vitrine configuravel, telas de admin |
| `paygw_mercadopago` | OAuth com PKCE, vincular/desvincular, `marketplace_fee`, webhook, modo de teste |
| `enrol_marketplace` | matricula por diferenca, a partir dos direitos vigentes |
| `availability_marketplace` | secao liberada por oferta especifica, com botao de compra |
| `block_marketplace` | assinaturas do aluno no Dashboard |
| Infra | VPS Oracle, deploy automatico no push para `dev`, TLS por certbot, CI validando os cinco plugins |

**33 testes PHPUnit, zero violacoes de phpcs**, rodados no CI a cada push. O job
de validacao bloqueia o deploy quando falha.

Tres idiomas completos: `en`, `pt_br` e `es`, com 261 chaves cada.

## O que NAO foi validado

Nao e o mesmo que "nao funciona" - e o que ninguem viu funcionar ainda.

**O split - PROVADO em 2026-08-27**, no sandbox do Asaas, com duas contas
distintas:

    cobranca ... pay_w1ijat3r74sx4nlp | CONFIRMED
    bruto ...... R$ 100,00 | liquido R$ 97,52
    SPLIT ...... carteira da plataforma | AWAITING_CREDIT | 25% | R$ 24,38

Primeira vez no projeto. No Mercado Pago vendedor e marketplace eram a mesma
conta, entao o `marketplace_fee` nao transferia nada - e nao havia erro, que e
o pior tipo de falso positivo. Continua sem prova no Mercado Pago, e falta ver o
split chegar a DONE com o saldo se movendo. Roteiro repetivel em
`../data-validation/asaas-sandbox.md`.

Descoberta no caminho, que vale mais que a prova: baixa manual (`receiveInCash`)
faz o split sair CANCELLED com o valor certo na tela. Dinheiro que nao passou
pelo gateway nao tem como ser dividido.

**Pix e boleto.** Pix exige chave registrada na conta do vendedor; boleto e
excluido pelo `wallet_purchase` do modo de teste. Sao os caminhos assincronos,
onde o webhook e a unica fonte da verdade.

**Expiracao de direito.** Ofertas com prazo nunca chegaram ao vencimento num
teste.

**Dominio de vendedor de ponta a ponta.** O mapa, o nginx e o certbot estao
prontos; falta apontar um dominio real e confirmar - inclusive que login num
dominio nao vaza para o outro.

## Lacunas conhecidas

### Regra do video externo e honra, nao sistema

`course_policy` guarda `hostingtype` por curso, e o papel de vendedor tem
`PROHIBIT` em `repository/upload:view`. Mas nada audita se um curso marcado como
`external` passou a hospedar video no `moodledata` por outro caminho.

Sem uma tarefa que meca o tamanho por curso e alerte, a regra dos 25% depende de
boa fe. Com voce e uma professora, tudo bem; com dez vendedores, nao.

### Assinatura nao renova sozinha

O Mercado Pago **nao tem recorrencia com split**: `preapproval` nao aceita
`marketplace_fee`, e o Transparente com cartao salvo exige CVV a cada cobranca.

Hoje o aluno recompra quando vence, avisado por e-mail cinco dias antes. Decisao
de negocio pendente: aceitar assim, ou montar cobranca recorrente fora do split
com repasse manual - o que muda o modelo de dados e o relatorio.

### Pagina publica de parceria

Desenho decidido - formulario aberto, e-mail para o admin, tabela guardando os
leads - e nao construido. E o que transforma o marketplace em algo que capta
vendedor sozinho.

## Fase 3 - dominio por vendedor

**Fundacao pronta.** Falta so o teste com dominio real.

| Camada | Responsabilidade |
|---|---|
| `nginx` | entrega o trafego, com `Host` preservado |
| Arquivo gerado no `dataroot` | diz quais dominios existem - **fronteira de seguranca** |
| Banco, no `after_config` | diz se a empresa esta ativa; bloqueia a plataforma inteira se nao |

O `config.php` usa o `Host` apenas como chave numa lista gerada do banco. Um
`Host: evil.com` nao encontra a chave e nada acontece - sem isso seria injecao de
cabecalho, com o Moodle gerando todo link para o dominio do atacante, inclusive o
de redefinicao de senha.

Provisionar:

```bash
sudo .devcontainer/nginx/provisiona-dominio.sh meuscursos.joao.com contato@leodg.dev
php local/marketplace/cli/domains.php --rebuild
```

Decisao mantida: **sem** `$CFG->sessioncookiedomain`. A sessao passa a ser por
dominio, e quem entra no dominio do vendedor nao esta logado na plataforma. Isso
precisa estar claro na UX de checkout.

## Fase 4 - captacao e relatorio

O relatorio financeiro **esta feito**: transacoes, cursos vendidos e assinaturas,
com comissao por empresa.

O tema de captacao foi abandonado por qualidade de codigo. Se voltar, comeca do
zero como tema filho do `boost_union`.

## Fase 5 - conteudo hospedado na plataforma

**Bloqueada por decisao de negocio**, nao por tecnica. Falta definir a cobranca:
assinatura com cota de storage e banda mais comissao menor, ou comissao maior com
limite tecnico por curso.

`course_policy::validate_hostingtype()` recusa `platform` hoje, de proposito.

## Multi-pais

**Implementado em 2026-08-27** - ver [ADR-0002](../adr/0002-conta-de-pagamento-por-pais.md).

A oferta tem `country` em ISO-3166 alpha-2, e a tabela
`local_marketplace_account` liga empresa x pais x conta. A moeda deixou de ser
escolhida e passou a ser derivada do pais, o que torna impossivel - e nao apenas
invalida - uma oferta em BRL vendendo na Argentina.

A chave e ISO e nao o `siteid` do Mercado Pago: Asaas e Pagar.me so existem no
Brasil e nao tem codigo de site. A traducao para o vocabulario de cada
fornecedor fica no cliente HTTP dele.

Falta ainda um par `client_id`/`client_secret` por pais no Mercado Pago, em vez
de um unico.

Restricao que decidiu o desenho: `core_payment::get_payable()` nao recebe o
usuario, entao valor, moeda e conta sao funcao pura do `itemid`. Uma oferta nao
pode ser BRL para um aluno e ARS para outro - o pais tem que estar na oferta.

## Divida tecnica conhecida

**AMD escrito a mao.** Nao ha grunt nem babel: `amd/src` e AMD de verdade e
`amd/build` e o mesmo arquivo com o `define` nomeado. Se alguem introduzir ES6 no
`src` sem um transpilador, o `requirejs.php` serve esse arquivo e o modulo quebra
com "No define call".

**Tres plugins de terceiros fora do suporte declarado.** `mod_attendance`,
`format_onetopic` e `format_tiles` declaram `supported = [501, 501]` e a
plataforma roda 5.2. O Moodle avisa e nao bloqueia; ficam sob observacao ate
publicarem release que declare 5.2.

**Disco da VPS.** Em 2026-08-25 estava em 97%, com 52 GB em imagens Docker de
varios projetos. Cada deploy precisa de ~2,8 GB para a imagem nova. Sem folga, o
deploy falha na extracao - e o site continua no ar com o container antigo, entao
"o site responde" nao prova que o deploy aplicou.

**Sem Behat.** Os 33 testes sao unitarios; nenhum exercita o fluxo pelo navegador.
