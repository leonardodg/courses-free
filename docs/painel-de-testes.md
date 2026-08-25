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
| Minhas assinaturas | `/local/marketplace/mysubscriptions.php` | Visão do aluno: vencimento, pagamentos, renovar e cancelar. |
| Categorias e cursos | `/course/index.php` | A categoria da empresa traz o painel no menu lateral. |

## Pagamento

| Tela | Caminho | O que verificar |
|---|---|---|
| Aplicação Mercado Pago | `/admin/settings.php?section=paymentgatewaymercadopago` | Client ID, secret, país, comissão e modo de teste. Nível site. |
| Vínculo do vendedor | `/payment/manage_gateway.php?accountid=1&gateway=mercadopago` | Vincular, trocar e desvincular. Mostra a moeda detectada. |
| Contas de pagamento | `/payment/accounts.php` | Lista do core. Só mostra contas no contexto do sistema — a da empresa vive na categoria e **não aparece aqui**. |
| Gateways habilitados | `/admin/settings.php?section=managepaymentgateways` | Liga e desliga o Mercado Pago no site. |

## Administração

| Tela | Caminho | O que verificar |
|---|---|---|
| Empresas | `/local/marketplace/admin/companies.php` | Criar, editar e gerenciar vendedores. |
| Visão geral dos plugins | `/admin/plugins.php` | Versão instalada dos cinco plugins do projeto. |
| Métodos de inscrição | `/admin/settings.php?section=manageenrols` | `enrol_marketplace` precisa estar habilitado, senão a compra não vira matrícula. |
| Papéis | `/admin/roles/manage.php` | O papel **Seller** e os `PROHIBIT` de upload. |
| Tarefas agendadas | `/admin/tool/task/scheduledtasks.php` | Renovação de tokens, sincronização de matrículas e aviso de vencimento. |
| Limpar caches | `/admin/purgecaches.php` | Necessário depois de mexer em AMD ou strings de idioma. |
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

### Sem prova

O código existe, ninguém viu funcionar.

- **Split de 25%** — vendedor e marketplace foram a mesma conta no teste, então o `marketplace_fee` não transferiu nada. Precisa de uma segunda conta real.
- **Pix e boleto** — Pix exige chave registrada na conta do vendedor. São os caminhos assíncronos, onde o webhook é a única fonte da verdade.
- **Expiração de direito** — ofertas com prazo nunca chegaram ao vencimento num teste.

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
