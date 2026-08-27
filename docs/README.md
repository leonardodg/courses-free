# Documentação do courses-free

Plataforma Moodle 5.2 onde qualquer pessoa publica curso gratuito ou pago, com
split de pagamento pelo Mercado Pago.

## Por onde começar

| Se você quer… | Vá para |
|---|---|
| montar o ambiente e começar a codar | [`dev/cf.md`](dev/cf.md) e [`dev/guia-worktrees.md`](dev/guia-worktrees.md) |
| entender **por que** o sistema é assim | [`architecture/decisoes-marketplace.md`](architecture/decisoes-marketplace.md) |
| saber o que existe e o que falta | [`architecture/estado-e-proximas-fases.md`](architecture/estado-e-proximas-fases.md) |
| mexer nas tabelas | [`data-model/marketplace.md`](data-model/marketplace.md) |
| testar o que está no ar | [`data-validation/painel-de-testes.md`](data-validation/painel-de-testes.md) |

## As pastas

| Pasta | O que guarda |
|---|---|
| [`adr/`](adr/) | **Architecture Decision Records** — uma decisão por arquivo, com contexto e consequências |
| [`architecture/`](architecture/) | visão de sistema, diagramas, estado do projeto e as decisões consolidadas |
| [`brand/`](brand/) | identidade visual: logo, cores, tipografia, tom de voz |
| [`coding-standards/`](coding-standards/) | padrão de código, convenções de commit, o que o CI cobra |
| [`data-model/`](data-model/) | tabelas, campos e relações |
| [`data-validation/`](data-validation/) | como se verifica que funciona: painel de testes, cenários, dados de teste |
| [`dev/`](dev/) | guias de quem desenvolve: ambiente, ferramentas, fluxo de trabalho |
| [`ai-plans/`](ai-plans/) | **registro de todo plano executado por agente de IA** |
| [`history/`](history/) | de onde o projeto veio: ideia inicial, conversas fundadoras |
| [`man/`](man/) | páginas de manual instaláveis (`man cf`) |

## Convenções

**Português, sem acentos no código.** Prosa e documentação levam acentuação
normal; comentários em código e mensagens de commit vão sem, por consistência
com o que já existe na base.

**Fim de linha LF.** Garantido por [`.gitattributes`](.gitattributes) — o git
global desta máquina tem `core.autocrlf=true`, que sem isso escreveria CRLF no
working tree e sujaria diffs (e quebraria a man page).

**Diagramas em Mermaid**, em bloco ```` ```mermaid ````, para versionar como
texto. O SVG em `architecture/` é a exceção: veio pronto.

**Um documento, um assunto.** Se um arquivo passa a responder duas perguntas
diferentes, é sinal de que virou dois.
