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

use core_course_category;
use moodle_exception;

/**
 * Operacoes do marketplace que envolvem mais de uma entidade.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {
    /**
     * Cria a empresa e provisiona tudo que ela precisa para operar.
     *
     * Uma empresa sem categoria seria uma linha inerte: e a categoria que da
     * a ela um contexto onde atribuir o papel, um lugar para os cursos e um
     * tema proprio. Por isso o provisionamento e parte da criacao, e nao um
     * passo separado que alguem poderia esquecer.
     *
     * Tudo numa transacao: uma empresa com categoria mas sem dono, ou com dono
     * mas sem papel atribuido, seria pior que nenhuma empresa - apareceria na
     * lista e nao funcionaria.
     *
     * @param object $data name, shortname, cnpj, themename, hostname
     * @param int $ownerid Usuario que passa a ser dono.
     * @return company
     */
    public static function create_company(object $data, int $ownerid): company {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        try {
            $company = new company();
            $company->set('name', $data->name);
            $company->set('shortname', $data->shortname);
            $company->set('cnpj', !empty($data->cnpj) ? $data->cnpj : null);
            $company->set('commissionpct', self::commission_input($data->commissionpct ?? null));
            $company->set('pagetitle', !empty($data->pagetitle) ? $data->pagetitle : null);
            $company->set('pageintro', $data->pageintro ?? null);
            $company->set('pageaccent', !empty($data->pageaccent) ? $data->pageaccent : null);
            $company->set('themename', !empty($data->themename) ? $data->themename : null);
            $company->set('hostname', !empty($data->hostname) ? $data->hostname : null);
            $company->create();

            $category = self::create_category($company);
            $company->set('categoryid', $category->id);
            $company->update();

            $owner = new member();
            $owner->set('companyid', $company->get('id'));
            $owner->set('userid', $ownerid);
            $owner->set('memberrole', member::ROLE_OWNER);
            $owner->create();

            self::assign_seller_role($company, $ownerid);
            self::apply_theme($company);
            self::create_payment_account($company);

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }

        // Fora da transacao de proposito: escrever arquivo nao tem rollback.
        // Gerar antes do commit deixaria o mapa anunciando um dominio de
        // empresa que nao chegou a existir.
        if ($company->get('hostname')) {
            self::regenerate_domain_map();
        }

        return $company;
    }

    /**
     * Percentual de comissao que vale para uma oferta.
     *
     * Hierarquia, do mais especifico para o mais geral:
     *
     *   1. politica do CURSO, quando a oferta libera um curso so
     *   2. comissao negociada com a EMPRESA
     *   3. padrao do SITE
     *
     * A politica de curso so entra em oferta de curso unico, e a razao e que
     * nao existe resposta correta para um combo: tres cursos com percentuais
     * diferentes nao produzem um percentual do pacote. Pegar o maior seria
     * predatorio, o menor seria arbitrario, e a media seria um numero que
     * ninguem negociou. Entao no combo vale a empresa, que e o que o vendedor
     * de fato acordou.
     *
     * A linha de politica so existe quando alguem a criou de proposito - nada
     * cria por padrao. Por isso a existencia dela conta como intencao, e nao
     * ha ambiguidade entre "definido como 25" e "nunca definido".
     *
     * @param offer $offer
     * @return float Percentual entre 0 e 100.
     */
    public static function resolve_commission_percent(offer $offer): float {
        if ($offer->get('offertype') === offer::TYPE_SINGLE) {
            $courseids = $offer->get_course_ids();
            if (count($courseids) === 1) {
                $policy = course_policy::get_by_course((int) reset($courseids));
                if ($policy) {
                    return self::clamp((float) $policy->get('commissionpct'));
                }
            }
        }

        $company = company::get_record(['id' => (int) $offer->get('companyid')]);
        if ($company) {
            $negotiated = $company->get_commission_percent();
            if ($negotiated !== null) {
                return self::clamp($negotiated);
            }
        }

        return self::clamp((float) (get_config('paygw_mercadopago', 'defaultfeepercent') ?: 25));
    }

    /**
     * Mantem o percentual dentro de 0 a 100.
     *
     * Um valor fora da faixa viraria marketplace_fee maior que o proprio preco,
     * e o Mercado Pago recusaria a preferencia - com o aluno ja no checkout.
     *
     * @param float $value
     * @return float
     */
    protected static function clamp(float $value): float {
        return max(0.0, min(100.0, $value));
    }

    /**
     * Interpreta o campo de comissao vindo do formulario.
     *
     * Vazio vira NULO - herda o site. "0" vira zero - isencao negociada. Usar
     * empty() aqui trataria os dois como a mesma coisa, e um parceiro isento
     * passaria a pagar a comissao padrao sem ninguem ter mudado nada.
     *
     * @param mixed $value
     * @return float|null
     */
    protected static function commission_input($value): ?float {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return self::clamp((float) $value);
    }

    /**
     * Altera os dados cadastrais de uma empresa.
     *
     * O atalho NAO muda. Ele vira o idnumber da categoria, entra nas URLs da
     * vitrine e do painel, e pode ja estar em link divulgado pelo vendedor.
     * Trocar exigiria renomear o idnumber e quebraria os links antigos sem
     * aviso - custo que nao se justifica para um campo cosmetico.
     *
     * O dono tambem nao muda aqui: quem responde pela empresa se resolve na
     * tela de vendedores, onde da para promover outro antes de rebaixar o atual.
     *
     * @param company $company
     * @param object $data name, cnpj, themename, hostname
     * @return company
     */
    public static function update_company(company $company, object $data): company {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $hostbefore = $company->get('hostname');

        try {
            $renamed = ($company->get('name') !== $data->name);

            $company->set('name', $data->name);
            $company->set('cnpj', !empty($data->cnpj) ? $data->cnpj : null);
            $company->set('commissionpct', self::commission_input($data->commissionpct ?? null));
            $company->set('pagetitle', !empty($data->pagetitle) ? $data->pagetitle : null);
            $company->set('pageintro', $data->pageintro ?? null);
            $company->set('pageaccent', !empty($data->pageaccent) ? $data->pageaccent : null);
            $company->set('themename', !empty($data->themename) ? $data->themename : null);
            $company->set('hostname', !empty($data->hostname) ? $data->hostname : null);
            $company->update();

            // A categoria carrega o nome da empresa. Deixar de renomear faria a
            // arvore de cursos divergir do cadastro, e e a arvore que o aluno ve.
            if ($renamed && $company->get('categoryid')) {
                $category = \core_course_category::get((int) $company->get('categoryid'), IGNORE_MISSING, true);
                if ($category) {
                    $category->update(['name' => $data->name]);
                }
            }

            self::apply_theme($company);

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }

        // Regenera quando o dominio ENTROU, SAIU ou mudou. Comparar com o
        // valor anterior evita reescrever o arquivo a cada troca de nome ou
        // de tema, que nao afetam o mapa. Suspender tambem nao afeta: o mapa
        // lista dominio existente, e a situacao e conferida no banco.
        if ($hostbefore !== $company->get('hostname')) {
            self::regenerate_domain_map();
        }

        return $company;
    }

    /**
     * Regenera o mapa Host -> empresa lido pelo config.php.
     *
     * O config.php roda ANTES do lib/setup.php, entao $DB ainda nao existe e
     * consultar o banco ali e impossivel. Por isso o mapa e um arquivo PHP
     * gerado: escrito quando um dominio muda, lido a cada requisicao.
     *
     * Escrito num temporario e movido por rename(), que e atomico no mesmo
     * sistema de arquivos. Escrever direto no destino deixaria uma janela em
     * que o config.php poderia incluir um arquivo pela metade - e um
     * $CFG->wwwroot truncado derruba o site inteiro, nao so a empresa.
     *
     * @return int Quantos dominios entraram no mapa.
     */
    public static function regenerate_domain_map(): int {
        global $CFG, $DB;

        // TODA empresa com dominio entra, inclusive a suspensa. O arquivo
        // responde "este dominio existe?", que e fronteira de SEGURANCA: sem
        // ele, o Host da requisicao definiria o wwwroot, e um atacante mandando
        // "Host: evil.com" faria o Moodle gerar todo link apontando para la -
        // inclusive o de redefinicao de senha, que sai por e-mail.
        //
        // "Esta suspensa?" e outra pergunta, respondida no banco pelo
        // after_config, onde da para mostrar uma pagina explicando. Deixar a
        // suspensao aqui faria o dominio cair no site padrao em silencio, e
        // acoplaria suspender a regenerar arquivo: se a regeneracao falhasse, a
        // suspensao nao surtiria efeito.
        $rows = $DB->get_records_select(
            company::TABLE,
            "hostname IS NOT NULL AND hostname <> ''",
            [],
            'hostname',
            'id, shortname, hostname'
        );

        $map = [];
        foreach ($rows as $row) {
            // O host e a chave e vem do cadastro; normalizar aqui evita que
            // "Meu.Site.com" no formulario nunca case com o Host do navegador,
            // que chega sempre em minusculas.
            $map[strtolower($row->hostname)] = [
                'wwwroot' => 'https://' . strtolower($row->hostname),
                'company' => $row->shortname,
            ];
        }

        $file = $CFG->dataroot . '/marketplace_domains.php';
        $tmp = $file . '.' . getmypid() . '.tmp';

        $php = "<?php\n"
            . "// GERADO por local_marketplace. Nao edite: sera sobrescrito.\n"
            . "// Ultima geracao: " . date('c') . "\n"
            . "return " . var_export($map, true) . ";\n";

        if (file_put_contents($tmp, $php, LOCK_EX) === false) {
            throw new moodle_exception('errordomainmap', 'local_marketplace');
        }
        @chmod($tmp, $CFG->filepermissions ?? 0666);
        rename($tmp, $file);

        return count($map);
    }

    /**
     * Cria a categoria de cursos da empresa.
     *
     * @param company $company
     * @return \stdClass
     */
    protected static function create_category(company $company): \stdClass {
        $category = core_course_category::create([
            'name' => $company->get('name'),
            'idnumber' => 'marketplace_' . $company->get('shortname'),
            'description' => '',
            'parent' => 0,
        ]);
        return $category->get_db_record();
    }

    /**
     * Cria a payment account da empresa, no contexto da categoria dela.
     *
     * E o contexto que faz o vendedor conseguir administrar a propria conta
     * sem enxergar as das outras empresas: moodle/payment:manageaccounts e
     * CONTEXT_COURSE, e helper::get_payment_accounts_menu() resolve pelos
     * contextos-pai.
     *
     * A conta nasce SEM gateway. E isso que mantem o portao de venda fechado
     * ate o vendedor concluir o vinculo: account::is_available() exige ao
     * menos um gateway habilitado.
     *
     * @param company $company
     * @return \core_payment\account
     */
    public static function create_payment_account(company $company): \core_payment\account {
        $context = $company->get_context();

        $account = new \core_payment\account();
        $account->set('name', $company->get('name'));
        $account->set('idnumber', 'marketplace_' . $company->get('shortname'));
        $account->set('contextid', $context->id);
        $account->set('enabled', true);
        $account->create();

        return $account;
    }

    /**
     * Da a um usuario o papel de vendedor no contexto da empresa.
     *
     * @param company $company
     * @param int $userid
     * @return void
     */
    public static function assign_seller_role(company $company, int $userid): void {
        global $DB;

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'marketplaceseller']);
        if (!$roleid) {
            throw new moodle_exception('errorsellerrolemissing', 'local_marketplace');
        }
        role_assign($roleid, $userid, $company->get_context()->id);
    }

    /**
     * Aplica o tema da empresa na categoria dela.
     *
     * Depende de $CFG->allowcategorythemes. Sem ele o campo e gravado e
     * simplesmente ignorado pelo Moodle, o que renderia um bug silencioso:
     * o vendedor escolhe o tema, a tela confirma, e nada muda.
     *
     * @param company $company
     * @return bool Falso quando o tema por categoria esta desligado no site.
     */
    public static function apply_theme(company $company): bool {
        global $DB, $CFG;

        $theme = $company->get('themename');
        $categoryid = $company->get('categoryid');
        if (empty($categoryid)) {
            return true;
        }

        // Tema vazio LIMPA o da categoria, e nao "nao faz nada". Sair cedo aqui
        // tornaria impossivel voltar ao tema do site pela edicao: a empresa
        // ficaria presa ao primeiro tema escolhido.
        if (empty($theme)) {
            $DB->set_field('course_categories', 'theme', '', ['id' => $categoryid]);
            theme_reset_static_caches();
            return true;
        }

        if (empty($CFG->allowcategorythemes)) {
            debugging(
                'local_marketplace: tema por empresa exige $CFG->allowcategorythemes ligado.',
                DEBUG_DEVELOPER
            );
            return false;
        }

        $DB->set_field('course_categories', 'theme', $theme, ['id' => $categoryid]);
        theme_reset_static_caches();
        return true;
    }

    /**
     * Acrescenta um vendedor a empresa.
     *
     * @param company $company
     * @param int $userid
     * @return member
     */
    public static function add_member(company $company, int $userid): member {
        global $DB;

        $existing = member::get_membership($company->get('id'), $userid);
        if ($existing) {
            return $existing;
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            $member = new member();
            $member->set('companyid', $company->get('id'));
            $member->set('userid', $userid);
            $member->set('memberrole', member::ROLE_SELLER);
            $member->create();

            self::assign_seller_role($company, $userid);
            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }

        return $member;
    }

    /**
     * Remove um vendedor da empresa.
     *
     * Tira o papel no contexto da categoria junto com o vinculo. Apagar so a
     * linha da tabela deixaria a pessoa com as capabilities do vendedor -
     * criando curso na categoria de uma empresa da qual nao faz mais parte.
     *
     * NAO mexe em direitos de acesso nem em vendas: quem comprou continua com
     * o que comprou, e o historico financeiro da empresa nao se altera porque
     * um vendedor saiu.
     *
     * @param company $company
     * @param int $userid
     * @return bool Falso se a pessoa nao era membro.
     */
    public static function remove_member(company $company, int $userid): bool {
        global $DB;

        $member = member::get_membership($company->get('id'), $userid);
        if (!$member) {
            return false;
        }

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'marketplaceseller']);

        $transaction = $DB->start_delegated_transaction();
        try {
            $member->delete();
            if ($roleid) {
                role_unassign($roleid, $userid, $company->get_context()->id);
            }
            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }

        return true;
    }

    /**
     * Troca o papel de um membro entre dono e vendedor.
     *
     * As capabilities sao as mesmas: o papel do Moodle nao muda, so o registro
     * de quem responde pela empresa. A distincao existe para a tela saber quem
     * nao pode ser removido - uma empresa sem dono fica sem responsavel pela
     * conta de pagamento.
     *
     * @param company $company
     * @param int $userid
     * @param string $memberrole
     * @return bool
     */
    public static function set_member_role(company $company, int $userid, string $memberrole): bool {
        $member = member::get_membership($company->get('id'), $userid);
        if (!$member) {
            return false;
        }
        $member->set('memberrole', $memberrole);
        $member->update();

        return true;
    }
}
