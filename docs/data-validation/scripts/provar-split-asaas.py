#!/usr/bin/env python3
"""Prova o split de PIX no sandbox do Asaas, do zero, sem passar pelo Moodle.

Por que existe: o split e o coracao do modelo de marketplace e nunca foi
exercitado neste projeto - no Mercado Pago vendedor e plataforma eram a mesma
conta, entao o marketplace_fee nao transferiu nada. Aqui a prova e explicita:
duas contas distintas, e os dois extratos conferidos no fim.

Como usar:

    export ASAAS_PLATFORM_API_KEY='$aact_hmlg_...'   # conta PJ do sandbox
    python3 provar_split.py

A conta da PLATAFORMA precisa ser pessoa juridica: o Asaas recusa criacao de
subconta a partir de conta PF com
"Contas de pessoa fisica (CPF) nao podem criar subcontas no Asaas".

O VENDEDOR e criado aqui como subconta, com CPF sintetico de digito valido.
Isso e artificio de sandbox: em PRODUCAO o vendedor tem conta propria e
independente, e so cola a chave dele - nao ha subconta no caminho.
"""

import json
import os
import random
import sys
import urllib.error
import urllib.request
from datetime import date, timedelta

BASE = "https://api-sandbox.asaas.com/v3"
PLATFORM_KEY = os.environ.get("ASAAS_PLATFORM_API_KEY", "").strip()
SITE = os.environ.get("MOODLE_SITE", "https://courses.leodg.dev").strip()
WEBHOOK_TOKEN = os.environ.get("ASAAS_WEBHOOK_TOKEN", "").strip()
PRICE = 100.00
COMMISSION = 25.0

if not PLATFORM_KEY:
    sys.exit("defina ASAAS_PLATFORM_API_KEY com a chave da conta PJ do sandbox")


def call(method, path, key, body=None):
    """Uma chamada a API, com o erro do Asaas legivel."""
    request = urllib.request.Request(
        BASE + path,
        method=method,
        data=json.dumps(body).encode() if body is not None else None,
        headers={
            "access_token": key,
            "Content-Type": "application/json",
            "User-Agent": "courses-free split proof",
        },
    )
    try:
        with urllib.request.urlopen(request, timeout=30) as response:
            return json.loads(response.read() or "{}")
    except urllib.error.HTTPError as error:
        detail = error.read().decode(errors="replace")
        try:
            errors = json.loads(detail).get("errors", [])
            detail = " | ".join(
                f"{e.get('code')}: {e.get('description')}" for e in errors
            ) or detail
        except ValueError:
            pass
        sys.exit(f"\n  FALHOU {method} {path}\n  HTTP {error.code} - {detail}\n")


def cpf_sintetico():
    """CPF com digito verificador valido, para o sandbox aceitar.

    Numero inventado de proposito: nao pertence a ninguem e so existe no
    ambiente de homologacao.
    """
    base = [random.randint(0, 9) for _ in range(9)]
    for peso_inicial in (10, 11):
        total = sum(d * (peso_inicial - i) for i, d in enumerate(base))
        digito = (total * 10) % 11
        base.append(0 if digito == 10 else digito)
    return "".join(map(str, base))


def passo(numero, texto):
    print(f"\n[{numero}] {texto}")


# --- 1. A plataforma -------------------------------------------------------

passo(1, "Conferindo a conta da plataforma")
me = call("GET", "/myAccount", PLATFORM_KEY)
if me.get("personType") != "JURIDICA":
    sys.exit(
        f"  a conta e {me.get('personType')} e precisa ser JURIDICA para criar subconta"
    )
platform_wallet = call("GET", "/wallets?limit=1", PLATFORM_KEY)["data"][0]["id"]
print(f"  {me.get('name')} | CNPJ {me.get('cpfCnpj')} | carteira {platform_wallet}")

# --- 2. O vendedor ---------------------------------------------------------

passo(2, "Criando a subconta do vendedor")
sufixo = random.randint(10000, 99999)
corpo = {
    "name": f"Vendedor Teste {sufixo}",
    "email": f"vendedor.teste.{sufixo}@example.com",
    "loginEmail": f"vendedor.teste.{sufixo}@example.com",
    "cpfCnpj": cpf_sintetico(),
    "birthDate": "1990-05-10",
    "mobilePhone": "48999998888",
    "incomeValue": 5000,
    "address": "Rua Teste",
    "addressNumber": "100",
    "province": "Centro",
    "postalCode": "88058400",
    # O site destrava o callback.successUrl: o Asaas exige que a URL de retorno
    # use o MESMO dominio cadastrado na conta que emite a cobranca.
    "site": SITE,
}
if WEBHOOK_TOKEN:
    corpo["webhooks"] = [{
        "name": "Moodle CoursesFree",
        "url": f"{SITE}/payment/gateway/asaas/webhook.php",
        "email": me.get("email"),
        "enabled": True,
        "interrupted": False,
        "apiVersion": 3,
        "authToken": WEBHOOK_TOKEN,
        "sendType": "SEQUENTIALLY",
        "events": ["PAYMENT_RECEIVED", "PAYMENT_CONFIRMED"],
    }]

vendedor = call("POST", "/accounts", PLATFORM_KEY, corpo)
seller_key = vendedor.get("apiKey") or (vendedor.get("accessToken") or {}).get("apiKey")
seller_wallet = vendedor["walletId"]
if not seller_key:
    sys.exit("  a resposta nao trouxe a apiKey - ela so vem UMA vez, na criacao")
print(f"  {vendedor['name']} | carteira {seller_wallet}")
print(f"  chave do vendedor: {seller_key}")
print("  GUARDE ESSA CHAVE: o Asaas so a devolve na criacao.")

if seller_wallet == platform_wallet:
    sys.exit("  as carteiras sao iguais - o split nao teria como ser provado")

# --- 3. O comprador --------------------------------------------------------

passo(3, "Criando o aluno na conta do VENDEDOR")
# O Asaas recusa cobranca de cliente sem CPF/CNPJ, embora aceite criar o
# cliente sem ele. Por isso o documento vai desde a criacao.
aluno = call("POST", "/customers", seller_key, {
    "name": "Aluno Teste",
    "email": f"aluno.{sufixo}@example.com",
    "cpfCnpj": cpf_sintetico(),
})
print(f"  {aluno['name']} | {aluno['id']}")

# --- 4. A cobranca com split ----------------------------------------------

passo(4, f"Cobranca PIX de R$ {PRICE:.2f} com split de {COMMISSION}% para a plataforma")
# A chave usada aqui e a do VENDEDOR: e o que faz o dinheiro cair na conta
# dele, aparecer o nome dele no payload do Pix e ser ele quem emite a nota.
cobranca = call("POST", "/payments", seller_key, {
    "customer": aluno["id"],
    "billingType": "PIX",
    "value": PRICE,
    "dueDate": (date.today() + timedelta(days=3)).isoformat(),
    "description": "Curso Demo - prova de split",
    "externalReference": f"prova-split-{sufixo}",
    "callback": {
        "successUrl": f"{SITE}/payment/gateway/asaas/return.php?ref=prova-split-{sufixo}",
        "autoRedirect": True,
    },
    "split": [{"walletId": platform_wallet, "percentualValue": COMMISSION}],
})
print(f"  {cobranca['id']} | status {cobranca['status']}")
print(f"  bruto R$ {cobranca['value']:.2f} | liquido R$ {cobranca.get('netValue', 0):.2f}")
print(f"  fatura: {cobranca['invoiceUrl']}")

qr = call("GET", f"/payments/{cobranca['id']}/pixQrCode", seller_key)
payload = qr.get("payload", "")
print(f"  QR Code emitido: {'sim' if payload else 'NAO'}")
if payload:
    # O nome dentro do payload e o do RECEBEDOR. E a prova visivel de quem o
    # banco do comprador enxerga como vendedor - e quem, portanto, emite a nota.
    print(f"  recebedor no payload do Pix: {payload[70:110]}")

# --- 5. Pagamento ----------------------------------------------------------

passo(5, "Confirmando o pagamento (baixa manual do sandbox)")
pago = call("POST", f"/payments/{cobranca['id']}/receiveInCash", seller_key, {
    "paymentDate": date.today().isoformat(),
    "value": PRICE,
    "notifyCustomer": False,
})
print(f"  status {pago['status']} | liquido R$ {pago.get('netValue', 0):.2f}")

# --- 6. O SPLIT ------------------------------------------------------------

passo(6, "O SPLIT")
detalhe = call("GET", f"/payments/{cobranca['id']}", seller_key)
splits = detalhe.get("split") or []
if not splits:
    print("  NENHUM SPLIT NA COBRANCA - a prova falhou")
else:
    for s in splits:
        print(f"  carteira {s.get('walletId')}")
        print(f"    situacao ......... {s.get('status')}")
        print(f"    percentual ....... {s.get('percentualValue')}%")
        print(f"    valor ............ R$ {s.get('totalValue') or s.get('fixedValue') or 0}")
        print(f"    e a plataforma? .. {'SIM' if s.get('walletId') == platform_wallet else 'nao'}")

passo(7, "Saldos")
saldo_v = call("GET", "/finance/balance", seller_key)
saldo_p = call("GET", "/finance/balance", PLATFORM_KEY)
print(f"  vendedor ... R$ {saldo_v.get('balance', 0)}")
print(f"  plataforma . R$ {saldo_p.get('balance', 0)}")

print("\n" + "=" * 68)
print("Se o split acima aparecer com a carteira da PLATAFORMA e um valor,")
print("o coracao do modelo esta provado pela primeira vez neste projeto.")
print("=" * 68)
print(f"\nPara ligar no Moodle, vincule a conta do vendedor com esta chave:\n  {seller_key}")
