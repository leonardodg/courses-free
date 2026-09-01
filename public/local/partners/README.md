# local_partners

Captação de empresas parceiras: a landing pública, o formulário de candidatura e
a fila que o administrador aprova.

**Candidatura não é empresa — é um pedido.** A distinção existe porque criar
empresa cria uma **categoria de curso**, que é objeto global do site e aparece na
árvore que todo usuário vê. Por isso a plataforma não tem auto-atendimento: esta
tabela é a fila, e alguém decide.

## Para que serve

```
visitante → landing → candidatura → [confirma e-mail] → fila → aprovação → empresa
```

A aprovação não cria categoria, papel nem membro por conta própria: ela chama
`\local_marketplace\api::create_company()`, que já faz tudo isso. Aqui só se
valida o estado, escolhe o atalho, grava o plano e notifica.

## Dependências

| Depende de | Por quê |
|---|---|
| `local_marketplace` (2026083110+) | a landing exibe planos, e a aprovação cria a empresa |

**Não depende do `theme_ldg`**, de propósito. A dependência é do *tema* para cá,
e é opcional: o tema chama `\local_partners\landing::is_enabled()` por
`class_exists()`, e cai no marketing do Moove quando este plugin não existe.
Assim o `moodle-plugin-ci` consegue instalar o tema sozinho.

## O que precisa configurar

`/admin/settings.php?section=local_partners_settings`

| Configuração | Padrão | O que faz |
|---|---|---|
| `enablelanding` | ligado | liga a landing pública |
| `frontpagemode` | — | usa a landing como home para visitante anônimo |
| `requireemailconfirmation` | ligado | exige confirmar o e-mail antes de entrar na fila |
| `enablerecaptcha` | desligado | usa o reCAPTCHA do core neste formulário |
| `maxperhour` | `3` | limite de candidaturas por IP por hora |
| `unconfirmedretentiondays` | `7` | prazo até apagar candidatura nunca confirmada; `0` desliga |

### Para a landing virar a home

Precisa de **duas** coisas, e a segunda costuma ser esquecida:

1. `local_partners/frontpagemode` ligado aqui.
2. `theme_ldg` como tema do site — é ele que sobrescreve `layout/frontpage.php`.

Com outro tema a landing continua existindo em `/local/partners/index.php`, mas
`/` mostra a frontpage padrão.

**`enablemyhome` desligado quebra isto**, e o sintoma engana: o core redireciona
o visitante anônimo para fora de `/` **antes** de qualquer código de tema rodar,
então a landing nunca chega a ser renderizada e parece que o plugin não funciona.

### Para a confirmação de e-mail funcionar

O site precisa **conseguir enviar e-mail** (SMTP configurado em
`/admin/settings.php?section=outgoingmailconfig`). Com SMTP quebrado e
`requireemailconfirmation` ligado, **a fila trava inteira**: toda candidatura
anônima fica em `unconfirmed` e ninguém recebe o link.

### Para o reCAPTCHA funcionar

Precisa das chaves **do site** (`$CFG->recaptchapublickey` e
`recaptchaprivatekey`, em `/admin/settings.php?section=manageauths`). O
interruptor daqui só decide se *este* formulário usa o captcha do core — ele não
reimplementa nada. Existe porque as chaves são globais: sem o interruptor local,
desligar o captcha aqui obrigaria a apagar chave que a tela de cadastro de
usuário do Moodle também usa.

### Capacidade

`local/partners:review` — ver a fila e decidir. `archetypes` vazio de propósito:
aprovar cria objeto global, e isso não pode cair em papel por herança.

## Os três caminhos de envio

É a parte com mais sutileza do plugin.

| Situação | Status inicial | Confirma? | Dono na aprovação |
|---|---|---|---|
| autenticado | `pending` | não | o próprio usuário |
| anônimo, confirmação ligada | `unconfirmed` | sim, por link | conta criada ou casada por e-mail |
| anônimo, confirmação desligada | `pending` | não | conta criada ou casada por e-mail |

**Usuário autenticado tem o e-mail digitado ignorado** — vale o do perfil.
Aceitar outro criaria uma candidatura que parece de terceiro, e o dono da empresa
sai daí.

**`unconfirmed` não entra na fila.** Enquanto o e-mail não for provado, aquilo
não é uma candidatura: é o que alguém digitou. Também não notifica revisor.

**Só `pending` bloqueia duplicidade.** Uma `unconfirmed` não pode bloquear: quem
não recebeu o e-mail precisa poder tentar de novo, e um envio nunca confirmado
trancaria a pessoa para sempre. O reenvio substitui a anterior.

Quando a conta do dono precisa ser criada, ela nasce com senha aleatória
desconhecida e recebe **link de redefinição** — nunca senha em texto no e-mail.

## Anti-robô, em três camadas

Da mais confiável à mais frágil, porque proteção que depende de configuração
estar certa não é proteção:

1. **Sempre ligada** — limite por IP/hora e recusa de `pending` duplicada por
   e-mail ou CNPJ. Não depende de nada externo.
2. **Sempre ligado** — honeypot (campo `fax`, escondido por CSS). Preenchido:
   não grava nada e mostra a página de obrigado, sem dar pista ao robô.
3. **Quando configurado** — reCAPTCHA, e só para visitante anônimo.

Há também limite de tamanho por campo, validado no servidor: sem ele, uma
submissão de 5 000 caracteres virava `dml_write_exception` — HTTP 500 numa página
pública.

## Privacidade

O formulário é público e anônimo, então esta tabela acumula nome, e-mail,
telefone e IP de quem talvez nem exista.

- **Candidatura de visitante anônimo** não tem `userid` e não aparece na
  exportação de ninguém. Quem a remove é a tarefa `purge_unconfirmed` (o que
  nunca foi confirmado) ou o administrador.
- **Candidatura de usuário autenticado** grava `userid` e **é dado pessoal
  dele** — exportável e apagável. Num pedido de exclusão: a não aprovada some
  inteira; a aprovada perde nome, e-mail, telefone, mensagem e IP, mas a linha
  fica, porque existe uma **empresa** criada a partir dela.

## Armadilhas

**`MESSAGE_DEFAULT_LOGGEDIN` não existe no Moodle 5.2** e derruba o upgrade em
`db/messages.php`. Use `MESSAGE_DEFAULT_ENABLED`.

**O elemento `recaptcha` do moodleform não se valida sozinho.** O formulário
precisa chamar `verify()` explicitamente — sem isso o captcha é decorativo, e o
sintoma é não haver sintoma nenhum.

**Aprovar é idempotente por `companyid`.** Duas submissões não podem produzir
duas categorias, e a checagem de estado vem **antes** de qualquer escrita.

## Testes

```bash
docker exec -u 1000:33 -w /var/www/html courses-free-moodle-1 \
  php vendor/bin/phpunit --testsuite local_partners_testsuite
```

29 testes: os três caminhos de envio, a confirmação, a aprovação e a privacidade.
