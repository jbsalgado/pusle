#!/bin/bash
# ==========================================
# Script de Sincronização Apache Local com Remoto
# ==========================================
# 
# Este script ajusta o httpd.conf local para ficar igual ao remoto,
# mas mantém um VirtualHost específico para o Pulse com AllowOverride All.
#
# Uso: sudo ./sincronizar-apache-local.sh
# ==========================================

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Erro: Este script precisa ser executado com sudo${NC}"
    exit 1
fi

HTTPD_CONF="/etc/httpd/conf/httpd.conf"
HTTPD_VHOSTS="/etc/httpd/conf/extra/httpd-vhosts.conf"

echo -e "${BLUE}==========================================${NC}"
echo -e "${BLUE}Sincronização Apache Local com Remoto${NC}"
echo -e "${BLUE}==========================================${NC}"
echo ""

# 1. Backup do httpd.conf
echo -e "${YELLOW}1. Criando backup do httpd.conf...${NC}"
if [ ! -f "${HTTPD_CONF}.backup-$(date +%Y%m%d)" ]; then
    cp "$HTTPD_CONF" "${HTTPD_CONF}.backup-$(date +%Y%m%d)"
    echo -e "${GREEN}✓ Backup criado: ${HTTPD_CONF}.backup-$(date +%Y%m%d)${NC}"
else
    echo -e "${GREEN}✓ Backup já existe${NC}"
fi

# 2. Ajustar MPM para event (igual ao remoto)
echo ""
echo -e "${YELLOW}2. Ajustando MPM para event...${NC}"
sed -i 's/^#LoadModule mpm_event_module/LoadModule mpm_event_module/' "$HTTPD_CONF"
sed -i 's/^LoadModule mpm_prefork_module/#LoadModule mpm_prefork_module/' "$HTTPD_CONF"
sed -i 's/^LoadModule mpm_worker_module/#LoadModule mpm_worker_module/' "$HTTPD_CONF"
echo -e "${GREEN}✓ MPM ajustado para event${NC}"

# 3. Adicionar Listen 443
echo ""
echo -e "${YELLOW}3. Adicionando Listen 443...${NC}"
if ! grep -q "^Listen 443" "$HTTPD_CONF"; then
    sed -i '/^Listen 80/a Listen 443' "$HTTPD_CONF"
    echo -e "${GREEN}✓ Listen 443 adicionado${NC}"
else
    echo -e "${GREEN}✓ Listen 443 já existe${NC}"
fi

# 4. Habilitar módulos adicionais do remoto
echo ""
echo -e "${YELLOW}4. Habilitando módulos adicionais...${NC}"

# Módulos que devem estar habilitados (igual ao remoto)
MODULES=(
    "socache_shmcb_module"
    "proxy_module"
    "proxy_fcgi_module"
    "ssl_module"
    "http2_module"
    "logio_module"
)

for module in "${MODULES[@]}"; do
    if grep -q "^#LoadModule ${module}" "$HTTPD_CONF"; then
        sed -i "s/^#LoadModule ${module}/LoadModule ${module}/" "$HTTPD_CONF"
        echo -e "${GREEN}✓ ${module} habilitado${NC}"
    elif grep -q "^LoadModule ${module}" "$HTTPD_CONF"; then
        echo -e "${GREEN}✓ ${module} já está habilitado${NC}"
    fi
done

# 5. Ajustar AllowOverride no DocumentRoot principal (igual ao remoto)
echo ""
echo -e "${YELLOW}5. Ajustando AllowOverride no DocumentRoot principal...${NC}"
sed -i 's|AllowOverride All|AllowOverride None|g' "$HTTPD_CONF"
# Mas manter AllowOverride All apenas no VirtualHost do Pulse
echo -e "${GREEN}✓ AllowOverride ajustado${NC}"

# 6. Ajustar DirectoryIndex (igual ao remoto - só index.html)
echo ""
echo -e "${YELLOW}6. Ajustando DirectoryIndex...${NC}"
if grep -q "DirectoryIndex index.html index.php" "$HTTPD_CONF"; then
    sed -i 's/DirectoryIndex index.html index.php/DirectoryIndex index.html/' "$HTTPD_CONF"
    echo -e "${GREEN}✓ DirectoryIndex ajustado${NC}"
else
    echo -e "${GREEN}✓ DirectoryIndex já está correto${NC}"
fi

# 7. Habilitar Include de vhosts e conf.d
echo ""
echo -e "${YELLOW}7. Habilitando Includes...${NC}"
if grep -q "^#Include conf/extra/httpd-vhosts.conf" "$HTTPD_CONF"; then
    sed -i 's/^#Include conf\/extra\/httpd-vhosts.conf/Include conf\/extra\/httpd-vhosts.conf/' "$HTTPD_CONF"
    echo -e "${GREEN}✓ Include conf/extra/httpd-vhosts.conf habilitado${NC}"
fi

if ! grep -q "IncludeOptional conf/conf.d/\*.conf" "$HTTPD_CONF"; then
    echo "IncludeOptional conf/conf.d/*.conf" >> "$HTTPD_CONF"
    echo -e "${GREEN}✓ IncludeOptional conf/conf.d/*.conf adicionado${NC}"
fi

# 8. Adicionar Protocols (igual ao remoto)
echo ""
echo -e "${YELLOW}8. Adicionando Protocols...${NC}"
if ! grep -q "^Protocols " "$HTTPD_CONF"; then
    echo "Protocols h2 h2c http/1.1" >> "$HTTPD_CONF"
    echo -e "${GREEN}✓ Protocols adicionado${NC}"
else
    echo -e "${GREEN}✓ Protocols já existe${NC}"
fi

# 9. Ajustar PHP (remover configuração antiga, usar php-fpm)
echo ""
echo -e "${YELLOW}9. Ajustando configuração PHP...${NC}"
# Remover linhas antigas do PHP se existirem
sed -i '/^LoadModule php_module/d' "$HTTPD_CONF"
sed -i '/^AddHandler php-script/d' "$HTTPD_CONF"
sed -i '/^Include conf\/extra\/php_module.conf/d' "$HTTPD_CONF"

# Adicionar include do php-fpm (igual ao remoto)
if ! grep -q "Include conf/extra/php-fpm.conf" "$HTTPD_CONF"; then
    echo "Include conf/extra/php-fpm.conf" >> "$HTTPD_CONF"
    echo -e "${GREEN}✓ Include php-fpm.conf adicionado${NC}"
fi

# 10. Criar/Atualizar VirtualHost do Pulse
echo ""
echo -e "${YELLOW}10. Criando VirtualHost do Pulse...${NC}"
mkdir -p /etc/httpd/conf/extra

# Backup do vhosts existente se houver
if [ -f "$HTTPD_VHOSTS" ]; then
    cp "$HTTPD_VHOSTS" "${HTTPD_VHOSTS}.backup-$(date +%Y%m%d)"
    echo -e "${GREEN}✓ Backup do vhosts criado${NC}"
fi

# Criar novo VirtualHost do Pulse
cat > "$HTTPD_VHOSTS" << 'EOF'
# VirtualHost para Pulse - Desenvolvimento Local
# Este VirtualHost permite AllowOverride All especificamente para o Pulse
# enquanto o DocumentRoot principal mantém AllowOverride None (igual ao remoto)
#
# Configurado para funcionar igual ao ambiente de produção

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
        RewriteRule . index.php [L]
    </Directory>
    
    ErrorLog /var/log/httpd/pulse-localhost-error.log
    CustomLog /var/log/httpd/pulse-localhost-access.log combined
    LogLevel warn
</VirtualHost>
EOF

echo -e "${GREEN}✓ VirtualHost do Pulse criado${NC}"

# 11. Adicionar ServerName global (para remover aviso)
echo ""
echo -e "${YELLOW}11. Adicionando ServerName global...${NC}"
if grep -q "^#ServerName www.example.com:80" "$HTTPD_CONF"; then
    sed -i 's/^#ServerName www.example.com:80/ServerName localhost:80/' "$HTTPD_CONF"
    echo -e "${GREEN}✓ ServerName global adicionado${NC}"
elif grep -q "^ServerName" "$HTTPD_CONF"; then
    echo -e "${GREEN}✓ ServerName global já existe${NC}"
else
    # Adicionar após a linha ServerAdmin
    sed -i '/^ServerAdmin/a ServerName localhost:80' "$HTTPD_CONF"
    echo -e "${GREEN}✓ ServerName global adicionado${NC}"
fi

# 12. Adicionar ao /etc/hosts se não existir
echo ""
echo -e "${YELLOW}12. Verificando /etc/hosts...${NC}"
if ! grep -q "pulse.localhost" /etc/hosts; then
    echo "127.0.0.1 pulse.localhost" >> /etc/hosts
    echo -e "${GREEN}✓ pulse.localhost adicionado ao /etc/hosts${NC}"
else
    echo -e "${GREEN}✓ pulse.localhost já está no /etc/hosts${NC}"
fi

# 13. Testar configuração
echo ""
echo -e "${YELLOW}13. Testando configuração...${NC}"
if apachectl configtest; then
    echo -e "${GREEN}✓ Configuração está OK${NC}"
else
    echo -e "${RED}✗ Erro na configuração. Verifique os erros acima.${NC}"
    echo -e "${YELLOW}Você pode restaurar o backup com:${NC}"
    echo -e "${YELLOW}sudo cp ${HTTPD_CONF}.backup-$(date +%Y%m%d) ${HTTPD_CONF}${NC}"
    exit 1
fi

# 14. Reiniciar Apache
echo ""
echo -e "${YELLOW}14. Reiniciando Apache...${NC}"
if systemctl restart httpd; then
    echo -e "${GREEN}✓ Apache reiniciado${NC}"
else
    echo -e "${RED}✗ Erro ao reiniciar Apache${NC}"
    exit 1
fi

# Resumo
echo ""
echo -e "${BLUE}==========================================${NC}"
echo -e "${GREEN}Sincronização concluída com sucesso!${NC}"
echo -e "${BLUE}==========================================${NC}"
echo ""
echo "Resumo das alterações:"
echo "  ✓ MPM alterado para event (igual ao remoto)"
echo "  ✓ Listen 443 adicionado"
echo "  ✓ Módulos adicionais habilitados"
echo "  ✓ AllowOverride None no DocumentRoot principal (igual ao remoto)"
echo "  ✓ VirtualHost do Pulse criado com AllowOverride All"
echo "  ✓ Includes habilitados"
echo "  ✓ Protocols adicionado"
echo "  ✓ Configuração PHP ajustada para php-fpm"
echo ""
echo "Acesse:"
echo "  - http://pulse.localhost/vendas/inicio"
echo "  - http://localhost/pulse/web/vendas/inicio"
echo ""
echo "Backup salvo em: ${HTTPD_CONF}.backup-$(date +%Y%m%d)"
echo ""

