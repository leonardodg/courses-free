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

namespace local_marketplace\form;

use local_marketplace\company;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Cadastro de empresa, usado pelo administrador.
 *
 * A parceria e fechada fora do sistema; esta tela apenas provisiona o que foi
 * combinado. Nao ha auto-atendimento porque criar empresa cria uma CATEGORIA,
 * que e objeto global - aparece na arvore que todos os usuarios veem.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_form extends \moodleform {

    /**
     * Campos.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('companyname', 'local_marketplace'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('text', 'shortname', get_string('companyshortname', 'local_marketplace'), ['size' => 30]);
        $mform->setType('shortname', PARAM_ALPHANUMEXT);
        $mform->addRule('shortname', null, 'required', null, 'client');
        $mform->addHelpButton('shortname', 'companyshortname', 'local_marketplace');

        // O dono e quem administra a empresa e vincula a conta Mercado Pago.
        // Autocomplete por ajax em vez de uma lista de usuarios: numa
        // plataforma aberta a lista cresce sem limite.
        $mform->addElement('autocomplete', 'ownerid', get_string('companyowner', 'local_marketplace'), [], [
            'ajax' => 'core_user/form_user_selector',
            'multiple' => false,
            'valuehtmlcallback' => function($userid) {
                if (empty($userid)) {
                    return '';
                }
                $user = \core_user::get_user($userid);
                return $user ? fullname($user) . ' (' . s($user->email) . ')' : '';
            },
        ]);
        $mform->addRule('ownerid', null, 'required', null, 'client');
        $mform->addHelpButton('ownerid', 'companyowner', 'local_marketplace');

        $mform->addElement('text', 'cnpj', get_string('companycnpj', 'local_marketplace'), ['size' => 20]);
        $mform->setType('cnpj', PARAM_ALPHANUM);
        $mform->addHelpButton('cnpj', 'companycnpj', 'local_marketplace');

        // Tema por categoria. Depende de $CFG->allowcategorythemes, que o
        // install.php garante - sem ele o campo e gravado e nao surte efeito.
        $themes = ['' => get_string('defaultthemename', 'local_marketplace')];
        foreach (\core_component::get_plugin_list('theme') as $name => $unused) {
            $themes[$name] = get_string('pluginname', 'theme_' . $name);
        }
        $mform->addElement('select', 'themename', get_string('companytheme', 'local_marketplace'), $themes);
        $mform->setType('themename', PARAM_PLUGIN);

        // Fase 3. O campo existe para o cadastro nao precisar ser refeito
        // depois, mas o dominio so passa a funcionar quando o mapa
        // Host->wwwroot do config.php entrar.
        $mform->addElement('text', 'hostname', get_string('companyhostname', 'local_marketplace'), ['size' => 40]);
        $mform->setType('hostname', PARAM_HOST);
        $mform->addHelpButton('hostname', 'companyhostname', 'local_marketplace');

        $this->add_action_buttons(true, get_string('createcompany', 'local_marketplace'));
    }

    /**
     * Validacao.
     *
     * Repete aqui o que o persistent ja checa porque a mensagem do persistent
     * sairia como excecao, e nao ao lado do campo errado.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['shortname']) && company::get_record(['shortname' => $data['shortname']])) {
            $errors['shortname'] = get_string('errorshortnametaken', 'local_marketplace');
        }

        if (!empty($data['hostname']) && company::get_record(['hostname' => $data['hostname']])) {
            $errors['hostname'] = get_string('errorhostnametaken', 'local_marketplace');
        }

        return $errors;
    }
}
