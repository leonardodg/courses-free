@local @local_partners
Feature: Envio de candidatura de parceria
  Para que uma empresa possa se candidatar sem ter conta no site
  Como visitante anonimo
  Preciso enviar o formulario e receber confirmacao do envio

  Background:
    Given the following config values are set as admin:
      | enablelanding            | 1 | local_partners |
      | requireemailconfirmation | 0 | local_partners |
      | enablerecaptcha          | 0 | local_partners |

  Scenario: Candidatura enviada por visitante anonimo entra na fila
    When I visit "/local/partners/apply.php"
    And I set the following fields to these values:
      | Company name | Editora Beta          |
      | Contact name | Maria Silva           |
      | Email        | maria@exemplo.com     |
    And I press "Send application"
    Then I should see "Application received"
    And I should see "Your application is in. We read every one and answer by email."

    # A fila e a prova de que gravou: a tela de obrigado apareceria igual se o
    # honeypot tivesse descartado o envio em silencio.
    And I log in as "admin"
    And I visit "/local/partners/admin/applications.php"
    And I should see "Editora Beta"
    And I should see "Pending"

  Scenario: Com confirmacao ligada a candidatura NAO entra na fila
    Given the following config values are set as admin:
      | requireemailconfirmation | 1 | local_partners |
    When I visit "/local/partners/apply.php"
    And I set the following fields to these values:
      | Company name | Editora Nao Confirmada |
      | Contact name | Joana Souza            |
      | Email        | joana@exemplo.com      |
    And I press "Send application"
    Then I should see "Check your inbox. Your application reaches us as soon as you open the link we just sent."

    # Enquanto o e-mail nao for provado, aquilo nao e uma candidatura: e o que
    # alguem digitou. Ela existe no banco, mas fora da fila.
    And I log in as "admin"
    And I visit "/local/partners/admin/applications.php"
    And I should see "Awaiting email confirmation"
    And I should not see "Pending"

  Scenario: CNPJ invalido e recusado antes de gravar
    When I visit "/local/partners/apply.php"
    And I set the following fields to these values:
      | Company name          | Editora do CNPJ Torto |
      | Contact name          | Carlos Lima           |
      | Email                 | carlos@exemplo.com    |
      | Company tax ID (CNPJ) | 11222333000180        |
    And I press "Send application"
    Then I should see "This is not a valid company tax ID."
    And I should not see "Application received"

  Scenario: Candidatura em aberto bloqueia um segundo envio do mesmo e-mail
    Given I visit "/local/partners/apply.php"
    And I set the following fields to these values:
      | Company name | Editora Primeira    |
      | Contact name | Ana Costa           |
      | Email        | ana@exemplo.com     |
    And I press "Send application"
    And I should see "Application received"

    When I visit "/local/partners/apply.php"
    And I set the following fields to these values:
      | Company name | Editora Segunda     |
      | Contact name | Ana Costa           |
      | Email        | ana@exemplo.com     |
    And I press "Send application"
    Then I should see "There is already an open application for this email or tax ID. We will be in touch."
