#!/bin/bash
# Script para configurar VirtualHost no localhost de forma idêntica à VPS

echo "=========================================="
echo "Configurando VirtualHost para localhost"
echo "=========================================="

VHOSTS_FILE="/etc/httpd/conf/extra/httpd-vhosts.conf"
HTTPD_CONF="/etc/httpd/conf/httpd.conf"
HOSTS_FILE="/etc/hosts"

# Verifica se está rodando como root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Este script precisa ser executado com sudo"
    echo "Execute: sudo bash docs/setup-localhost-vhost.sh"
    exit 1
fi

# Backup do arquivo de VirtualHosts
if [ -f "$VHOSTS_FILE" ]; then
    cp "$VHOSTS_FILE" "${VHOSTS_FILE}.backup.$(date +%Y%m%d_%H%M%S)"
    echo "✅ Backup criado: ${VHOSTS_FILE}.backup.*"
fi

# Adiciona configuração do VirtualHost para Pulse
if ! grep -q "PULSE - LOCALHOST" "$VHOSTS_FILE" 2>/dev/null; then
    cat >> "$VHOSTS_FILE" << 'EOF'

# ==========================================
# PULSE - LOCALHOST (Desenvolvimento)
# Configurado para funcionar de forma idêntica à VPS
# ==========================================

<VirtualHost *:80>
    ServerName pulse.localhost
    ServerAlias localhost
    DocumentRoot "/srv/http/pulse/web"

    <Directory "/srv/http/pulse/web">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        RewriteEngine on
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . index.php
    </Directory>

    ErrorLog /var/log/httpd/pulse-localhost-error.log
    CustomLog /var/log/httpd/pulse-localhost-access.log combined
</VirtualHost>
EOF
    echo "✅ VirtualHost adicionado ao $VHOSTS_FILE"
else
    echo "ℹ️  VirtualHost já existe em $VHOSTS_FILE"
fi

# Descomenta a linha Include no httpd.conf
if grep -q "^#Include conf/extra/httpd-vhosts.conf" "$HTTPD_CONF"; then
    sed -i 's|^#Include conf/extra/httpd-vhosts.conf|Include conf/extra/httpd-vhosts.conf|' "$HTTPD_CONF"
    echo "✅ Linha Include descomentada no $HTTPD_CONF"
elif grep -q "^Include conf/extra/httpd-vhosts.conf" "$HTTPD_CONF"; then
    echo "ℹ️  Linha Include já está ativa no $HTTPD_CONF"
else
    echo "⚠️  Não foi possível encontrar a linha Include no $HTTPD_CONF"
    echo "   Adicione manualmente: Include conf/extra/httpd-vhosts.conf"
fi

# Adiciona pulse.localhost ao /etc/hosts
if ! grep -q "pulse.localhost" "$HOSTS_FILE"; then
    echo "127.0.0.1 pulse.localhost" >> "$HOSTS_FILE"
    echo "✅ pulse.localhost adicionado ao $HOSTS_FILE"
else
    echo "ℹ️  pulse.localhost já existe no $HOSTS_FILE"
fi

# Testa a configuração do Apache
echo ""
echo "=========================================="
echo "Testando configuração do Apache..."
echo "=========================================="
if httpd -t 2>/dev/null || apache2ctl -t 2>/dev/null; then
    echo "✅ Configuração do Apache está válida"
    echo ""
    echo "=========================================="
    echo "Próximos passos:"
    echo "=========================================="
    echo "1. Reinicie o Apache:"
    echo "   sudo systemctl reload httpd"
    echo ""
    echo "2. Acesse a aplicação em:"
    echo "   http://pulse.localhost/vendas/inicio"
    echo "   ou"
    echo "   http://localhost/vendas/inicio"
    echo ""
    echo "3. As URLs funcionarão de forma idêntica à VPS:"
    echo "   - http://pulse.localhost/vendas/inicio"
    echo "   - https://pulse-v1.catalogo.cloud/vendas/inicio"
    echo "=========================================="
else
    echo "❌ Erro na configuração do Apache!"
    echo "   Verifique os logs: /var/log/httpd/error_log"
    exit 1
fi

