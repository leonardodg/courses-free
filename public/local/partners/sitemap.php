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
 * Mapa do site, para os buscadores.
 *
 * Pagina PUBLICA por definicao: um sitemap que exige login nao e um sitemap.
 *
 * Lista APENAS as paginas publicas de captacao. NAO lista curso, categoria nem
 * perfil - conteudo de dentro do LMS depende de matricula, e anunciar endereco
 * que devolve tela de login gasta o orcamento de rastreamento do buscador com
 * porta fechada.
 *
 * O endereco bonito e /sitemap.xml, servido por um Alias no vhost. Este arquivo
 * continua acessivel pelo caminho proprio, e os dois devolvem o mesmo XML.
 *
 * @package    local_partners
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing -- Sitemap e publico por definicao, ver o docblock.
require(__DIR__ . '/../../config.php');

use local_partners\landing;
use local_partners\seo;

$paginas = [];

if (landing::is_enabled()) {
    // A landing entra pelo endereco CANONICO, e nao pelos dois: anunciar a raiz
    // e a URL propria ao mesmo tempo e pedir ao buscador que escolha, e ele
    // escolhe sozinho, normalmente a errada.
    $paginas[] = ['url' => seo::canonical_url(), 'priority' => '1.0', 'changefreq' => 'weekly'];
    $paginas[] = [
        'url' => (new moodle_url('/local/partners/apply.php'))->out(false),
        'priority' => '0.8',
        'changefreq' => 'monthly',
    ];
}

// Cabecalho antes de qualquer saida. O sitemap muda quando o site muda, e nao a
// cada requisicao - meia hora de cache poupa o servidor sem atrasar nada que
// importe.
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=1800');

$idiomas = array_keys(get_string_manager()->get_list_of_translations());

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

foreach ($paginas as $pagina) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($pagina['url'], ENT_XML1) . "</loc>\n";

    // As versoes por idioma vao DENTRO da entrada, e nao como URLs separadas:
    // e a mesma pagina em outro idioma, e o buscador precisa saber disso para
    // nao tratar as tres como conteudo duplicado.
    foreach ($idiomas as $lang) {
        $alternativa = new moodle_url($pagina['url'], ['lang' => $lang]);

        echo '    <xhtml:link rel="alternate" hreflang="'
            . htmlspecialchars(str_replace('_', '-', $lang), ENT_XML1)
            . '" href="' . htmlspecialchars($alternativa->out(false), ENT_XML1) . '"/>' . "\n";
    }

    echo '    <xhtml:link rel="alternate" hreflang="x-default" href="'
        . htmlspecialchars($pagina['url'], ENT_XML1) . '"/>' . "\n";
    echo '    <changefreq>' . $pagina['changefreq'] . "</changefreq>\n";
    echo '    <priority>' . $pagina['priority'] . "</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>' . "\n";
