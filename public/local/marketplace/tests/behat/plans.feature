@local @local_marketplace
Feature: Planos comerciais e a base de calculo da comissao
  Para que preco e comissao mudem por decisao comercial, e nao por deploy
  Como administrador
  Preciso editar os planos por tela, incluindo a base de calculo

  Background:
    Given I log in as "admin"

  # O seed roda na instalacao e NAO faz update - so insere o que falta. Se estes
  # tres sumirem, alguem trocou insercao por sincronizacao, e o preco ajustado
  # pelo administrador seria sobrescrito no proximo deploy.
  Scenario: A instalacao ja traz os tres planos
    When I visit "/local/marketplace/admin/plans.php"
    Then I should see "Starter"
    And I should see "Pro"
    And I should see "Scale"

  Scenario: A base da comissao e editavel no plano, e herda por padrao
    When I visit "/local/marketplace/admin/plans.php"
    And I follow "Starter"
    Then the field "Commission base" matches value "Inherit the site base"

    When I set the field "Commission base" to "net"
    And I press "Save changes"
    And I visit "/local/marketplace/admin/plans.php"
    And I follow "Starter"
    Then the field "Commission base" matches value "net"

  # Herdar e escolher sao coisas diferentes: gravar a base padrao em toda linha
  # congelaria o contrato, e depois nao haveria como distinguir quem escolheu
  # bruto de quem so aceitou a politica do site.
  Scenario: Voltar para herdar e possivel
    Given I visit "/local/marketplace/admin/plans.php"
    And I follow "Starter"
    And I set the field "Commission base" to "gross"
    And I press "Save changes"

    When I visit "/local/marketplace/admin/plans.php"
    And I follow "Starter"
    And I set the field "Commission base" to "Inherit the site base"
    And I press "Save changes"
    And I visit "/local/marketplace/admin/plans.php"
    And I follow "Starter"
    Then the field "Commission base" matches value "Inherit the site base"

  Scenario: A comissao do plano e editavel
    When I visit "/local/marketplace/admin/plans.php"
    And I follow "Starter"
    And I set the field "Commission (%)" to "7.5"
    And I press "Save changes"
    Then I should see "7.50"
