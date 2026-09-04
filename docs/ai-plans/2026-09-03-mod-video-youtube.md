# mod_video — aula de vídeo do YouTube, só para contas free

> **Situação:** pendente · **Pedido em:** 2026-09-03
> **Origem:** memória `plugin-futuro-mod-video.md` — o pedido nunca virou documento de plano,
> e o que segue é o conteúdo dela, sem acréscimo. **Não é um plano de execução:** é o
> recorte e as decisões já tomadas, para o plano nascer daqui.


**Pedido em 2026-09-03, para planejar depois.** Criar um modulo de atividade
`mod_video`:

- **exclusivo para video do YouTube**, embutido por iframe
- **comportamento parecido com o `mod_page`**: uma atividade simples, com
  conteudo proprio, conclusao por visualizacao, sem nota
- **SO PARA AS CONTAS FREE.** Esta e a fronteira do plugin, e nao um detalhe.

**Por que existe:** hoje uma aula em video vira um `mod_page` com o iframe colado
na descricao. Isso funciona, mas o video nao e um campo - e HTML solto, sem
validacao de URL, sem miniatura e sem nada que o resto do sistema consiga ler.

## SAO DOIS PLUGINS, e nao um com dois modos

| Conta | Plugin | Video |
|---|---|---|
| **free** | `mod_video` | YouTube, por iframe |
| **paga** | **outro plugin, ainda sem nome** | Bunny, com mensalidade **por resolucao** |

**Nao tentar servir os dois no mesmo modulo.** A hospedagem paga cobra por
resolucao e por banda, entao ela precisa de conta de provedor, upload, chave de
API, cobranca por consumo e trava de qualidade. Enfiar isso num modulo cuja
razao de existir e "colar um link do YouTube" acabaria com a simplicidade que
justifica ele.

**Ligacoes que ja existem e devem entrar no plano:**

- **`format_ldg`** (o portal do aluno) embute a atividade por iframe e le
  `cm_info->uservisible` para o cadeado. Um `mod_video` seria o tipo natural de
  aula ali, e ja teria a duracao registrada em `format_ldg_lesson`, por cmid -
  ver [o registro do portal](2026-09-02-format-ldg-e-fechamento-do-tema.md).
- **`local_marketplace\plan::max_resolution_for()` NAO TEM CONSUMIDOR.**
  Verificado em 2026-09-03: so os proprios testes a chamam. Ela devolve 720p,
  1080p ou 4K conforme o preco do ticket, e a [ADR-0005](../adr/0005-trava-de-resolucao-por-ticket.md) segue como Proposta.
  **O consumidor dela e o plugin do Bunny, NAO o `mod_video`.** O `mod_video` e
  YouTube, e o YouTube nem aceita travar resolucao pela URL do embed - o
  parametro `vq` nunca foi suportado e o player decide sozinho pela banda. Ou
  seja: a trava de resolucao so faz sentido onde nos hospedamos o arquivo.

**Como aplicar quando for a hora:**

- O esqueleto vem do **MDLCode Wizard**, tipo `mod`, e quem roda e o usuario -
  ver o wizard do VSCode.
- Worktree propria (`cf new`), nao junto de outro assunto.
- O componente sai `mod_video`. Conferir antes se o nome nao colide com plugin do
  diretorio do Moodle.
- **Decidir no plano como o formato de curso descobre o tipo de video.** O
  `format_ldg` embute qualquer atividade pela `view.php` dela; se um dia as duas
  fontes coexistirem no mesmo curso, ele nao pode ter que saber a diferenca -
  quem sabe e cada modulo.
