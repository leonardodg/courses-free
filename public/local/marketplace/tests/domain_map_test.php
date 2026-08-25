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

/**
 * Mapa Host -> empresa.
 *
 * O config.php inclui este arquivo em TODA requisicao, antes do lib/setup.php.
 * Um mapa mal formado nao derruba uma empresa: derruba o site inteiro, e sem
 * mensagem util, porque acontece antes do Moodle existir.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_marketplace\api::regenerate_domain_map
 */
final class domain_map_test extends \advanced_testcase {

    /**
     * Cria uma empresa com dominio.
     *
     * @param string|null $host
     * @param string $status
     * @return company
     */
    protected function make_company(?string $host, string $status = company::STATUS_ACTIVE): company {
        $owner = $this->getDataGenerator()->create_user();
        $c = api::create_company((object) [
            'name' => 'Empresa ' . random_int(1000, 9999),
            'shortname' => 'c' . random_int(100000, 999999),
            'hostname' => $host,
        ], (int) $owner->id);

        if ($status !== company::STATUS_ACTIVE) {
            $c->set('status', $status);
            $c->update();
        }

        return $c;
    }

    /**
     * Le o mapa como o config.php le.
     *
     * @return array
     */
    protected function read_map(): array {
        global $CFG;
        $file = $CFG->dataroot . '/marketplace_domains.php';

        return file_exists($file) ? include($file) : [];
    }

    /**
     * Setup.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Empresa com dominio entra no mapa, apontando para o proprio host.
     *
     * @return void
     */
    public function test_company_with_domain_is_mapped(): void {
        $this->make_company('cursos.exemplo.test');
        api::regenerate_domain_map();

        $map = $this->read_map();

        $this->assertArrayHasKey('cursos.exemplo.test', $map);
        $this->assertSame('https://cursos.exemplo.test', $map['cursos.exemplo.test']['wwwroot']);
    }

    /**
     * O host e gravado em minusculas.
     *
     * O navegador manda o Host em qualquer caixa. Se a chave guardasse
     * "Cursos.Exemplo.Test" como digitado no formulario, o dominio nunca
     * casaria - e o vendedor veria o site padrao sem entender por que.
     *
     * @return void
     */
    public function test_host_is_normalised_to_lowercase(): void {
        $this->make_company('Cursos.MAIUSCULO.test');
        api::regenerate_domain_map();

        $map = $this->read_map();

        $this->assertArrayHasKey('cursos.maiusculo.test', $map);
        $this->assertArrayNotHasKey('Cursos.MAIUSCULO.test', $map);
    }

    /**
     * Empresa sem dominio nao entra.
     *
     * @return void
     */
    public function test_company_without_domain_is_absent(): void {
        $this->make_company(null);
        api::regenerate_domain_map();

        $this->assertSame([], $this->read_map());
    }

    /**
     * Empresa suspensa sai do mapa.
     *
     * Suspender e a forma de tirar uma empresa do ar. Se o dominio continuasse
     * resolvendo, a suspensao nao suspenderia nada para quem chega pelo
     * dominio proprio - justamente o publico dela.
     *
     * @return void
     */
    public function test_suspended_company_is_removed(): void {
        $c = $this->make_company('sai.exemplo.test');
        api::regenerate_domain_map();
        $this->assertArrayHasKey('sai.exemplo.test', $this->read_map());

        $c->set('status', company::STATUS_SUSPENDED);
        $c->update();
        api::regenerate_domain_map();

        $this->assertArrayNotHasKey('sai.exemplo.test', $this->read_map());
    }

    /**
     * O arquivo gerado e PHP valido e devolve array.
     *
     * E o teste que mais importa: o config.php inclui este arquivo em toda
     * requisicao, antes do Moodle existir. PHP invalido ali derruba o site
     * inteiro com um erro de parse, nao a empresa.
     *
     * @return void
     */
    public function test_generated_file_is_valid_php(): void {
        global $CFG;

        // Aspas e barras no nome forcam o escape do var_export.
        $this->make_company('aspas.exemplo.test');
        api::regenerate_domain_map();

        $file = $CFG->dataroot . '/marketplace_domains.php';
        $this->assertFileExists($file);

        $out = [];
        $code = 0;
        exec('php -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
        $this->assertSame(0, $code, 'mapa gerado nao e PHP valido: ' . implode("\n", $out));

        $this->assertIsArray(include($file));
    }

    /**
     * Regenerar troca o conteudo inteiro, sem deixar resto da geracao anterior.
     *
     * @return void
     */
    public function test_regeneration_replaces_previous_content(): void {
        $c = $this->make_company('antigo.exemplo.test');
        api::regenerate_domain_map();

        api::update_company($c, (object) [
            'name' => $c->get('name'),
            'hostname' => 'novo.exemplo.test',
        ]);

        $map = $this->read_map();
        $this->assertArrayHasKey('novo.exemplo.test', $map);
        $this->assertArrayNotHasKey('antigo.exemplo.test', $map);
    }
}
