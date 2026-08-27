# Modelo de dados

| Documento | O que cobre |
|---|---|
| [`marketplace.md`](marketplace.md) | as tabelas dos cinco plugins, campo a campo, e o diagrama ER |

A regra central: **o direito de acesso é a única fonte da verdade**. Matrícula e
liberação de seção consultam `local_marketplace_entitlement`; ninguém lê a venda
para decidir acesso.

A credencial de pagamento **não vive aqui** — fica em `payment_gateways.config`
do core. A tabela `local_marketplace_mpaccount` existiu e foi removida
justamente por criar uma segunda fonte de verdade para credencial financeira.
