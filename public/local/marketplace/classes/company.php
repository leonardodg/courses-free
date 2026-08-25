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

use core\persistent;
use lang_string;

/**
 * Empresa vendedora.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company extends persistent {

    /** @var string Tabela. */
    const TABLE = 'local_marketplace_company';

    /** @var string Empresa em operacao. */
    const STATUS_ACTIVE = 'active';

    /** @var string Empresa bloqueada pelo dono da plataforma. */
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Define as propriedades.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'shortname' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'cnpj' => [
                'type' => PARAM_ALPHANUM,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'categoryid' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'themename' => [
                'type' => PARAM_COMPONENT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'hostname' => [
                'type' => PARAM_HOST,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'status' => [
                'type' => PARAM_ALPHA,
                'default' => self::STATUS_ACTIVE,
                'choices' => [self::STATUS_ACTIVE, self::STATUS_SUSPENDED],
            ],
        ];
    }

    /**
     * O nome curto vai para a URL, entao precisa ser unico.
     *
     * @param string $value
     * @return true|lang_string
     */
    protected function validate_shortname($value) {
        $existing = self::get_record(['shortname' => $value]);
        if ($existing && $existing->get('id') != $this->get('id')) {
            return new lang_string('errorshortnametaken', 'local_marketplace');
        }
        return true;
    }

    /**
     * Dois dominios iguais quebrariam a resolucao Host->empresa da Fase 3.
     *
     * @param string|null $value
     * @return true|lang_string
     */
    protected function validate_hostname($value) {
        if ($value === null || $value === '') {
            return true;
        }
        $existing = self::get_record(['hostname' => $value]);
        if ($existing && $existing->get('id') != $this->get('id')) {
            return new lang_string('errorhostnametaken', 'local_marketplace');
        }
        return true;
    }

    /**
     * A empresa pode vender curso pago?
     *
     * Este e o portao de venda, e e ESTADO, nao permissao. Uma capability
     * responderia "tem direito de vender?"; a pergunta real e "esta habilitada
     * a receber?". Sem meio de recebimento configurado nao ha para onde mandar
     * o dinheiro do vendedor, entao a empresa so publica curso gratuito.
     *
     * A pergunta e feita ao core_payment, NAO ao gateway. account::is_available()
     * ja responde "habilitada e com ao menos um gateway configurado", e mantem
     * isto agnostico: se um dia entrar outro meio de pagamento, o portao
     * continua correto sem tocar aqui.
     *
     * @return bool
     */
    public function can_sell(): bool {
        if ($this->get('status') !== self::STATUS_ACTIVE) {
            return false;
        }
        $account = $this->get_payment_account();
        return $account && $account->is_available();
    }

    /**
     * Payment account da empresa, no contexto da categoria dela.
     *
     * @return \core_payment\account|null
     */
    public function get_payment_account(): ?\core_payment\account {
        $categoryid = $this->get('categoryid');
        if (empty($categoryid)) {
            return null;
        }
        $context = \context_coursecat::instance($categoryid, IGNORE_MISSING);
        if (!$context) {
            return null;
        }
        $account = \core_payment\account::get_record([
            'contextid' => $context->id,
            'archived' => 0,
        ]);

        return $account ?: null;
    }

    /**
     * Moeda em que esta empresa recebe.
     *
     * Nao e escolha livre: vem do pais da conta Mercado Pago vinculada, gravada
     * no gateway pelo fluxo OAuth. Uma conta e presa a um pais e so recebe na
     * moeda dele, entao a empresa "escolhe" a moeda escolhendo QUAL conta
     * vincular - e trocar de moeda significa vincular outra conta.
     *
     * Vazio quando a empresa ainda nao vinculou, ou quando o vinculo ocorreu
     * antes de passarmos a consultar o pais.
     *
     * @return string Codigo ISO de tres letras, ou vazio
     */
    public function get_payment_currency(): string {
        $account = $this->get_payment_account();
        if (!$account) {
            return '';
        }
        foreach ($account->get_gateways() as $gw) {
            if (!$gw->get('id') || !$gw->get('enabled')) {
                continue;
            }
            $currency = (string) ($gw->get_configuration()['currency'] ?? '');
            if ($currency !== '') {
                return $currency;
            }
        }
        return '';
    }

    /**
     * Contexto da categoria da empresa, onde vivem as capabilities e o papel.
     *
     * @return \context
     */
    public function get_context(): \context {
        $categoryid = $this->get('categoryid');
        if (empty($categoryid)) {
            return \context_system::instance();
        }
        return \context_coursecat::instance($categoryid);
    }

    /**
     * Empresas em que o usuario e membro.
     *
     * @param int $userid
     * @return company[]
     */
    public static function get_by_member(int $userid): array {
        global $DB;

        $sql = "SELECT c.*
                  FROM {" . self::TABLE . "} c
                  JOIN {" . member::TABLE . "} m ON m.companyid = c.id
                 WHERE m.userid = :userid
              ORDER BY c.name";
        $records = $DB->get_records_sql($sql, ['userid' => $userid]);

        return array_map(fn($record) => new self(0, $record), $records);
    }
}
