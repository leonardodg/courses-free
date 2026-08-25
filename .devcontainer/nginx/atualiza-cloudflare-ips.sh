#!/usr/bin/env bash
#
# Regenera cloudflare-realip.conf a partir das faixas publicadas pela Cloudflare.
#
# Por que isto existe: as faixas mudam de tempos em tempos. Uma faixa nova que
# nao esteja no arquivo faz o nginx tratar aquela requisicao como vinda da
# propria Cloudflare - ou seja, o Moodle volta a gravar o IP do CDN no log,
# so para parte dos visitantes. O sintoma e sutil e ninguem percebe.
#
# Uso:
#   ./atualiza-cloudflare-ips.sh            # grava ao lado deste script
#   ./atualiza-cloudflare-ips.sh /destino/  # grava em outro lugar
#
# Depois de rodar na VPS: sudo nginx -t && sudo systemctl reload nginx
#
set -euo pipefail

DEST="${1:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)}/cloudflare-realip.conf"
TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT

fetch() {
    # --fail para o script parar num 404/500 em vez de gravar pagina de erro
    # como se fosse faixa de IP.
    curl -fsS --max-time 20 "$1"
}

V4="$(fetch https://www.cloudflare.com/ips-v4)"
V6="$(fetch https://www.cloudflare.com/ips-v6)"

if [ -z "$V4" ] || [ -z "$V6" ]; then
    echo "ERRO: resposta vazia da Cloudflare; arquivo atual preservado." >&2
    exit 1
fi

{
    echo "# GERADO por atualiza-cloudflare-ips.sh - nao edite a mao."
    echo "# Fonte: https://www.cloudflare.com/ips-v4 e ips-v6"
    echo "# Atualizado em: $(date -u '+%Y-%m-%d %H:%M UTC')"
    echo "#"
    echo "# Sem isto o nginx registra o IP da Cloudflare como se fosse o do"
    echo "# visitante, e o Moodle grava esse IP em todo log de acesso."
    echo ""
    # printf '%s\n' garante a quebra de linha final: os arquivos da Cloudflare"
    # nao terminam com newline, e sem isto a ultima faixa IPv4 cola na primeira
    # IPv6, produzindo uma diretiva invalida.
    printf '%s\n' "$V4" | sed 's/^/set_real_ip_from /; s/$/;/'
    printf '%s\n' "$V6" | sed 's/^/set_real_ip_from /; s/$/;/'
    echo ""
    echo "# CF-Connecting-IP e o header que a Cloudflare preenche com o IP real."
    echo "# X-Forwarded-For nao serve sozinho: o cliente pode forjar a cadeia."
    echo "real_ip_header CF-Connecting-IP;"
    echo "real_ip_recursive on;"
} > "$TMP"

mv "$TMP" "$DEST"
trap - EXIT

echo "Gravado: $DEST"
grep -c '^set_real_ip_from' "$DEST" | xargs echo "Faixas:"
