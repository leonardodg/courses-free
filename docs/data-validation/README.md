# Validação

Como se verifica que o sistema funciona — e o que ainda não foi visto funcionar.

| Documento | O que cobre |
|---|---|
| [`painel-de-testes.md`](painel-de-testes.md) | caminhos de gestão, CLI, cartões de teste e o que falta provar |
| [`asaas-sandbox.md`](asaas-sandbox.md) | provar o **split** no Asaas: contas, webhook, script e passo a passo com `curl` |

Scripts em [`scripts/`](scripts/). Credenciais **nunca** entram aqui: ficam em
`.devcontainer/secrets/`, coberto pelo `.gitignore`. Este repositório está no
GitHub, e chave de API em markdown versionado é um caminho sem volta.

A distinção que importa neste projeto:

- **sem prova** — o código existe, ninguém viu funcionar
- **falta construir** — decidido, não feito
- **bloqueado** — parado por decisão de negócio

O split de 25% continua **sem prova**: vendedor e marketplace foram a mesma conta
no teste, então o `marketplace_fee` não transferiu nada. É o coração do modelo.
