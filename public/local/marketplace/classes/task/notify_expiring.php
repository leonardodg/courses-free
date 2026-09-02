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

namespace local_marketplace\task;

use local_marketplace\company;
use local_marketplace\entitlement;
use local_marketplace\offer;

/**
 * Avisa o aluno que o acesso esta para vencer.
 *
 * Existe porque o Mercado Pago nao cobra sozinho com split: nao ha debito
 * automatico a disparar, entao o aviso E o mecanismo de renovacao. Sem ele o
 * aluno so descobre que venceu quando tenta entrar no curso - e a essa altura
 * ja perdeu aula, e a chance de ele culpar a plataforma e alta.
 *
 * @package    local_marketplace
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notify_expiring extends \core\task\scheduled_task {
    /** @var int Quantos dias antes do vencimento avisar. */
    const NOTICE_DAYS = 5;

    /** @var string Preferencia que guarda o ultimo aviso enviado. */
    const PREF_PREFIX = 'local_marketplace_notified_';

    /**
     * Nome exibido na lista de tarefas.
     *
     * @return string
     */
    public function get_name() {
        return get_string('tasknotifyexpiring', 'local_marketplace');
    }

    /**
     * Executa.
     *
     * @return void
     */
    public function execute() {
        global $DB;

        $now = time();
        $limit = $now + (self::NOTICE_DAYS * DAYSECS);

        // Só direitos com data marcada: vitalicio nao vence, e quem ja venceu
        // nao adianta avisar - viraria "seu acesso vai vencer" depois do fato.
        // norenew de fora: quem cancelou pediu para nao ser mais cobrado.
        // Insistir seria transformar o aviso em spam de quem ja disse nao.
        $records = $DB->get_records_select(
            'local_marketplace_entitlement',
            'status = :status AND norenew = 0 AND timeend > :now AND timeend <= :limit',
            ['status' => entitlement::STATUS_ACTIVE, 'now' => $now, 'limit' => $limit]
        );

        $sent = 0;
        foreach ($records as $record) {
            if ($this->notify($record)) {
                $sent++;
            }
        }

        mtrace("local_marketplace: {$sent} aviso(s) de vencimento enviado(s).");
    }

    /**
     * Manda um aviso, se ainda nao foi mandado para este vencimento.
     *
     * @param \stdClass $record
     * @return bool
     */
    protected function notify(\stdClass $record): bool {
        $user = \core_user::get_user((int) $record->userid, '*', IGNORE_MISSING);
        if (!$user || !empty($user->deleted) || !empty($user->suspended)) {
            return false;
        }

        // A chave inclui o vencimento. Assim uma renovacao, que muda o timeend,
        // volta a permitir aviso - e o cron rodando de hora em hora nao manda o
        // mesmo e-mail dezenas de vezes.
        $key = self::PREF_PREFIX . (int) $record->id;
        if (get_user_preferences($key, '', $user) === (string) (int) $record->timeend) {
            return false;
        }

        $offer = offer::get_record(['id' => (int) $record->offerid]);
        $company = company::get_record(['id' => (int) $record->companyid]);
        if (!$offer || !$company) {
            return false;
        }

        // Oferta fora de venda nao tem como ser renovada. Avisar levaria o
        // aluno a uma vitrine onde o botao nao existe.
        if ($offer->get('status') !== offer::STATUS_PUBLISHED) {
            return false;
        }

        // Assinatura que atingiu o limite de ciclos tambem nao renova.
        if (!$offer->accepts_cycle((int) $record->cycles)) {
            return false;
        }

        $renewurl = new \moodle_url('/local/marketplace/offers.php', [
            'company' => $company->get('shortname'),
            'highlight' => $offer->get('id'),
        ]);

        $a = (object) [
            'offer' => format_string($offer->get('name')),
            'company' => format_string($company->get('name')),
            'date' => userdate((int) $record->timeend, get_string('strftimedaydate')),
            'days' => max(1, (int) ceil(((int) $record->timeend - time()) / DAYSECS)),
            'url' => $renewurl->out(false),
        ];

        $message = new \core\message\message();
        $message->component = 'local_marketplace';
        $message->name = 'expiring';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = get_string('expiringsubject', 'local_marketplace', $a);
        $message->fullmessage = get_string('expiringbody', 'local_marketplace', $a);
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = get_string('expiringbodyhtml', 'local_marketplace', $a);
        $message->smallmessage = get_string('expiringsubject', 'local_marketplace', $a);
        $message->notification = 1;
        $message->contexturl = $renewurl->out(false);
        $message->contexturlname = get_string('renewnow', 'local_marketplace');

        if (message_send($message)) {
            set_user_preference($key, (string) (int) $record->timeend, $user);
            return true;
        }

        return false;
    }
}
