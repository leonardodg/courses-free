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
 * Landing de captacao de empresas parceiras.
 *
 * Pagina PUBLICA: sem require_login de proposito. O visitante que ela quer
 * atingir e justamente quem ainda nao tem conta.
 *
 * @package    local_partners
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing -- Pagina publica, ver o docblock acima.
require(__DIR__ . '/../../config.php');

use local_partners\landing;

$PAGE->set_context(\core\context\system::instance());
$PAGE->set_url(new moodle_url('/local/partners/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('landingtitle', 'local_partners'));
$PAGE->set_heading('');

if (!landing::is_enabled()) {
    throw new moodle_exception('landingdisabled', 'local_partners');
}

echo $OUTPUT->header();
echo landing::render($OUTPUT);
echo $OUTPUT->footer();
