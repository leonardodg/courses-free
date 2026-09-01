<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_partners\form;

use local_marketplace\cnpj;
use local_marketplace\plan;
use local_partners\api;
use local_partners\application;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Candidatura de empresa parceira, preenchida por visitante anonimo.
 *
 * Formulario PUBLICO. Tudo aqui parte do principio de que quem envia pode ser
 * um robo, e as tres camadas de defesa estao nesta ordem de confiabilidade:
 *
 *   1. limite de taxa por IP   sempre ligado, nao depende de nada externo
 *   2. honeypot                sempre ligado, nao depende de nada externo
 *   3. reCAPTCHA               so com o interruptor ligado E as chaves do site
 *                              cadastradas - as duas condicoes
 *
 * A ordem importa: a camada mais forte nao pode ser a que alguem precisa
 * lembrar de configurar. O honeypot NAO tem interruptor de proposito: ele e
 * invisivel, nao atrapalha ninguem e nao custa nada, entao desliga-lo so
 * pioraria o site.
 *
 * @package    local_partners
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class application_form extends \moodleform {
    /**
     * @var array Teto de cada campo, igual ao tamanho da coluna no banco.
     *
     * Fica aqui e nao no install.xml porque quem precisa recusar antes do
     * INSERT e o formulario: o banco recusa tambem, mas com erro 500.
     */
    private const MAX_LENGTHS = [
        'companyname' => 255,
        'cnpj' => 18,
        'contactname' => 255,
        'contactemail' => 255,
        'contactphone' => 30,
        'website' => 255,
    ];

    /** @var int Teto da mensagem livre. */
    private const MAX_MESSAGE = 2000;

    /**
     * Campos.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'companyname', get_string('companyname', 'local_partners'), ['size' => 50, 'maxlength' => 255]);
        $mform->setType('companyname', PARAM_TEXT);
        $mform->addRule('companyname', null, 'required', null, 'client');

        $mform->addElement('text', 'cnpj', get_string('cnpj', 'local_partners'), ['size' => 20, 'maxlength' => 18]);
        $mform->setType('cnpj', PARAM_TEXT);
        $mform->addHelpButton('cnpj', 'cnpj', 'local_partners');

        // Quem esta autenticado nao redigita o que o site ja sabe. Alem do
        // atrito, um e-mail diferente do da conta criaria uma candidatura que
        // parece de outra pessoa - e o dono da empresa sai daqui.
        if ($this->is_authenticated()) {
            global $USER;

            $mform->addElement(
                'static',
                'contactnamestatic',
                get_string('contactname', 'local_partners'),
                fullname($USER)
            );
            $mform->addElement('hidden', 'contactname', fullname($USER));
            $mform->setType('contactname', PARAM_TEXT);

            $mform->addElement(
                'static',
                'contactemailstatic',
                get_string('contactemail', 'local_partners'),
                s($USER->email)
            );
            $mform->addElement('hidden', 'contactemail', $USER->email);
            $mform->setType('contactemail', PARAM_RAW_TRIMMED);
        } else {
            $mform->addElement(
                'text',
                'contactname',
                get_string('contactname', 'local_partners'),
                ['size' => 50, 'maxlength' => 255]
            );
            $mform->setType('contactname', PARAM_TEXT);
            $mform->addRule('contactname', null, 'required', null, 'client');

            $mform->addElement(
                'text',
                'contactemail',
                get_string('contactemail', 'local_partners'),
                ['size' => 50, 'maxlength' => 255]
            );
            $mform->setType('contactemail', PARAM_RAW_TRIMMED);
            $mform->addRule('contactemail', null, 'required', null, 'client');
        }

        $mform->addElement('text', 'contactphone', get_string('contactphone', 'local_partners'), ['size' => 30, 'maxlength' => 30]);
        $mform->setType('contactphone', PARAM_TEXT);

        $mform->addElement('text', 'website', get_string('website', 'local_partners'), ['size' => 50, 'maxlength' => 255]);
        $mform->setType('website', PARAM_RAW_TRIMMED);

        // Os planos vem do banco, e nao de uma lista escrita aqui: e a mesma
        // fonte que a comparacao de planos da landing usa.
        $plans = ['' => get_string('planundecided', 'local_partners')];
        foreach (plan::get_public_plans() as $plan) {
            $plans[(int) $plan->get('id')] = format_string($plan->get('name'));
        }
        $mform->addElement('select', 'planid', get_string('planofinterest', 'local_partners'), $plans);
        $mform->setType('planid', PARAM_INT);

        $mform->addElement('textarea', 'message', get_string('applicationmessage', 'local_partners'), [
            'rows' => 4,
            'cols' => 50,
        ]);
        $mform->setType('message', PARAM_TEXT);

        // Honeypot. Escondido por CSS, nunca por type="hidden": um robo ignora
        // display:none e preenche tudo que parece campo, mas tambem preenche
        // hidden. O rotulo existe para leitor de tela avisar para nao preencher.
        $mform->addElement('text', 'fax', get_string('honeypotlabel', 'local_partners'), [
            'autocomplete' => 'off',
            'tabindex' => '-1',
            'class' => 'local-partners-honeypot',
        ]);
        $mform->setType('fax', PARAM_TEXT);

        // Captcha so para visitante anonimo: quem ja entrou no site passou pelo
        // login, e um captcha ali seria atrito sem defesa nenhuma.
        if (!$this->is_authenticated() && api::recaptcha_available()) {
            $mform->addElement('recaptcha', 'recaptcha_element', get_string('security_question', 'auth'));
            $mform->closeHeaderBefore('recaptcha_element');
        }

        $this->add_action_buttons(true, get_string('submitapplication', 'local_partners'));
    }

    /**
     * Validacao.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // O maxlength dos campos e atributo HTML, e some com um curl. A
        // checagem que vale e esta: sem ela, um envio maior que a coluna sobe
        // um dml_write_exception - erro 500 numa pagina publica, com stack
        // trace no log a cada tentativa.
        foreach (self::MAX_LENGTHS as $field => $max) {
            if (\core_text::strlen((string) ($data[$field] ?? '')) > $max) {
                $errors[$field] = get_string('errortoolong', 'local_partners', $max);
            }
        }

        // A mensagem e TEXT no banco, mas sem teto um robo posta megabytes.
        if (\core_text::strlen((string) ($data['message'] ?? '')) > self::MAX_MESSAGE) {
            $errors['message'] = get_string('errortoolong', 'local_partners', self::MAX_MESSAGE);
        }

        if (!validate_email($data['contactemail'] ?? '')) {
            $errors['contactemail'] = get_string('erroremailinvalid', 'local_partners');
        }

        $digits = !empty($data['cnpj']) ? cnpj::normalise($data['cnpj']) : '';

        if ($digits !== '' && !cnpj::is_valid($digits)) {
            $errors['cnpj'] = get_string('errorcnpjinvalid', 'local_partners');
        }

        // Duplicidade em aberto. Nao e anti-spam: e evitar que a mesma empresa
        // ocupe tres linhas da fila porque quem enviou nao viu a confirmacao.
        if (empty($errors) && application::has_pending_for($data['contactemail'], $digits ?: null)) {
            $errors['contactemail'] = get_string('errorduplicatepending', 'local_partners');
        }

        // Limite de taxa por IP.
        if (empty($errors) && application::count_recent_from_ip(getremoteaddr('', 45)) >= api::max_per_hour()) {
            $errors['companyname'] = get_string('errortoomany', 'local_partners');
        }

        // O elemento recaptcha do Moodle NAO se valida sozinho: ele desenha o
        // widget e para por ai. Quem verifica e o formulario, chamando verify()
        // - e o signup_form.php do core faz exatamente isto. Sem este bloco o
        // captcha e decoracao: aparece na tela e nao barra ninguem.
        if (!$this->is_authenticated() && api::recaptcha_available()) {
            $element = $this->_form->getElement('recaptcha_element');
            $response = $this->_form->_submitValues['g-recaptcha-response'] ?? '';

            if ($response === '') {
                $errors['recaptcha_element'] = get_string('missingrecaptchachallengefield');
            } else if (!$element->verify($response)) {
                $errors['recaptcha_element'] = get_string('incorrectpleasetryagain', 'auth');
            }
        }

        return $errors;
    }

    /**
     * O envio veio de alguem autenticado?
     *
     * @return bool
     */
    protected function is_authenticated(): bool {
        return isloggedin() && !isguestuser();
    }

    /**
     * O honeypot foi preenchido?
     *
     * Fica fora do validation() de proposito: devolver erro ensinaria o robo a
     * nao preencher o campo da proxima vez. Quem chama deve mostrar a mesma
     * tela de sucesso e nao gravar nada.
     *
     * @return bool
     */
    public function is_bot(): bool {
        $data = $this->get_data();

        return $data && !empty($data->fax);
    }
}
