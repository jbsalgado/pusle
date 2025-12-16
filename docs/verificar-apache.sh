#!/bin/bash
# ==========================================
# Script de Verificação de Configuração Apache
# ==========================================
# 
# Este script verifica se o Apache está configurado corretamente
# para o projeto Pulse.
#
# Uso: ./verificar-apache.sh
# ==========================================

echo "=========================================="
echo "Verificação de Configuração Apache - Pulse"
echo "=========================================="
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para verificar se comando existe
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Verificar se está rodando como root
if [ "$EUID" -eq 0 ]; then 
    echo -e "${YELLOW}Aviso: Execute este script sem sudo para verificar permissões${NC}"
fi

# 1. Verificar se Apache está instalado (Arch Linux)
echo "1. Verificando se Apache está instalado..."
if command_exists httpd; then
    APACHE_CMD="apachectl"
    APACHE_SERVICE="httpd"
    echo -e "${GREEN}✓ Httpd encontrado (Arch Linux)${NC}"
    
    # Verificar se está instalado via pacman
    if pacman -Q apache >/dev/null 2>&1; then
        echo -e "${GREEN}✓ Apache instalado via pacman${NC}"
    fi
else
    echo -e "${RED}✗ Apache não encontrado${NC}"
    echo "  Execute: sudo pacman -S apache"
    exit 1
fi

# 2. Verificar se Apache está rodando
echo ""
echo "2. Verificando se Apache está rodando..."
if systemctl is-active --quiet $APACHE_SERVICE; then
    echo -e "${GREEN}✓ Apache está rodando${NC}"
else
    echo -e "${RED}✗ Apache não está rodando${NC}"
    echo "  Execute: sudo systemctl start $APACHE_SERVICE"
fi

# 3. Verificar módulos necessários
echo ""
echo "3. Verificando módulos necessários..."
MODULES=("rewrite" "headers")
for module in "${MODULES[@]}"; do
    if $APACHE_CMD -M 2>/dev/null | grep -q "$module"; then
        echo -e "${GREEN}✓ Módulo $module está carregado${NC}"
    else
        echo -e "${RED}✗ Módulo $module NÃO está carregado${NC}"
        echo "  Edite /etc/httpd/conf/httpd.conf e descomente:"
        echo "  LoadModule ${module}_module modules/mod_${module}.so"
    fi
done

# 4. Verificar sintaxe da configuração
echo ""
echo "4. Verificando sintaxe da configuração..."
if sudo $APACHE_CMD configtest 2>&1 | grep -q "Syntax OK"; then
    echo -e "${GREEN}✓ Sintaxe da configuração está OK${NC}"
else
    echo -e "${RED}✗ Erro na sintaxe da configuração${NC}"
    sudo $APACHE_CMD configtest
fi

# 5. Verificar se .htaccess existe
echo ""
echo "5. Verificando arquivo .htaccess..."
HTACCESS_PATH="/srv/http/pulse/web/.htaccess"
if [ -f "$HTACCESS_PATH" ]; then
    echo -e "${GREEN}✓ Arquivo .htaccess encontrado em: $HTACCESS_PATH${NC}"
    
    # Verificar permissões
    if [ -r "$HTACCESS_PATH" ]; then
        echo -e "${GREEN}✓ Arquivo .htaccess é legível${NC}"
    else
        echo -e "${RED}✗ Arquivo .htaccess não é legível${NC}"
    fi
else
    echo -e "${RED}✗ Arquivo .htaccess NÃO encontrado em: $HTACCESS_PATH${NC}"
fi

# 6. Verificar AllowOverride
echo ""
echo "6. Verificando configuração AllowOverride..."
# Tentar detectar a configuração do VirtualHost (Arch Linux)
if grep -r "AllowOverride" /etc/httpd/conf.d/ 2>/dev/null | grep -q "All"; then
    echo -e "${GREEN}✓ AllowOverride All encontrado na configuração${NC}"
elif grep -r "AllowOverride" /etc/httpd/conf/extra/ 2>/dev/null | grep -q "All"; then
    echo -e "${GREEN}✓ AllowOverride All encontrado na configuração${NC}"
else
    echo -e "${YELLOW}⚠ AllowOverride pode não estar configurado como 'All'${NC}"
    echo "  Verifique a configuração do VirtualHost em:"
    echo "  - /etc/httpd/conf/extra/httpd-vhosts.conf"
    echo "  - /etc/httpd/conf.d/*.conf"
fi

# 7. Verificar DocumentRoot
echo ""
echo "7. Verificando DocumentRoot..."
DOCUMENT_ROOT=$(httpd -S 2>/dev/null | grep "pulse" | head -1 | awk '{print $NF}')
if [ -n "$DOCUMENT_ROOT" ]; then
    echo -e "${GREEN}✓ DocumentRoot encontrado: $DOCUMENT_ROOT${NC}"
    if [ -d "$DOCUMENT_ROOT" ]; then
        echo -e "${GREEN}✓ Diretório existe${NC}"
    else
        echo -e "${RED}✗ Diretório NÃO existe${NC}"
    fi
else
    echo -e "${YELLOW}⚠ DocumentRoot do Pulse não encontrado${NC}"
    echo "  Verifique se o VirtualHost está configurado corretamente"
fi

# 8. Teste de URL
echo ""
echo "8. Testando acesso à aplicação..."
if command_exists curl; then
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/pulse/web/vendas/inicio 2>/dev/null || \
               curl -s -o /dev/null -w "%{http_code}" http://pulse.localhost/vendas/inicio 2>/dev/null)
    if [ "$RESPONSE" = "200" ] || [ "$RESPONSE" = "302" ] || [ "$RESPONSE" = "401" ]; then
        echo -e "${GREEN}✓ Aplicação responde (HTTP $RESPONSE)${NC}"
    elif [ "$RESPONSE" = "404" ]; then
        echo -e "${RED}✗ Erro 404 - Página não encontrada${NC}"
        echo "  Verifique:"
        echo "  - Se o .htaccess está sendo processado"
        echo "  - Se AllowOverride está como 'All'"
        echo "  - Se mod_rewrite está habilitado"
    else
        echo -e "${YELLOW}⚠ Resposta HTTP: $RESPONSE${NC}"
    fi
else
    echo -e "${YELLOW}⚠ curl não encontrado, pulando teste de URL${NC}"
fi

# Resumo
echo ""
echo "=========================================="
echo "Resumo da Verificação"
echo "=========================================="
echo ""
echo "Se todos os itens estão verdes (✓), sua configuração está correta."
echo "Se houver itens vermelhos (✗), siga as instruções para corrigir."
echo ""
echo "Documentação completa em: docs/apache-producao.conf e docs/apache-localhost.conf"
echo ""

