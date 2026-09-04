#!/bin/bash
# ==============================================================================
# Script de Comparação de Esquemas de Banco de Dados (Local vs Remoto)
# VPS 2 - Top Construções (pulse_top_construcoes)
# ==============================================================================

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Auto-detectar banco LOCAL pelo .env se disponível
LOCAL_HOST="localhost"
LOCAL_PORT="5432"
LOCAL_USER="postgres"
LOCAL_PASS="postgres"
LOCAL_DB="alex_birds"

if [ -f .env ]; then
    ENV_DB=$(grep -E '^DB_DSN=' .env | sed -E 's/.*dbname=([^;"]+).*/\1/')
    if [ -n "$ENV_DB" ]; then LOCAL_DB="$ENV_DB"; fi
    ENV_USER=$(grep -E '^DB_USERNAME=' .env | sed -E 's/.*="?([^"]+)"?.*/\1/')
    if [ -n "$ENV_USER" ]; then LOCAL_USER="$ENV_USER"; fi
    ENV_PASS=$(grep -E '^DB_PASSWORD=' .env | sed -E 's/.*="?([^"]+)"?.*/\1/')
    if [ -n "$ENV_PASS" ]; then LOCAL_PASS="$ENV_PASS"; fi
fi

# Configurações do banco REMOTO (PULSE TOP CONSTRUCOES)
PROD_HOST="72.61.221.180"
PROD_PORT="5432"
PROD_DB="pulse_top_construcoes"
PROD_USER="postgres"
PROD_PASS='@#628928@#'

# Arquivos temporários
FILE_LOCAL="/tmp/db_schema_local.txt"
FILE_REMOTE="/tmp/db_schema_remote.txt"
FILE_LOCAL_COLS="/tmp/db_cols_local.txt"
FILE_REMOTE_COLS="/tmp/db_cols_remote.txt"
FILE_DIFF="/tmp/db_diff.txt"

echo -e "${BLUE}=== Iniciando Comparação de Bancos de Dados ===${NC}"
echo -e "Banco Local : ${CYAN}${LOCAL_DB}@${LOCAL_HOST}:${LOCAL_PORT}${NC}"
echo -e "Banco Remoto: ${CYAN}${PROD_DB}@${PROD_HOST}:${PROD_PORT}${NC}\n"

# Função para obter esquema
get_schema() {
    local HOST=$1
    local PORT=$2
    local DB=$3
    local USER=$4
    local PASS=$5
    local OUT_FILE=$6
    local NAME=$7

    echo -e "${YELLOW}Obtendo esquema do banco $NAME ($HOST - db: $DB)...${NC}"
    export PGPASSWORD="$PASS"
    psql -h "$HOST" -p "$PORT" -U "$USER" -d "$DB" -t -c "
        SELECT
            table_name || '.' || column_name || ' (' || data_type || ')'
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name NOT LIKE 'pg_%'
        ORDER BY table_name, column_name;
    " 2>/dev/null > "$OUT_FILE"

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}Esquema $NAME obtido com sucesso!${NC}"
        sed -i '/^$/d; s/^ *//; s/ *$//' "$OUT_FILE"
        LC_ALL=C sort -u -o "$OUT_FILE" "$OUT_FILE"
    else
        echo -e "${RED}Erro ao obter esquema do banco $NAME! Verifique as conexões.${NC}"
        exit 1
    fi
}

# 1. Obter esquemas
get_schema "$LOCAL_HOST" "$LOCAL_PORT" "$LOCAL_DB" "$LOCAL_USER" "$LOCAL_PASS" "$FILE_LOCAL" "LOCAL"
get_schema "$PROD_HOST" "$PROD_PORT" "$PROD_DB" "$PROD_USER" "$PROD_PASS" "$FILE_REMOTE" "REMOTO"

# Extrair apenas nomes das colunas (tabela.coluna)
cut -d' ' -f1 "$FILE_LOCAL" | LC_ALL=C sort -u > "$FILE_LOCAL_COLS"
cut -d' ' -f1 "$FILE_REMOTE" | LC_ALL=C sort -u > "$FILE_REMOTE_COLS"

echo -e "\n${BLUE}=== Analisando Diferenças ===${NC}"

# 2. Colunas realmente faltando em produção (não existem na tabela remota)
echo -e "\n${RED}🚨 COLUNAS FALTANDO EM PRODUÇÃO (Local tem, Remoto não):${NC}"
echo "------------------------------------------------------------"
LC_ALL=C comm -23 "$FILE_LOCAL_COLS" "$FILE_REMOTE_COLS" > "$FILE_DIFF"

if [ -s "$FILE_DIFF" ]; then
    LC_ALL=C join -t ' ' "$FILE_DIFF" "$FILE_LOCAL"

    echo -e "\n${YELLOW}💡 Sugestão de script SQL para corrigir:${NC}"
    echo "------------------------------------------------------------"
    while read -r line; do
        full_line=$(LC_ALL=C grep -m1 "^${line} " "$FILE_LOCAL")
        TABLE=$(echo "$full_line" | cut -d'.' -f1)
        COLUMN_TYPE=$(echo "$full_line" | cut -d'.' -f2)
        COLUMN=$(echo "$COLUMN_TYPE" | cut -d' ' -f1)
        TYPE=$(echo "$COLUMN_TYPE" | cut -d'(' -f2 | tr -d ')')

        SQL_TYPE=$TYPE
        if [ "$TYPE" == "character varying" ]; then SQL_TYPE="VARCHAR(255)"; fi
        if [ "$TYPE" == "integer" ]; then SQL_TYPE="INTEGER"; fi
        if [ "$TYPE" == "boolean" ]; then SQL_TYPE="BOOLEAN"; fi
        if [ "$TYPE" == "numeric" ]; then SQL_TYPE="DECIMAL(12,3)"; fi
        if [ "$TYPE" == "text" ]; then SQL_TYPE="TEXT"; fi
        if [ "$TYPE" == "uuid" ]; then SQL_TYPE="UUID"; fi
        if [ "$TYPE" == "date" ]; then SQL_TYPE="DATE"; fi

        echo "ALTER TABLE $TABLE ADD COLUMN IF NOT EXISTS $COLUMN $SQL_TYPE;"
    done < "$FILE_DIFF"
else
    echo -e "${GREEN}Nenhuma coluna faltando em produção!${NC}"
fi

# 3. Colunas que existem em ambos, mas com tipo diferente (executado instantaneamente via awk)
echo -e "\n${YELLOW}⚠️  DIVERGÊNCIAS DE TIPO ENTRE LOCAL E PRODUÇÃO:${NC}"
echo "------------------------------------------------------------"
awk '
    NR==FNR {
        col=$1; sub(/^[^(]*\(/, "", $0); sub(/\).*$/, "", $0); local[col]=$0; next
    }
    {
        col=$1; sub(/^[^(]*\(/, "", $0); sub(/\).*$/, "", $0);
        if (col in local && local[col] != $0) {
            printf "%s: Local=[%s] vs Remoto=[%s]\n", col, local[col], $0;
            found=1;
        }
    }
    END {
        if (!found) print "Todos os tipos de dados coincidem perfeitamente!";
    }
' "$FILE_LOCAL" "$FILE_REMOTE"

# 4. Colunas extras em produção
echo -e "\n${BLUE}ℹ️  Colunas que existem APENAS em Produção (Local não tem):${NC}"
echo "------------------------------------------------------------"
LC_ALL=C comm -13 "$FILE_LOCAL_COLS" "$FILE_REMOTE_COLS" > "$FILE_DIFF"
if [ -s "$FILE_DIFF" ]; then
    cat "$FILE_DIFF"
else
    echo -e "${GREEN}Nenhuma coluna extra em produção.${NC}"
fi

echo -e "\n${BLUE}=== Fim da Comparação ===${NC}"

# Limpeza
export PGPASSWORD=""
rm -f "$FILE_LOCAL" "$FILE_REMOTE" "$FILE_LOCAL_COLS" "$FILE_REMOTE_COLS" "$FILE_DIFF"
