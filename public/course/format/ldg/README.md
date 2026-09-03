# format_ldg

Formato de curso no estilo **portal do aluno**: uma aula por vez no centro, a
lista completa ao lado, e o progresso à vista.

## Para que serve

O formato padrão do Moodle empilha todas as atividades numa página. Aqui a
**seção é o módulo** do curso e cada atividade dentro dela é uma **aula**. A tela
mostra a aula em foco embutida, com a lista de todas as aulas agrupadas por
módulo, marcando o que já foi concluído e o que ainda está bloqueado.

O bloqueio é o motivo de o formato existir neste projeto: num marketplace, parte
do conteúdo fica atrás de uma compra, e o aluno precisa **ver que existe** antes
de decidir comprar.

## Dependências

| Dependência | Por quê |
|---|---|
| Moodle 5.2 (`2026042000`) | usa `core\output\choicelist` e o `courseformat` reativo |
| **`theme_ldg`** *(opcional)* | **o estilo e o chrome do portal vivem no tema**, não aqui |

**Leia isto antes de instalar com outro tema.** O SCSS está em
`theme/ldg/scss/ldg/_format.scss`, e não no plugin. O formato entrega marcação
semântica; quem pinta é o tema. Com outro tema, ele **funciona e aparece sem
estilo nenhum** — lista sem colunas, sem cores, sem barra desenhada. Isso é
decisão de projeto, não esquecimento: assim o formato continua legível para quem
quiser aplicar o próprio tema.

### O chrome do portal, e como ele se desliga sozinho

Para o aluno, a página do curso é servida por um layout **sem navbar e sem
drawers** — o chrome inteiro é do portal. Quem pede a troca é
`\format_ldg\hook_callbacks`, no hook `before_http_headers`; quem desenha é o
tema, no layout `ldgportal`.

A troca só acontece se **todas** estas forem verdade:

- a página é a do curso (`pagelayout` = `course`), num curso neste formato;
- o usuário **não** está com a edição ligada — professor editando volta para o
  chrome do Moodle, porque arrastar atividade e o menu de ações vivem nos ganchos
  que o core põe na pilha de seções;
- **o tema ativo declara o layout `ldgportal`**.

A última é o que torna a dependência opcional de verdade: com outro tema, o
formato não troca nada e o curso abre no chrome daquele tema — sem erro e sem
`debugging()` no log. Há um cenário de Behat fixando exatamente isso.

> `ldgportal` é o **contrato** entre os dois plugins. Renomear o layout no tema
> desliga o portal em silêncio.

O `availability_marketplace` **não** é dependência. O formato nunca pergunta ao
marketplace quem comprou o quê: ele lê `cm_info->uservisible`, que já chega
resolvido. Se o plugin não estiver instalado, o formato simplesmente nunca mostra
cadeado de compra.

## O que precisa configurar

Nada, no nível do site. No curso:

1. **Configurações do curso → Formato → Portal LDG**
2. **Conclusão de atividades ligada**, no curso e nas atividades. Sem isso não há
   progresso para mostrar, e as barras não aparecem — o que é diferente de
   aparecerem zeradas.
3. Opcional: **duração de cada aula**, preenchida com a edição ligada, no campo
   ao lado de cada item da lista. Em segundos. Vazio significa "não sei", e a
   lista simplesmente não mostra nada — diferente de zero.

### O índice lateral fica desligado neste curso

`uses_course_index()` devolve `false`. A lista de aulas já cumpre esse papel, na
mesma tela, e duas navegações concorrentes disputariam o mesmo espaço.

Não é só estética: o índice do Moodle é renderizado **no cliente**, a partir do
state reativo, com um template fixo do core. Trocar o conteúdo dele exigiria um
módulo AMD próprio — custo que não se paga.

## Armadilhas

**Com a edição ligada, a tela muda.** A coluna principal volta a ser a pilha de
seções do core. Não é bug: arrastar atividade, renomear e o menu de ações vivem
nos ganchos que o core põe naquela marcação, e nenhum funciona dentro de um
quadro embutido. Para ver o curso como o aluno vê, **desligue a edição**.

**A atividade embutida vem sem o cabeçalho do site — só com o `theme_ldg`.** O
Moodle não tem um modo embutido genérico: apenas o `mod_page` aceita `inpopup`, e
só num caso. Quem esconde o cabeçalho é o tema, detectando o cabeçalho HTTP
`Sec-Fetch-Dest: iframe`. Com outro tema, o quadro mostra o site inteiro dentro
dele.

**O progresso por seção é código copiado do core.** `\format_ldg\section_progress`
replica `cmsummary::calculate_section_stats()`, que é `protected`. Se o Moodle
mudar a regra de contagem, a nossa **diverge em silêncio**. O teste
`section_progress_test` existe para avisar: quando ele falhar depois de um
upgrade, o provável é que a regra mudou lá, não que o teste está ruim.

**Aula bloqueada não entra no denominador do progresso.** Se entrasse, quem não
comprou veria a barra travar abaixo de 100% sem nenhuma explicação na tela.

**Trocar de formato não apaga as durações.** Sair para o formato de seções e
voltar preserva o que foi preenchido. As linhas ficam órfãs no meio-tempo, e isso
é de propósito — a tabela não tem chave estrangeira para `course_modules`
justamente para que dado de formato nunca impeça a troca de formato. A limpeza só
acontece quando o **curso** é apagado.

**`availableinfo` nem sempre é `string`.** Com mais de uma condição de acesso ele
vem como `core_availability_multiple_messages`, e passá-lo para `format_string()`
estoura `TypeError`. Use `\core_availability\info::format_info()`.

**Não escreva tag mustache dentro de comentário mustache.** O comentário termina
no primeiro `}}`, então uma tag citada em prosa vira seção de verdade sem
fechamento. O erro sai como `Missing closing tag` apontando para uma linha que não
tem nada a ver.

## Estrutura

```
classes/
  lesson.php                    duração por cmid (persistent)
  section_progress.php          a contagem que o core não expõe
  external/set_duration.php     grava a duração, via AJAX
  output/courseformat/
    content.php                 estende o content do core
    content/lessonlist.php      a lista de aulas
    content/lessonviewer.php    a aula embutida
templates/local/               content, lessonlist, lessonviewer
amd/src/                       player (altura do quadro), duration (edição)
backup/moodle2/                leva e traz a duração
cli/make_testdata.php          monta um curso de demonstração
```

## Testes

```bash
docker exec -u 1000:33 -w /var/www/html courses-free-moodle-1 \
  php vendor/bin/phpunit --testsuite format_ldg_testsuite

docker exec -u 1000:33 -w /var/www/html courses-free-moodle-1 \
  vendor/bin/behat --config /var/www/behatdata/behatrun/behat/behat.yml \
  --tags "@format_ldg"
```

As features **não** usam `@javascript`: tudo o que elas verificam é desenhado no
servidor. O que depende de navegador — a altura do quadro e a gravação da duração
— fica fora, porque o ambiente não tem um, e teste que não roda não protege nada.

## Dados de demonstração

```bash
docker exec -u 1000:33 courses-free-moodle-1 \
  php /var/www/html/public/course/format/ldg/cli/make_testdata.php --run --reset
```

Monta um curso com quatro módulos, aulas de conclusão manual e automática, um
quiz com pergunta, uma url e um **certificado trancado** por duas condições em E:
concluir todas as aulas **e** comprar uma oferta. O script se recusa a rodar fora
de um endereço local.
