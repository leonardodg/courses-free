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
 * Matricula guiada pelos direitos de acesso.
 *
 * @package    enrol_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use local_marketplace\entitlement;

/**
 * Plugin de matricula do marketplace.
 *
 * Nao tem tela de inscricao: ninguem se matricula aqui por vontade propria.
 * A matricula e consequencia de um direito de acesso, e some quando o direito
 * some. Por isso o plugin e um SINCRONIZADOR, nao um formulario.
 *
 * A instancia no curso e criada sob demanda: um curso so ganha a sua quando o
 * primeiro aluno adquire direito a ele.
 */
class enrol_marketplace_plugin extends enrol_plugin {
    /**
     * O admin nao adiciona instancia manualmente: quem cria e o sincronizador.
     *
     * @param int $courseid
     * @return bool
     */
    public function can_add_instance($courseid) {
        return false;
    }

    /**
     * Desmatricular na mao quebraria a correspondencia com o direito: o aluno
     * continuaria pagando e sem acesso, e o proximo sync o traria de volta.
     *
     * @param stdClass $instance
     * @return bool
     */
    public function allow_unenrol(stdClass $instance) {
        return false;
    }

    /**
     * Mesma razao do allow_unenrol.
     *
     * @param stdClass $instance
     * @return bool
     */
    public function allow_manage(stdClass $instance) {
        return false;
    }

    /**
     * O plugin roda sozinho pelo cron.
     *
     * @return bool
     */
    public function roles_protected() {
        return true;
    }

    /**
     * Nome exibido da instancia.
     *
     * @param stdClass $instance
     * @return string
     */
    public function get_instance_name($instance) {
        return get_string('pluginname', 'enrol_marketplace');
    }

    /**
     * Devolve a instancia do curso, criando se ainda nao existir.
     *
     * @param int $courseid
     * @return stdClass
     */
    public function get_or_create_instance(int $courseid): stdClass {
        global $DB;

        $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'marketplace']);
        if ($instance) {
            return $instance;
        }

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $instanceid = $this->add_instance($course, [
            'status' => ENROL_INSTANCE_ENABLED,
            'roleid' => $this->get_config('roleid', 0) ?: $this->get_student_roleid(),
        ]);

        return $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
    }

    /**
     * ID do papel de estudante, usado como padrao da matricula.
     *
     * @return int
     */
    protected function get_student_roleid(): int {
        global $DB;

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        return $roleid ?: 0;
    }

    /**
     * Sincroniza as matriculas de um usuario com os direitos dele.
     *
     * Compara o que o usuario DEVE ter com o que ele TEM, e ajusta. Trabalhar
     * por diferenca, e nao por evento, e o que torna a operacao idempotente:
     * pode rodar mil vezes, ou depois de uma falha no meio, e o resultado e o
     * mesmo.
     *
     * Matricula existente nao e apagada, e SUSPENSA. Apagar levaria junto
     * notas e progresso - e quem perdeu acesso por vencimento costuma
     * renovar.
     *
     * @param int $userid
     * @return array [matriculados, suspensos, reativados]
     */
    public function sync_user(int $userid): array {
        global $DB;

        $shouldhave = [];
        foreach (entitlement::get_active_for_user($userid) as $ent) {
            $offer = new \local_marketplace\offer($ent->get('offerid'));
            foreach ($offer->get_course_ids() as $courseid) {
                $shouldhave[$courseid] = true;
            }
        }

        $sql = "SELECT e.courseid, ue.status, e.id AS enrolid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :userid AND e.enrol = 'marketplace'";
        $current = $DB->get_records_sql($sql, ['userid' => $userid]);

        $enrolled = $suspended = $reactivated = 0;

        foreach (array_keys($shouldhave) as $courseid) {
            if (!isset($current[$courseid])) {
                $instance = $this->get_or_create_instance($courseid);
                $this->enrol_user($instance, $userid, null, 0, 0, ENROL_USER_ACTIVE);
                $enrolled++;
            } else if ((int) $current[$courseid]->status !== ENROL_USER_ACTIVE) {
                $instance = $DB->get_record('enrol', ['id' => $current[$courseid]->enrolid], '*', MUST_EXIST);
                $this->update_user_enrol($instance, $userid, ENROL_USER_ACTIVE);
                $reactivated++;
            }
        }

        foreach ($current as $courseid => $record) {
            if (!isset($shouldhave[$courseid]) && (int) $record->status === ENROL_USER_ACTIVE) {
                $instance = $DB->get_record('enrol', ['id' => $record->enrolid], '*', MUST_EXIST);
                $this->update_user_enrol($instance, $userid, ENROL_USER_SUSPENDED);
                $suspended++;
            }
        }

        return [$enrolled, $suspended, $reactivated];
    }
}
