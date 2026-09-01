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

namespace theme_ldg\util;

use theme_config;

/**
 * Leitor das configuracoes deste tema.
 *
 * Existe porque a classe equivalente do Moove faz theme_config::load('moove')
 * fixo no construtor. Herdar dela faria o filho ler os settings do PAI - que
 * nunca configuramos - e o sintoma seria "o site nao tem logo", nao "o site tem
 * a logo errada". Dificil de notar e pior ainda de diagnosticar.
 *
 * So implementa footer(): a frontpage do Moove (slider, marketing boxes,
 * numeros, FAQ) e substituida pela landing de captacao, entao os metodos dela
 * seriam codigo morto.
 *
 * @package    theme_ldg
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings {
    /**
     * @var \theme_config $theme A configuracao deste tema.
     */
    protected $theme;

    /**
     * @var array $files Settings que sao arquivo, e nao valor.
     */
    protected $files = ['logo', 'logodark', 'favicon', 'loginbg'];

    /** @var array Campos de contato do rodape. */
    protected const CONTACT = ['website', 'mobile', 'mail'];

    /** @var array Redes sociais do rodape. */
    protected const SOCIAL = [
        'facebook', 'instagram', 'linkedin', 'youtube', 'whatsapp',
        'telegram', 'tiktok', 'twitter', 'pinterest',
    ];

    /**
     * Construtor.
     */
    public function __construct() {
        $this->theme = theme_config::load('ldg');
    }

    /**
     * Devolve um setting do tema pelo nome.
     *
     * @param string $name Nome do setting.
     * @return false|string|null
     */
    public function __get(string $name) {
        if (in_array($name, $this->files, true)) {
            return $this->theme->setting_file_url($name, $name);
        }

        if (empty($this->theme->settings->$name)) {
            return false;
        }

        return $this->theme->settings->$name;
    }

    /**
     * Contexto do rodape, no formato que o footer.mustache do Moove consome.
     *
     * As chaves e os nomes dos settings sao os mesmos do pai de proposito: o
     * template e dele, e renomear qualquer coisa aqui exigiria fork do template
     * so para trocar um nome.
     *
     * @return array
     */
    public function footer() {
        global $CFG;

        $templatecontext = [
            'hasfootercontact' => false,
            'hasfootersocial' => false,
        ];

        foreach (array_merge(self::CONTACT, self::SOCIAL) as $setting) {
            $value = $this->$setting;
            $templatecontext[$setting] = $value;

            if (empty($value)) {
                continue;
            }

            if (in_array($setting, self::CONTACT, true)) {
                $templatecontext['hasfootercontact'] = true;
            } else {
                $templatecontext['hasfootersocial'] = true;
            }
        }

        $templatecontext['enablemobilewebservice'] = $CFG->enablemobilewebservice;

        if ($CFG->enablemobilewebservice) {
            $iosappid = get_config('tool_mobile', 'iosappid');
            if (!empty($iosappid)) {
                $templatecontext['iosappid'] = $iosappid;
            }

            $androidappid = get_config('tool_mobile', 'androidappid');
            if (!empty($androidappid)) {
                $templatecontext['androidappid'] = $androidappid;
            }

            $setuplink = get_config('tool_mobile', 'setuplink');
            if (!empty($setuplink)) {
                $templatecontext['mobilesetuplink'] = $setuplink;
            }
        }

        return $templatecontext;
    }

    /**
     * Modo de cor efetivo desta requisicao: 'dark' ou 'light'.
     *
     * Tres estados, e nao dois. A preferencia do usuario pode estar AUSENTE, e
     * ausente nao e o mesmo que "claro":
     *
     *   ausente  -> o padrao do site (theme_ldg/defaultcolormode)
     *   '1'      -> escuro, o usuario escolheu
     *   ''/'0'   -> claro, o usuario escolheu
     *
     * Sem o estado "ausente" o visitante anonimo - que nunca tem preferencia -
     * cairia sempre no claro, e a landing e a tela de login, que sao as duas
     * paginas anonimas, sairiam claras para todo o publico de captacao.
     *
     * A preferencia e a 'dark-mode-on' do Moove, de proposito: o webservice e o
     * AMD que a gravam sao dele, e duplicar isso aqui seria manter um segundo
     * caminho para o mesmo dado.
     *
     * @return string
     */
    public function color_mode(): string {
        $preference = get_user_preferences('dark-mode-on', null);

        if ($preference === null) {
            return $this->default_color_mode();
        }

        return empty($preference) ? 'light' : 'dark';
    }

    /**
     * Modo de cor padrao do site, para quem ainda nao escolheu.
     *
     * @return string
     */
    public function default_color_mode(): string {
        $value = $this->theme->settings->defaultcolormode ?? 'dark';

        return $value === 'light' ? 'light' : 'dark';
    }

    /**
     * O botao de alternar entre claro e escuro deve aparecer?
     *
     * @return bool
     */
    public function color_mode_toggle_enabled(): bool {
        return (bool) ($this->theme->settings->enablecolormodetoggle ?? 1);
    }

    /**
     * O course index deve ser montado?
     *
     * Metodo, e nao acesso direto pelo __get: a magica devolve false para
     * setting ausente, e este tema nao declara 'enablecourseindex'. Lido pelo
     * __get, um site sem o setting salvo ficaria SEM course index nenhum, o que
     * e o oposto do padrao do Moodle.
     *
     * @return bool
     */
    public function course_index_enabled(): bool {
        return (bool) ($this->theme->settings->enablecourseindex ?? 1);
    }
}
