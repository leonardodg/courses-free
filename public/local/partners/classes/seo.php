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
     * A landing: a raiz quando ela e a home, ou a URL propria do plugin.
     */
    public const SURFACE_LANDING = 'landing';

    /**
     * O formulario publico de candidatura.
     */
    public const SURFACE_APPLY = 'apply';

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

            // Quem assina o trabalho. Aparece no credito do rodape.
            'creatorname' => 'LeoDG',
            'creatorurl' => 'https://leodg.dev',

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
     * A razao social, quando declarada.
     *
     * Publica porque o rodape tambem a mostra, e as duas superficies precisam
     * dizer o mesmo nome. Vazia quando ninguem preencheu - e ai o rodape usa o
     * nome do site, em vez de inventar uma empresa.
     *
     * @return string
     */
    public static function legal_name(): string {
        return trim((string) self::brand()['legalname']);
    }

    /**
     * O identificador fiscal, quando declarado.
     *
     * Publico porque o rodape mostra, e no Brasil rodape de plataforma que cobra
     * costuma mostrar - e o que liga o site a uma pessoa juridica de verdade.
     *
     * @return string
     */
    public static function tax_id(): string {
        return trim((string) self::brand()['taxid']);
    }

    /**
     * Quem assina o trabalho, para o credito do rodape.
     *
     * @return array{name: string, url: string}
     */
    public static function creator(): array {
        $marca = self::brand();

        return [
            'name' => trim((string) $marca['creatorname']),
            'url' => trim((string) $marca['creatorurl']),
        ];
    }

    /**
     * O titulo da aba, com a palavra-chave que interessa.
     *
     * NAO e o mesmo texto do H1. O H1 fala com quem ja esta na pagina; o title
     * fala com quem esta lendo uma lista de resultados e ainda nao clicou, e
     * precisa dizer o que a pagina resolve em menos de 60 caracteres.
     *
     * A MARCA ENTRA AQUI, e nao pelo nome do site. O core anexaria o nome curto
     * do site (ver moodle_page::set_title()), que e um valor unico no banco: o
     * titulo em portugues sairia com o sufixo em ingles. Vindo da string de
     * idioma, a marca acompanha o idioma - e por isso quem chama precisa passar
     * false no segundo parametro do set_title(), senao o nome do site vem por
     * cima e o titulo fica com duas marcas.
     *
     * @param string $superficie
     * @return string
     */
    public static function page_title(string $superficie = self::SURFACE_LANDING): string {
        $titulo = $superficie === self::SURFACE_APPLY
            ? get_string('applytitle', 'local_partners')
            : get_string('seotitle', 'local_partners');

        $marca = trim(get_string('brandname', 'local_partners'));

        if ($marca === '') {
            return $titulo;
        }

        return $titulo . ' | ' . $marca;
    }

    /**
     * O nome do site, em texto puro, para dentro do JSON-LD.
     *
     * O format_string() escapa para HTML por padrao, e um "&" no nome do site
     * vira "&amp;". Em atributo de meta tag isso esta certo - o navegador
     * decodifica -, mas dentro de <script type="application/ld+json"> NAO:
     * ali o conteudo e texto cru, e o parser le a entidade literalmente. O nome
     * da empresa chega corrompido ao buscador, e nenhum validador reclama.
     *
     * @return string
     */
    protected static function site_name(): string {
        global $SITE;

        return format_string($SITE->fullname, true, ['escape' => false]);
    }

    /**
     * A descricao desta superficie.
     *
     * @param string $superficie
     * @return string
     */
    protected static function meta_description(string $superficie): string {
        return $superficie === self::SURFACE_APPLY
            ? get_string('applymetadescription', 'local_partners')
            : get_string('metadescription', 'local_partners');
    }

    /**
     * O site permite que os buscadores indexem?
     *
     * O core ja emite `<meta name="robots" content="noindex">` quando o
     * administrador escolhe "em lugar nenhum" ($CFG->allowindexing == 2). Se
     * emitissemos "index, follow" ao lado, o buscador ficaria com as duas -
     * e em caso de conflito vale a MAIS RESTRITIVA. A nossa tag seria
     * silenciosamente derrotada, e ninguem descobriria olhando a pagina.
     *
     * @return bool
     */
    protected static function indexing_allowed(): bool {
        global $CFG;

        return (int) ($CFG->allowindexing ?? 0) !== 2;
    }

    /**
     * Tudo que vai para o <head> da landing.
     *
     * @return string
     */
    public static function head_html(string $superficie = self::SURFACE_LANDING): string {
        $partes = array_merge(
            self::meta_tags($superficie),
            self::alternate_links($superficie),
            [self::structured_data($superficie)]
        );

        return implode("\n", array_filter($partes)) . "\n"
            . self::verification_html()
            . self::analytics_html();
    }

    /**
     * O endereco da superficie, SEM parametro de idioma.
     *
     * E a raiz quando a landing e a home, e a URL propria quando nao e. Duas
     * paginas com o mesmo conteudo e sem canonical fazem o buscador escolher
     * uma delas por conta propria, e normalmente a errada.
     *
     * Esta e a URL que serve de x-default: a versao que o Moodle escolhe
     * sozinho, e a resposta certa para quem o buscador nao conseguiu
     * classificar por idioma.
     *
     * @param string $superficie
     * @return string
     */
    public static function base_url(string $superficie = self::SURFACE_LANDING): string {
        if ($superficie === self::SURFACE_APPLY) {
            return (new moodle_url('/local/partners/apply.php'))->out(false);
        }

        $url = landing::replaces_frontpage()
            ? new moodle_url('/')
            : new moodle_url('/local/partners/index.php');

        return $url->out(false);
    }

    /**
     * O endereco canonico DESTA versao da pagina.
     *
     * Cada versao de idioma aponta para ELA MESMA, e nao para a versao sem
     * parametro. Nao e preciosismo: canonical e hreflang se contradizem quando
     * /?lang=pt_br declara canonica para /, e a canonica vence. O buscador
     * consolida as versoes numa so e DESCARTA o cluster inteiro - a versao em
     * portugues nunca chega a ser indexada, e o Search Console reporta isso
     * como "pagina alternativa com tag canonica adequada", que parece um aviso
     * benigno e nao e.
     *
     * O idioma sai da requisicao quando nao vem por parametro. O PARAM_LANG
     * devolve vazio para idioma que nao esta instalado, entao um ?lang=xyz nao
     * vira canonica.
     *
     * @param string $superficie
     * @param string|null $lang Nulo le da requisicao.
     * @return string
     */
    public static function canonical_url(
        string $superficie = self::SURFACE_LANDING,
        ?string $lang = null
    ): string {
        $base = self::base_url($superficie);
        $lang ??= optional_param('lang', '', PARAM_LANG);

        if ($lang === '') {
            return $base;
        }

        return (new moodle_url($base, ['lang' => $lang]))->out(false);
    }

    /**
     * Meta tags de indexacao e de compartilhamento.
     *
     * @param string $superficie
     * @return array
     */
    protected static function meta_tags(string $superficie = self::SURFACE_LANDING): array {
        // O nome do SITE fica no og:site_name, que e o campo dele. O og:title e
        // a manchete que o WhatsApp, o LinkedIn e o assistente de IA mostram:
        // repetir a marca ali gasta a manchete sem dizer o que a pagina resolve.
        //
        // O site_name() vem SEM escape, e quem escapa e o s() aqui embaixo, uma
        // vez so. Escapar nos dois lugares fazia um "&" no nome do site virar
        // "&amp;amp;" no atributo, e o compartilhamento mostrava a entidade.
        $site = self::site_name();
        $title = self::page_title($superficie);
        $descricao = self::meta_description($superficie);
        $url = self::canonical_url($superficie);
        $imagem = (new moodle_url('/local/partners/pix/hero.jpg'))->out(false);

        $tags = [
            '<meta name="description" content="' . s($descricao) . '">',
            '<link rel="canonical" href="' . s($url) . '">',
        ];

        // A foto do hero e o FUNDO de uma secao, e imagem de fundo o preload
        // scanner do navegador NAO enxerga: ela so comeca a baixar depois do
        // CSSOM, e e a candidata a maior elemento visivel da pagina. O preload
        // devolve a descoberta para o inicio do documento sem tocar no layout -
        // o contraste daquele bloco foi medido no pixel, e virar <img> pediria
        // remedir tudo por um ganho que o preload ja da.
        //
        // So na landing: a candidatura nao mostra o hero, e preload de recurso
        // que a pagina nao usa e aviso no console e banda jogada fora.
        if ($superficie === self::SURFACE_LANDING) {
            $tags[] = '<link rel="preload" as="image" href="' . s($imagem) . '" fetchpriority="high">';
        }

        $tags = array_merge($tags, [
            '<meta property="og:type" content="website">',
            '<meta property="og:site_name" content="' . s($site) . '">',
            '<meta property="og:title" content="' . s($title) . '">',
            '<meta property="og:description" content="' . s($descricao) . '">',
            '<meta property="og:url" content="' . s($url) . '">',
            '<meta property="og:image" content="' . s($imagem) . '">',
            '<meta property="og:locale" content="' . s(self::og_locale()) . '">',
        ]);

        // Os outros idiomas em que a MESMA pagina existe. E o par do hreflang
        // no vocabulario do Open Graph: sem eles, quem compartilha a versao em
        // portugues nao informa que existe uma em espanhol.
        foreach (self::alternate_locales() as $locale) {
            $tags[] = '<meta property="og:locale:alternate" content="' . s($locale) . '">';
        }

        $tags = array_merge($tags, [
            '<meta name="twitter:card" content="summary_large_image">',
            '<meta name="twitter:title" content="' . s($title) . '">',
            '<meta name="twitter:description" content="' . s($descricao) . '">',
            '<meta name="twitter:image" content="' . s($imagem) . '">',
        ]);

        // So pedimos indexacao quando o site permite. Ver indexing_allowed().
        if (self::indexing_allowed()) {
            // O max-image-preview:large e o que libera a imagem grande no
            // resultado; sem ele o buscador mostra miniatura ou nada.
            array_splice($tags, 1, 0, [
                '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">',
            ]);
        }

        return $tags;
    }

    /**
     * A meta de verificacao do Google Search Console.
     *
     * O valor sai da configuracao, e nao do codigo: ele e proprio de cada
     * propriedade cadastrada, e trocar de propriedade nao pode exigir deploy.
     * Vazio, a tag nao sai - meta de verificacao com valor errado e pior que
     * ausente, porque o Search Console reporta "falhou" em vez de "faltando".
     *
     * @return string
     */
    public static function verification_html(): string {
        $token = trim((string) get_config('local_partners', 'searchconsoletoken'));

        if ($token === '') {
            return '';
        }

        return '<meta name="google-site-verification" content="' . s($token) . '">' . "\n";
    }

    /**
     * O trecho do Google Analytics.
     *
     * SO NAS PAGINAS PUBLICAS de captacao, e nao no site inteiro. Quem entra no
     * LMS e aluno em atividade de estudo, e mandar o comportamento dele para um
     * terceiro e outra decisao, com outro peso - a de medir visitante que ainda
     * nao e cliente e bem menor.
     *
     * ISTO NAO DISPENSA AVISO DE COOKIES. O gtag grava cookie de primeira parte
     * e envia dados a um terceiro; sob a LGPD e o GDPR isso pede base legal e,
     * na pratica, banner de consentimento. O interruptor aqui e tecnico, nao
     * juridico.
     *
     * @return string
     */
    public static function analytics_html(): string {
        $id = trim((string) get_config('local_partners', 'analyticsid'));

        // Formato do GA4 (G-XXXXXXX) ou do Ads/UA legado. Um id malformado
        // carrega o script de um terceiro sem medir nada.
        if ($id === '' || !preg_match('/^(G|GT|AW|UA)-[A-Z0-9-]+$/i', $id)) {
            return '';
        }

        $seguro = s($id);

        return '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $seguro . '"></script>' . "\n"
            . '<script>window.dataLayer=window.dataLayer||[];'
            . 'function gtag(){dataLayer.push(arguments);}'
            . 'gtag("js", new Date());'
            . 'gtag("config", "' . $seguro . '", {"anonymize_ip": true});'
            . '</script>' . "\n";
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
    protected static function alternate_links(string $superficie = self::SURFACE_LANDING): array {
        $links = [];

        // Cada alternate e a CANONICA daquele idioma, e nao uma URL montada por
        // fora: se as duas divergirem em um caractere, o buscador procura o link
        // de retorno no endereco anunciado, nao acha, e descarta o cluster.
        foreach (array_keys(get_string_manager()->get_list_of_translations()) as $lang) {
            $links[] = '<link rel="alternate" hreflang="' . s(self::hreflang($lang))
                . '" href="' . s(self::canonical_url($superficie, $lang)) . '">';
        }

        // O x-default aponta para a pagina SEM parametro de idioma: e a versao
        // que o Moodle escolhe sozinho, e a resposta certa para quem o buscador
        // nao conseguiu classificar.
        $links[] = '<link rel="alternate" hreflang="x-default" href="'
            . s(self::base_url($superficie)) . '">';

        return $links;
    }

    /**
     * As entradas do sitemap, ja com o cluster de idioma de cada uma.
     *
     * UMA ENTRADA POR IDIOMA, e nao uma entrada com os alternates dentro. O
     * formato antigo parecia economico e estava errado: o protocolo exige que
     * cada URL do cluster tenha a propria entrada, repetindo o conjunto
     * completo de alternates - inclusive a que aponta para ela mesma. Sem isso
     * nao existe link de retorno, e o cluster e descartado inteiro.
     *
     * Vive aqui, e nao no sitemap.php, porque assim tem teste: script de saida
     * direta so se prova abrindo o navegador.
     *
     * @return array
     */
    public static function sitemap_entries(): array {
        $superficies = [
            [self::SURFACE_LANDING, '1.0', 'weekly'],
            [self::SURFACE_APPLY, '0.8', 'monthly'],
        ];

        $idiomas = array_keys(get_string_manager()->get_list_of_translations());
        $entradas = [];

        foreach ($superficies as [$superficie, $prioridade, $frequencia]) {
            $alternates = [];

            foreach ($idiomas as $lang) {
                $alternates[self::hreflang($lang)] = self::canonical_url($superficie, $lang);
            }

            $alternates['x-default'] = self::base_url($superficie);

            // A URL limpa e as por idioma sao todas <loc> proprias, e todas
            // carregam o mesmo cluster.
            foreach ($alternates as $url) {
                $entradas[] = [
                    'url' => $url,
                    'priority' => $prioridade,
                    'changefreq' => $frequencia,
                    'alternates' => $alternates,
                ];
            }
        }

        return $entradas;
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
        return self::locale_for(current_language());
    }

    /**
     * O locale de um idioma, no formato que o Open Graph espera.
     *
     * O TERRITORIO SAI DO CODIGO DO IDIOMA, e so quando ele existe de verdade:
     * 'pt_br' vira pt_BR porque o pacote E do Brasil, e 'en' continua 'en'
     * porque o pacote base do Moodle e ingles GLOBAL.
     *
     * O Open Graph pede lingua_TERRITORIO, e e tentador completar o que falta -
     * "en_US" para satisfazer o formato, ou o locale do langconfig, que devolve
     * en_AU para o pacote base. Os dois DECLARAM UM TERRITORIO QUE NINGUEM
     * CONFIGUROU, e o site em ingles nao e americano nem australiano: e global.
     * Vale aqui a mesma regra do resto desta classe - campo que ninguem
     * preencheu nao sai preenchido por nos. Sem territorio reconhecido, o
     * consumidor cai no padrao dele, que e melhor que uma alegacao falsa.
     *
     * @param string $lang
     * @return string
     */
    protected static function locale_for(string $lang): string {
        return str_replace('-', '_', self::hreflang($lang));
    }

    /**
     * Os locales dos OUTROS idiomas em que esta pagina existe.
     *
     * @return array
     */
    protected static function alternate_locales(): array {
        $atual = current_language();
        $locales = [];

        foreach (array_keys(get_string_manager()->get_list_of_translations()) as $lang) {
            if ($lang === $atual) {
                continue;
            }

            $locales[] = self::locale_for($lang);
        }

        return array_values(array_unique($locales));
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
    protected static function structured_data(string $superficie = self::SURFACE_LANDING): string {
        // A candidatura leva so a identidade e a propria pagina. O FAQ e os
        // planos descrevem a landing: repeti-los aqui seria declarar que esta
        // pagina responde perguntas que ela nao mostra, e validador recusa.
        $pecas = $superficie === self::SURFACE_APPLY
            ? [self::organization(), self::website(), self::webpage($superficie)]
            : [
                self::organization(),
                self::website(),
                self::webpage($superficie),
                self::faq_page(),
                self::offer_catalog(),
            ];

        $grafo = array_values(array_filter($pecas));

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
        $marca = self::brand();
        $raiz = (new moodle_url('/'))->out(false);

        $org = [
            '@type' => 'Organization',
            '@id' => $raiz . '#organization',
            'name' => self::site_name(),
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
        $raiz = (new moodle_url('/'))->out(false);

        return [
            '@type' => 'WebSite',
            '@id' => $raiz . '#website',
            'name' => self::site_name(),
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
    protected static function webpage(string $superficie = self::SURFACE_LANDING): array {
        $raiz = (new moodle_url('/'))->out(false);
        $url = self::canonical_url($superficie);
        $nome = $superficie === self::SURFACE_APPLY
            ? get_string('applytitle', 'local_partners')
            : get_string('landingtitle', 'local_partners');

        return [
            '@type' => 'WebPage',
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => $nome,
            'description' => self::meta_description($superficie),
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
