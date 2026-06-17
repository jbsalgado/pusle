#!/bin/bash

# =============================================================================
# setup-apache-pulse.sh — Instalação inicial do httpd.conf.pulse
# Execute UMA VEZ com sudo para preparar o ambiente de toggle.
# =============================================================================

CONF_DIR="/etc/httpd/conf"
ATUAL="$CONF_DIR/httpd.conf"
PULSE="$CONF_DIR/httpd.conf.pulse"

if [ "$EUID" -ne 0 ]; then
  echo "❌ Execute como root: sudo ./setup-apache-pulse.sh"
  exit 1
fi

if [ -f "$PULSE" ]; then
  echo "✅ $PULSE já existe. Nada a fazer."
  echo "   Execute: sudo ./apache-toggle.sh"
  exit 0
fi

if [ ! -f "$ATUAL" ]; then
  echo "❌ $ATUAL não encontrado. Apache instalado?"
  exit 1
fi

echo "🔧 Gerando $PULSE..."

# Gera o httpd.conf.pulse aplicando as 4 modificações:
sed \
    's|#LoadModule rewrite_module modules/mod_rewrite.so|LoadModule rewrite_module modules/mod_rewrite.so|' \
    "$ATUAL" \
    | sed 's|DocumentRoot "/srv/http"|DocumentRoot "/srv/http/pulse/web"|' \
    | sed 's|<Directory "/srv/http">|<Directory "/srv/http/pulse/web">|' \
    | awk '
        /^<Directory "\/srv\/http\/pulse\/web">/{in_block=1}
        in_block && /AllowOverride None/{sub(/AllowOverride None/, "AllowOverride All"); in_block=0}
        {print}
    ' > "$PULSE"

echo "# Configuracao PULSE DEV - gerada em $(date)" >> "$PULSE"

# Valida antes de confirmar
echo "🔍 Validando arquivo gerado..."
if httpd -t -f "$PULSE" 2>&1; then
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "✅ $PULSE criado com sucesso!"
    echo ""
    echo "📋 Alterações aplicadas:"
    echo "   • mod_rewrite: HABILITADO"
    echo "   • DocumentRoot: /srv/http/pulse/web"
    echo "   • AllowOverride: All (no bloco pulse/web)"
    echo ""
    echo "▶️  Agora execute: sudo ./apache-toggle.sh"
    echo "   → Modo PULSE ativado: URLs serão /vendas/inicio"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
else
    echo "❌ Erro na validação. Removendo arquivo inválido."
    rm -f "$PULSE"
    exit 1
fi
