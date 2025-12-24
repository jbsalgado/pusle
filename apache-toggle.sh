#!/bin/bash

# Definição dos caminhos
CONF_DIR="/etc/httpd/conf"
ATUAL="$CONF_DIR/httpd.conf"
PULSE="$CONF_DIR/httpd.conf.pulse"
BACKUP="$CONF_DIR/httpd.conf.backup"

# Verifica se é root
if [ "$EUID" -ne 0 ]; then
  echo "❌ Por favor, execute como root (sudo)."
  exit 1
fi

echo "--- Gerenciador de Configuração do Apache ---"

# LÓGICA DO TOGGLE
if [ -f "$PULSE" ]; then
    # Se o arquivo .pulse existe, significa que ele está INATIVO. Vamos ativá-lo.
    echo "🔄 Detectado modo PADRÃO/BACKUP ativo."
    echo "🚀 Ativando configuração PULSE..."

    # 1. Guarda o atual (Padrão) como Backup
    mv "$ATUAL" "$BACKUP"
    # 2. Renomeia o Pulse para ser o Atual
    mv "$PULSE" "$ATUAL"

    TIPO="PULSE"

elif [ -f "$BACKUP" ]; then
    # Se o arquivo .backup existe, significa que o Pulse está ativo. Vamos voltar ao backup.
    echo "🔄 Detectado modo PULSE ativo."
    echo "🔙 Restaurando configuração PADRÃO (Backup)..."

    # 1. Guarda o atual (Pulse) como .pulse
    mv "$ATUAL" "$PULSE"
    # 2. Renomeia o Backup para ser o Atual
    mv "$BACKUP" "$ATUAL"

    TIPO="PADRÃO"

else
    echo "⚠️ Erro: Não encontrei nem '$PULSE' nem '$BACKUP'."
    echo "Certifique-se de que os arquivos estão na pasta $CONF_DIR"
    exit 1
fi

# Reinicia o serviço
echo "🔄 Reiniciando o Apache (httpd)..."
systemctl restart httpd

# Verifica se o restart deu certo
if [ $? -eq 0 ]; then
    echo "✅ Sucesso! O Apache agora está rodando no modo: $TIPO"
else
    echo "❌ Erro ao reiniciar o Apache. Verifique as configurações."
fi
