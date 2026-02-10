#!/bin/bash

# ==============================================================================
# Script para comparar e sincronizar bancos PULSE
# ==============================================================================

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

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Criar diretório temporário
TEMP_DIR="/tmp/db_sync_$$"
mkdir -p $TEMP_DIR

echo -e "${BLUE}🔍 Comparando bancos de dados...${NC}"
echo "LOCAL: $LOCAL_DB em $LOCAL_HOST"
echo "REMOTO: $PROD_DB em $PROD_HOST"
echo ""

# Arquivos temporários
TEMP_LOCAL="$TEMP_DIR/local_tables.txt"
TEMP_PROD="$TEMP_DIR/prod_tables.txt"
MISSING_LOCAL="$TEMP_DIR/missing_local.txt"
MISSING_PROD="$TEMP_DIR/missing_prod.txt"

# Extrair tabelas do banco LOCAL
echo -e "${BLUE}📊 Buscando tabelas do banco LOCAL...${NC}"
PGPASSWORD=$LOCAL_PASS psql -h $LOCAL_HOST -p $LOCAL_PORT -U $LOCAL_USER -d $LOCAL_DB -t -A -c \
  "SELECT table_name FROM information_schema.tables WHERE table_schema='public' ORDER BY table_name;" \
  > $TEMP_LOCAL

if [ $? -ne 0 ]; then
  echo -e "${RED}❌ Erro ao conectar no banco LOCAL${NC}"
  rm -rf $TEMP_DIR
  exit 1
fi

# Extrair tabelas do banco REMOTO
echo -e "${BLUE}📊 Buscando tabelas do banco REMOTO...${NC}"
PGPASSWORD=$PROD_PASS psql -h $PROD_HOST -p $PROD_PORT -U $PROD_USER -d $PROD_DB -t -A -c \
  "SELECT table_name FROM information_schema.tables WHERE table_schema='public' ORDER BY table_name;" \
  > $TEMP_PROD

if [ $? -ne 0 ]; then
  echo -e "${RED}❌ Erro ao conectar no banco REMOTO${NC}"
  rm -rf $TEMP_DIR
  exit 1
fi

# Identificar tabelas faltantes
comm -13 $TEMP_LOCAL $TEMP_PROD > $MISSING_LOCAL
comm -23 $TEMP_LOCAL $TEMP_PROD > $MISSING_PROD

echo ""
echo "=========================================="
echo -e "${RED}❌ TABELAS QUE FALTAM NO LOCAL${NC}"
echo "=========================================="
if [ -s $MISSING_LOCAL ]; then
  cat $MISSING_LOCAL | while read table; do
    echo "  - $table"
  done
else
  echo -e "${GREEN}  ✅ Nenhuma tabela faltando${NC}"
fi

echo ""
echo "=========================================="
echo -e "${YELLOW}⚠️  TABELAS QUE FALTAM NO REMOTO${NC}"
echo "=========================================="
if [ -s $MISSING_PROD ]; then
  cat $MISSING_PROD | while read table; do
    echo "  - $table"
  done
else
  echo -e "${GREEN}  ✅ Nenhuma tabela faltando${NC}"
fi

echo ""
echo "=========================================="
echo -e "${BLUE}📈 RESUMO${NC}"
echo "=========================================="
LOCAL_COUNT=$(wc -l < $TEMP_LOCAL | tr -d ' ')
PROD_COUNT=$(wc -l < $TEMP_PROD | tr -d ' ')
MISSING_LOCAL_COUNT=$(wc -l < $MISSING_LOCAL | tr -d ' ')
MISSING_PROD_COUNT=$(wc -l < $MISSING_PROD | tr -d ' ')

echo "Tabelas no LOCAL: $LOCAL_COUNT"
echo "Tabelas no REMOTO: $PROD_COUNT"
echo "Faltam no LOCAL: $MISSING_LOCAL_COUNT"
echo "Faltam no REMOTO: $MISSING_PROD_COUNT"

# ==============================================================================
# SINCRONIZAÇÃO
# ==============================================================================

echo ""
echo "=========================================="
echo -e "${YELLOW}🔄 OPÇÕES DE SINCRONIZAÇÃO${NC}"
echo "=========================================="
echo "1) Copiar tabelas faltantes do REMOTO para LOCAL (estrutura + dados)"
echo "2) Copiar apenas estrutura do REMOTO para LOCAL (sem dados)"
echo "3) Copiar tabelas faltantes do LOCAL para REMOTO (⚠️ CUIDADO!)"
echo "4) Sincronização bidirecional (⚠️ MODIFICA AMBOS!)"
echo "5) Apenas gerar scripts SQL (não executar - RECOMENDADO)"
echo "6) Sair sem sincronizar"
echo ""
read -p "Escolha uma opção (1-6): " SYNC_OPTION

case $SYNC_OPTION in
  1)
    echo -e "${GREEN}📥 Copiando tabelas do REMOTO para LOCAL (estrutura + dados)...${NC}"
    if [ -s $MISSING_LOCAL ]; then
      cat $MISSING_LOCAL | while read table; do
        echo "  Copiando tabela: $table"
        PGPASSWORD=$PROD_PASS pg_dump -h $PROD_HOST -p $PROD_PORT -U $PROD_USER -d $PROD_DB \
          --table=public.$table --no-owner --no-acl \
          | PGPASSWORD=$LOCAL_PASS psql -h $LOCAL_HOST -p $LOCAL_PORT -U $LOCAL_USER -d $LOCAL_DB -q
        if [ $? -eq 0 ]; then
          echo -e "  ${GREEN}✅ $table copiada com sucesso${NC}"
        else
          echo -e "  ${RED}❌ Erro ao copiar $table${NC}"
        fi
      done
      echo -e "${GREEN}✅ Sincronização concluída!${NC}"
    else
      echo -e "${GREEN}Nada para copiar!${NC}"
    fi
    ;;
  2)
    echo -e "${GREEN}📥 Copiando apenas ESTRUTURA do REMOTO para LOCAL (sem dados)...${NC}"
    if [ -s $MISSING_LOCAL ]; then
      cat $MISSING_LOCAL | while read table; do
        echo "  Copiando estrutura: $table"
        PGPASSWORD=$PROD_PASS pg_dump -h $PROD_HOST -p $PROD_PORT -U $PROD_USER -d $PROD_DB \
          --table=public.$table --schema-only --no-owner --no-acl \
          | PGPASSWORD=$LOCAL_PASS psql -h $LOCAL_HOST -p $LOCAL_PORT -U $LOCAL_USER -d $LOCAL_DB -q
        if [ $? -eq 0 ]; then
          echo -e "  ${GREEN}✅ $table (estrutura) copiada com sucesso${NC}"
        else
          echo -e "  ${RED}❌ Erro ao copiar $table${NC}"
        fi
      done
      echo -e "${GREEN}✅ Estruturas copiadas com sucesso!${NC}"
    else
      echo -e "${GREEN}Nada para copiar!${NC}"
    fi
    ;;
  3)
    echo -e "${RED}⚠️⚠️⚠️  ATENÇÃO: Você está prestes a modificar o banco REMOTO! ⚠️⚠️⚠️${NC}"
    echo -e "${YELLOW}Isso pode causar problemas graves em produção!${NC}"
    echo ""
    read -p "Tem ABSOLUTA certeza? (digite 'SIM TENHO CERTEZA' para confirmar): " CONFIRM
    if [ "$CONFIRM" == "SIM TENHO CERTEZA" ]; then
      echo -e "${GREEN}📤 Copiando tabelas do LOCAL para REMOTO...${NC}"
      if [ -s $MISSING_PROD ]; then
        cat $MISSING_PROD | while read table; do
          echo "  Copiando tabela: $table"
          PGPASSWORD=$LOCAL_PASS pg_dump -h $LOCAL_HOST -p $LOCAL_PORT -U $LOCAL_USER -d $LOCAL_DB \
            --table=public.$table --no-owner --no-acl \
            | PGPASSWORD=$PROD_PASS psql -h $PROD_HOST -p $PROD_PORT -U $PROD_USER -d $PROD_DB -q
          if [ $? -eq 0 ]; then
            echo -e "  ${GREEN}✅ $table copiada com sucesso${NC}"
          else
            echo -e "  ${RED}❌ Erro ao copiar $table${NC}"
          fi
        done
      fi
    else
      echo -e "${RED}Operação cancelada${NC}"
    fi
    ;;
  4)
    echo -e "${RED}⚠️⚠️⚠️  Sincronização bidirecional - Modificará AMBOS os bancos! ⚠️⚠️⚠️${NC}"
    read -p "Tem ABSOLUTA certeza? (digite 'SIM TENHO CERTEZA' para confirmar): " CONFIRM
    if [ "$CONFIRM" == "SIM TENHO CERTEZA" ]; then
      if [ -s $MISSING_LOCAL ]; then
        echo -e "${GREEN}📥 Copiando do REMOTO para LOCAL...${NC}"
        cat $MISSING_LOCAL | while read table; do
          PGPASSWORD=$PROD_PASS pg_dump -h $PROD_HOST -p $PROD_PORT -U $PROD_USER -d $PROD_DB \
            --table=public.$table --no-owner --no-acl \
            | PGPASSWORD=$LOCAL_PASS psql -h $LOCAL_HOST -p $LOCAL_PORT -U $LOCAL_USER -d $LOCAL_DB -q 2>&1 | grep -v "already exists"
        done
      fi
      if [ -s $MISSING_PROD ]; then
        echo -e "${GREEN}📤 Copiando do LOCAL para REMOTO...${NC}"
        cat $MISSING_PROD | while read table; do
          PGPASSWORD=$LOCAL_PASS pg_dump -h $LOCAL_HOST -p $LOCAL_PORT -U $LOCAL_USER -d $LOCAL_DB \
            --table=public.$table --no-owner --no-acl \
            | PGPASSWORD=$PROD_PASS psql -h $PROD_HOST -p $PROD_PORT -U $PROD_USER -d $PROD_DB -q 2>&1 | grep -v "already exists"
        done
      fi
    fi
    ;;
  5)
    echo -e "${BLUE}📝 Gerando scripts SQL...${NC}"
    if [ -s $MISSING_LOCAL ]; then
      SQL_FILE_ESTRUTURA="$TEMP_DIR/sync_to_local_estrutura.sql"
      SQL_FILE_COMPLETO="$TEMP_DIR/sync_to_local_completo.sql"
      echo "-- Script para LOCAL - Fonte: $PROD_DB ($PROD_HOST)" > $SQL_FILE_ESTRUTURA
      echo "-- Script Completo para LOCAL - Fonte: $PROD_DB ($PROD_HOST)" > $SQL_FILE_COMPLETO
      cat $MISSING_LOCAL | while read table; do
        PGPASSWORD=$PROD_PASS pg_dump -h $PROD_HOST -p $PROD_PORT -U $PROD_USER -d $PROD_DB \
          --table=public.$table --schema-only --no-owner --no-acl >> $SQL_FILE_ESTRUTURA
        PGPASSWORD=$PROD_PASS pg_dump -h $PROD_HOST -p $PROD_PORT -U $PROD_USER -d $PROD_DB \
          --table=public.$table --no-owner --no-acl >> $SQL_FILE_COMPLETO
      done
      echo -e "${GREEN}✅ Scripts salvos em $TEMP_DIR${NC}"
      echo "Arquivos: sync_to_local_estrutura.sql e sync_to_local_completo.sql"
    fi
    ;;
  6) echo -e "Saindo...";;
esac

echo ""
read -p "Deseja remover arquivos temporários? (s/n): " CLEANUP
if [ "$CLEANUP" == "s" ]; then rm -rf $TEMP_DIR; fi
