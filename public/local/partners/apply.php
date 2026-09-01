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

/**
 * Formulario de candidatura a parceria.
 *
 * Pagina PUBLICA. Ver as tres camadas de anti-spam em
 * classes/form/application_form.php.
 *
 * @package    local_partners
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing -- Pagina publica, ver o docblock acima.
require(__DIR__ . '/../../config.php');

use local_partners\api;
use local_partners\form\application_form;
use local_partners\landing;

$PAGE->set_context(\core\context\system::instance());
$PAGE->set_url(new moodle_url('/local/partners/apply.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('applytitle', 'local_partners'));
$PAGE->set_heading('');

if (!landing::is_enabled()) {
    throw new moodle_exception('landingdisabled', 'local_partners');
}

$form = new application_form($PAGE->url);
$thanksurl = new moodle_url('/local/partners/thanks.php');

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/partners/index.php'));
}

if ($data = $form->get_data()) {
    // Honeypot preenchido: responde como se tivesse dado certo e nao grava
    // nada. Devolver erro ensinaria o robo a nao preencher o campo da proxima
    // vez, e a proxima vez seria bem-sucedida.
    if (!empty($data->fax)) {
        redirect($thanksurl);
    }

    $application = api::submit($data);

    if ($application->get('status') === \local_partners\application::STATUS_UNCONFIRMED) {
        $thanksurl->param('confirm', 1);
    }

    redirect($thanksurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('applytitle', 'local_partners'));
echo html_writer::tag('p', get_string('applylead', 'local_partners'), ['class' => 'lead']);
$form->display();
echo $OUTPUT->footer();
