#!/bin/bash

# =============================================================================
# apache-toggle.sh — Alterna entre configuração padrão e PULSE (dev local)
#
# MODO PULSE:   DocumentRoot=/srv/http/pulse/web  → URLs limpas: /vendas/inicio
# MODO PADRÃO:  DocumentRoot=/srv/http            → URLs originais do sistema
# =============================================================================

CONF_DIR="/etc/httpd/conf"
ATUAL="$CONF_DIR/httpd.conf"
PULSE="$CONF_DIR/httpd.conf.pulse"
BACKUP="$CONF_DIR/httpd.conf.backup"

# Arquivo-fonte do httpd.conf.pulse (gerado por este script se não existir)
PULSE_SOURCE_TMP="/tmp/httpd.conf.pulse.generated"

# ---------------------------------------------------------------------------
# Verifica se é root
# ---------------------------------------------------------------------------
if [ "$EUID" -ne 0 ]; then
  echo "❌ Por favor, execute como root (sudo ./apache-toggle.sh)."
  exit 1
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "   Gerenciador de Configuração do Apache  "
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# ---------------------------------------------------------------------------
# Garante que o httpd.conf.pulse existe
# Se não existir, gera automaticamente a partir do httpd.conf atual/backup
# ---------------------------------------------------------------------------
gerar_pulse() {
    local BASE_CONF="$1"
    echo "🔧 Gerando httpd.conf.pulse a partir de: $BASE_CONF"

    # Aplica as 4 modificações necessárias para modo PULSE:
    # 1. Habilita mod_rewrite
    # 2. Muda DocumentRoot para /srv/http/pulse/web
    # 3. Muda o bloco <Directory> correspondente
    # 4. Habilita AllowOverride All no bloco do DocumentRoot
    sed \
        's|#LoadModule rewrite_module modules/mod_rewrite.so|LoadModule rewrite_module modules/mod_rewrite.so|' \
        "$BASE_CONF" \
        | sed 's|DocumentRoot "/srv/http"|DocumentRoot "/srv/http/pulse/web"|' \
        | sed 's|<Directory "/srv/http">|<Directory "/srv/http/pulse/web">|' \
        | awk '
            /^<Directory "\/srv\/http\/pulse\/web">/{in_block=1}
            in_block && /AllowOverride None/{sub(/AllowOverride None/, "AllowOverride All"); in_block=0}
            {print}
        ' > "$PULSE_SOURCE_TMP"

    echo "# Configuracao PULSE DEV - gerada em $(date)" >> "$PULSE_SOURCE_TMP"
    mv "$PULSE_SOURCE_TMP" "$PULSE"
    echo "✅ httpd.conf.pulse gerado com sucesso."
}

# ---------------------------------------------------------------------------
# LÓGICA DO TOGGLE
# ---------------------------------------------------------------------------
if [ -f "$PULSE" ]; then
    # httpd.conf.pulse existe → Pulse está INATIVO. Ativamos.
    echo ""
    echo "🔄 Estado atual: PADRÃO ativo."
    echo "🚀 Ativando configuração PULSE..."
    echo "   DocumentRoot será: /srv/http/pulse/web"
    echo ""

    mv "$ATUAL" "$BACKUP"
    mv "$PULSE" "$ATUAL"

    TIPO="PULSE"
    URL_ACESSO="http://localhost/vendas/inicio"

elif [ -f "$BACKUP" ]; then
    # httpd.conf.backup existe → Pulse está ATIVO. Voltamos ao backup.
    echo ""
    echo "🔄 Estado atual: PULSE ativo."
    echo "🔙 Restaurando configuração PADRÃO (Backup)..."
    echo ""

    mv "$ATUAL" "$PULSE"
    mv "$BACKUP" "$ATUAL"

    TIPO="PADRÃO"
    URL_ACESSO="http://localhost/pulse/web/"

else
    # Nenhum dos dois arquivos existe.
    # Isso acontece na primeira execução: geramos o PULSE e fazemos o toggle.
    echo ""
    echo "⚠️  Primeira execução detectada (sem .pulse nem .backup)."
    echo "🔧 Gerando configuração PULSE automaticamente..."
    echo ""

    if [ ! -f "$ATUAL" ]; then
        echo "❌ Erro: $ATUAL não encontrado. O Apache está instalado?"
        exit 1
    fi

    gerar_pulse "$ATUAL"

    # Agora faz o toggle: atual → backup, pulse → atual
    mv "$ATUAL" "$BACKUP"
    mv "$PULSE" "$ATUAL"

    TIPO="PULSE"
    URL_ACESSO="http://localhost/vendas/inicio"
fi

# ---------------------------------------------------------------------------
# Valida a config antes de reiniciar
# ---------------------------------------------------------------------------
echo "🔍 Validando configuração do Apache..."
if ! httpd -t 2>&1; then
    echo ""
    echo "❌ Configuração inválida! Revertendo para evitar downtime..."
    # Reverte a troca
    if [ "$TIPO" = "PULSE" ]; then
        mv "$ATUAL" "$PULSE"
        mv "$BACKUP" "$ATUAL"
    else
        mv "$ATUAL" "$BACKUP"
        mv "$PULSE" "$ATUAL"
    fi
    echo "✅ Revertido. Apache não foi reiniciado."
    exit 1
fi
echo "✅ Configuração válida."
echo ""

# ---------------------------------------------------------------------------
# Reinicia o serviço
# ---------------------------------------------------------------------------
echo "🔄 Reiniciando o Apache (httpd)..."
systemctl restart httpd

if [ $? -eq 0 ]; then
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "✅ Apache rodando no modo: $TIPO"
    echo "🌐 Acesse: $URL_ACESSO"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
else
    echo "❌ Erro ao reiniciar o Apache. Verifique: journalctl -xe --unit httpd"
    exit 1
fi
