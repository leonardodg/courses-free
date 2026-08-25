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

namespace local_marketplace;

use core_course_category;
use moodle_exception;

/**
 * Operacoes do marketplace que envolvem mais de uma entidade.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {

    /**
     * Cria a empresa e provisiona tudo que ela precisa para operar.
     *
     * Uma empresa sem categoria seria uma linha inerte: e a categoria que da
     * a ela um contexto onde atribuir o papel, um lugar para os cursos e um
     * tema proprio. Por isso o provisionamento e parte da criacao, e nao um
     * passo separado que alguem poderia esquecer.
     *
     * Tudo numa transacao: uma empresa com categoria mas sem dono, ou com dono
     * mas sem papel atribuido, seria pior que nenhuma empresa - apareceria na
     * lista e nao funcionaria.
     *
     * @param object $data name, shortname, cnpj, themename, hostname
     * @param int $ownerid Usuario que passa a ser dono.
     * @return company
     */
    public static function create_company(object $data, int $ownerid): company {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        try {
            $company = new company();
            $company->set('name', $data->name);
            $company->set('shortname', $data->shortname);
            $company->set('cnpj', !empty($data->cnpj) ? $data->cnpj : null);
            $company->set('themename', !empty($data->themename) ? $data->themename : null);
            $company->set('hostname', !empty($data->hostname) ? $data->hostname : null);
            $company->create();

            $category = self::create_category($company);
            $company->set('categoryid', $category->id);
            $company->update();

            $owner = new member();
            $owner->set('companyid', $company->get('id'));
            $owner->set('userid', $ownerid);
            $owner->set('memberrole', member::ROLE_OWNER);
            $owner->create();

            self::assign_seller_role($company, $ownerid);
            self::apply_theme($company);
            self::create_payment_account($company);

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }

        return $company;
    }

    /**
     * Cria a categoria de cursos da empresa.
     *
     * @param company $company
     * @return \stdClass
     */
    protected static function create_category(company $company): \stdClass {
        $category = core_course_category::create([
            'name' => $company->get('name'),
            'idnumber' => 'marketplace_' . $company->get('shortname'),
            'description' => '',
            'parent' => 0,
        ]);
        return $category->get_db_record();
    }

    /**
     * Cria a payment account da empresa, no contexto da categoria dela.
     *
     * E o contexto que faz o vendedor conseguir administrar a propria conta
     * sem enxergar as das outras empresas: moodle/payment:manageaccounts e
     * CONTEXT_COURSE, e helper::get_payment_accounts_menu() resolve pelos
     * contextos-pai.
     *
     * A conta nasce SEM gateway. E isso que mantem o portao de venda fechado
     * ate o vendedor concluir o vinculo: account::is_available() exige ao
     * menos um gateway habilitado.
     *
     * @param company $company
     * @return \core_payment\account
     */
    public static function create_payment_account(company $company): \core_payment\account {
        $context = $company->get_context();

        $account = new \core_payment\account();
        $account->set('name', $company->get('name'));
        $account->set('idnumber', 'marketplace_' . $company->get('shortname'));
        $account->set('contextid', $context->id);
        $account->set('enabled', true);
        $account->create();

        return $account;
    }

    /**
     * Da a um usuario o papel de vendedor no contexto da empresa.
     *
     * @param company $company
     * @param int $userid
     * @return void
     */
    public static function assign_seller_role(company $company, int $userid): void {
        global $DB;

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'marketplaceseller']);
        if (!$roleid) {
            throw new moodle_exception('errorsellerrolemissing', 'local_marketplace');
        }
        role_assign($roleid, $userid, $company->get_context()->id);
    }

    /**
     * Aplica o tema da empresa na categoria dela.
     *
     * Depende de $CFG->allowcategorythemes. Sem ele o campo e gravado e
     * simplesmente ignorado pelo Moodle, o que renderia um bug silencioso:
     * o vendedor escolhe o tema, a tela confirma, e nada muda.
     *
     * @param company $company
     * @return bool Falso quando o tema por categoria esta desligado no site.
     */
    public static function apply_theme(company $company): bool {
        global $DB, $CFG;

        $theme = $company->get('themename');
        $categoryid = $company->get('categoryid');
        if (empty($theme) || empty($categoryid)) {
            return true;
        }

        if (empty($CFG->allowcategorythemes)) {
            debugging(
                'local_marketplace: tema por empresa exige $CFG->allowcategorythemes ligado.',
                DEBUG_DEVELOPER
            );
            return false;
        }

        $DB->set_field('course_categories', 'theme', $theme, ['id' => $categoryid]);
        theme_reset_static_caches();
        return true;
    }

    /**
     * Acrescenta um vendedor a empresa.
     *
     * @param company $company
     * @param int $userid
     * @return member
     */
    public static function add_member(company $company, int $userid): member {
        global $DB;

        $existing = member::get_membership($company->get('id'), $userid);
        if ($existing) {
            return $existing;
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            $member = new member();
            $member->set('companyid', $company->get('id'));
            $member->set('userid', $userid);
            $member->set('memberrole', member::ROLE_SELLER);
            $member->create();

            self::assign_seller_role($company, $userid);
            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }

        return $member;
    }
}
