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

namespace theme_ldg\output;

use moodle_url;
use theme_config;
use theme_ldg\util\settings;

/**
 * Renderer principal do tema LDG.
 *
 * Herda do Moove e sobrescreve exatamente os metodos em que ele faz
 * theme_config::load('moove') fixo. Sao seis:
 *
 *   standard_head_html()        Google Analytics e a fonte do Google Fonts
 *   get_theme_logo_url()        logo
 *   get_theme_logo_dark_url()   logo do modo escuro
 *   favicon()                   favicon
 *   body_attributes()           modo escuro
 *   render_darkmode_controls()  o botao de alternar modo escuro
 *
 * Esquecer um nao produz erro: o site fica no ar lendo o setting do PAI, que
 * nunca foi configurado. O sintoma e "sumiu o logo", e ninguem procura no
 * lugar certo. Ao atualizar o theme_moove, reconferir esta lista.
 *
 * @package    theme_ldg
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {
    /**
     * HTML do <head>, com o Google Analytics e a fonte deste tema.
     *
     * Nao chama parent::standard_head_html() de proposito. O do Moove le os
     * settings DELE, e o theme_moove/fontsite ja nasce com 'Roboto' gravado na
     * instalacao do pai - o site sairia baixando duas fontes do Google e
     * aplicando a errada. Pulamos um degrau e refazemos o trabalho com a config
     * certa; o degrau abaixo (Boost) continua sendo chamado, para nao perder o
     * que ele acrescenta.
     *
     * @return string
     */
    public function standard_head_html() {
        $output = \theme_boost\output\core_renderer::standard_head_html();

        $theme = theme_config::load('ldg');

        if (!empty($theme->settings->googleanalytics)) {
            $gacode = trim($theme->settings->googleanalytics);
            $output .= "<script async src='https://www.googletagmanager.com/gtag/js?id=" . s($gacode) . "'></script>
                        <script>
                            window.dataLayer = window.dataLayer || [];
                            function gtag() {
                                dataLayer.push(arguments);
                            }
                            gtag('js', new Date());
                            gtag('config', '" . s($gacode) . "');
                        </script>";
        }

        $sitefont = $theme->settings->fontsite ?? 'Inter';

        if ($sitefont !== 'Moodle') {
            // O preconnect economiza um RTT no handshake com o Google Fonts, e
            // essa fonte esta no caminho critico do primeiro render.
            $output .= '<link rel="preconnect" href="https://fonts.googleapis.com">
                        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                        <link href="https://fonts.googleapis.com/css2?family=' . urlencode($sitefont) .
                ':ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">';
        }

        return $output;
    }

    /**
     * Atributos da tag <body>.
     *
     * Refeito, e nao estendido, por dois motivos. O do Moove monta a string
     * inteira no return - nao ha como injetar nada sem reescrever a string. E
     * ele decide o modo escuro por *user preference*, que visitante anonimo nao
     * tem: a landing e a tela de login sairiam claras para todo o publico de
     * captacao, que e justamente quem o design dark foi feito para atender.
     *
     * @param string|array $additionalclasses Classes extras para o body.
     * @return string
     */
    public function body_attributes($additionalclasses = []) {
        if (!is_array($additionalclasses)) {
            $additionalclasses = explode(' ', $additionalclasses);
        }

        // A barra de acessibilidade e OPT-IN.
        //
        // Ela ja foi sempre visivel aqui, e o resultado era uma faixa fixa
        // acima da navbar em toda pagina, para todo mundo. Barra permanente
        // ocupa altura util e some com a hierarquia do topo; quem precisa do
        // recurso liga uma vez e ele fica.
        //
        // Visitante anonimo nao tem preferencia, e portanto nao ve a barra -
        // esta certo: nao ha onde guardar a escolha dele.
        if (!empty(get_user_preferences('theme_ldg-accessibilitybar', 0))) {
            $additionalclasses[] = 'hasaccessibilitybar';
        }

        // O valor vem do PARAM_ALPHANUMEXT declarado em theme_ldg_user_prefe-
        // rences(), entao ja chega limitado a letra, numero, hifen e sublinhado
        // - nao da para injetar atributo pelo nome da classe.
        foreach (['accessibilitystyles_fontsizeclass', 'accessibilitystyles_sitecolorclass'] as $preference) {
            $class = get_user_preferences($preference, '');

            if ($class) {
                $additionalclasses[] = $class;
            }
        }

        $settings = new settings();
        $colormode = $settings->color_mode();

        // Uma classe so, e o data-bs-theme abaixo.
        //
        // Havia tambem uma 'moove-darkmode' aqui, porque o darkmode.js do Moove
        // decidia o estado do botao olhando para ela. O colormode.js deste tema
        // le o data-bs-theme, que e o que o Bootstrap 5 de fato consulta -
        // entao a classe extra virou codigo morto.
        $additionalclasses[] = 'ldg-' . $colormode;

        return " id='{$this->body_id()}' class='{$this->body_css_classes($additionalclasses)}'" .
            " data-bs-theme='{$colormode}' ";
    }

    /**
     * URL da logo principal.
     *
     * Cai na logo embarcada em pix/ quando nao ha upload. Sem esse fallback o
     * tema nasce sem marca nenhuma: o Moove desce para a logo do site, que
     * tambem esta vazia, e o navbar renderiza um <img> com src vazio - um
     * icone de imagem quebrada ao lado do nome do site.
     *
     * @return string
     */
    public function get_theme_logo_url() {
        $theme = theme_config::load('ldg');

        $logo = $theme->setting_file_url('logo', 'logo');

        if (!empty($logo)) {
            return $logo;
        }

        return (new moodle_url('/theme/ldg/pix/logo.svg'))->out(false);
    }

    /**
     * URL da logo do modo escuro.
     *
     * O navbar e escuro nos DOIS modos, entao a logo embarcada e a mesma: um
     * wordmark branco. Sao dois arquivos, e nao um, para que trocar a logo do
     * modo escuro no futuro nao exija tocar em codigo.
     *
     * @return string
     */
    public function get_theme_logo_dark_url() {
        $theme = theme_config::load('ldg');

        $logo = $theme->setting_file_url('logodark', 'logodark');

        if (!empty($logo)) {
            return $logo;
        }

        return (new moodle_url('/theme/ldg/pix/logo_dark.svg'))->out(false);
    }

    /**
     * URL do favicon.
     *
     * @return moodle_url
     */
    public function favicon() {
        global $CFG;

        $theme = theme_config::load('ldg');

        $favicon = $theme->setting_file_url('favicon', 'favicon');

        if (!empty($favicon)) {
            // O setting_file_url devolve URL absoluta; o core espera relativa
            // ao wwwroot para nao quebrar quando o site responde por mais de um
            // dominio - que e exatamente o caso aqui, com dominio por vendedor.
            $urlreplace = preg_replace('|^https?://|i', '//', $CFG->wwwroot);
            $favicon = str_replace($urlreplace, '', $favicon);

            return new moodle_url($favicon);
        }

        return \theme_boost\output\core_renderer::favicon();
    }

    /**
     * Botao de alternar entre claro e escuro.
     *
     * Refeito porque o do Moove le theme_moove/enabledarkmode - o setting do
     * PAI, que nunca configuramos e vale 1 por padrao. Aqui quem manda e o
     * theme_ldg/enablecolormodetoggle.
     *
     * Continua so para usuario autenticado, como no Moove: a escolha e gravada
     * como preferencia de usuario, e visitante anonimo nao tem onde guardar.
     * Para ele vale o padrao do site.
     *
     * @return string
     */
    public function render_darkmode_controls() {
        if (!isloggedin() || isguestuser()) {
            return '';
        }

        $settings = new settings();

        if (!$settings->color_mode_toggle_enabled()) {
            return '';
        }

        return $this->render_from_template('theme_ldg/colormode', []);
    }

    /**
     * Ha logo para mostrar na navbar?
     *
     * Vinha do Moove por heranca. Sem estes tres metodos o navbar.mustache cai
     * no ramo {{^should_display_logo}} e imprime o NOME DO SITE em texto - foi
     * exatamente o que aconteceu ao sair do Moove, e o sintoma pareceu "a logo
     * sumiu" quando na verdade o metodo e que nao existia mais.
     *
     * @return bool
     */
    public function should_display_logo() {
        return $this->should_display_theme_logo() || parent::should_display_navbar_logo();
    }

    /**
     * O tema tem logo propria configurada?
     *
     * @return bool
     */
    public function should_display_theme_logo() {
        return !empty($this->get_theme_logo_url());
    }

    /**
     * A logo para fundo claro.
     *
     * A do tema vence a do site: quem subiu logo aqui quer a dela.
     *
     * @return string|bool
     */
    public function get_logo() {
        $logo = $this->get_theme_logo_url();

        if ($logo) {
            return $logo;
        }

        $logo = $this->get_logo_url();

        return $logo ? $logo->out(false) : false;
    }

    /**
     * A logo para fundo escuro.
     *
     * Cai na clara quando nao ha variante escura - melhor uma logo com contraste
     * ruim do que nenhuma.
     *
     * @return string|bool
     */
    public function get_logo_dark() {
        $logo = $this->get_theme_logo_dark_url();

        return $logo ?: $this->get_logo();
    }
}
