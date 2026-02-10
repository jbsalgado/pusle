#!/bin/bash
# ==============================================================================
# Script de Comparação de Esquemas de Banco de Dados (Local vs Remoto)
# ==============================================================================
# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color
# Configurações do banco LOCAL
LOCAL_HOST="localhost"
LOCAL_PORT="5432"
LOCAL_DB="pulse"
LOCAL_USER="postgres"
LOCAL_PASS="postgres"
# Configurações do banco REMOTO (PULSE TOP CONSTRUCOES)
PROD_HOST="72.61.221.180"
PROD_PORT="5432"
PROD_DB="pulse_top_construcoes"
PROD_USER="postgres"
PROD_PASS='@#628928@#'
# Arquivos temporários
FILE_LOCAL="/tmp/db_schema_local.txt"
FILE_REMOTE="/tmp/db_schema_remote.txt"
FILE_DIFF="/tmp/db_diff.txt"
echo -e "${BLUE}=== Iniciando Comparação de Bancos de Dados ===${NC}"
# Função para obter esquema
get_schema() {
    local HOST=$1
    local PORT=$2
    local DB=$3
    local USER=$4
    local PASS=$5
    local OUT_FILE=$6
    local NAME=$7
    echo -e "${YELLOW}Obtendo esquema do banco $NAME ($HOST)...${NC}"
    export PGPASSWORD="$PASS"
    psql -h "$HOST" -p "$PORT" -U "$USER" -d "$DB" -t -c "
        SELECT
            table_name || '.' || column_name || ' (' || data_type || ')'
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name NOT LIKE 'pg_%'
        ORDER BY table_name, column_name;
    " > "$OUT_FILE"
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}Esquema $NAME obtido com sucesso!${NC}"
        # Limpar linhas vazias e espaços extras
        sed -i '/^$/d' "$OUT_FILE"
        sed -i 's/^ *//;s/ *$//' "$OUT_FILE"
    else
        echo -e "${RED}Erro ao obter esquema do banco $NAME! Verifique as conexões.${NC}"
        exit 1
    fi
}
# 1. Obter esquemas
get_schema "$LOCAL_HOST" "$LOCAL_PORT" "$LOCAL_DB" "$LOCAL_USER" "$LOCAL_PASS" "$FILE_LOCAL" "LOCAL"
get_schema "$PROD_HOST" "$PROD_PORT" "$PROD_DB" "$PROD_USER" "$PROD_PASS" "$FILE_REMOTE" "REMOTO"
echo -e "${BLUE}=== Analisando Diferenças ===${NC}"
# 2. Comparar arquivos
# Linhas que estão no LOCAL mas não no REMOTO (Colunas faltando em produção)
echo -e "\n${RED}🚨 COLUNAS FALTANDO EM PRODUÇÃO (Local tem, Remoto não):${NC}"
echo "------------------------------------------------------------"
comm -23 "$FILE_LOCAL" "$FILE_REMOTE" > "$FILE_DIFF"
if [ -s "$FILE_DIFF" ]; then
    cat "$FILE_DIFF"

    echo -e "\n${YELLOW}💡 Sugestão de script SQL para corrigir:${NC}"
    echo "------------------------------------------------------------"
    while read line; do
        TABLE=$(echo "$line" | cut -d'.' -f1)
        COLUMN_TYPE=$(echo "$line" | cut -d'.' -f2)
        COLUMN=$(echo "$COLUMN_TYPE" | cut -d' ' -f1)
        TYPE=$(echo "$COLUMN_TYPE" | cut -d'(' -f2 | tr -d ')')

        # Ajuste básico de tipos para sintaxe SQL
        SQL_TYPE=$TYPE
        if [ "$TYPE" == "character varying" ]; then SQL_TYPE="VARCHAR(255)"; fi
        if [ "$TYPE" == "integer" ]; then SQL_TYPE="INTEGER"; fi
        if [ "$TYPE" == "boolean" ]; then SQL_TYPE="BOOLEAN"; fi
        if [ "$TYPE" == "numeric" ]; then SQL_TYPE="DECIMAL(10,2)"; fi
        if [ "$TYPE" == "text" ]; then SQL_TYPE="TEXT"; fi
        if [ "$TYPE" == "uuid" ]; then SQL_TYPE="UUID"; fi
        if [ "$TYPE" == "date" ]; then SQL_TYPE="DATE"; fi

        echo "ALTER TABLE $TABLE ADD COLUMN IF NOT EXISTS $COLUMN $SQL_TYPE;"
    done < "$FILE_DIFF"
else
    echo -e "${GREEN}Nenhuma coluna faltando em produção!${NC}"
fi
# 3. Colunas extras em produção (apenas informativo)
# echo -e "\n${BLUE}ℹ️  Colunas que existem APENAS em Produção (Locais não tem):${NC}"
# echo "------------------------------------------------------------"
# comm -13 "$FILE_LOCAL" "$FILE_REMOTE"
echo -e "\n${BLUE}=== Fim da Comparação ===${NC}"
# Limpeza
export PGPASSWORD=""
rm -f "$FILE_LOCAL" "$FILE_REMOTE" "$FILE_DIFF"
