#!/bin/bash
# ==============================================================================
# backup_pulse.sh — Backup completo do Pulse (Banco + Imagens)
# ==============================================================================
# Uso:
#   bash backup_pulse.sh             # modo interativo
#   bash backup_pulse.sh --cron      # modo silencioso (para crontab)
#   bash backup_pulse.sh --restaurar # restaurar um backup
#
# Crontab sugerido (backup diário às 02:00):
#   0 2 * * * /bin/bash /srv/http/pulse/backup_pulse.sh --cron >> /var/log/backup_pulse.log 2>&1
# ==============================================================================

set -euo pipefail

# ──────────────────────────────────────────────
# CONFIGURAÇÕES
# ──────────────────────────────────────────────

# Banco LOCAL
LOCAL_HOST="localhost"
LOCAL_PORT="5432"
LOCAL_DB="pulse"
LOCAL_USER="postgres"
LOCAL_PASS="postgres"

# Banco REMOTO (Produção - Top Construções)
PROD_HOST="72.61.221.180"
PROD_PORT="5432"
PROD_DB="pulse_top_construcoes"
PROD_USER="postgres"
PROD_PASS='@#628928@#'

# SSH do VPS Remoto
SSH_USER="root"
SSH_PASS='@#Jbs928628Jbs@#'
SSH_PORT="22"
REMOTE_UPLOADS="/srv/http/pulse-top-construcoes/web/uploads"

# Diretórios
UPLOADS_DIR="/srv/http/pulse/web/uploads"
BACKUP_DEST="${HOME}/backups/pulse"
RETENTION_DAYS=14   # apaga backups mais antigos que X dias

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# Timestamp
TS=$(date '+%Y-%m-%d_%H-%M')
BACKUP_DIR="${BACKUP_DEST}/${TS}"
CRON_MODE=false

# ──────────────────────────────────────────────
# FUNÇÕES UTILITÁRIAS
# ──────────────────────────────────────────────

log()    { echo -e "${GREEN}✅ $*${NC}"; }
info()   { echo -e "${BLUE}ℹ️  $*${NC}"; }
warn()   { echo -e "${YELLOW}⚠️  $*${NC}"; }
error()  { echo -e "${RED}❌ $*${NC}" >&2; }
header() { echo -e "\n${BOLD}${CYAN}═══════════════════════════════${NC}"; echo -e "${BOLD}${CYAN}  $*${NC}"; echo -e "${BOLD}${CYAN}═══════════════════════════════${NC}"; }

check_deps() {
    local missing=()
    for cmd in pg_dump psql gzip tar; do
        command -v "$cmd" &>/dev/null || missing+=("$cmd")
    done
    if [[ ${#missing[@]} -gt 0 ]]; then
        error "Dependências ausentes: ${missing[*]}"
        error "Instale com: sudo pacman -S postgresql-libs (Arch) ou sudo apt install postgresql-client (Debian/Ubuntu)"
        exit 1
    fi
}

criar_manifest() {
    local dir="$1"
    local tipo="$2"
    cat > "${dir}/manifest.txt" <<EOF
Pulse Backup Manifest
=====================
Tipo:         $tipo
Data/Hora:    $(date '+%Y-%m-%d %H:%M:%S')
Hostname:     $(hostname)
PostgreSQL:   $(pg_dump --version 2>/dev/null || echo "desconhecido")
Conteúdo:
$(ls -lh "${dir}"/*.{gz,txt} 2>/dev/null || true)
EOF
    log "Manifest salvo em ${dir}/manifest.txt"
}

limpar_backups_antigos() {
    if [[ -d "$BACKUP_DEST" ]]; then
        info "Removendo backups com mais de ${RETENTION_DAYS} dias..."
        find "$BACKUP_DEST" -maxdepth 1 -mindepth 1 -type d -mtime "+${RETENTION_DAYS}" -exec rm -rf {} \; 2>/dev/null || true
        log "Limpeza concluída"
    fi
}

tamanho_legivel() {
    du -sh "$1" 2>/dev/null | cut -f1 || echo "?"
}

# ──────────────────────────────────────────────
# BACKUP DO BANCO LOCAL
# ──────────────────────────────────────────────

backup_banco_local() {
    header "Backup Banco LOCAL ($LOCAL_DB)"
    mkdir -p "$BACKUP_DIR"

    local arquivo="${BACKUP_DIR}/local_${LOCAL_DB}_${TS}.sql.gz"
    info "Executando pg_dump → ${arquivo}"

    PGPASSWORD="$LOCAL_PASS" pg_dump \
        -h "$LOCAL_HOST" \
        -p "$LOCAL_PORT" \
        -U "$LOCAL_USER" \
        -d "$LOCAL_DB" \
        --no-password \
        --verbose \
        --format=plain \
        --no-acl \
        --no-owner \
        2>/dev/null \
        | gzip -9 > "$arquivo"

    log "Banco LOCAL salvo: ${arquivo} ($(tamanho_legivel "$arquivo"))"
}

# ──────────────────────────────────────────────
# BACKUP DO BANCO REMOTO
# ──────────────────────────────────────────────

backup_banco_remoto() {
    header "Backup Banco REMOTO ($PROD_DB @ $PROD_HOST)"
    mkdir -p "$BACKUP_DIR"

    local arquivo="${BACKUP_DIR}/remote_${PROD_DB}_${TS}.sql.gz"
    info "Conectando ao banco remoto e executando pg_dump..."
    info "Host: ${PROD_HOST}:${PROD_PORT} → ${arquivo}"

    PGPASSWORD="$PROD_PASS" pg_dump \
        -h "$PROD_HOST" \
        -p "$PROD_PORT" \
        -U "$PROD_USER" \
        -d "$PROD_DB" \
        --no-password \
        --verbose \
        --format=plain \
        --no-acl \
        --no-owner \
        2>/dev/null \
        | gzip -9 > "$arquivo"

    log "Banco REMOTO salvo: ${arquivo} ($(tamanho_legivel "$arquivo"))"
}

# ──────────────────────────────────────────────
# BACKUP DAS IMAGENS (LOCAL)
# ──────────────────────────────────────────────

backup_imagens_local() {
    header "Backup Imagens Locais"
    mkdir -p "$BACKUP_DIR"

    if [[ ! -d "$UPLOADS_DIR" ]]; then
        warn "Diretório de uploads não encontrado: ${UPLOADS_DIR}"
        return
    fi

    local arquivo="${BACKUP_DIR}/imagens_locais_${TS}.tar.gz"
    local total
    total=$(find "$UPLOADS_DIR" -type f | wc -l)
    info "Compactando ${total} arquivos de imagem → ${arquivo}"

    tar -czf "$arquivo" -C "$(dirname "$UPLOADS_DIR")" "$(basename "$UPLOADS_DIR")"

    log "Imagens locais salvas: ${arquivo} ($(tamanho_legivel "$arquivo"))"
}

# ──────────────────────────────────────────────
# BACKUP DAS IMAGENS DO SERVIDOR REMOTO (via SCP)
# ──────────────────────────────────────────────

backup_imagens_remoto() {
    header "Backup Imagens Remotas (Otimizado)"
    mkdir -p "$BACKUP_DIR"

    # Verifica sshpass
    if ! command -v sshpass &>/dev/null; then
        error "sshpass não encontrado. Instale com 'sudo pacman -S sshpass' ou similar."
        return
    fi

    local remote_temp_tar="/tmp/pulse_images_${TS}.tar.gz"
    local arquivo_local="${BACKUP_DIR}/imagens_remotas_${TS}.tar.gz"

    info "1. Conectando ao VPS ${SSH_USER}@${PROD_HOST}..."
    
    # 1. Verifica diretório e compacta no remoto
    info "2. Compactando arquivos no servidor remoto (pode demorar um pouco)..."
    if ! sshpass -p "${SSH_PASS}" ssh -o StrictHostKeyChecking=no -p "${SSH_PORT}" "${SSH_USER}@${PROD_HOST}" \
        "if [ -d '${REMOTE_UPLOADS}' ]; then tar -czf '${remote_temp_tar}' -C '${REMOTE_UPLOADS}' . ; else exit 1; fi" 2>/dev/null; then
        error "Diretório remoto não encontrado ou erro ao compactar: ${REMOTE_UPLOADS}"
        return
    fi

    # 2. Pega o tamanho do arquivo remoto para informar o usuário
    local size_remoto
    size_remoto=$(sshpass -p "${SSH_PASS}" ssh -o StrictHostKeyChecking=no -p "${SSH_PORT}" "${SSH_USER}@${PROD_HOST}" \
        "du -sh '${remote_temp_tar}' | cut -f1" 2>/dev/null || echo "?")
    info "3. Arquivo compactado no servidor: ${size_remoto}"

    # 3. Faz o download do arquivo único (muito mais rápido que centenas de arquivos pequenos)
    info "4. Baixando arquivo para local..."
    if sshpass -p "${SSH_PASS}" scp -P "${SSH_PORT}" -o StrictHostKeyChecking=no \
        "${SSH_USER}@${PROD_HOST}:${remote_temp_tar}" "${arquivo_local}"; then
        
        log "Download concluído: ${arquivo_local} ($(tamanho_legivel "$arquivo_local"))"
    else
        error "Falha ao baixar arquivo via SCP"
        return
    fi

    # 4. Limpa o arquivo temporário no servidor remoto
    info "5. Limpando arquivos temporários no servidor remoto..."
    sshpass -p "${SSH_PASS}" ssh -o StrictHostKeyChecking=no -p "${SSH_PORT}" "${SSH_USER}@${PROD_HOST}" \
        "rm -f '${remote_temp_tar}'" 2>/dev/null || true
    
    log "Backup de imagens remotas finalizado com sucesso!"
}

# ──────────────────────────────────────────────
# RESTAURAÇÃO
# ──────────────────────────────────────────────

restaurar_backup() {
    header "Restauração de Backup"

    if [[ ! -d "$BACKUP_DEST" ]]; then
        error "Diretório de backups não encontrado: ${BACKUP_DEST}"
        exit 1
    fi

    echo -e "\n${BOLD}Backups disponíveis:${NC}"
    local i=1
    declare -a dirs
    while IFS= read -r d; do
        dirs+=("$d")
        echo "  ${i}) $(basename "$d")"
        ((i++))
    done < <(find "$BACKUP_DEST" -maxdepth 1 -mindepth 1 -type d | sort -r)

    if [[ ${#dirs[@]} -eq 0 ]]; then
        error "Nenhum backup encontrado em ${BACKUP_DEST}"
        exit 1
    fi

    read -rp $'\nEscolha o número do backup para restaurar: ' escolha
    local idx=$((escolha - 1))
    local dir_selecionado="${dirs[$idx]}"

    echo ""
    echo -e "${BOLD}Arquivos disponíveis em $(basename "$dir_selecionado"):${NC}"
    local j=1
    declare -a arquivos
    while IFS= read -r f; do
        arquivos+=("$f")
        local size
        size=$(tamanho_legivel "$f")
        echo "  ${j}) $(basename "$f") [${size}]"
        ((j++))
    done < <(find "$dir_selecionado" -maxdepth 1 -type f \( -name "*.gz" -o -name "*.tar.gz" \) | sort)

    read -rp $'\nEscolha o arquivo para restaurar: ' arq_escolha
    local arq_idx=$((arq_escolha - 1))
    local arq="${arquivos[$arq_idx]}"
    local nome
    nome=$(basename "$arq")

    echo ""
    error "ATENÇÃO: Esta operação pode sobrescrever dados existentes!"
    read -rp "Tem certeza? Digite 'SIM RESTAURAR' para confirmar: " conf
    if [[ "$conf" != "SIM RESTAURAR" ]]; then
        warn "Operação cancelada"
        exit 0
    fi

    if [[ "$nome" == *.sql.gz ]]; then
        read -rp "Restaurar em qual banco? [${LOCAL_DB}]: " target_db
        target_db="${target_db:-$LOCAL_DB}"
        info "Restaurando ${nome} → ${target_db}..."
        zcat "$arq" | PGPASSWORD="$LOCAL_PASS" psql -h "$LOCAL_HOST" -p "$LOCAL_PORT" -U "$LOCAL_USER" -d "$target_db"
        log "Banco restaurado com sucesso!"
    elif [[ "$nome" == *.tar.gz ]]; then
        read -rp "Restaurar imagens em [${UPLOADS_DIR}]: " target_dir
        target_dir="${target_dir:-$UPLOADS_DIR}"
        info "Restaurando ${nome} → ${target_dir}..."
        mkdir -p "$target_dir"
        tar -xzf "$arq" -C "$target_dir"
        log "Imagens restauradas com sucesso!"
    else
        error "Tipo de arquivo não suportado para restauração: ${nome}"
    fi
}

# ──────────────────────────────────────────────
# LISTAR BACKUPS
# ──────────────────────────────────────────────

listar_backups() {
    header "Backups Existentes"

    if [[ ! -d "$BACKUP_DEST" ]]; then
        warn "Nenhum backup encontrado em ${BACKUP_DEST}"
        return
    fi

    local total=0
    while IFS= read -r d; do
        echo -e "\n${BOLD}📁 $(basename "$d")${NC}"
        find "$d" -maxdepth 1 -type f | while IFS= read -r f; do
            echo "   $(tamanho_legivel "$f")  $(basename "$f")"
        done
        ((total++))
    done < <(find "$BACKUP_DEST" -maxdepth 1 -mindepth 1 -type d | sort -r)

    echo ""
    info "Total de backups encontrados: ${total}"
    info "Espaço total ocupado: $(tamanho_legivel "$BACKUP_DEST")"
}

# ──────────────────────────────────────────────
# MENU PRINCIPAL
# ──────────────────────────────────────────────

menu_principal() {
    clear
    echo -e "${BOLD}${CYAN}"
    cat << 'EOF'
  ██████  █████   ██████ ██   ██ ██    ██ ██████
  ██   ██ ██   ██ ██      ██  ██ ██    ██ ██   ██
  ██████  ███████ ██      █████  ██    ██ ██████
  ██   ██ ██   ██ ██      ██  ██ ██    ██ ██
  ██████  ██   ██  ██████ ██   ██  ██████ ██
            Sistema de Backup Completo
EOF
    echo -e "${NC}"
    echo -e "  ${BOLD}Destino dos backups:${NC} ${BACKUP_DEST}"
    echo -e "  ${BOLD}Retenção:${NC} ${RETENTION_DAYS} dias"
    echo ""
    echo -e "  ${BOLD}1)${NC} Backup LOCAL  (banco ${LOCAL_DB} + imagens locais)"
    echo -e "  ${BOLD}2)${NC} Backup REMOTO (banco ${PROD_DB} no servidor de produção)"
    echo -e "  ${BOLD}3)${NC} Backup COMPLETO (local + remoto + imagens remotas via SCP)"
    echo -e "  ${BOLD}4)${NC} Restaurar um backup"
    echo -e "  ${BOLD}5)${NC} Listar backups existentes"
    echo -e "  ${BOLD}6)${NC} Sair"
    echo ""
    read -rp "Escolha uma opção (1-6): " opcao
    echo ""

    case "$opcao" in
        1)
            backup_banco_local
            backup_imagens_local
            criar_manifest "$BACKUP_DIR" "LOCAL"
            limpar_backups_antigos
            ;;
        2)
            backup_banco_remoto
            backup_imagens_remoto
            criar_manifest "$BACKUP_DIR" "REMOTO"
            limpar_backups_antigos
            ;;
        3)
            backup_banco_local
            backup_banco_remoto
            backup_imagens_local
            backup_imagens_remoto
            criar_manifest "$BACKUP_DIR" "COMPLETO"
            limpar_backups_antigos
            ;;
        4) restaurar_backup ;;
        5) listar_backups ;;
        6) echo -e "${GREEN}Até logo!${NC}"; exit 0 ;;
        *) error "Opção inválida: ${opcao}"; exit 1 ;;
    esac

    echo ""
    header "Backup Concluído"
    listar_backups

    echo ""
    echo -e "${BOLD}💡 Crontab sugerido (backup automático diário às 02:00):${NC}"
    echo -e "   ${CYAN}0 2 * * * /bin/bash $(realpath "$0") --cron >> /var/log/backup_pulse.log 2>&1${NC}"
}

# ──────────────────────────────────────────────
# MODO CRON (backup completo silencioso)
# ──────────────────────────────────────────────

modo_cron() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Iniciando backup automático (cron)..."
    mkdir -p "$BACKUP_DIR"
    backup_banco_local    2>&1 | sed 's/\x1b\[[0-9;]*m//g'
    backup_banco_remoto   2>&1 | sed 's/\x1b\[[0-9;]*m//g' || warn "Backup remoto falhou (continuando...)"
    backup_imagens_local  2>&1 | sed 's/\x1b\[[0-9;]*m//g'
    criar_manifest "$BACKUP_DIR" "CRON"
    limpar_backups_antigos
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Backup concluído → ${BACKUP_DIR}"
}

# ──────────────────────────────────────────────
# PONTO DE ENTRADA
# ──────────────────────────────────────────────

check_deps

case "${1:-}" in
    --cron)      modo_cron ;;
    --restaurar) restaurar_backup ;;
    --listar)    listar_backups ;;
    *)           menu_principal ;;
esac
