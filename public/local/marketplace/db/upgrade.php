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
 * Upgrade do local_marketplace.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Passos de upgrade.
 *
 * @param int $oldversion Versao instalada.
 * @return bool
 */
function xmldb_local_marketplace_upgrade($oldversion) {
    if ($oldversion < 2026082403) {
        // O install.php passou a garantir allowcategorythemes, mas ele so roda
        // em instalacao nova. Sem este passo, todo ambiente que ja tem o plugin
        // continuaria com o tema por empresa quebrado em silencio.
        require_once(__DIR__ . '/install.php');
        local_marketplace_require_category_themes();

        upgrade_plugin_savepoint(true, 2026082403, 'local', 'marketplace');
    }

    return true;
}
