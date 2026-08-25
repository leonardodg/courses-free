# Documentação

| Documento | O que traz |
|---|---|
| [`../CLAUDE.md`](../CLAUDE.md) | **Comece por aqui.** Contexto para agentes de IA: decisões fechadas, restrições externas, erros já cometidos e como não repeti-los. Carregado automaticamente pelo Claude Code. |
| [Guia do desenvolvedor](guia-desenvolvedor.md) | Modelo de dados com diagrama ER, tabelas campo a campo, hierarquia da comissão, domínio por vendedor, configuração e armadilhas. |
| [Painel de testes](painel-de-testes.md) | Todos os caminhos de gestão, comandos de CLI, cartões de teste do Mercado Pago. |
| [Decisões de arquitetura](decisoes-marketplace.md) | Por que não IOMAD, e as evidências de cada escolha. |
| [Estado e próximas fases](estado-e-proximas-fases.md) | O que foi validado em produção, o que ninguém viu funcionar, e o que não existe. |

## Para quem chega agora

Três coisas que economizam tempo:

**O split nunca foi exercitado.** Vendedor e marketplace foram a mesma conta no
único teste real, então os 25% são código não testado — e são o modelo de
negócio. Precisa de uma segunda conta Mercado Pago.

**Não há transpilador de JavaScript.** `amd/src` é AMD de verdade, não ES6.
Introduzir `import`/`export` quebra o módulo com "No define call".

**O `config.php` da VPS é gerado a cada deploy.** Ajuste de máquina vai em
`config-local.php`, que o deploy nunca toca.
