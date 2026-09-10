# Provar o layout da landing e do cadastro nos três temas

Roteiro para repetir, sem mim, a conferência que fecha a reforma visual do
`local_partners`. Ele responde uma pergunta só: **a página que o visitante vê
corresponde ao mockup, e continua correspondendo sob `theme_boost`, `theme_moove`
e `theme_ldg`?**

> Desconfie de "está bonito na minha tela". O estilo deste plugin vem de um
> `styles.css` que entra na CSS compilada de **todos** os temas, e uma única
> regra sem o escopo `.ldgp` funciona sob Boost e desaparece sob o `ldg` — sem
> erro, sem log, e só na tela de quem usa o outro tema.

O método é o de [`../dev/portal-conferencia-visual.md`](../dev/portal-conferencia-visual.md),
generalizado em [`../dev/padrao-de-implementacao.md`](../dev/padrao-de-implementacao.md):
medir o DOM depois do JavaScript, número antes de opinião, e o mockup renderizado
no mesmo Chrome em vez de comparado com PNG.

## O que medir, e os alvos

| Medida | Alvo | Onde vale |
|---|---|---|
| Rolagem horizontal | **nunca** | todas as larguras |
| Cards de plano | 1 coluna | < 768px |
| Cards de plano | 3 colunas, mesmo `top` | ≥ 992px |
| Pilares | 1 coluna | < 768px |
| Pilares | 4 colunas | ≥ 1200px |
| Barra de seções, ao rolar | grudada abaixo do cabeçalho | todas |
| Alvo de toque na barra | ≥ 44px de altura | < 768px |
| Cadastro: coluna de valor / formulário | empilhado | < 992px |
| Cadastro: coluna de valor / formulário | 5/7, coluna esquerda pegajosa | ≥ 992px |
| `data-bs-theme` no wrapper | `dark` sem preferência | os três temas |
| Honeypot `fax` | fora da tela | os três temas |
| Contraste texto médio / fundo | ≥ 4,5:1 | os dois modos |

As larguras são **360, 390, 768, 1024 e 1440**.

## Preparar

Os planos da landing vêm do banco, não do template — sem plano público, a seção
de preços não aparece e a medição não tem o que medir.

```bash
# Confere que existem planos publicos; o seed do marketplace cria tres.
docker exec -u 1000:33 -w /var/www/html courses-free-moodle-1 \
  php public/local/marketplace/cli/status.php < /dev/null
```

```bash
# A landing precisa estar ligada. Os scripts de CLI ficam na RAIZ, em
# admin/cli/, e nao em public/admin/cli/ - no layout public/ do Moodle 5.x eles
# vivem de proposito fora do webroot.
docker exec -u 1000:33 -w /var/www/html courses-free-moodle-1 \
  php admin/cli/cfg.php --component=local_partners --name=enablelanding --set=1
```

**Editou o `styles.css`? Suba o `version.php` do plugin.** O `purge_caches` não
invalida CSS de plugin — foi medido em 03/09/2026, a revisão ficou parada antes e
depois do purge. Medir com CSS velho produz um relatório inteiro de conclusões
falsas.

O Chrome sobe **destacado**, senão morre junto com o comando que o lançou:

```bash
google-chrome --headless=new --disable-gpu --no-sandbox \
  --ignore-certificate-errors --remote-debugging-port=9222 \
  --user-data-dir=<scratchpad>/chrome-profile about:blank
```

## O roteiro

1. **Trocar o tema do site** para `boost` e carregar `/local/partners/index.php`
   como visitante anônimo. Repetir a tabela de medidas nas cinco larguras.
2. **Repetir sob `moove`**, e depois sob `ldg`. É o mesmo comando com o tema
   trocado — e é a única prova de que o estilo saiu do tema para o plugin.
3. **Alternar o modo de cor** pela barra de seções, medir de novo no claro, e
   **recarregar** para provar que a escolha ficou. Anônimo persiste em
   `localStorage`; logado, na preferência `dark-mode-on`.
4. **Rolar até o FAQ** e medir o `top` da barra de seções contra a altura do
   cabeçalho fixo. É o que pega `position: sticky` morto por um ancestral com
   `overflow: hidden` — falha silenciosa, sem erro e sem log.
5. **Abrir o cadastro** (`/local/partners/apply.php`) e repetir 1 a 3, mais a
   medida do `left` da caixa do honeypot.
6. **Renderizar o mockup no mesmo Chrome e no mesmo viewport** e capturar lado a
   lado. Os arquivos de referência são `Image 6.html` (landing, celular),
   `Image 2.html` (cadastro, desktop), e os PNGs `Image 3` e `Image 5`.
7. **Calcular o contraste** de cada par de cores da paleta clara, que foi
   derivada e não desenhada. O número vai como comentário no `styles.css`.

Os cenários `@javascript` cobrem os itens 3, 4 e as duas primeiras linhas da
tabela; este roteiro existe para o que sobra — a comparação com o mockup e o
julgamento de tipografia, respiro e hierarquia, que número não decide.

```bash
moodev up --full
docker exec -d -u 1000:33 courses-free-moodle-1 \
  sh -c 'cd /var/www/html/public && php -S 127.0.0.1:8000 >/tmp/behatweb.log 2>&1'
docker exec -u 1000:33 -w /var/www/html courses-free-moodle-1 \
  vendor/bin/behat --config /var/www/behatdata/behatrun/behat/behat.yml \
  --profile=chrome --tags "@local_partners&&@javascript"
```

## Resultado

<!-- Preenchido na etapa 10, com medidas reais e data. Roteiro com resultado
     inventado e pior que roteiro sem resultado. -->

*Ainda não executado.*

## Armadilhas

<!-- Preenchido conforme aparecerem. As conhecidas antes de comecar: -->

**`purge_caches` não invalida CSS de plugin.** Suba o `version.php`. Sem isso, a
medição descreve o estilo anterior.

**Feature nova não é coletada sozinha.** Depois de criar um `.feature`, rode
`php public/admin/tool/behat/cli/util.php --enable`, senão o behat responde
`No scenarios` e parece que o arquivo está errado.

**Sem sessão, a sonda mede a tela de login** e conclui que está tudo bem.

**`evaluate_script` avalia expressão, não bloco.** Envolva numa IIFE, senão o
Chrome devolve `Unexpected token 'const'` longe da causa.
