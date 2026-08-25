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
 * Direito de acesso: o que o aluno comprou.
 *
 * E a fonte da verdade do acesso. O enrol_marketplace e o
 * availability_marketplace consultam esta tabela, nunca a venda: o pagamento
 * diz o que aconteceu uma vez, o direito diz o que vale agora.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entitlement extends persistent {

    /** @var string Tabela. */
    const TABLE = 'local_marketplace_entitlement';

    /** @var string Vigente. */
    const STATUS_ACTIVE = 'active';

    /** @var string Passou de timeend. */
    const STATUS_EXPIRED = 'expired';

    /** @var string Cancelado pelo aluno ou pela plataforma. */
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Define as propriedades.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'userid' => ['type' => PARAM_INT],
            'offerid' => ['type' => PARAM_INT],
            'companyid' => ['type' => PARAM_INT],
            'timestart' => ['type' => PARAM_INT, 'default' => 0],
            'timeend' => ['type' => PARAM_INT, 'default' => 0],
            'status' => [
                'type' => PARAM_ALPHA,
                'default' => self::STATUS_ACTIVE,
                'choices' => [self::STATUS_ACTIVE, self::STATUS_EXPIRED, self::STATUS_CANCELLED],
            ],
            'cycles' => ['type' => PARAM_INT, 'default' => 0],
            'norenew' => ['type' => PARAM_INT, 'default' => 0],
        ];
    }

    /**
     * O direito vale agora?
     *
     * Confere a data alem do status. O status so muda quando a task de
     * expiracao roda; entre o vencimento e a proxima execucao do cron o
     * registro ainda diz "active", e confiar nele daria acesso a quem ja
     * perdeu o direito.
     *
     * @param int|null $when Momento a verificar; agora se omitido.
     * @return bool
     */
    public function is_active(?int $when = null): bool {
        if ($this->get('status') !== self::STATUS_ACTIVE) {
            return false;
        }
        $when = $when ?? time();
        $end = (int) $this->get('timeend');

        return ($this->get('timestart') <= $when) && ($end === 0 || $end > $when);
    }

    /**
     * Revoga o direito imediatamente.
     *
     * Decisao de negocio: cancelou, perde na hora - nao ao fim do periodo pago.
     * Se um dia mudar, e AQUI que muda: basta gravar timeend com o fim do
     * ciclo em vez de marcar cancelled, e o resto do sistema acompanha
     * sozinho, porque tudo consulta is_active().
     *
     * @return void
     */
    public function revoke(): void {
        $this->set('status', self::STATUS_CANCELLED);
        $this->update();
    }

    /**
     * Estende o direito, usado na renovacao da assinatura.
     *
     * Soma ao vencimento atual, nao a agora: pagar adiantado nao pode encurtar
     * o que ja foi pago.
     *
     * @param int $seconds
     * @return void
     */
    public function extend(int $seconds): void {
        $end = (int) $this->get('timeend');
        if ($end === 0) {
            return; // Vitalicio nao se estende.
        }
        $base = max($end, time());
        $this->set('timeend', $base + $seconds);
        $this->set('status', self::STATUS_ACTIVE);
        $this->update();
    }

    /**
     * Direitos vigentes de um usuario.
     *
     * @param int $userid
     * @param int|null $companyid Filtra por empresa.
     * @return entitlement[]
     */
    public static function get_active_for_user(int $userid, ?int $companyid = null): array {
        global $DB;

        $now = time();
        $params = ['userid' => $userid, 'status' => self::STATUS_ACTIVE, 'now' => $now, 'now2' => $now];
        $where = "userid = :userid
                  AND status = :status
                  AND timestart <= :now
                  AND (timeend = 0 OR timeend > :now2)";
        if ($companyid !== null) {
            $where .= " AND companyid = :companyid";
            $params['companyid'] = $companyid;
        }
        $records = $DB->get_records_select(self::TABLE, $where, $params);

        return array_map(fn($record) => new self(0, $record), $records);
    }

    /**
     * O usuario tem direito vigente a este curso?
     *
     * Resolve por oferta, nao por matricula: um combo ou uma assinatura dao
     * acesso a cursos que o aluno nunca comprou individualmente.
     *
     * @param int $userid
     * @param int $courseid
     * @return bool
     */
    public static function user_has_course_access(int $userid, int $courseid): bool {
        foreach (self::get_active_for_user($userid) as $ent) {
            $offer = new offer($ent->get('offerid'));
            if (in_array($courseid, $offer->get_course_ids())) {
                return true;
            }
        }
        return false;
    }
}
