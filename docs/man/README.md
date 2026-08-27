# Páginas de manual

| Página | Comando |
|---|---|
| [`cf.1`](cf.1) | `man cf` |

## Instalar

```bash
sudo install -m 644 docs/man/cf.1 /usr/local/share/man/man1/cf.1
sudo mandb -q
```

Sem `sudo`:

```bash
mkdir -p ~/.local/share/man/man1
install -m 644 docs/man/cf.1 ~/.local/share/man/man1/cf.1
mandb -q ~/.local/share/man
```

## Conferir antes de commitar

```bash
groff -man -t -Tascii -ww docs/man/cf.1 >/dev/null   # sem avisos
MANWIDTH=72 man -l docs/man/cf.1                     # cabe em terminal estreito
```

A primeira linha do arquivo (`'\" t`) é obrigatória: manda o `man` rodar o
preprocessador `tbl`, sem o qual as tabelas não renderizam.
