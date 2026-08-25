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
 * Navegacao do marketplace.
 *
 * Sem este arquivo o plugin e invisivel: painel da empresa, vitrine e
 * configuracao do gateway existem, mas so por URL digitada a mao. Funcionalidade
 * que nao tem como ser alcancada e, na pratica, funcionalidade que nao existe.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use local_marketplace\company;

/**
 * Acrescenta o marketplace ao menu da categoria.
 *
 * A categoria e o lugar natural: e onde vivem os cursos da empresa, o contexto
 * onde o papel de vendedor foi atribuido e onde mora a conta de pagamento. O
 * vendedor ja passa por aqui para administrar os cursos dele.
 *
 * @param navigation_node $categorynode
 * @param context_coursecat $catcontext
 * @return void
 */
function local_marketplace_extend_navigation_category_settings($categorynode, $catcontext) {
    $company = company::get_record(['categoryid' => $catcontext->instanceid]);
    if (!$company) {
        return;
    }

    if (!has_capability('local/marketplace:managecompany', $catcontext)) {
        return;
    }

    $categorynode->add(
        get_string('companypanel', 'local_marketplace'),
        new moodle_url('/local/marketplace/company.php', ['company' => $company->get('shortname')]),
        navigation_node::TYPE_SETTING,
        null,
        'local_marketplace_company',
        new pix_icon('i/payment', '')
    );

    $categorynode->add(
        get_string('viewstorefront', 'local_marketplace'),
        new moodle_url('/local/marketplace/offers.php', ['company' => $company->get('shortname')]),
        navigation_node::TYPE_SETTING,
        null,
        'local_marketplace_offers',
        new pix_icon('i/courseevent', '')
    );

    // Capability propria: ver quanto a empresa faturou nao acompanha
    // administrar a empresa. Um vendedor pode publicar curso sem ter acesso ao
    // financeiro dela.
    if (has_capability('local/marketplace:viewreport', $catcontext)) {
        $categorynode->add(
            get_string('reportsection', 'local_marketplace'),
            new moodle_url('/local/marketplace/report.php', ['company' => $company->get('shortname')]),
            navigation_node::TYPE_SETTING,
            null,
            'local_marketplace_report',
            new pix_icon('i/report', '')
        );
    }
}
