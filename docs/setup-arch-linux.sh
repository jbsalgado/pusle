#!/bin/bash
# ==========================================
# Script de Configuração Automática - Arch Linux
# ==========================================
# 
# Este script configura automaticamente o Apache no Arch Linux
# para o projeto Pulse.
#
# Uso: sudo ./setup-arch-linux.sh [localhost|producao]
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

# Verificar argumento
MODE=${1:-localhost}
if [ "$MODE" != "localhost" ] && [ "$MODE" != "producao" ]; then
    echo -e "${RED}Uso: sudo $0 [localhost|producao]${NC}"
    exit 1
fi

echo -e "${BLUE}==========================================${NC}"
echo -e "${BLUE}Configuração Apache Pulse - Arch Linux${NC}"
echo -e "${BLUE}Modo: $MODE${NC}"
echo -e "${BLUE}==========================================${NC}"
echo ""

# 1. Verificar se Apache está instalado
echo -e "${YELLOW}1. Verificando Apache...${NC}"
if ! pacman -Q apache >/dev/null 2>&1; then
    echo -e "${YELLOW}Apache não encontrado. Instalando...${NC}"
    pacman -S --noconfirm apache
    echo -e "${GREEN}✓ Apache instalado${NC}"
else
    echo -e "${GREEN}✓ Apache já está instalado${NC}"
fi

# 2. Habilitar módulos necessários
echo ""
echo -e "${YELLOW}2. Habilitando módulos necessários...${NC}"
HTTPD_CONF="/etc/httpd/conf/httpd.conf"

# Backup do arquivo original
if [ ! -f "${HTTPD_CONF}.backup" ]; then
    cp "$HTTPD_CONF" "${HTTPD_CONF}.backup"
    echo -e "${GREEN}✓ Backup criado: ${HTTPD_CONF}.backup${NC}"
fi

# Habilitar mod_rewrite
if grep -q "^#LoadModule rewrite_module" "$HTTPD_CONF"; then
    sed -i 's/^#LoadModule rewrite_module/LoadModule rewrite_module/' "$HTTPD_CONF"
    echo -e "${GREEN}✓ mod_rewrite habilitado${NC}"
elif grep -q "^LoadModule rewrite_module" "$HTTPD_CONF"; then
    echo -e "${GREEN}✓ mod_rewrite já está habilitado${NC}"
else
    echo -e "${YELLOW}⚠ Adicionando mod_rewrite manualmente${NC}"
    echo "LoadModule rewrite_module modules/mod_rewrite.so" >> "$HTTPD_CONF"
fi

# Habilitar mod_headers
if grep -q "^#LoadModule headers_module" "$HTTPD_CONF"; then
    sed -i 's/^#LoadModule headers_module/LoadModule headers_module/' "$HTTPD_CONF"
    echo -e "${GREEN}✓ mod_headers habilitado${NC}"
elif grep -q "^LoadModule headers_module" "$HTTPD_CONF"; then
    echo -e "${GREEN}✓ mod_headers já está habilitado${NC}"
else
    echo -e "${YELLOW}⚠ Adicionando mod_headers manualmente${NC}"
    echo "LoadModule headers_module modules/mod_headers.so" >> "$HTTPD_CONF"
fi

# 3. Habilitar Include de vhosts
echo ""
echo -e "${YELLOW}3. Configurando VirtualHosts...${NC}"
if grep -q "^#Include conf/extra/httpd-vhosts.conf" "$HTTPD_CONF"; then
    sed -i 's/^#Include conf\/extra\/httpd-vhosts.conf/Include conf\/extra\/httpd-vhosts.conf/' "$HTTPD_CONF"
    echo -e "${GREEN}✓ Include de vhosts habilitado${NC}"
elif grep -q "^Include conf/extra/httpd-vhosts.conf" "$HTTPD_CONF"; then
    echo -e "${GREEN}✓ Include de vhosts já está habilitado${NC}"
fi

# Criar diretório se não existir
mkdir -p /etc/httpd/conf/extra
mkdir -p /etc/httpd/conf.d

# 4. Copiar configuração
echo ""
echo -e "${YELLOW}4. Copiando configuração...${NC}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

if [ "$MODE" = "localhost" ]; then
    CONFIG_FILE="$PROJECT_DIR/docs/apache-localhost.conf"
    TARGET_FILE="/etc/httpd/conf/extra/httpd-vhosts.conf"
    
    if [ -f "$CONFIG_FILE" ]; then
        cp "$CONFIG_FILE" "$TARGET_FILE"
        echo -e "${GREEN}✓ Configuração de localhost copiada${NC}"
        
        # Adicionar ao /etc/hosts
        if ! grep -q "pulse.localhost" /etc/hosts; then
            echo "127.0.0.1 pulse.localhost" >> /etc/hosts
            echo -e "${GREEN}✓ pulse.localhost adicionado ao /etc/hosts${NC}"
        else
            echo -e "${GREEN}✓ pulse.localhost já está no /etc/hosts${NC}"
        fi
    else
        echo -e "${RED}✗ Arquivo de configuração não encontrado: $CONFIG_FILE${NC}"
        exit 1
    fi
else
    CONFIG_FILE="$PROJECT_DIR/docs/apache-producao.conf"
    TARGET_FILE="/etc/httpd/conf/extra/httpd-vhosts.conf"
    
    if [ -f "$CONFIG_FILE" ]; then
        cp "$CONFIG_FILE" "$TARGET_FILE"
        echo -e "${GREEN}✓ Configuração de produção copiada${NC}"
        echo -e "${YELLOW}⚠ IMPORTANTE: Edite $TARGET_FILE e ajuste:${NC}"
        echo -e "${YELLOW}   - DocumentRoot (caminho do projeto)${NC}"
        echo -e "${YELLOW}   - ServerName (seu domínio)${NC}"
    else
        echo -e "${RED}✗ Arquivo de configuração não encontrado: $CONFIG_FILE${NC}"
        exit 1
    fi
fi

# 5. Testar configuração
echo ""
echo -e "${YELLOW}5. Testando configuração...${NC}"
if apachectl configtest; then
    echo -e "${GREEN}✓ Configuração está OK${NC}"
else
    echo -e "${RED}✗ Erro na configuração. Verifique os erros acima.${NC}"
    exit 1
fi

# 6. Reiniciar Apache
echo ""
echo -e "${YELLOW}6. Reiniciando Apache...${NC}"
if systemctl restart httpd; then
    echo -e "${GREEN}✓ Apache reiniciado${NC}"
else
    echo -e "${RED}✗ Erro ao reiniciar Apache${NC}"
    exit 1
fi

# 7. Habilitar Apache no boot
echo ""
echo -e "${YELLOW}7. Habilitando Apache no boot...${NC}"
systemctl enable httpd
echo -e "${GREEN}✓ Apache habilitado para iniciar no boot${NC}"

# Resumo
echo ""
echo -e "${BLUE}==========================================${NC}"
echo -e "${GREEN}Configuração concluída com sucesso!${NC}"
echo -e "${BLUE}==========================================${NC}"
echo ""
echo "Próximos passos:"
if [ "$MODE" = "localhost" ]; then
    echo "  - Acesse: http://pulse.localhost/vendas/inicio"
    echo "  - Ou: http://localhost/pulse/web/vendas/inicio"
else
    echo "  - Edite /etc/httpd/conf/extra/httpd-vhosts.conf"
    echo "  - Ajuste DocumentRoot e ServerName"
    echo "  - Execute: sudo apachectl configtest"
    echo "  - Execute: sudo systemctl restart httpd"
fi
echo ""
echo "Verificar logs:"
echo "  sudo tail -f /var/log/httpd/error_log"
echo ""

