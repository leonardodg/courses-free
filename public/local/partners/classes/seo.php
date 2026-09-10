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

use local_marketplace\plan;
use local_partners\output\landing_page;
use moodle_url;

/**
 * Descoberta da landing por buscador, humano ou generativo.
 *
 * Resolve duas coisas que se sobrepoem mas nao sao a mesma:
 *
 *  - SEO classico: description, canonical, robots, Open Graph, Twitter e
 *    hreflang, para o buscador indexar e mostrar o resultado certo.
 *  - GEO, no sentido de Generative Engine Optimization: dados estruturados que
 *    um buscador com IA consegue citar. Uma resposta gerada precisa achar a
 *    pergunta, a resposta e o preco em forma de DADO, e nao so em paragrafo.
 *
 * E resolve tambem o GEO geografico, pela lista de hreflang e pelo pais da
 * organizacao.
 *
 * REGRA QUE VALE MAIS QUE QUALQUER OTIMIZACAO: nada aqui e inventado. Preco e
 * comissao saem do banco - os mesmos que a landing mostra e que o checkout
 * pratica -, o titulo sai do nome do site, e os campos de marca que ninguem
 * preencheu simplesmente NAO SAEM. Schema.org com numero que a plataforma nao
 * sustenta e alegacao falsa com carimbo de dado estruturado, e e pior que nao
 * ter schema nenhum: o buscador passa a repetir a mentira em nome do site.
 *
 * @package    local_partners
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class seo {
    /**
     * Dados da marca que o Moodle NAO tem como saber.
     *
     * Ficam em codigo, e nao em configuracao de administrador, por decisao do
     * dono do projeto. Cada campo vazio e OMITIDO do schema - preencher com
     * placeholder publicaria dado falso.
     *
     * Para preencher: razao social, o identificador fiscal, o endereco, o
     * telefone de contato e os perfis oficiais. Nada aqui e obrigatorio, e o
     * schema continua valido sem eles.
     *
     * @return array
     */
    protected static function brand(): array {
        return [
            // Razao social. Vem do contrato social, e nao do nome de fantasia:
            // e o nome que aparece na nota fiscal que o comprador recebe.
            'legalname' => 'LDG Tecnologia Ltda',

            // CNPJ. E dado publico da PESSOA JURIDICA - sai na nota e no
            // cadastro da Receita -, e por isso pode ser publicado.
            //
            // O CPF do socio NAO entra aqui, nem em lugar nenhum deste arquivo.
            // Identificador de pessoa natural em dado estruturado publico e
            // exposicao permanente, indexada e fora do nosso controle.
            'taxid' => '68.976.131/0001-42',

            // ENDERECO INTENCIONALMENTE VAZIO.
            //
            // O do contrato social e residencial - apartamento do socio. Endereco
            // de casa em schema.org publico e indexado por buscador e nao sai
            // mais de la. Preencher so quando houver endereco comercial, e o
            // conjunto TODO de uma vez: pela metade os validadores recusam.
            'street' => '',
            'city' => '',
            'region' => '',
            'postalcode' => '',
            'countrycode' => '',

            // Contato. Vazio ate existir telefone e e-mail de empresa; o
            // pessoal do socio nao serve para pagina publica.
            'telephone' => '',
            'email' => '',

            // Perfis oficiais - o sinal de identidade que o schema chama de
            // sameAs. O site profissional do responsavel entra porque e ele que
            // liga esta plataforma a uma pessoa que existe, e e o unico endereco
            // publico que temos hoje alem do proprio site.
            'sameas' => [
                'https://leodg.dev',
            ],
        ];
    }

    /**
     * Tudo que vai para o <head> da landing.
     *
     * @return string
     */
    public static function head_html(): string {
        $partes = array_merge(
            self::meta_tags(),
            self::alternate_links(),
            [self::structured_data()]
        );

        return implode("\n", array_filter($partes)) . "\n";
    }

    /**
     * O endereco canonico da landing.
     *
     * E a RAIZ quando a landing e a home, e a URL propria quando nao e. Duas
     * paginas com o mesmo conteudo e sem canonical fazem o buscador escolher
     * uma delas por conta propria, e normalmente a errada.
     *
     * @return string
     */
    public static function canonical_url(): string {
        $url = landing::replaces_frontpage()
            ? new moodle_url('/')
            : new moodle_url('/local/partners/index.php');

        return $url->out(false);
    }

    /**
     * Meta tags de indexacao e de compartilhamento.
     *
     * @return array
     */
    protected static function meta_tags(): array {
        global $SITE;

        $title = format_string($SITE->fullname);
        $descricao = get_string('metadescription', 'local_partners');
        $url = self::canonical_url();
        $imagem = (new moodle_url('/local/partners/pix/hero.jpg'))->out(false);

        return [
            '<meta name="description" content="' . s($descricao) . '">',
            // O max-image-preview:large e o que libera a imagem grande no
            // resultado; sem ele o buscador mostra miniatura ou nada.
            '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">',
            '<link rel="canonical" href="' . s($url) . '">',
            '<meta property="og:type" content="website">',
            '<meta property="og:site_name" content="' . s($title) . '">',
            '<meta property="og:title" content="' . s($title) . '">',
            '<meta property="og:description" content="' . s($descricao) . '">',
            '<meta property="og:url" content="' . s($url) . '">',
            '<meta property="og:image" content="' . s($imagem) . '">',
            '<meta property="og:locale" content="' . s(self::og_locale()) . '">',
            '<meta name="twitter:card" content="summary_large_image">',
            '<meta name="twitter:title" content="' . s($title) . '">',
            '<meta name="twitter:description" content="' . s($descricao) . '">',
            '<meta name="twitter:image" content="' . s($imagem) . '">',
        ];
    }

    /**
     * Os hreflang, um por idioma instalado, mais o x-default.
     *
     * E a peca geografica: quem procura em espanhol recebe a versao em
     * espanhol, em vez de a mesma pagina competir consigo mesma em tres
     * idiomas.
     *
     * @return array
     */
    protected static function alternate_links(): array {
        $canonica = self::canonical_url();
        $links = [];

        foreach (array_keys(get_string_manager()->get_list_of_translations()) as $lang) {
            $url = new moodle_url($canonica, ['lang' => $lang]);

            $links[] = '<link rel="alternate" hreflang="' . s(self::hreflang($lang))
                . '" href="' . s($url->out(false)) . '">';
        }

        // O x-default aponta para a pagina SEM parametro de idioma: e a versao
        // que o Moodle escolhe sozinho, e a resposta certa para quem o buscador
        // nao conseguiu classificar.
        $links[] = '<link rel="alternate" hreflang="x-default" href="' . s($canonica) . '">';

        return $links;
    }

    /**
     * O codigo de idioma do Moodle no formato que o hreflang espera.
     *
     * O Moodle usa 'pt_br'; o padrao BCP 47 quer 'pt-BR'. Publicar o formato
     * do Moodle faz o buscador ignorar a tag inteira, em silencio.
     *
     * @param string $lang
     * @return string
     */
    protected static function hreflang(string $lang): string {
        $partes = explode('_', str_replace('-', '_', $lang));
        $partes[0] = strtolower($partes[0]);

        if (isset($partes[1])) {
            $partes[1] = strtoupper($partes[1]);
        }

        return implode('-', array_slice($partes, 0, 2));
    }

    /**
     * O locale do Open Graph, que usa sublinhado.
     *
     * @return string
     */
    protected static function og_locale(): string {
        return str_replace('-', '_', self::hreflang(current_language()));
    }

    /**
     * O bloco JSON-LD.
     *
     * Um grafo unico, e nao varios blocos soltos: assim as pecas se referenciam
     * por @id, e o buscador entende que a organizacao da pagina e a mesma que
     * vende os planos.
     *
     * @return string
     */
    protected static function structured_data(): string {
        $grafo = array_values(array_filter([
            self::organization(),
            self::website(),
            self::webpage(),
            self::faq_page(),
            self::offer_catalog(),
        ]));

        $json = json_encode(
            ['@context' => 'https://schema.org', '@graph' => $grafo],
            // Sem UNESCAPED_SLASHES de proposito: com as barras escapadas, um
            // "</script>" que venha de dado nao consegue fechar o bloco.
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT
        );

        return '<script type="application/ld+json">' . $json . '</script>';
    }

    /**
     * A organizacao dona da plataforma.
     *
     * @return array
     */
    protected static function organization(): array {
        global $SITE;

        $marca = self::brand();
        $raiz = (new moodle_url('/'))->out(false);

        $org = [
            '@type' => 'Organization',
            '@id' => $raiz . '#organization',
            'name' => format_string($SITE->fullname),
            'url' => $raiz,
        ];

        if ($logo = self::logo_url()) {
            $org['logo'] = $logo;
        }

        foreach (['legalname' => 'legalName', 'taxid' => 'taxID', 'telephone' => 'telephone'] as $de => $para) {
            if (!empty($marca[$de])) {
                $org[$para] = $marca[$de];
            }
        }

        if (!empty($marca['email'])) {
            $org['email'] = $marca['email'];
        }

        if (!empty($marca['sameas'])) {
            $org['sameAs'] = array_values($marca['sameas']);
        }

        // Endereco so entra INTEIRO. Pela metade, os validadores recusam e o
        // resultado e pior que nao ter endereco nenhum.
        $endereco = array_filter([
            'streetAddress' => $marca['street'],
            'addressLocality' => $marca['city'],
            'addressRegion' => $marca['region'],
            'postalCode' => $marca['postalcode'],
            'addressCountry' => $marca['countrycode'],
        ]);

        if (count($endereco) === 5) {
            $org['address'] = ['@type' => 'PostalAddress'] + $endereco;
        }

        return $org;
    }

    /**
     * O site, para o buscador ligar a pagina a marca.
     *
     * @return array
     */
    protected static function website(): array {
        global $SITE;

        $raiz = (new moodle_url('/'))->out(false);

        return [
            '@type' => 'WebSite',
            '@id' => $raiz . '#website',
            'name' => format_string($SITE->fullname),
            'url' => $raiz,
            'publisher' => ['@id' => $raiz . '#organization'],
            'inLanguage' => self::hreflang(current_language()),
        ];
    }

    /**
     * A propria landing.
     *
     * @return array
     */
    protected static function webpage(): array {
        $raiz = (new moodle_url('/'))->out(false);
        $url = self::canonical_url();

        return [
            '@type' => 'WebPage',
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => get_string('landingtitle', 'local_partners'),
            'description' => get_string('metadescription', 'local_partners'),
            'isPartOf' => ['@id' => $raiz . '#website'],
            'about' => ['@id' => $raiz . '#organization'],
            'inLanguage' => self::hreflang(current_language()),
            'primaryImageOfPage' => (new moodle_url('/local/partners/pix/hero.jpg'))->out(false),
        ];
    }

    /**
     * As perguntas frequentes, em forma de dado.
     *
     * E a peca que mais rende num buscador generativo: a resposta ja vem
     * separada da pergunta, autocontida, e nao precisa ser extraida de um
     * paragrafo. Sao as MESMAS quatro que a pagina mostra - schema com pergunta
     * que nao esta na tela e recusado, e com razao.
     *
     * @return array
     */
    protected static function faq_page(): array {
        $itens = [];

        foreach ((new landing_page())->faq_items() as $item) {
            $itens[] = [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ];
        }

        return [
            '@type' => 'FAQPage',
            '@id' => self::canonical_url() . '#faq',
            'mainEntity' => $itens,
        ];
    }

    /**
     * Os planos, com o preco que o sistema pratica.
     *
     * Sai do banco, e nao de numero escrito aqui. Um preco desatualizado no
     * schema aparece no resultado de busca depois de ja ter mudado na pagina, e
     * quem clica chega achando que foi enganado.
     *
     * @return array|null Nulo quando nao ha plano publico.
     */
    protected static function offer_catalog(): ?array {
        $ofertas = [];

        foreach (plan::get_public_plans() as $plan) {
            $ofertas[] = [
                '@type' => 'Offer',
                'name' => format_string($plan->get('name')),
                'description' => format_string((string) $plan->get('description')),
                'price' => number_format((float) $plan->get('monthlyfee'), 2, '.', ''),
                'priceCurrency' => $plan->get('currency'),
                'url' => (new moodle_url('/local/partners/apply.php'))->out(false),
                'availability' => 'https://schema.org/InStock',
            ];
        }

        if (!$ofertas) {
            return null;
        }

        return [
            '@type' => 'Service',
            '@id' => self::canonical_url() . '#service',
            'name' => get_string('landingtitle', 'local_partners'),
            'description' => get_string('metadescription', 'local_partners'),
            'provider' => ['@id' => (new moodle_url('/'))->out(false) . '#organization'],
            'areaServed' => self::area_served(),
            'offers' => $ofertas,
        ];
    }

    /**
     * Onde a plataforma atende.
     *
     * Sai dos paises que as OFERTAS declaram, e nao de uma lista escrita aqui:
     * o pais decide a moeda e a conta que pode receber o split, entao dizer que
     * atendemos onde nao ha conta seria promessa vazia. Sem nenhum, cai no pais
     * do proprio site.
     *
     * @return array
     */
    protected static function area_served(): array {
        global $DB, $CFG;

        $paises = [];

        if ($DB->get_manager()->table_exists('local_marketplace_offer')) {
            $paises = $DB->get_fieldset_sql(
                'SELECT DISTINCT country FROM {local_marketplace_offer} WHERE country IS NOT NULL AND country <> :vazio',
                ['vazio' => '']
            );
        }

        if (!$paises && !empty($CFG->country)) {
            $paises = [$CFG->country];
        }

        return array_values(array_map(static function ($pais) {
            return ['@type' => 'Country', 'identifier' => $pais];
        }, $paises));
    }

    /**
     * O logo do site, quando houver um configurado.
     *
     * Sai do renderer do core, que resolve o logo do tema em uso - e nao de um
     * caminho fixo para a imagem de um tema especifico, que sumiria no dia em
     * que o site trocasse de tema. Sem logo configurado, o campo nao entra no
     * schema.
     *
     * @return string|null
     */
    protected static function logo_url(): ?string {
        global $OUTPUT;

        if (!method_exists($OUTPUT, 'get_logo_url')) {
            return null;
        }

        $url = $OUTPUT->get_logo_url();

        return $url ? $url->out(false) : null;
    }
}
