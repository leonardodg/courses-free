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
 * Atualizacoes do paygw_mercadopago.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Executa os passos de atualizacao.
 *
 * @param int $oldversion Versao instalada.
 * @return bool
 */
function xmldb_paygw_mercadopago_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026090110) {
        // Foto dos termos da comissao na propria linha da cobranca.
        //
        // As cobrancas que ja existem ficam com os defaults, e isso e uma
        // aproximacao e nao um fato sobre elas: foram criadas quando a base nao
        // era configuravel. Ver docs/adr/0007-comissao-sobre-o-bruto.md.
        $table = new xmldb_table('paygw_mercadopago');

        // O Mercado Pago nunca guardou o percentual, so o valor. Sem ele a
        // linha nao explica como chegou naquele marketplace_fee.
        $field = new xmldb_field('feepercent', XMLDB_TYPE_NUMBER, '5, 2', null, XMLDB_NOTNULL, null, '0', 'feeamount');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $after = 'feepercent';

        $field = new xmldb_field('feebase', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'gross', $after);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('feesource', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'site', 'feebase');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026090110, 'paygw', 'mercadopago');
    }

    return true;
}
