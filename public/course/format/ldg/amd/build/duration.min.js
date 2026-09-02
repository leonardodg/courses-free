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
 * Edicao da duracao das aulas, na propria lista.
 *
 * Grava ao sair do campo, e nao a cada tecla: quem digita "1200" nao quer
 * quatro gravacoes, e a ultima delas seria a unica certa.
 *
 * ESTE ARQUIVO NAO PASSA POR TRANSPILADOR. O requirejs.php serve o amd/src
 * quando nao existe .map, entao o codigo aqui precisa ser AMD de verdade.
 *
 * @module     format_ldg/duration
 * @author     LeoDG <callme@leodg.dev>
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    /**
     * Marca o campo por um instante, para a gravacao ter resposta visivel.
     *
     * Sem isso, digitar e sair nao produz sinal nenhum, e a duvida "sera que
     * salvou?" leva a pessoa a recarregar a pagina para conferir.
     *
     * @param {HTMLInputElement} input
     * @param {boolean} ok
     * @return {void}
     */
    var flash = function(input, ok) {
        var classe = ok ? 'is-valid' : 'is-invalid';

        input.classList.add(classe);

        window.setTimeout(function() {
            input.classList.remove(classe);
        }, 1500);
    };

    /**
     * Grava a duracao de uma aula.
     *
     * @param {HTMLInputElement} input
     * @return {void}
     */
    var save = function(input) {
        var cmid = parseInt(input.dataset.cmid, 10);
        var valor = parseInt(input.value, 10);

        if (isNaN(valor) || valor < 0) {
            valor = 0;
        }

        // Nada mudou desde a ultima gravacao - nao ha por que chamar o servidor.
        if (input.dataset.saved === String(valor)) {
            return;
        }

        Ajax.call([{
            methodname: 'format_ldg_set_duration',
            args: {cmid: cmid, duration: valor}
        }])[0].then(function(resposta) {
            input.dataset.saved = String(resposta.duration);
            input.value = resposta.duration ? resposta.duration : '';
            flash(input, true);

            return resposta;
        }).catch(function(erro) {
            flash(input, false);
            Notification.exception(erro);
        });
    };

    return {
        /**
         * Liga a edicao inline em todos os campos de duracao da pagina.
         *
         * @return {void}
         */
        init: function() {
            var campos = document.querySelectorAll('[data-region="ldg-duration"]');

            campos.forEach(function(input) {
                input.dataset.saved = String(parseInt(input.value, 10) || 0);

                input.addEventListener('change', function() {
                    save(input);
                });

                // Enter grava sem esperar o foco sair. Quem preenche uma lista
                // inteira usa o teclado, e nao o mouse.
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        save(input);
                    }
                });
            });
        }
    };
});
