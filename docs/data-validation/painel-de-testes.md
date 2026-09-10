# Painel de testes

Caminhos de gestão em `courses.leodg.dev`. Empresa de teste: `demo`, conta de
pagamento `1`.

## Marketplace

| Tela | Caminho | O que verificar |
|---|---|---|
| Vitrine da empresa | `/local/marketplace/offers.php?company=demo` | Onde o aluno compra. Aceita `&highlight=N` para destacar uma oferta. |
| Painel da empresa | `/local/marketplace/company.php?company=demo` | Meio de pagamento, moeda, ofertas e vendedores. |
| Nova oferta | `/local/marketplace/offer_edit.php?company=demo` | Tipo, preço, modelo de acesso e cursos liberados. |
| Relatórios | `/local/marketplace/report.php?company=demo` | Transações, cursos vendidos e assinaturas. |
| Minhas assinaturas | `/local/marketplace/mysubscriptions.php` | Visão do aluno: vencimento, pagamentos, **pagar a fatura do ciclo** e cancelar. |
| Cancelar assinatura | `/local/marketplace/cancel.php?id=N` | `N` é o id do **direito**, não da oferta. Aceita `&undo=1` para desfazer. |
| Reenviar fatura | `/local/marketplace/resend.php?id=N&sesskey=...` | Do gerente. Manda ao aluno a mesma mensagem do cron. |
| Estornar venda | `/local/marketplace/refund.php?payment=N` | `N` é o id em `{payments}`. Exige `refundsale`. |
| Categorias e cursos | `/course/index.php` | A categoria da empresa traz o painel no menu lateral. |

## Quem faz o quê, e onde

O que decide o que aparece é a **capability no contexto da categoria da
empresa** — não o menu. Testar com o usuário errado dá "não aparece" e parece
bug.

| Ação | Aluno | Gerente (`managesales`) | Admin (`refundsale`) |
|---|---|---|---|
| Ver as próprias assinaturas | *Minhas assinaturas* | — | — |
| Pagar a fatura do ciclo | botão em *Minhas assinaturas* | — | — |
| Assinar / renovar | vitrine, botão *Renovar agora* | — | — |
| Cancelar assinatura | *Minhas assinaturas* ou vitrine | aba **Assinaturas** do relatório | — |
| Reenviar a fatura ao aluno | — | aba **Assinaturas** do relatório | — |
| Ver as vendas | — | aba **Transações** do relatório | — |
| **Estornar venda** | — | **não** | aba **Transações** do relatório |

O botão de **renovar** só aparece dentro dos 5 dias antes do vencimento
(`NOTICE_DAYS`), e só para oferta `recurring`. O de **cancelar**, no relatório,
só para assinatura vigente que ainda cobra. O de **estornar** some quando o
gateway diz que não dá — boleto nunca, ciclo do meio de assinatura nunca,
cobrança não paga nunca.

## Roteiro para repetir os testes

Sem esperar mês nenhum. O vencimento é um `timestamp` na linha do direito.

```bash
# 1. avisos: mover o vencimento para dentro de cada marco e rodar a tarefa
#    (3 dias -> aviso brando; 12 horas -> ultimo aviso, com outro texto)
moodev cli scheduled_task.php --execute='\local_marketplace\task\notify_expiring'

# 2. corte de acesso: vencimento no passado
moodev cli scheduled_task.php --execute='\enrol_marketplace\task\sync_entitlements'

# 3. reconciliacao, quando o webhook se perder
moodev cli scheduled_task.php --execute='\paygw_asaas\task\reconcile'
moodev cli scheduled_task.php --execute='\paygw_mercadopago\task\reconcile'
```

Para mexer no vencimento sem tela, o caminho é o mesmo dos roteiros de
`asaas-assinatura.md`: alterar `timeend` em `local_marketplace_entitlement` e
rodar a tarefa. **Isole a assinatura** antes de testar o corte — direito de
outra oferta cobrindo o mesmo curso segura o acesso, e o sync suspende zero.

## Pagamento

| Tela | Caminho | O que verificar |
|---|---|---|
| Aplicação Mercado Pago | `/admin/settings.php?section=paymentgatewaymercadopago` | Client ID, secret, país, comissão e modo de teste. Nível site. |
| Vínculo do vendedor | `/payment/manage_gateway.php?accountid=1&gateway=mercadopago` | Vincular, trocar e desvincular. Mostra a moeda detectada. |
| Contas de pagamento | `/payment/accounts.php` | Lista do core. Só mostra contas no contexto do sistema — a da empresa vive na categoria e **não aparece aqui**. |
| Gateways habilitados | `/admin/settings.php?section=managepaymentgateways` | Liga e desliga cada gateway. **Desligar não para assinatura já criada** — quem cobra é o gateway. |
| Aplicação Asaas | `/admin/settings.php?section=paymentgatewayasaas` | Ambiente, carteira da plataforma, token do webhook, forma de cobrança e campo do CPF. |
| Vínculo do vendedor Asaas | `/payment/manage_gateway.php?accountid=N&gateway=asaas` | Colar a chave do vendedor. A chave declara o ambiente pelo prefixo. |

## Captação de parceiros

| Tela | Caminho | O que verificar |
|---|---|---|
| Landing | `/local/partners/index.php` | Página **pública**, sem login. Escura por padrão, com alternador de modo na barra de seções. Preço e comissão saem do banco, não do template. |
| Cadastro | `/local/partners/apply.php` | Duas colunas no desktop, empilhado no celular. Aceite dos termos é obrigatório **no servidor**. |
| Fila de candidaturas | `/local/partners/admin/applications.php` | Ordem de chegada, situação em badge. |
| Detalhe e decisão | `/local/partners/admin/application_view.php?id=N` | País, faixa de alunos e **momento** do aceite. Aprovar cria a empresa. |

A conferência visual — três temas, cinco larguras, comparação com o mockup —
está em [`local-partners-layout.md`](local-partners-layout.md).

## Administração

| Tela | Caminho | O que verificar |
|---|---|---|
| Empresas | `/local/marketplace/admin/companies.php` | Criar, editar e gerenciar vendedores. |
| Visão geral dos plugins | `/admin/plugins.php` | Versão instalada dos cinco plugins do projeto. |
| Métodos de inscrição | `/admin/settings.php?section=manageenrols` | `enrol_marketplace` precisa estar habilitado, senão a compra não vira matrícula. |
| Papéis | `/admin/roles/manage.php` | O papel **Seller** e os `PROHIBIT` de upload. |
| Tarefas agendadas | `/admin/tool/task/scheduledtasks.php` | Renovação de tokens, sincronização de matrículas e aviso de vencimento. |
| Limpar caches | `/admin/purgecaches.php` | Necessário depois de mexer em AMD ou strings de idioma. **Não invalida `styles.css` de plugin** — para isso, suba o `version.php`. |
| Logs | `/report/log/index.php` | Primeiro lugar a olhar quando o webhook não entregar acesso. |

## Linha de comando

Rodam dentro do container, a partir de `<VPS_PATH>/repo`. O `< /dev/null`
importa: `exec -T` lê o stdin até EOF e engoliria o resto de um script.

```bash
# Estado geral: plugins, empresas, ofertas, direitos vigentes
docker compose exec -T moodle \
  php /var/www/html/public/local/marketplace/cli/status.php < /dev/null

# Listar ofertas com id, tipo, preço e situação
docker compose exec -T moodle \
  php /var/www/html/public/local/marketplace/cli/set_offer.php --list < /dev/null

# Alterar todas as ofertas pagas de uma empresa (a gratuita é pulada)
docker compose exec -T moodle \
  php /var/www/html/public/local/marketplace/cli/set_offer.php \
  --company=demo --price=1.00 < /dev/null

# Criar um cenário de teste completo do zero
docker compose exec -T moodle \
  php /var/www/html/public/local/marketplace/cli/seed_demo.php \
  --company=teste --seller=vendedor1 < /dev/null
```

## Cartões de teste do Mercado Pago

| Bandeira | Número | CVV | Validade |
|---|---|---|---|
| Mastercard | `5480 8328 0103 3311` | `123` | `11/30` |
| Visa | `4235 6477 2802 5682` | `123` | `11/30` |
| Elo (débito) | `5067 7667 8388 8311` | `123` | `11/30` |

Titular **`APRO`** força aprovação. CPF `12345678909`, só os 11 dígitos.

Em modo de teste, o checkout usa `purpose=wallet_purchase` e **exige login** —
sem isso o pagador fica sem identidade e o Mercado Pago recusa a compra.

## O que falta

### Provado em 08 e 09/09/2026

- **Split no Mercado Pago** — R$ 5,00 → `application_fee` R$ 1,25, com o valor no extrato das duas contas. Ver `mercadopago-split.md`.
- **Vendedor pessoa física** — não é preciso CNPJ para vender. Ver `docs/adr/0010`.
- **Compra pela vitrine** — webhook chegando sozinho, `feesource = company`, direito e matrícula.
- **Expiração de direito** — o ciclo inteiro, sem esperar mês: aviso, corte e volta ao pagar. Ver `asaas-assinatura.md`.
- **Assinatura recorrente no Asaas** — split em cada ciclo, cancelamento, estorno e reenvio de fatura.

### Sem prova

O código existe, ninguém viu funcionar.

- **Vendedor pessoa jurídica no Mercado Pago** — o caso convencional, e o único que sobrou. Sem risco aparente: o MP aceitou a conta mais restrita.
- **Retentativa de cartão guardado** — quantas vezes o Asaas tenta quando o cartão vence no meio da assinatura, e que status intermediários produz. Exige esperar um ciclo real.
- **Pix e boleto liquidando de verdade** — o sandbox do Asaas não liquida nenhum dos dois; a prova foi feita com cartão fictício.

### Falta construir

- **Página pública de parceria** — captação de leads, decidida mas não feita.
- **Auditoria de vídeo externo** — nada verifica se um curso marcado como externo passou a hospedar vídeo na plataforma. Sem isso, a regra dos 25% depende de boa fé.
- **Fase 3, domínio por vendedor** — o campo existe e é editável; o mapa Host→wwwroot, o nginx por domínio e o certificado no onboarding não.

### Bloqueado por decisão de negócio

- **Fase 5** — cobrança para conteúdo hospedado na plataforma.

## Sem solução técnica

**Assinatura não renova sozinha.** O Mercado Pago não tem recorrência com split —
`preapproval` não aceita `marketplace_fee`, e o Transparente com cartão salvo
exige CVV a cada cobrança. Hoje o aluno compra de novo quando vence, avisado por
e-mail cinco dias antes. A saída é de negócio: aceitar isso, ou montar cobrança
recorrente fora do split com repasse manual.

**Um marketplace por país.** O split só acontece entre contas do mesmo país,
porque a comissão cai na conta da plataforma e uma conta só guarda a moeda do
próprio país. Vendedores de outros países exigem um par de credenciais por país —
a fundação está pronta, falta multiplicar a configuração.
