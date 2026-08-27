# Padrão de código

O que o CI cobra, e as convenções que não dá para automatizar.

## O que já está em outro lugar

O padrão de código do Moodle e como rodar o `phpcs` estão na seção
"Testes e padrão de código" de [`../dev/guia-desenvolvedor.md`](../dev/guia-desenvolvedor.md).

## Regras que já custaram tempo

**Leia o total do `phpcs`.** `tail -3` na saída esconde o relatório: já se
reportou "zero violações" com 16 erros presentes, e o CI reprovou.

```bash
… phpcs --standard=moodle --report=summary <caminho> | grep -E "A TOTAL OF"
```

**Strings de idioma têm ordem alfabética obrigatória.** Inserir por âncora quebra
o `phpcs`; reordene o arquivo inteiro depois de acrescentar. Os idiomas cobertos
são `en`, `pt_br` e `es`.

**Comentários e mensagens de commit em português, sem acentos.** Prosa de
documentação leva acentuação normal.

**Nada de regex cego em comentários.** Um padrão que capitaliza `// texto`
também pega a segunda linha de comentários multi-linha — já corrompeu o cabeçalho
GPL de 74 arquivos. Corrija por arquivo e linha exatos.
