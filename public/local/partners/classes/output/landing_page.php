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

namespace local_partners\output;

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use local_marketplace\plan;
use local_marketplace\plan_tier;
use moodle_url;

/**
 * A pagina de captacao de empresas parceiras.
 *
 * A comparacao de planos NAO e escrita no template: vem de
 * local_marketplace_plan, a mesma tabela que resolve a comissao de verdade.
 * Uma tabela de precos escrita a mao na landing envelheceria no primeiro
 * reajuste, e o visitante veria um numero que o sistema nao pratica.
 *
 * @package    local_partners
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class landing_page implements renderable, templatable {
    /**
     * Contexto para o template.
     *
     * @param renderer_base $output O renderer.
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'applyurl' => (new moodle_url('/local/partners/apply.php'))->out(false),
            'heroimage' => (new moodle_url('/local/partners/pix/hero.jpg'))->out(false),
            'plans' => $this->plans(),
            'hasplans' => !empty($this->plans()),
            'steps' => $this->steps(),
            'faq' => $this->faq(),
        ];
    }

    /**
     * Planos publicos, prontos para a comparacao.
     *
     * @return array
     */
    protected function plans(): array {
        $out = [];

        foreach (plan::get_public_plans() as $plan) {
            $fee = (float) $plan->get('monthlyfee');

            $out[] = [
                'name' => format_string($plan->get('name')),
                'description' => format_string((string) $plan->get('description')),
                // Mensalidade zero vira palavra, e nao "R$ 0,00": e o argumento
                // central do Starter, e um zero formatado nao o comunica.
                'isfree' => $fee <= 0,
                'monthlyfee' => self::money($fee, $plan->get('currency')),
                'commissionpct' => format_float((float) $plan->get('commissionpct'), 2),
                'isbyos' => $plan->get('hostingmodel') === plan::HOSTING_BYOS,
                'hosting' => $plan->get('hostingmodel') === plan::HOSTING_BYOS
                    ? get_string('planhostingbyos', 'local_partners')
                    : get_string('planhostingnative', 'local_partners'),
                'tiers' => $this->tiers($plan),
                'hastiers' => !empty($this->tiers($plan)),
            ];
        }

        return $out;
    }

    /**
     * Faixas de resolucao de um plano, em texto.
     *
     * @param plan $plan
     * @return array
     */
    protected function tiers(plan $plan): array {
        $out = [];
        $previous = null;

        /** @var plan_tier $tier */
        foreach ($plan->get_tiers() as $tier) {
            $max = $tier->get('maxprice');
            $currency = $plan->get('currency');

            if ($max === null) {
                // A faixa final e descrita pelo teto da ANTERIOR: "acima de X".
                // Dizer "sem limite de preco" nao ajudaria quem esta comparando.
                $label = $previous === null
                    ? get_string('tierany', 'local_partners')
                    : get_string('tierabove', 'local_partners', self::money($previous, $currency));
            } else {
                $label = get_string('tierupto', 'local_partners', self::money((float) $max, $currency));
                $previous = (float) $max;
            }

            $out[] = [
                'label' => $label,
                'resolution' => $tier->get('maxresolution'),
            ];
        }

        return $out;
    }

    /**
     * Como funciona, em passos.
     *
     * @return array
     */
    protected function steps(): array {
        $steps = [];

        for ($i = 1; $i <= 4; $i++) {
            $steps[] = [
                'number' => $i,
                'title' => get_string('step' . $i . 'title', 'local_partners'),
                'text' => get_string('step' . $i . 'text', 'local_partners'),
            ];
        }

        return $steps;
    }

    /**
     * Perguntas frequentes.
     *
     * @return array
     */
    protected function faq(): array {
        $faq = [];

        for ($i = 1; $i <= 4; $i++) {
            $faq[] = [
                'id' => 'ldgfaq' . $i,
                'question' => get_string('faq' . $i . 'question', 'local_partners'),
                'answer' => get_string('faq' . $i . 'answer', 'local_partners'),
            ];
        }

        return $faq;
    }

    /**
     * Formata um valor com a moeda do plano.
     *
     * @param float $amount
     * @param string $currency Codigo ISO da moeda.
     * @return string
     */
    protected static function money(float $amount, string $currency): string {
        // O core sabe formatar moeda pelo idioma da pagina - e o mesmo helper
        // que o checkout usa, entao o visitante ve o valor no formato que vai
        // reencontrar na hora de pagar.
        return \core_payment\helper::get_cost_as_string($amount, $currency);
    }
}
