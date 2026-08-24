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
 * Strings do local_marketplace.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Marketplace';

// Capabilities.
$string['marketplace:createcompany'] = 'Criar uma empresa';
$string['marketplace:managecompany'] = 'Administrar a empresa';
$string['marketplace:managepayment'] = 'Administrar a conta de pagamento da empresa';
$string['marketplace:publishcourse'] = 'Publicar curso pela empresa';
$string['marketplace:viewreport'] = 'Ver o relatório financeiro da empresa';
$string['marketplace:manageall'] = 'Administrar todas as empresas da plataforma';

// Papel de vendedor.
$string['sellerrole'] = 'Vendedor';
$string['sellerroledesc'] = 'Publica cursos por uma empresa. Não envia arquivos, então o vídeo do curso precisa ficar hospedado fora da plataforma.';

// Empresa.
$string['company'] = 'Empresa';
$string['companies'] = 'Empresas';
$string['companyname'] = 'Nome';
$string['companyshortname'] = 'Nome curto';
$string['companyshortname_help'] = 'Usado na URL da empresa. Apenas letras, números e hífens.';
$string['companycnpj'] = 'CNPJ';
$string['companycnpj_help'] = 'Opcional. Pessoa física pode vender sem CNPJ.';
$string['companyhostname'] = 'Domínio próprio';
$string['companytheme'] = 'Tema';
$string['companystatus'] = 'Situação';
$string['statusactive'] = 'Ativa';
$string['statussuspended'] = 'Suspensa';

// Membros.
$string['members'] = 'Vendedores';
$string['memberowner'] = 'Dono';
$string['memberseller'] = 'Vendedor';

// Portão de pagamento.
$string['mpaccount'] = 'Conta Mercado Pago';
$string['mpnotlinked'] = 'Esta empresa não tem conta Mercado Pago vinculada, então só pode publicar cursos gratuitos.';
$string['mplinked'] = 'Conta Mercado Pago vinculada';
$string['mpexpired'] = 'A autorização do Mercado Pago expirou e precisa ser renovada.';
$string['mprevoked'] = 'A autorização do Mercado Pago foi revogada.';
$string['statuspending'] = 'Pendente';
$string['statuslinked'] = 'Vinculada';
$string['statusexpired'] = 'Expirada';
$string['statusrevoked'] = 'Revogada';

// Política do curso.
$string['hostingtype'] = 'Hospedagem do vídeo';
$string['hostingexternal'] = 'Fora da plataforma';
$string['hostingplatform'] = 'Na plataforma';
$string['commissionpct'] = 'Comissão da plataforma (%)';

// Erros.
$string['errorshortnametaken'] = 'Este nome curto já está em uso.';
$string['errorhostnametaken'] = 'Este domínio já está vinculado a outra empresa.';
$string['errorsellerrolemissing'] = 'O papel de vendedor não existe. Reinstale o plugin Marketplace.';
$string['errorcannotsell'] = 'Esta empresa ainda não pode vender: vincule uma conta Mercado Pago primeiro.';
$string['errorplatformhostingunavailable'] = 'Hospedar vídeo na plataforma ainda não está disponível.';

$string['privacy:metadata'] = 'O plugin Marketplace armazena empresas, seus vendedores e credenciais de pagamento.';
