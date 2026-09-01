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

namespace local_partners;

use local_partners\output\landing_page;
use moodle_url;

/**
 * Superficie publica da landing, consumida pelo tema.
 *
 * E o unico ponto de contato entre o local_partners e o theme_ldg, e existe
 * para que nenhum dos dois conheca as tabelas do outro. O tema testa
 * class_exists() e chama estes tres metodos; nao le config, nao consulta o
 * banco, e nao declara dependencia deste plugin - o moodle-plugin-ci instala o
 * tema sem ele, e um fatal ali reprovaria o CI do tema por causa de um plugin
 * que nao esta la.
 *
 * @package    local_partners
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class landing {
    /**
     * A landing deve ser exibida?
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return (bool) get_config('local_partners', 'enablelanding');
    }

    /**
     * A landing deve tomar o lugar da pagina inicial do site?
     *
     * Sao DUAS condicoes, e as duas precisam valer:
     *
     *   enablelanding    a landing existe como pagina
     *   frontpagemode    o administrador escolheu que ela e a home
     *
     * A segunda mora na tela de Configuracoes da pagina inicial, junto das
     * opcoes do Moodle, porque e ali que o administrador vai procurar - ver o
     * comentario em settings.php sobre por que ela nao pode entrar na lista
     * nativa de conteudo da home.
     *
     * Isto NAO decide se o visitante e anonimo nem se o dominio e de vendedor:
     * quem sabe disso e o layout do tema, que e quem tem a requisicao na mao.
     *
     * @return bool
     */
    public static function replaces_frontpage(): bool {
        return self::is_enabled() && get_config('local_partners', 'frontpagemode') === 'landing';
    }

    /**
     * O miolo da landing, ja renderizado.
     *
     * Devolve so o conteudo. O cabecalho, o rodape e o resto do chrome sao do
     * tema - quem manda no chrome e quem desenha o site.
     *
     * @param \renderer_base $output O renderer da pagina.
     * @return string
     */
    public static function render(\renderer_base $output): string {
        return $output->render_from_template(
            'local_partners/landing',
            (new landing_page())->export_for_template($output)
        );
    }

    /**
     * Meta tags de compartilhamento da landing.
     *
     * Tudo passa por s() e format_string(): sao textos de configuracao indo
     * para dentro de atributo HTML.
     *
     * @return string
     */
    public static function head_html(): string {
        global $SITE;

        $title = format_string($SITE->fullname);
        $description = get_string('metadescription', 'local_partners');
        $url = (new moodle_url('/'))->out(false);
        $image = (new moodle_url('/local/partners/pix/hero.jpg'))->out(false);

        $tags = [
            '<meta name="description" content="' . s($description) . '">',
            '<link rel="canonical" href="' . s($url) . '">',
            '<meta property="og:type" content="website">',
            '<meta property="og:title" content="' . s($title) . '">',
            '<meta property="og:description" content="' . s($description) . '">',
            '<meta property="og:url" content="' . s($url) . '">',
            '<meta property="og:image" content="' . s($image) . '">',
            '<meta name="twitter:card" content="summary_large_image">',
        ];

        return implode("\n", $tags) . "\n";
    }
}
