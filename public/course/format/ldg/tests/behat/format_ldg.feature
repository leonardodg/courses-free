@format @format_ldg
Feature: O portal do aluno
  Para acompanhar o curso sem se perder
  Como aluno
  Preciso ver a aula em foco com a lista de aulas ao lado, e saber o que ainda nao posso abrir

  # Sem @javascript de proposito. Tudo o que se verifica aqui e desenhado no
  # SERVIDOR: a lista, o cadeado, o quadro e a troca de coluna por edicao. O que
  # depende de navegador - a altura do quadro e a gravacao da duracao - fica de
  # fora, porque este ambiente nao tem um, e teste que nao roda nao protege
  # nada.

  Background:
    Given the following "courses" exist:
      | fullname  | shortname | format | enablecompletion |
      | Curso LDG | ldgcurso  | ldg    | 1                |
    And the following "users" exist:
      | username  | firstname | lastname  | email           |
      | aluno     | Ana       | Aluna     | aluno@teste.com |
      | professor | Pedro     | Professor | prof@teste.com  |
    And the following "course enrolments" exist:
      | user      | course   | role           |
      | aluno     | ldgcurso | student        |
      | professor | ldgcurso | editingteacher |
    And the following "activities" exist:
      | activity | course   | section | name      | completion |
      | page     | ldgcurso | 1       | Aula um   | 1          |
      | page     | ldgcurso | 1       | Aula dois | 1          |
      | page     | ldgcurso | 2       | Aula tres | 1          |

  Scenario: O aluno ve a lista de aulas agrupada por modulo
    When I am on the "ldgcurso" "Course" page logged in as "aluno"
    Then I should see "Aula um"
    And I should see "Aula dois"
    And I should see "Aula tres"
    And ".ldg-lessonlist" "css_element" should exist

  Scenario: A aula em foco aparece embutida, e a lista marca qual e
    When I am on the "ldgcurso" "Course" page logged in as "aluno"
    Then "iframe.ldg-lesson__frame" "css_element" should exist
    And ".ldg-lessonlist__lesson.is-current" "css_element" should exist

  Scenario: O curso com conclusao mostra a barra de progresso
    When I am on the "ldgcurso" "Course" page logged in as "aluno"
    Then ".ldg-portal__progress" "css_element" should exist
    And ".ldg-lessonlist__module-bar" "css_element" should exist

  Scenario: Aula bloqueada nao vira link
    Given the following config values are set as admin:
      | enableavailability | 1 |
    And the following "activities" exist:
      | activity | course   | section | name         | completion | availability                                                                     |
      | page     | ldgcurso | 2       | Aula trancada | 1         | {"op":"&","c":[{"type":"date","d":">=","t":4102444800}],"showc":[true]}           |
    When I am on the "ldgcurso" "Course" page logged in as "aluno"
    Then ".ldg-lessonlist__lesson.is-locked" "css_element" should exist
    And "//a[contains(@class,'ldg-lessonlist__link')][contains(., 'Aula trancada')]" "xpath_element" should not exist

  Scenario: Com edicao ligada a coluna principal volta a ser a pilha de secoes
    Given I am on the "ldgcurso" "Course" page logged in as "professor"
    When I turn editing mode on
    Then "[data-region='ldg-duration']" "css_element" should exist
    # Arrastar atividade e o menu de acoes vivem nos ganchos que o core poe na
    # marcacao das secoes, e nenhum funciona dentro de um quadro embutido.
    And "iframe.ldg-lesson__frame" "css_element" should not exist
    And "[data-for='course_sectionlist']" "css_element" should exist

  Scenario: Voltar para o formato de secoes nao quebra o curso
    Given I am on the "ldgcurso" "course editing" page logged in as "professor"
    And I expand all fieldsets
    When I set the field "Format" to "Custom sections"
    And I press "Save and display"
    Then I should see "Aula um"
    And I should see "Aula dois"
    And I should see "Aula tres"
    # A tela volta a ser a do formato de secoes, e nada do portal sobra.
    And ".ldg-lessonlist" "css_element" should not exist
    And "iframe.ldg-lesson__frame" "css_element" should not exist
    # NAO se checa "Debug info" aqui: esse texto existe no JSON de strings de
    # idioma que o Moodle embute para o JavaScript, e a assercao falharia sempre.
    # Pagina com excecao ja reprova sozinha, pelo behat_hooks.
