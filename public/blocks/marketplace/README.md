# block_marketplace

As assinaturas do aluno, no Dashboard.

## Para que serve

Existe por causa de uma limitação real: **não há débito automático** neste
projeto — o Mercado Pago não faz recorrência com split, e o Asaas ainda não foi
habilitado para isso. Então o aluno precisa **agir** para continuar assinando.

O e-mail de aviso chega uma vez e some na caixa de entrada. O bloco fica.

## Dependências

| Depende de | Por quê |
|---|---|
| `local_marketplace` (qualquer versão) | lê os direitos de acesso do aluno |

## O que precisa configurar

**Nada.** Adicione o bloco ao Dashboard — para todos os usuários de uma vez em
`/my/indexsys.php`.

## O que ele mostra, e o que não mostra

Mostra **só o que exige atenção ou decisão**: assinatura vigente com a data de
vencimento, e o que está perto de vencer ou já venceu.

**O histórico de pagamentos não fica aqui**, de propósito. Numa barra lateral,
uma lista que cresce a cada mês empurraria para baixo justamente o que precisa
ser visto. O histórico tem página própria.

## Armadilha

**Assinatura aqui é acesso com prazo**, e não cobrança recorrente. Um aluno cujo
prazo venceu perde o acesso e precisa comprar de novo — não há tentativa
automática de cobrança, e o bloco é o principal lugar onde ele descobre isso a
tempo.
