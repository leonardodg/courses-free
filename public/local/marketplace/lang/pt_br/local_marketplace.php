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

$string['accessdays'] = 'Acesso por {$a} dias';
$string['accessgranted'] = 'Acesso liberado. Bom curso!';
$string['accesslifetime'] = 'Acesso vitalício';
$string['accessrecurring'] = 'Assinatura, renovada a cada {$a} dias';
$string['accessrecurringlimited'] = 'Assinatura: {$a->billing} dias por pagamento, até {$a->cycles} pagamentos';
$string['accessrecurringopen'] = 'Assinatura: {$a->billing} dias por pagamento, sem prazo final';
$string['accessuntil'] = 'Acesso até {$a}';
$string['addmember'] = 'Adicionar vendedor';
$string['addmember_help'] = 'Vincula um usuário existente da plataforma a esta empresa e concede a ele o papel de vendedor na categoria dela. A pessoa precisa já ter conta.';
$string['alreadyowned'] = 'Você já tem acesso a esta oferta.';
$string['buynow'] = 'Comprar agora';
$string['cancelconfirm'] = 'Cancelar <strong>{$a->offer}</strong>? Você mantém o acesso até {$a->date} — pagou por esse período e ele não é retirado. Depois disso o acesso simplesmente termina, e paramos de lembrar você de renovar.';
$string['cancelconfirmlifetime'] = 'Parar os avisos de renovação de <strong>{$a}</strong>? Seu acesso não vence, então nada muda além dos avisos.';
$string['canceldone'] = 'Assinatura cancelada. Seu acesso vale até {$a}.';
$string['cancelledbut'] = 'Cancelada. Seu acesso vale até {$a} — o período que você pagou não é retirado.';
$string['cancelledlifetime'] = 'Avisos de renovação desligados. Seu acesso não vence.';
$string['cancelsubscription'] = 'Cancelar assinatura';
$string['cancelundo'] = 'Reativar';
$string['cancelundone'] = 'Assinatura reativada. Você será avisado antes do vencimento.';
$string['cannotsell'] = 'Somente cursos gratuitos';
$string['cansell'] = 'Vendendo';
$string['cansellyes'] = 'Pronta para vender. Gateway ativo: {$a}';
$string['commissionpct'] = 'Comissão da plataforma (%)';
$string['companies'] = 'Empresas';
$string['company'] = 'Empresa';
$string['companycnpj'] = 'CNPJ';
$string['companycnpj_help'] = 'Opcional. Pessoa física pode vender sem CNPJ.';
$string['companycommission'] = 'Comissão (%)';
$string['companycommission_help'] = 'Percentual que a plataforma retém nas vendas desta empresa, de 0 a 100. Deixe vazio para usar o padrão do site — vazio e zero são diferentes: vazio significa que nada foi negociado, zero significa que o parceiro é isento.';
$string['companycreated'] = 'Empresa {$a} criada. Adicione os outros vendedores abaixo.';
$string['companyhostname'] = 'Domínio próprio';
$string['companyname'] = 'Nome';
$string['companyowner'] = 'Responsável';
$string['companyowner_help'] = 'Quem administra a empresa e vincula a conta Mercado Pago dela. Precisa já ter conta na plataforma.';
$string['companypanel'] = 'Marketplace: painel da empresa';
$string['companyshortname'] = 'Nome curto';
$string['companyshortname_help'] = 'Usado na URL da empresa. Apenas letras, números e hifens.';
$string['companystatus'] = 'Situação';
$string['companytheme'] = 'Tema';
$string['companyupdated'] = 'Empresa {$a} atualizada.';
$string['configurepayment'] = 'Configurar Mercado Pago';
$string['createcompany'] = 'Criar empresa';
$string['createcompanyintro'] = 'Criar uma empresa provisiona uma categoria de cursos, atribui o papel de vendedor ao responsável nessa categoria e cria uma conta de pagamento. Feche a parceria antes — esta tela apenas executa.';
$string['defaultthemename'] = 'Tema padrão do site';
$string['editcompanyintro'] = 'O nome curto não pode ser alterado: ele é o número de identificação da categoria e aparece em links da vitrine e do painel que o vendedor pode já ter divulgado. O responsável é trocado na tela de vendedores, onde você pode promover outra pessoa antes.';
$string['erroraccessdays'] = 'Informe ao menos um dia de acesso.';
$string['erroralreadymember'] = 'Esta pessoa já é vendedora desta empresa.';
$string['errorbillingdays'] = 'Informe o intervalo de cobrança em dias.';
$string['errorcannotremoveowner'] = 'O responsável não pode ser removido. Promova outra pessoa antes — uma empresa sem responsável fica sem ninguém encarregado da conta de pagamento.';
$string['errorcannotsell'] = 'Esta empresa ainda não pode vender: configure um meio de pagamento primeiro.';
$string['errorcommissionrange'] = 'Use um número de 0 a 100, ou deixe vazio para herdar o padrão do site.';
$string['errorcurrencymismatch'] = 'Esta empresa recebe em {$a->expected}, então não pode vender uma oferta com preço em {$a->given}. Para vender em {$a->given}, vincule uma conta Mercado Pago daquele país.';
$string['errordomainmap'] = 'Nao foi possivel gravar o mapa de dominios dos vendedores. Confira se o diretorio de dados do Moodle tem permissao de escrita.';
$string['errorhostnametaken'] = 'Este domínio já está vinculado a outra empresa.';
$string['errormaxcycles'] = 'Use zero para não haver limite, ou um número positivo.';
$string['errornoaccount'] = 'Esta empresa não tem conta de pagamento. Reinstale ou recrie a empresa.';
$string['errornocourses'] = 'Escolha ao menos um curso, ou use o tipo Catálogo completo.';
$string['errorplatformhostingunavailable'] = 'Hospedar vídeo na plataforma ainda não está disponível.';
$string['errorrecurringfree'] = 'Uma assinatura precisa de preço. Gratuita, ela venceria sem forma de renovar.';
$string['errorsellerrolemissing'] = 'O papel de vendedor não existe. Reinstale o plugin Marketplace.';
$string['errorshortnametaken'] = 'Este nome curto já está em uso.';
$string['errorsinglemanycourses'] = 'Uma oferta de curso único libera um curso. Use Combo para mais de um.';
$string['expiringbody'] = 'Olá,

Seu acesso a {$a->offer}, de {$a->company}, termina em {$a->date}.

Não há cobrança automática — para manter o acesso, pague novamente aqui:
{$a->url}

Se preferir parar, não faça nada e o acesso simplesmente termina.';
$string['expiringbodyhtml'] = '<p>Olá,</p><p>Seu acesso a <strong>{$a->offer}</strong>, de {$a->company}, termina em <strong>{$a->date}</strong>.</p><p>Não há cobrança automática — para manter o acesso, <a href="{$a->url}">pague novamente aqui</a>.</p><p>Se preferir parar, não faça nada e o acesso simplesmente termina.</p>';
$string['expiringsubject'] = 'Seu acesso a {$a->offer} termina em {$a->days} dia(s)';
$string['free'] = 'Gratuito';
$string['getfree'] = 'Obter acesso gratuito';
$string['hostingexternal'] = 'Fora da plataforma';
$string['hostingplatform'] = 'Na plataforma';
$string['hostingtype'] = 'Hospedagem do vídeo';
$string['linkednotenabled'] = 'A conta Mercado Pago está vinculada, mas o gateway está desligado, então nada pode ser vendido ainda. Abra as configurações de pagamento e habilite.';
$string['makeowner'] = 'Tornar responsável';
$string['makeseller'] = 'Tornar vendedor';
$string['managecourses'] = 'Gerenciar cursos';
$string['managemembers'] = 'Vendedores';
$string['marketplace:createcompany'] = 'Criar uma empresa';
$string['marketplace:manageall'] = 'Administrar todas as empresas da plataforma';
$string['marketplace:managecompany'] = 'Administrar a empresa';
$string['marketplace:managepayment'] = 'Administrar a conta de pagamento da empresa';
$string['marketplace:publishcourse'] = 'Publicar curso pela empresa';
$string['marketplace:viewreport'] = 'Ver o relatório financeiro da empresa';
$string['memberadded'] = 'Vendedor adicionado.';
$string['memberowner'] = 'Responsável';
$string['memberremoved'] = 'Vendedor removido.';
$string['memberrolechanged'] = 'Papel alterado.';
$string['members'] = 'Vendedores';
$string['memberseller'] = 'Vendedor';
$string['membersof'] = 'Vendedores de {$a}';
$string['messageprovider:expiring'] = 'Acesso prestes a vencer';
$string['modedays'] = 'Prazo fixo';
$string['modelifetime'] = 'Vitalício';
$string['moderecurring'] = 'Assinatura';
$string['mysubsactive'] = 'Assinaturas';
$string['mysubscriptions'] = 'Minhas assinaturas';
$string['mysubspayments'] = 'Pagamentos';
$string['nocompanies'] = 'Nenhuma empresa cadastrada.';
$string['nocompany'] = 'Você não pertence a nenhuma empresa.';
$string['nomembers'] = 'Esta empresa não tem vendedores.';
$string['nooffers'] = 'Esta empresa ainda não tem ofertas publicadas.';
$string['nopaymentaccount'] = 'Esta empresa não tem meio de pagamento configurado, então só pode publicar cursos gratuitos.';
$string['nopayments'] = 'Nenhum pagamento ainda.';
$string['nosubscriptions'] = 'Você ainda não comprou nada.';
$string['offeraccess'] = 'Acesso e cobrança';
$string['offeraccessdays'] = 'Dias de acesso por pagamento';
$string['offeraccessdays_help'] = 'Quanto acesso cada pagamento libera. Numa assinatura, pode ser maior que o intervalo de cobrança para dar carência: cobrar a cada 30 dias liberando 35 deixa um pagamento atrasado passar sem cortar o aluno.';
$string['offeraccessmode'] = 'Modelo de acesso';
$string['offeraccessmode_help'] = 'Vitalício nunca vence. Prazo fixo dá um número de dias por compra. Assinatura dá um período e espera renovação.';
$string['offerbillingdays'] = 'Intervalo de cobrança (dias)';
$string['offerbillingdays_help'] = 'De quanto em quanto tempo se espera o próximo pagamento. Usado no aviso de vencimento.';
$string['offercourses'] = 'Cursos liberados';
$string['offercourses_help'] = 'Quais cursos esta oferta libera. Não é necessário no Catálogo completo, que segue a categoria da empresa.';
$string['offercreate'] = 'Nova oferta';
$string['offeredit'] = 'Oferta';
$string['offerincludes'] = 'Inclui {$a} curso(s)';
$string['offermaxcycles'] = 'Máximo de pagamentos';
$string['offermaxcycles_help'] = 'Quantas vezes esta assinatura pode ser cobrada no total. Zero significa sem limite. Use 12 para um plano mensal que dura um ano, ou 3 para um plano anual que dura três anos.';
$string['offername'] = 'Oferta';
$string['offerprice'] = 'Preço';
$string['offerprice_help'] = 'Zero torna a oferta gratuita. Ofertas gratuitas não passam pelo Mercado Pago.';
$string['offerpublication'] = 'Publicação';
$string['offerrecurringwarning'] = 'O Mercado Pago não tem cobrança recorrente com split, então nada é debitado automaticamente. O aluno recebe um aviso antes do vencimento, com link para pagar de novo.';
$string['offersaved'] = 'Oferta salva.';
$string['offersortorder'] = 'Ordem de exibição';
$string['offerssection'] = 'Ofertas';
$string['offerstatus_help'] = 'Somente ofertas publicadas aparecem na vitrine. Arquivar não revoga acesso já comprado.';
$string['offertype'] = 'Tipo';
$string['offertype_help'] = 'Curso único vende um curso. Combo vende um conjunto escolhido — é assim que se montam planos em níveis como Básico, Intermediário e Completo para a mesma empresa. Catálogo completo segue a categoria da empresa, então cursos novos entram sozinhos.';
$string['offerunlocks'] = 'Esta é a oferta que libera o conteúdo que você estava vendo.';
$string['paymentcurrency'] = 'Moeda de recebimento: {$a}';
$string['paymentcurrencyunknown'] = 'Moeda de recebimento desconhecida. Vincule a conta Mercado Pago de novo para detectar.';
$string['paymentsection'] = 'Meio de pagamento';
$string['pluginname'] = 'Marketplace';
$string['privacy:metadata'] = 'O plugin Marketplace guarda empresas, seus vendedores e credenciais de pagamento.';
$string['privacy:metadata:entitlement'] = 'O que o aluno comprou e por quanto tempo vale o acesso dele.';
$string['privacy:metadata:entitlement:companyid'] = 'A empresa que vendeu.';
$string['privacy:metadata:entitlement:cycles'] = 'Quantos pagamentos já foram feitos.';
$string['privacy:metadata:entitlement:offerid'] = 'A oferta comprada.';
$string['privacy:metadata:entitlement:status'] = 'Se o acesso está vigente, vencido ou revogado.';
$string['privacy:metadata:entitlement:timeend'] = 'Quando o acesso termina. Zero significa que não vence.';
$string['privacy:metadata:entitlement:timestart'] = 'Quando o acesso começou.';
$string['privacy:metadata:entitlement:userid'] = 'O aluno.';
$string['privacy:metadata:member'] = 'Por quais empresas a pessoa vende.';
$string['privacy:metadata:member:companyid'] = 'A empresa.';
$string['privacy:metadata:member:memberrole'] = 'Se ela responde pela empresa ou vende por ela.';
$string['privacy:metadata:member:timecreated'] = 'Quando o vínculo foi feito.';
$string['privacy:metadata:member:userid'] = 'A pessoa vinculada à empresa.';
$string['renewnotice'] = 'Seu acesso termina em {$a}. Renove para mantê-lo.';
$string['renewnow'] = 'Renovar agora';
$string['reportaccessuntil'] = 'Acesso até';
$string['reportall'] = 'Todo o período';
$string['reportcommission'] = 'Comissão da plataforma';
$string['reportcoursesnotice'] = 'Um combo conta inteiro para cada curso que libera — ninguém compra um terço de um combo. Então esta coluna soma mais que seu faturamento total, e serve para comparar cursos entre si, não para somar.';
$string['reportdays'] = 'Últimos {$a} dias';
$string['reportentries'] = 'Vendas';
$string['reportgross'] = 'Bruto';
$string['reportlastpayment'] = 'Último pagamento';
$string['reportmppayment'] = 'Pagamento no Mercado Pago';
$string['reportnetnotice'] = 'A taxa do Mercado Pago não aparece aqui porque não nos é informada: ela varia por meio de pagamento e por prazo de recebimento, e é descontada do lado deles antes da comissão da plataforma. Seu valor líquido é o do extrato do Mercado Pago.';
$string['reportnocourses'] = 'Nenhum curso foi vendido ainda.';
$string['reportnosales'] = 'Nenhuma venda aprovada neste período.';
$string['reportnosubs'] = 'Nenhuma oferta de assinatura, ou ninguém assinou ainda.';
$string['reportpayments'] = 'Pagamentos';
$string['reportsales'] = 'Vendas aprovadas';
$string['reportsaleswith'] = 'Vendas que o incluem';
$string['reportsection'] = 'Vendas';
$string['reportsubactive'] = 'Vigente';
$string['reportsubcancelled'] = 'Cancelada';
$string['reportsubduesoon'] = 'Vence em {$a} d';
$string['reportsubexpired'] = 'Vencida';
$string['reportsubsnotice'] = 'Não há cronograma de cobrança a exibir: o Mercado Pago não tem pagamento recorrente com split, então cada renovação é uma compra separada que estende o acesso. Esta tela mostra quantas vezes cada aluno pagou e por quanto tempo o acesso dele ainda vale.';
$string['reportviewcourses'] = 'Cursos vendidos';
$string['reportviewsubscriptions'] = 'Assinaturas';
$string['reportviewtransactions'] = 'Transações';
$string['sellerrole'] = 'Vendedor';
$string['sellerroledesc'] = 'Publica cursos por uma empresa. Não pode enviar arquivos, então os vídeos do curso precisam ser hospedados fora da plataforma.';
$string['statusactive'] = 'Ativa';
$string['statusarchived'] = 'Arquivada';
$string['statusdraft'] = 'Rascunho';
$string['statuspublished'] = 'Publicada';
$string['statussuspended'] = 'Suspensa';
$string['tasknotifyexpiring'] = 'Avisar alunos sobre acesso prestes a vencer';
$string['typebundle'] = 'Combo';
$string['typecatalog'] = 'Catálogo completo';
$string['typesingle'] = 'Curso único';
$string['unavailable'] = 'Ainda não disponível para compra.';
$string['viewstorefront'] = 'Ver vitrine';
