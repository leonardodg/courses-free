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
 * Cria o papel de vendedor na instalacao do plugin.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Cria o papel "Vendedor", atribuido no contexto da categoria da empresa.
 *
 * A regra de negocio "video tem que ficar fora da plataforma" NAO e uma
 * capability propria: e a AUSENCIA das capabilities que colocam arquivo no
 * moodledata. Negar o upload e preciso e nao depende de inspecionar mimetype:
 *
 *   repository/upload:view  -> enviar arquivo do computador
 *   repository/url:view     -> baixar de uma URL PARA DENTRO do moodledata,
 *                              que e o contorno obvio do anterior
 *
 * Sem essas duas, o vendedor so consegue referenciar video externo (mod_url,
 * ou embed no editor). Efeito colateral aceito: tambem nao envia imagem de
 * capa pelo seletor de arquivos - usa URL externa.
 *
 * Nao vale confiar so em maxbytes: limita tamanho, nao tipo, e um video curto
 * passaria.
 *
 * @return void
 */
function xmldb_local_marketplace_install() {
    global $DB;

    local_marketplace_require_category_themes();

    // As capabilities do db/access.php ainda NAO estao registradas neste ponto:
    // o Moodle roda esta funcao antes de processar o access.php. Sem isto,
    // assign_capability() aborta com "Capability ... was not found".
    update_capabilities('local_marketplace');

    // Reaproveita o papel se ele ja existir, mas NUNCA sai cedo daqui: uma
    // instalacao que falhou no meio deixa o papel criado e sem capability
    // nenhuma, e um "return" nesse ponto produziria um vendedor que nao pode
    // fazer nada - sem erro visivel.
    $roleid = $DB->get_field('role', 'id', ['shortname' => 'marketplaceseller']);
    if (!$roleid) {
        $roleid = create_role(
            get_string('sellerrole', 'local_marketplace'),
            'marketplaceseller',
            get_string('sellerroledesc', 'local_marketplace')
        );
    }

    // O papel so faz sentido numa categoria: e la que vive a empresa.
    set_role_contextlevels($roleid, [CONTEXT_COURSECAT]);

    $catcontext = context_system::instance();

    $allow = [
        'local/marketplace:managecompany',
        'local/marketplace:managepayment',
        'local/marketplace:publishcourse',
        'local/marketplace:viewreport',
        'moodle/course:create',
        'moodle/course:manageactivities',
        'moodle/course:update',
        'moodle/course:visibility',
        'moodle/course:viewhiddencourses',
        'moodle/course:enrolreview',
        'moodle/category:viewcourselist',
        'enrol/fee:config',
        'enrol/manual:enrol',
        'moodle/role:assign',
    ];
    foreach ($allow as $capability) {
        assign_capability($capability, CAP_ALLOW, $roleid, $catcontext->id, true);
    }

    // O portao do video externo.
    //
    // PROHIBIT, nao PREVENT. O vendedor tambem carrega o papel de usuario
    // autenticado, que PERMITE repository/upload:view - e no Moodle, quando
    // dois papeis se contradizem no mesmo contexto, o ALLOW vence o PREVENT.
    // So o PROHIBIT nao pode ser sobreposto por papel nenhum, em contexto
    // nenhum, que e a semantica correta para uma regra de negocio.
    $prohibit = [
        'repository/upload:view',
        'repository/url:view',
        'moodle/course:ignorefilesizelimits',
    ];
    foreach ($prohibit as $capability) {
        assign_capability($capability, CAP_PROHIBIT, $roleid, $catcontext->id, true);
    }
}

/**
 * Liga o tema por categoria, do qual o tema por empresa depende.
 *
 * Nao e preferencia de administrador: e requisito do produto. O vinculo
 * empresa->tema e feito gravando o tema na categoria dela, e com
 * allowcategorythemes desligado o Moodle simplesmente ignora esse campo.
 *
 * O modo de falhar e silencioso, que e o pior: o vendedor escolhe o tema, a
 * tela confirma, e nada muda. Nenhum erro, nenhum log. Por isso a dependencia
 * e garantida em codigo, e nao deixada para um clique que alguem precisa
 * lembrar de dar em cada ambiente novo.
 *
 * @return void
 */
function local_marketplace_require_category_themes() {
    if (empty(get_config('moodle', 'allowcategorythemes'))) {
        set_config('allowcategorythemes', 1);
        theme_reset_static_caches();
    }
}
