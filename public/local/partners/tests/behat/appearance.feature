@local @local_partners @javascript
Feature: A landing e o cadastro na tela
  Para que a pagina publica funcione no celular e em qualquer tema do site
  Como visitante
  Preciso que o layout responda a largura da tela e nao dependa do tema

  # Estes cenarios exigem navegador de verdade:
  #   moodev up --full
  #   vendor/bin/behat --config ... --profile=chrome --tags "@local_partners&&@javascript"
  #
  # Sem o --profile=chrome o behat procura Selenium em localhost:4444 e morre
  # com erro que parece problema de ambiente.
  #
  # E "sem escala em tempo de execucao" NAO e detalhe: por padrao o Moodle
  # escala o conteudo depois de redimensionar, e o "mobile" de 425px passa a se
  # comportar como uns 600. Uma assercao sobre celular medida assim afirma outra
  # coisa - foi o que fez este arquivo passar dizendo o que nao era.

  Background:
    Given the following config values are set as admin:
      | enablelanding | 1 | local_partners |

  Scenario: No celular a pagina cabe na tela e tudo fica em uma coluna
    Given I change viewport size to "mobile" without runtime scaling
    When I visit "/local/partners/index.php"
    Then the page should not scroll sideways
    And the ".ldgp-plan" elements should be stacked in one column
    And the ".ldgp-pillar" elements should be stacked in one column
    And the ".ldgp-step" elements should be stacked in one column

  Scenario: No desktop os planos ficam lado a lado
    Given I change viewport size to "1440x900"
    When I visit "/local/partners/index.php"
    Then the ".ldgp-plan" elements should sit in 3 columns
    And the ".ldgp-pillar" elements should sit in 4 columns
    And the page should not scroll sideways

  Scenario: O alvo de toque da barra respeita o minimo no celular
    Given I change viewport size to "mobile" without runtime scaling
    When I visit "/local/partners/index.php"
    Then every ".ldgp-bar-link" touch target should be at least 44 pixels tall

  Scenario: A barra de secoes gruda ao rolar
    Given I change viewport size to "1440x900"
    And I visit "/local/partners/index.php"
    When I click on "FAQ" "link" in the ".ldgp-bar" "css_element"
    Then the section bar should stick below the header

  Scenario: O cadastro empilha no celular e nao rola para o lado
    Given I change viewport size to "mobile" without runtime scaling
    When I visit "/local/partners/apply.php"
    Then the page should not scroll sideways
    And the ".ldgp-plancard" elements should be stacked in one column

  # O modo escuro e o padrao para quem nunca escolheu, e o alternador existe
  # porque o theme_boost do 5.2 nao tem nenhum.
  Scenario: Quem nunca escolheu ve a pagina escura, e pode trocar
    Given I change viewport size to "1440x900"
    And I visit "/local/partners/index.php"
    Then the partner page should be in "dark" mode
    When I click on "button[data-ldgp='colormode']" "css_element"
    Then the partner page should be in "light" mode

  # Esta e a prova concreta de "funciona sob Boost e Moove", em vez da promessa.
  # A regra que esconde o campo-armadilha morava no tema, e por isso ele
  # aparecia na tela para todo visitante nos outros dois.
  Scenario Outline: O cadastro se comporta igual em qualquer tema
    Given the following config values are set as admin:
      | theme | <tema> |
    And I change viewport size to "1440x900"
    When I visit "/local/partners/apply.php"
    Then the partner page should be in "dark" mode
    And the honeypot field should be off screen
    And the page should not scroll sideways

    Examples:
      | tema  |
      | boost |
      | moove |
      | ldg   |
