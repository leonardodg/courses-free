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

/**
 * Credencial Mercado Pago da empresa.
 *
 * E o portao de venda do marketplace: enquanto nao houver um registro com
 * status "linked", a empresa so publica curso gratuito.
 *
 * O token vem do fluxo OAuth da NOSSA aplicacao, nao de uma chave colada pelo
 * vendedor. A distincao nao e cosmetica: o marketplace_fee, que e como a
 * comissao de 25% e cobrada, so funciona com token emitido via OAuth para a
 * aplicacao que cria a preferencia de pagamento.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mpaccount extends persistent {

    /** @var string Tabela. */
    const TABLE = 'local_marketplace_mpaccount';

    /** @var string Fluxo OAuth iniciado, ainda sem token. */
    const STATUS_PENDING = 'pending';

    /** @var string Token valido: a empresa pode vender. */
    const STATUS_LINKED = 'linked';

    /** @var string Token venceu. O MP expira em ~6 meses. */
    const STATUS_EXPIRED = 'expired';

    /** @var string Vendedor revogou a autorizacao no painel do MP. */
    const STATUS_REVOKED = 'revoked';

    /**
     * Define as propriedades.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'companyid' => [
                'type' => PARAM_INT,
            ],
            'mpuserid' => [
                'type' => PARAM_ALPHANUMEXT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'accesstoken' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'refreshtoken' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'tokenexpires' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'oauthstatus' => [
                'type' => PARAM_ALPHA,
                'default' => self::STATUS_PENDING,
                'choices' => [
                    self::STATUS_PENDING,
                    self::STATUS_LINKED,
                    self::STATUS_EXPIRED,
                    self::STATUS_REVOKED,
                ],
            ],
        ];
    }

    /**
     * A conta esta apta a receber?
     *
     * Confere a data alem do status: o registro pode ainda dizer "linked" e o
     * token ja ter vencido, se a task de renovacao nao rodou. Tratar como
     * valido nesse intervalo faria a venda falhar so no checkout, diante do
     * aluno.
     *
     * @return bool
     */
    public function is_linked(): bool {
        if ($this->get('oauthstatus') !== self::STATUS_LINKED) {
            return false;
        }
        $expires = (int) $this->get('tokenexpires');
        return $expires === 0 || $expires > time();
    }

    /**
     * Falta pouco para o token vencer?
     *
     * @param int $days Antecedencia em dias.
     * @return bool
     */
    public function expires_within(int $days = 15): bool {
        $expires = (int) $this->get('tokenexpires');
        if ($expires === 0) {
            return false;
        }
        return $expires <= (time() + ($days * DAYSECS));
    }
}
