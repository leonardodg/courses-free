# Rodar behat neste projeto

O behat vem no `vendor/` desde sempre, mas **nunca tinha sido inicializado** —
não havia `$CFG->behat_*` nem features escritas. Este documento é o caminho
completo.

## Por que ele encontra o que o phpunit não encontra

Os três bugs que apareceram na primeira execução são exatamente do tipo que
teste unitário não pega, porque nenhum deles está numa função isolada:

1. **`TypeError` ao aprovar com o dono em branco.** O autocomplete do moodleform
   manda **string vazia**, e não `null`. O `?? null` do chamador não pegava, e
   `''` chegava num parâmetro `?int`. Quebrava o caminho que a própria tela
   instrui a usar. O phpunit passava porque os testes chamavam `approve()` com
   `null` — que é o que um programador escreve, e não o que o navegador manda.
2. **`redirect()` depois de `$OUTPUT->header()`** na tela de decisão. O Moodle
   emite *"You should really redirect before you start page output"* e cai num
   redirecionamento por meta/JS.
3. **`commissionbase` gravado e nunca lido de volta**, em `plan_edit.php` e em
   `company_edit.php`. A tela mostrava "herdar" para todo mundo, e quem
   reabrisse e salvasse **apagaria o acordo negociado**.

O terceiro é o mais instrutivo: o campo foi acrescentado ao formulário e ao
salvamento, e o carregamento foi esquecido. Só uma ida e volta pela tela revela.

## Configurar, uma vez

O behat precisa de banco, dataroot e URL **separados** do ambiente de trabalho:
ele derruba e recria o banco a cada execução.

Os três valores vão no `config-local.php`, que é gitignored e carregado antes do
`setup.php`:

```php
$CFG->behat_wwwroot = 'http://127.0.0.1:8000';
$CFG->behat_dataroot = '/var/www/behatdata';
$CFG->behat_prefix = 'bht_';
```

Depois, instalar o ambiente:

```bash
docker exec -u 1000:33 -e COMPOSER_HOME=/tmp/composer courses-free-moodle-1 \
  php /var/www/html/public/admin/tool/behat/cli/init.php
```

Demora alguns minutos — ele instala um site limpo e compila o CSS de todos os
temas, o `ldg` inclusive.

## Rodar

O `behat_wwwroot` precisa estar sendo servido. Um `php -S` dentro do container
resolve, e não conflita com o Apache do ambiente de trabalho:

```bash
docker exec -d -u 1000:33 courses-free-moodle-1 \
  sh -c 'cd /var/www/html/public && php -S 127.0.0.1:8000 >/tmp/behatweb.log 2>&1'
```

```bash
# Todas as nossas features
docker exec -u 1000:33 -w /var/www/html courses-free-moodle-1 \
  vendor/bin/behat --config /var/www/behatdata/behatrun/behat/behat.yml \
  --tags "@local_partners,@local_marketplace"

# Um arquivo só
docker exec -u 1000:33 -w /var/www/html courses-free-moodle-1 \
  vendor/bin/behat --config /var/www/behatdata/behatrun/behat/behat.yml \
  public/local/partners/tests/behat/approval.feature
```

> **Feature nova não é coletada sozinha.** Depois de criar ou renomear um
> `.feature`, rode `php public/admin/tool/behat/cli/util.php --enable` — sem
> isso o behat responde `No scenarios` e parece que o arquivo está errado.

## O que dá para testar aqui, e o que não dá

**Não há Chrome nem Selenium neste ambiente**, então cenários `@javascript` não
rodam. O que fica de fora:

- o menu lateral recolhível e o alternador de modo de cor do `theme_ldg`
- o autocomplete de dono na tela de aprovação
- qualquer `hideIf` — no driver sem JS os campos escondidos continuam no DOM e
  são submetíveis, o que na prática **ajuda**: dá para exercitar o formulário
  inteiro sem simular a interação

## Duas armadilhas que custaram tempo

**Asserção em página de exceção sempre falha.** O `behat_hooks` procura exceções
depois de cada passo, então `I should see "<mensagem de erro>"` numa página que
lançou `moodle_exception` falha mesmo quando a exceção é o comportamento
correto. Casos assim ficam no phpunit.

**Botão de moodleform não é "visto".** `add_action_buttons` renderiza
`<input type="submit" value="...">`, e `I should see` lê nó de texto. Use
`"Salvar" "button" should exist`, ou simplesmente `I press`.

## Onde estão as features

| Arquivo | O que cobre |
|---|---|
| `local/partners/tests/behat/landing.feature` | landing e formulário alcançáveis **sem login** |
| `local/partners/tests/behat/application.feature` | envio, confirmação de e-mail, CNPJ inválido, duplicidade |
| `local/partners/tests/behat/approval.feature` | aprovar cria empresa, recusar não cria, a fila |
| `local/marketplace/tests/behat/plans.feature` | os três planos do seed, e a base de comissão indo e voltando |
