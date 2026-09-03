# Conferir o portal no navegador

Roteiro para repetir, sem mim, a conferência que fecha qualquer mudança visual do
portal do aluno. É o portão que pegou os quatro bugs de 02/09/2026 e mais três em
03/09 — todos invisíveis para PHPUnit e Behat.

**Por que navegador:** o layout do portal só existe depois que o JavaScript mede
o quadro da aula e as fontes trocam a métrica do texto. Um teste de servidor vê
marcação correta e conclui que está tudo bem.

## O que medir, e os alvos

Do [`../brand/DESIGN.md`](../brand/DESIGN.md):

| Medida | Alvo | Onde vale |
|---|---|---|
| Altura do cabeçalho | 56px | sempre |
| Coluna de navegação | 280px | ≥ 992px |
| Coluna do índice | 360px | ≥ 1200px |
| Abas no miolo | visíveis | < 992px |
| Barra inferior | visível, 60px | < 992px |
| Altura do quadro da aula | **estável** entre leituras | sempre |
| Rolagem horizontal | **nunca** | sempre |

Os viewports são **1280** e **390** — os PNGs de referência a 2×.

## Preparar

```bash
# Curso de demonstracao com os quatro destinos e os estados que o desenho mostra.
docker exec -u 1000:33 -w /var/www/html courses-free-moodle-1 \
  php public/course/format/ldg/cli/make_testdata.php --run --reset < /dev/null
```

O Chrome precisa subir **destacado** — em segundo plano de verdade, senão morre
junto com o comando que o lançou:

```bash
google-chrome --headless=new --disable-gpu --no-sandbox \
  --ignore-certificate-errors --remote-debugging-port=9222 \
  --user-data-dir=<scratchpad>/chrome-profile about:blank
```

## As sondas

Vivem no **scratchpad da sessão**, nunca dentro de `public/` — arquivo de sonda
com nome curto na raiz do Moodle sobrescreve arquivo do core, e isso já
aconteceu aqui.

- `probe_visual.js` — mede as linhas da tabela acima nos dois viewports e nos
  dois modos, e captura as telas.
- `probe_axe.js` — injeta o `axe-core` que o **próprio Moodle empacota**
  (`lib/behat/axe/axe.min.js`) e roda `wcag2a`+`wcag2aa` nos quatro destinos.

Ambas precisam da sessão: sem o cookie `MoodleSession`, a sonda mede a tela de
login e diz que está tudo bem.

## Comparar com a referência

Renderize o mockup **no mesmo Chrome e no mesmo viewport**, e compare com essa
captura — não com o PNG. PNG contra tela é comparar dois motores de renderização,
e a diferença que sobrar não é do nosso código.

```bash
node probe_visual.js "file:///home/leodg/Downloads/portal-aluno-version-bootstrip-responsive.html" --shot mockup
```

## Armadilhas de cache que custam medições

**`styles.php/ldg/1/all` devolve cache.** Para ver compilação recente, use a
revisão que a página carrega, ou `-1`.

**`purge_caches` pode não invalidar o CSS de plugin.** Em 03/09 a revisão do tema
ficou parada antes e depois do purge; o que fez o CSS novo aparecer foi o **bump
de `version.php`**. Editou `styles.css` de plugin? Suba a versão.

## O que a medição não decide

Tipografia, respiro e hierarquia. Número resolve 56px; "chegar próximo" do
desenho é julgamento humano, com as capturas lado a lado.
