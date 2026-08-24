#!/bin/bash
# =============================================================================
# Automação de Criação de Nova Loja / SaaS - Oncode
# Uso: sudo ./criar_nova_loja_saas.sh <nome_subdominio> <nome_banco>
# Exemplo: sudo ./criar_nova_loja_saas.sh construcao pulse_top_contrucoes
# =============================================================================
set -e

SUBDOMINIO=$1
DB_NAME=$2

if [ -z "$SUBDOMINIO" ] || [ -z "$DB_NAME" ]; then
    echo "❌ Erro: Informe o subdomínio e o nome do banco de dados."
    echo "Uso: sudo $0 <subdominio> <nome_banco>"
    echo "Exemplo: sudo $0 novaloja pulse_novaloja"
    exit 1
fi

if [ "$EUID" -ne 0 ]; then
  echo "❌ Por favor, execute como root (sudo $0 ...)."
  exit 1
fi

BASE_DIR="/srv/http/$SUBDOMINIO/pulse-plus"
VHOST_FILE="/etc/httpd/conf/extra/httpd-vhosts.conf"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🚀 Iniciando provisionamento para: $SUBDOMINIO.oncode.app.br"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# 1. Criar pasta e clonar repositório
if [ ! -d "$BASE_DIR" ]; then
    echo "📁 Criando diretório e clonando repositório..."
    mkdir -p "/srv/http/$SUBDOMINIO"
    git clone https://github.com/jbsalgado/pulse.git "$BASE_DIR"
else
    echo "ℹ️ Diretório $BASE_DIR já existe. Ignorando git clone."
fi

# 2. Criar Banco de Dados PostgreSQL (se não existir) e Estruturas Essenciais
echo "🗄️ Verificando banco de dados PostgreSQL: $DB_NAME..."
if ! sudo -u postgres psql -lqt | cut -d \| -f 1 | grep -qw "$DB_NAME"; then
    echo "➕ Criando banco de dados $DB_NAME..."
    sudo -u postgres psql -c "CREATE DATABASE $DB_NAME;"
    if [ -f "$BASE_DIR/PulseDDLs.sql" ]; then
        echo "📜 Importando PulseDDLs.sql..."
        sudo -u postgres psql -d "$DB_NAME" -f "$BASE_DIR/PulseDDLs.sql"
    fi
else
    echo "ℹ️ Banco de dados $DB_NAME já existe."
fi

# Garantir tabelas essenciais para suporte aos gráficos (Dashboard e Financeiro)
sudo -u postgres psql -d "$DB_NAME" -c '
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TABLE IF NOT EXISTS public.prest_formas_pagamento (
    id uuid DEFAULT gen_random_uuid() PRIMARY KEY NOT NULL,
    usuario_id uuid NOT NULL REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT,
    nome character varying(100) NOT NULL,
    tipo character varying(30) NOT NULL,
    ativo boolean DEFAULT true NOT NULL,
    aceita_parcelamento boolean DEFAULT false NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_formas_pagamento_usuario_id ON public.prest_formas_pagamento USING btree (usuario_id);

CREATE TABLE IF NOT EXISTS public.prest_tipos_despesa (
    id uuid DEFAULT gen_random_uuid() PRIMARY KEY NOT NULL,
    usuario_id uuid NOT NULL REFERENCES public.prest_usuarios(id) ON DELETE CASCADE,
    nome character varying(100) NOT NULL,
    grupo character varying(30) NOT NULL CHECK (grupo IN ('"'"'FIXA'"'"', '"'"'VARIAVEL'"'"', '"'"'MERCADORIA'"'"')),
    descricao text,
    ativo boolean DEFAULT true NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL
);

CREATE TABLE IF NOT EXISTS public.prest_fornecedores (
    id uuid DEFAULT gen_random_uuid() PRIMARY KEY NOT NULL,
    usuario_id uuid NOT NULL REFERENCES public.prest_usuarios(id) ON DELETE CASCADE,
    nome_fantasia character varying(150) NOT NULL,
    razao_social character varying(255),
    cnpj character varying(18),
    cpf character varying(14),
    telefone character varying(20),
    email character varying(100),
    ativo boolean DEFAULT true NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL
);

CREATE TABLE IF NOT EXISTS public.prest_contas_pagar (
    id uuid DEFAULT gen_random_uuid() PRIMARY KEY NOT NULL,
    usuario_id uuid NOT NULL REFERENCES public.prest_usuarios(id) ON DELETE CASCADE,
    fornecedor_id uuid REFERENCES public.prest_fornecedores(id) ON DELETE SET NULL,
    compra_id uuid,
    tipo_despesa_id uuid REFERENCES public.prest_tipos_despesa(id) ON DELETE SET NULL,
    descricao character varying(255) NOT NULL,
    valor numeric(10,2) NOT NULL CHECK (valor > 0),
    data_vencimento date NOT NULL,
    data_pagamento date,
    status character varying(20) DEFAULT '"'"'PENDENTE'"'"' NOT NULL CHECK (status IN ('"'"'PENDENTE'"'"', '"'"'PAGA'"'"', '"'"'VENCIDA'"'"', '"'"'CANCELADA'"'"')),
    forma_pagamento_id uuid REFERENCES public.prest_formas_pagamento(id) ON DELETE SET NULL,
    observacoes text,
    arquivo_comprovante character varying(255),
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL
);
' 2>/dev/null || true

# 3. Criar .env
echo "⚙️ Configurando .env..."
cat <<EOF > "$BASE_DIR/.env"
DB_DSN="pgsql:host=127.0.0.1;port=5432;dbname=$DB_NAME"
DB_USERNAME="postgres"
DB_PASSWORD="@#Jbs992888872Jbs@#"
YII_ENV="prod"
YII_DEBUG="false"
EOF

# 4. Ajustar permissões
echo "🔐 Ajustando permissões de pasta..."
mkdir -p "$BASE_DIR/runtime" "$BASE_DIR/web/assets"
chown -R http:http "$BASE_DIR/runtime" "$BASE_DIR/web/assets" 2>/dev/null || true
chmod -R 777 "$BASE_DIR/runtime" "$BASE_DIR/web/assets" 2>/dev/null || true

# 5. Configurar Apache VirtualHost
if ! grep -q "ServerName $SUBDOMINIO.oncode.app.br" "$VHOST_FILE"; then
    echo "🔧 Adicionando VirtualHost em $VHOST_FILE..."
    cat <<EOF >> "$VHOST_FILE"

# --- SUBDOMÍNIO: $SUBDOMINIO ---
<VirtualHost *:443>
    ServerName $SUBDOMINIO.oncode.app.br
    DocumentRoot "$BASE_DIR/web"

    <Directory "$BASE_DIR/web">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        RewriteEngine on
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . index.php
    </Directory>

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/oncode.app.br-0001/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/oncode.app.br-0001/privkey.pem

    ErrorLog "/var/log/httpd/$SUBDOMINIO-error_log"
    CustomLog "/var/log/httpd/$SUBDOMINIO-access_log" common
</VirtualHost>
EOF
else
    echo "ℹ️ VirtualHost para $SUBDOMINIO.oncode.app.br já existe em $VHOST_FILE."
fi

# 6. Validar e reiniciar Apache
echo "🔍 Validando sintaxe do Apache..."
httpd -t
echo "🔄 Reiniciando Apache..."
systemctl restart httpd

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Provisionamento concluído com sucesso!"
echo "🌐 Acesse: https://$SUBDOMINIO.oncode.app.br/catalogo/lojas.html"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
