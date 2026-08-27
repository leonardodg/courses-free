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

namespace local_marketplace\payment;

use core_payment\local\entities\payable;
use local_marketplace\company;
use local_marketplace\entitlement;
use local_marketplace\offer;
use moodle_exception;
use moodle_url;

/**
 * Liga o marketplace ao subsistema de pagamento do Moodle.
 *
 * O item vendido e a OFERTA, nao o curso: e a oferta que carrega preco, prazo
 * e quais cursos libera. Por isso o itemid destes callbacks e sempre um
 * offerid.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_provider implements \core_payment\local\callback\service_provider {
    /** @var string Unica area de pagamento por enquanto. */
    const PAYMENT_AREA = 'offer';

    /**
     * Valor, moeda e conta que recebe.
     *
     * A conta e a da EMPRESA no PAIS da oferta, no contexto da categoria dela -
     * e o que faz o dinheiro cair na conta do vendedor, e nao numa conta
     * central da plataforma. A comissao sai como split, no gateway.
     *
     * O core nao passa o usuario aqui, de proposito: valor, moeda e conta sao
     * funcao pura do itemid. E por isso que o pais vive na oferta - nao ha como
     * uma oferta ser BRL para um aluno e ARS para outro, e um plano por pais e
     * uma oferta separada.
     *
     * @param string $paymentarea
     * @param int $itemid offerid
     * @return payable
     */
    public static function get_payable(string $paymentarea, int $itemid): payable {
        $offer = new offer($itemid);
        $company = new company($offer->get('companyid'));

        $account = $company->get_payment_account((string) $offer->get('country'));
        if (!$account) {
            throw new moodle_exception('errorcannotsell', 'local_marketplace');
        }

        return new payable(
            (float) $offer->get('price'),
            $offer->get('currency'),
            (int) $account->get('id')
        );
    }

    /**
     * Para onde mandar o aluno depois de pagar.
     *
     * Oferta de um curso so vai direto para ele. Combo e assinatura liberam
     * varios, entao nao ha "o curso" para onde ir - vai para a lista de
     * cursos do aluno, onde os novos ja aparecem.
     *
     * @param string $paymentarea
     * @param int $itemid offerid
     * @return moodle_url
     */
    public static function get_success_url(string $paymentarea, int $itemid): moodle_url {
        $offer = new offer($itemid);
        $courseids = $offer->get_course_ids();

        if ($offer->get('offertype') === offer::TYPE_SINGLE && count($courseids) === 1) {
            return new moodle_url('/course/view.php', ['id' => reset($courseids)]);
        }

        return new moodle_url('/my/courses.php');
    }

    /**
     * Entrega o que foi pago.
     *
     * Cria o direito de acesso e sincroniza a matricula. Nao matricula
     * diretamente: quem decide em quais cursos o aluno entra e o
     * enrol_marketplace, a partir dos direitos vigentes. Duplicar essa logica
     * aqui abriria espaco para os dois discordarem.
     *
     * Idempotente de proposito: o Mercado Pago reenvia notificacao de webhook,
     * e uma segunda entrega nao pode gerar um segundo direito. Se ja existe
     * direito vigente para esta oferta, ESTENDE em vez de criar - que e
     * exatamente o comportamento desejado na renovacao de assinatura.
     *
     * @param string $paymentarea
     * @param int $itemid offerid
     * @param int $paymentid
     * @param int $userid
     * @return bool
     */
    public static function deliver_order(string $paymentarea, int $itemid, int $paymentid, int $userid): bool {
        $offer = new offer($itemid);

        $existing = null;
        foreach (entitlement::get_active_for_user($userid, (int) $offer->get('companyid')) as $ent) {
            if ((int) $ent->get('offerid') === $itemid) {
                $existing = $ent;
                break;
            }
        }

        if ($existing) {
            $seconds = $offer->get_access_duration();
            if ($seconds > 0) {
                $existing->extend($seconds);
            }
            // O ciclo e contado mesmo em oferta vitalicia, onde nao ha o que
            // estender: o numero serve ao relatorio e ao limite de cobrancas,
            // e nao a data de validade.
            $existing->set('cycles', (int) $existing->get('cycles') + 1);
            $existing->update();
        } else {
            $ent = new entitlement();
            $ent->set('userid', $userid);
            $ent->set('offerid', $itemid);
            $ent->set('companyid', (int) $offer->get('companyid'));
            $ent->set('timestart', time());
            $ent->set('timeend', $offer->calculate_expiry());
            $ent->set('cycles', 1);
            $ent->create();
        }

        $plugin = enrol_get_plugin('marketplace');
        if ($plugin) {
            $plugin->sync_user($userid);
        } else {
            // Sem o enrol o direito existe mas nao vira acesso. E uma falha de
            // configuracao, nao do pagamento - por isso registra e segue, em
            // vez de recusar a entrega de algo que o aluno ja pagou.
            debugging(
                'local_marketplace: enrol_marketplace desabilitado; direito criado sem matricula.',
                DEBUG_DEVELOPER
            );
        }

        return true;
    }
}
