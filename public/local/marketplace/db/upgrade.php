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
    global $DB;

    if ($oldversion < 2026082403) {
        // O install.php passou a garantir allowcategorythemes, mas ele so roda
        // em instalacao nova. Sem este passo, todo ambiente que ja tem o plugin
        // continuaria com o tema por empresa quebrado em silencio.
        require_once(__DIR__ . '/install.php');
        local_marketplace_require_category_themes();

        upgrade_plugin_savepoint(true, 2026082403, 'local', 'marketplace');
    }

    if ($oldversion < 2026082404) {
        // Remove a tabela local_marketplace_mpaccount.
        //
        // Ela guardava o token do Mercado Pago, o que criava DUAS fontes de
        // verdade para uma credencial financeira. A credencial passou a viver
        // onde o Moodle espera: account_gateway.config do core_payment.
        //
        // Tirar a tabela do install.xml nao basta - o Moodle nunca apaga
        // tabela sozinho num upgrade, e com razao. O sintoma era o
        // check_database_schema acusando "table is not expected".
        //
        // Nao ha dado a migrar: o vinculo com o Mercado Pago sempre foi feito
        // pelo fluxo OAuth do gateway, que grava direto na conta de pagamento.
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_marketplace_mpaccount');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_plugin_savepoint(true, 2026082404, 'local', 'marketplace');
    }

    return true;
}
