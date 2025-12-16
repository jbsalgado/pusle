#!/bin/bash
# ==========================================
# Script Rápido - Corrigir Aviso ServerName
# ==========================================
# 
# Corrige o aviso: "Could not reliably determine the server's fully qualified domain name"
#
# Uso: sudo ./corrigir-servername.sh
# ==========================================

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Erro: Este script precisa ser executado com sudo${NC}"
    exit 1
fi

HTTPD_CONF="/etc/httpd/conf/httpd.conf"

echo -e "${YELLOW}Corrigindo aviso ServerName...${NC}"

# Verificar se ServerName já está configurado
if grep -q "^ServerName" "$HTTPD_CONF"; then
    echo -e "${GREEN}✓ ServerName já está configurado${NC}"
    grep "^ServerName" "$HTTPD_CONF"
    exit 0
fi

# Descomentar e ajustar ServerName
if grep -q "^#ServerName www.example.com:80" "$HTTPD_CONF"; then
    sed -i 's/^#ServerName www.example.com:80/ServerName localhost:80/' "$HTTPD_CONF"
    echo -e "${GREEN}✓ ServerName configurado como localhost:80${NC}"
    
    # Testar configuração
    if apachectl configtest >/dev/null 2>&1; then
        echo -e "${GREEN}✓ Configuração testada com sucesso${NC}"
        echo ""
        echo "Reinicie o Apache para aplicar:"
        echo "  sudo systemctl restart httpd"
    else
        echo -e "${RED}✗ Erro na configuração${NC}"
        apachectl configtest
        exit 1
    fi
else
    echo -e "${YELLOW}⚠ Linha ServerName não encontrada no formato esperado${NC}"
    echo "Adicione manualmente ao httpd.conf:"
    echo "  ServerName localhost:80"
fi

