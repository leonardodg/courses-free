# theme_ldg

Tema global da plataforma. É **filho do `theme_moove`**, e o Moove é plugin de
terceiro versionado no repositório.

Além da identidade visual, ele carrega duas funções: o **menu lateral de
navegação** recolhível em trilho de ícones, e a decisão de mostrar a **landing de
parceria** como home para visitante anônimo.

## Dependências

| Depende de | Versão | Por quê |
|---|---|---|
| `theme_moove` | 2026042100+ | é o pai; sem ele o filho não compila |
| `theme_boost` | 2026042000+ | avô, e precisa estar em `$THEME->parents` |

**Não declara `local_partners`**, de propósito. A landing é dependência
*opcional*, resolvida por `class_exists()` — sem o plugin, `/` cai no marketing
do próprio Moove e o site não fica sem home. É o que permite o `moodle-plugin-ci`
instalar o tema sozinho.

> **Armadilha de CI:** o `theme_moove` vive em
> `.github/moodle-plugins-thirdparty.txt`, que **não** entra no `--extra-plugins`
> do workflow. Acrescentar este tema à lista sem levar o pai junto instala um
> filho órfão, e a falha é dura.

## O que precisa configurar

`/admin/settings.php?section=themesettingldg`

| Aba | Settings |
|---|---|
| Marca | `logo`, `logodark`, `favicon`, `loginbg`, `brandcolor`, `secondarymenucolor`, `fontsite` |
| Cor | `defaultcolormode`, `enablecolormodetoggle` |
| Avançado | `preset`, `presetfiles`, `scsspre`, `scss`, `googleanalytics` |

**Depois de trocar qualquer asset, purgue os caches.** O SCSS e as URLs de
arquivo carregam `theme_get_revision()`; sem purgar, a logo antiga continua sendo
servida e parece que o upload não funcionou.

Para a landing virar a home, ver o README do `local_partners` — são duas
configurações, uma em cada plugin.

## O que NÃO fazer aqui

**Não edite `public/theme/moove/`.** É código de upstream. Toda customização
entra neste diretório; o dia em que o Moove for atualizado, o `git status` do
diretório dele tem que estar limpo.

**Não crie `scss/moove/`.** Os import paths do compilador são
`[ldg/scss, moove/scss, boost/scss]`. Um arquivo aqui com o nome de um parcial do
pai — `_variables.scss`, por exemplo — sombrearia o dele sem aviso nenhum, e o
`@import "moove/variables"` de dentro do `default.scss` do Moove passaria a
resolver no filho.

**Não remova `boost` de `$THEME->parents`.** A lista é plana: o `theme_config`
não expande os pais dos pais. Com `['moove']` apenas, o `mustache_template_finder`
e o `layout_file()` param de enxergar `theme/boost`, e todo override que o Boost
faz de template `core/*` some — sem erro, servindo o template do core.

**Não remova `$THEME->rendererfactory`.** Sem
`theme_overridden_renderer_factory` a classe `\theme_ldg\output\core_renderer`
nunca é instanciada. Também aqui não há erro: o site fica no ar lendo os
settings do **Moove**, que nunca configuramos. O sintoma é "sumiu o logo".

## Os seis overrides, e por que existem

O Moove faz `theme_config::load('moove')` fixo em vários pontos. Herdar esses
métodos faria o filho ler a configuração do pai. `classes/output/core_renderer.php`
sobrescreve:

| Método | O que estaria errado sem o override |
|---|---|
| `standard_head_html()` | Google Analytics e fonte do Moove — o `fontsite` dele já nasce `Roboto`, então o site baixaria duas fontes e aplicaria a errada |
| `get_theme_logo_url()` | logo do Moove |
| `get_theme_logo_dark_url()` | logo escura do Moove |
| `favicon()` | favicon do Moove |
| `body_attributes()` | modo escuro por preferência de usuário — que visitante anônimo não tem |
| `render_darkmode_controls()` | um botão de alternar que não alterna nada |

E `classes/util/settings.php` existe pelo mesmo motivo: a classe equivalente do
Moove carrega `'moove'` no construtor.

**Ao atualizar o `theme_moove`, reconfira esta tabela.** Um `theme_config::load`
novo no pai vira um bug silencioso aqui.

## Escuro é o padrão, claro é suportado

O design system da marca é dark-first, mas os **dois** modos são de primeira
classe. No claro, o azul escuro predomina — não é o tema claro do Moove com outra
cor de destaque.

O modo é resolvido em três estados por `\theme_ldg\util\settings::color_mode()`:

```
preferência do usuário (dark-mode-on)   ← se existir, vence
        ↓ ausente
theme_ldg/defaultcolormode              ← escuro por padrão
```

O terceiro estado é o que importa: **visitante anônimo não tem user preference**.
A landing de captação e a tela de login são as duas páginas anônimas, e sem esse
degrau elas sairiam claras para todo o público-alvo. Com ele, saem no padrão
configurado.

| Setting | Padrão | O que faz |
|---|---|---|
| `defaultcolormode` | `dark` | modo de quem ainda não escolheu |
| `enablecolormodetoggle` | ligado | mostra o botão de alternar na navbar |

**`enablecolormodetoggle` é `admin_setting_configcheckbox`, e não
`configselect`.** Com um select cuja primeira opção era "Não", salvar qualquer
outra coisa na mesma página gravava `0` junto — o `config_log` mostrou o toggle
indo de `[1]` para `[0]` no mesmo POST do upload de logo, e o sintoma foi "sumiu
a opção de modo escuro".

Custo aceito: a área de edição do TinyMCE (`$THEME->editor_scss`) continua clara.
É tela de autoria, não pública.

### O modo claro precisou de `_corefixes.scss`

Vinte e dois seletores do core do Moodle chegam com `$body-bg` embutido no CSS
compilado, o que deixava telas inteiras ilegíveis no escuro — a de gestão de
cursos foi a primeira a aparecer. Ver a seção sobre esse arquivo abaixo.

## `loginbg`, e não `loginbgimg`

O setting da imagem de fundo do login tem nome diferente do Moove **de
propósito**. Os callbacks de SCSS do pai rodam junto com os do filho, recebendo a
config do filho: se o nome fosse igual, `theme_moove_get_extra_scss` emitiria o
CSS do fundo e o SCSS do admin, e o nosso callback emitiria tudo de novo.

Com o nome diferente, o do Moove sai no `return ''` da linha 77 do `lib.php`
dele, e o nosso faz o trabalho inteiro uma vez só — de quebra contornando o bug
em que aquele `return` descarta o setting `scss` do administrador.

**Regra geral:** toda vez que este tema declarar um setting com nome igual a um
do Moove, pergunte "o callback dele vai reagir a isso?".

## O menu lateral e o course index dividem um drawer só

O Boost já usa o drawer esquerdo para o **course index**, e só dentro de curso.
O menu de navegação da plataforma foi para o **mesmo** drawer, empilhado acima
dele, em vez de ganhar um drawer próprio. Um botão, uma preferência, e toda a
mecânica de foco, teclado e fechar-no-resize continua sendo a do Boost.

O course index é renderizado **dentro** do `templates/ldg/navmenu.mustache`, e
não como irmão dele. Se fosse irmão, o rodapé do menu — avatar, preferências e
sair — cairia no meio da coluna, antes das seções do curso.

Peças envolvidas:

| Arquivo | Papel |
|---|---|
| `classes/output/navmenu.php` | monta os grupos a partir da configuração do site |
| `templates/ldg/navmenu.mustache` | o menu, com o course index no meio |
| `layout/drawers.php` | substitui o do Moove; o drawer esquerdo deixa de exigir curso |
| `templates/theme_moove/drawers.mustache` | override: troca a condição do drawer |
| `amd/src/navmenu.js` | recolher/expandir, e grava a preferência |
| `scss/ldg/_navmenu.scss` | trilho no desktop, off-canvas no mobile |

**O conteúdo do menu não é escrito aqui.** Vem de `enablemyhome`,
`enabledashboard`, `enablemycourses` e `defaulthomepage` (Aparência →
Navegação), e de `customusermenuitems`, lido pela mesma
`user_get_user_navigation_info()` que alimenta o menu do usuário na navbar —
escrever um parser próprio criaria duas verdades para a mesma configuração.

**Dois comportamentos, fronteira no breakpoint `md`.** No desktop o drawer é
permanente e recolher o estreita para um trilho de ícones. No mobile não há
trilho: volta a ser o off-canvas do Boost, porque uma faixa fixa comeria largura
onde ela já falta.

Duas armadilhas que custaram tempo aqui:

- **O Boost esconde o drawer com `left: calc(-285px + -10px)`**, não com
  `transform`. Sobrescrever só o `transform` deixa a página deslocada e o menu
  invisível. O `max-width: 285px` também precisa ir junto, ou o trilho não
  estreita.
- **Não escreva chaves duplas de mustache dentro de um comentário `{{! }}`.**
  O comentário termina no primeiro `}}`, e o resto vira tag de verdade — foi
  assim que o `drawers.mustache` quebrou com `Missing closing tag`.

## `scss/ldg/_corefixes.scss` precisa ser revisado a cada upgrade do Moodle

O core e o Boost pintam fundo com a variável SCSS `$body-bg` em vários lugares.
`$body-bg` é resolvido em tempo de compilação e **não tem variante dark** — o
Bootstrap tem `$body-bg-dark` para a custom property, mas quem escreve
`background: $body-bg` direto congela o valor do modo claro. No modo escuro isso
vira painel claro com texto branco por cima.

Não dá para consertar na origem: trocar `$body-bg` por uma custom property
quebra a compilação, porque o `_root.scss` do Bootstrap chama `to-rgb($body-bg)`.

Então a correção é por seletor, com os mesmos seletores do core. Para refazer a
lista depois de um upgrade:

```bash
curl -sk "https://<site>/theme/styles.php/ldg/<rev>/all" -o /tmp/t.css
grep -o '[^{}]*{[^}]*background[^}]*#f4f6fa[^}]*}' /tmp/t.css
```

`#f4f6fa` é o `$body-bg` do modo claro definido em `theme_ldg_get_pre_scss`. Se
aparecer seletor que não está no `_corefixes.scss`, o core ganhou um lugar novo.

## Assets

`pix/` guarda cópias otimizadas; a fonte fica em `docs/brand/`. Trocar a arte lá
não muda o site até a cópia ser refeita **e os caches, purgados** — o
`setting_file_url()` embute o `theme_get_revision()` na URL do arquivo.

`pix/login_hero.jpg` é um recorte de `docs/brand/hero.png`. O recorte não é
estética: o arquivo original é um preview de banco de imagens com texto
chapado ("High Resolution / E-learning Dashboard tackground"), e só a faixa de
baixo está limpa.

## Testar

Depois de qualquer mudança de SCSS ou de arquivo de setting, **purgue os
caches** — o CSS é cacheado por `theme_get_revision()`.

Para iterar rápido, `$CFG->themedesignermode = true` no `config-local.php`.
**Só na máquina local**: na VPS isso recompila o SCSS inteiro a cada requisição.
