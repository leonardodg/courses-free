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
 * Unidade de venda.
 *
 * O curso deixa de ser o que se vende e passa a ser o que se libera. A mesma
 * aula pode ser vendida como pacote basico, como parte de um combo e dentro
 * de uma assinatura, cada um com preco e prazo proprios.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offer extends persistent {

    /** @var string Tabela. */
    const TABLE = 'local_marketplace_offer';

    /** @var string Um curso. */
    const TYPE_SINGLE = 'single';

    /** @var string Combo de cursos escolhidos. */
    const TYPE_BUNDLE = 'bundle';

    /** @var string Todo o catalogo da empresa, inclusive cursos futuros. */
    const TYPE_CATALOG = 'catalog';

    /** @var string Acesso sem prazo. */
    const ACCESS_LIFETIME = 'lifetime';

    /** @var string Acesso por accessdays dias. */
    const ACCESS_DAYS = 'days';

    /** @var string Assinatura: renova enquanto o aluno pagar. */
    const ACCESS_RECURRING = 'recurring';

    /** @var string Em edicao, invisivel para o aluno. */
    const STATUS_DRAFT = 'draft';

    /** @var string A venda. */
    const STATUS_PUBLISHED = 'published';

    /** @var string Fora de venda. NAO revoga quem ja comprou. */
    const STATUS_ARCHIVED = 'archived';

    /**
     * Define as propriedades.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'companyid' => ['type' => PARAM_INT],
            'name' => ['type' => PARAM_TEXT],
            'description' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'offertype' => [
                'type' => PARAM_ALPHA,
                'default' => self::TYPE_SINGLE,
                'choices' => [self::TYPE_SINGLE, self::TYPE_BUNDLE, self::TYPE_CATALOG],
            ],
            'price' => ['type' => PARAM_FLOAT, 'default' => 0],
            'currency' => ['type' => PARAM_ALPHA, 'default' => 'BRL'],
            'accessmode' => [
                'type' => PARAM_ALPHA,
                'default' => self::ACCESS_LIFETIME,
                'choices' => [self::ACCESS_LIFETIME, self::ACCESS_DAYS, self::ACCESS_RECURRING],
            ],
            'accessdays' => ['type' => PARAM_INT, 'default' => 0],
            'status' => [
                'type' => PARAM_ALPHA,
                'default' => self::STATUS_DRAFT,
                'choices' => [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_ARCHIVED],
            ],
            'sortorder' => ['type' => PARAM_INT, 'default' => 0],
        ];
    }

    /**
     * A oferta e gratuita?
     *
     * Importa porque o portao de venda so vale para oferta paga: uma empresa
     * sem meio de pagamento configurado pode publicar curso de graca.
     *
     * @return bool
     */
    public function is_free(): bool {
        return (float) $this->get('price') <= 0;
    }

    /**
     * Quando expira um direito comprado agora.
     *
     * @param int|null $from Momento da compra; agora se omitido.
     * @return int Timestamp, ou 0 para vitalicio.
     */
    public function calculate_expiry(?int $from = null): int {
        $from = $from ?? time();
        switch ($this->get('accessmode')) {
            case self::ACCESS_DAYS:
            case self::ACCESS_RECURRING:
                // Em recurring, accessdays e o intervalo de cobranca: o direito
                // vale ate a proxima fatura e e estendido a cada pagamento.
                $days = (int) $this->get('accessdays');
                return $days > 0 ? $from + ($days * DAYSECS) : 0;
            case self::ACCESS_LIFETIME:
            default:
                return 0;
        }
    }

    /**
     * Duracao do acesso em segundos, ou 0 para vitalicio.
     *
     * Separado de calculate_expiry() porque a renovacao precisa somar a
     * duracao ao vencimento ATUAL, nao calcular uma data a partir de agora -
     * senao renovar antes do vencimento encurtaria o que ja foi pago.
     *
     * @return int
     */
    public function get_access_duration(): int {
        if ($this->get('accessmode') === self::ACCESS_LIFETIME) {
            return 0;
        }
        return max(0, (int) $this->get('accessdays')) * DAYSECS;
    }

    /**
     * Cursos que esta oferta libera.
     *
     * Em catalog a lista NAO vem de offer_course: vem da categoria da empresa,
     * para que um curso publicado depois da compra ja entre para quem assina.
     *
     * @return int[] IDs de curso.
     */
    public function get_course_ids(): array {
        global $DB;

        if ($this->get('offertype') === self::TYPE_CATALOG) {
            $company = new company($this->get('companyid'));
            $categoryid = $company->get('categoryid');
            if (empty($categoryid)) {
                return [];
            }
            return $DB->get_fieldset_select('course', 'id', 'category = ?', [$categoryid]);
        }

        return $DB->get_fieldset_select(
            'local_marketplace_offer_course',
            'courseid',
            'offerid = ?',
            [$this->get('id')]
        );
    }

    /**
     * Vincula um curso a oferta.
     *
     * @param int $courseid
     * @return void
     */
    public function add_course(int $courseid): void {
        global $DB;

        if ($this->get('offertype') === self::TYPE_CATALOG) {
            throw new \coding_exception('Oferta de catalogo segue a categoria da empresa e nao lista cursos.');
        }
        $params = ['offerid' => $this->get('id'), 'courseid' => $courseid];
        if (!$DB->record_exists('local_marketplace_offer_course', $params)) {
            $DB->insert_record('local_marketplace_offer_course', (object) $params);
        }
    }

    /**
     * Ofertas publicadas de uma empresa.
     *
     * @param int $companyid
     * @return offer[]
     */
    public static function get_published(int $companyid): array {
        return self::get_records(
            ['companyid' => $companyid, 'status' => self::STATUS_PUBLISHED],
            'sortorder, name'
        );
    }
}
