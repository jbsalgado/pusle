import subprocess
import sys

VPS_LIST = [
    {
        "name": "VPS 1 - Only Code (2.25.182.204)",
        "host": "2.25.182.204",
        "pass": "@#Jbs992888872Jbs@#",
        "dirs": [
            "/srv/http/alex-birds/pulse-plus",
            "/srv/http/construcao/pulse-plus",
            "/srv/http/sistemas/pulse-plus"
        ]
    },
    {
        "name": "VPS 2 - Top Construções (72.61.221.180)",
        "host": "72.61.221.180",
        "pass": "@#Jbs992888872Jbs@#",
        "dirs": [
            "/srv/http/pulse-arte-blusas",
            "/srv/http/pulse-top-construcoes",
            "/srv/http/pulse-sara-shop",
            "/srv/http/pulse-v1"
        ]
    }
]

def update_vps(vps):
    print("\n" + "="*70)
    print(f" INICIANDO ATUALIZAÇÃO: {vps['name']}")
    print("="*70)
    
    dirs_str = " ".join(vps["dirs"])
    remote_script = f"""
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
for d in {dirs_str}; do
    if [ -d "$d/.git" ]; then
        echo ""
        echo "------------------------------------------------------------"
        echo ">>> Atualizando: $d"
        echo "------------------------------------------------------------"
        cd "$d" || continue
        
        echo "[1/4] git fetch & sync origin/main..."
        git checkout -f main 2>/dev/null || true
        git clean -fd web/downloads/ 2>/dev/null || true
        git fetch origin main
        git reset --hard origin/main
        
        if [ -f yii ]; then
            echo "[2/4] Verificando migrations..."
            php yii migrate --interactive=0 2>&1 || true
            
            echo "[3/4] Limpando cache do Yii..."
            php yii cache/flush-schema --interactive=0 2>&1 || true
            php yii cache/flush-all --interactive=0 2>&1 || true
            rm -rf runtime/cache/* 2>/dev/null || true
            chown -R http:http runtime 2>/dev/null || true
            chmod -R 775 runtime 2>/dev/null || true
        fi

        if [ -d "$d/server-ws" ] && ( [ "$d" = "/srv/http/alex-birds/pulse-plus" ] || [ "$d" = "/srv/http/pulse-v1" ] ); then
            echo "[WebSocket] Configurando daemon WebSocket server-ws..."
            cd "$d/server-ws" || true
            npm install --production 2>&1 || true
            if [ -f "pulse-ws.service" ]; then
                sed "s|/srv/http/alex-birds/pulse-plus/server-ws|$d/server-ws|g" pulse-ws.service > /etc/systemd/system/pulse-ws.service
                systemctl daemon-reload
                systemctl enable pulse-ws 2>/dev/null || true
                systemctl restart pulse-ws
                echo "[WebSocket] Status do servico: $(systemctl is-active pulse-ws)"
            else
                systemctl restart pulse-ws 2>/dev/null || true
            fi
            cd "$d" || true
        fi
        
        echo "[4/4] Versão atualizada do repositório:"
        git log -1 --format="%h - %an: %s (%ci)"
    else
        echo "Aviso: Diretório $d não encontrado ou não é git."
    fi
done

echo ""
echo ">>> Recarregando PHP-FPM e fila pulse-queue..."
systemctl reload php-fpm 2>/dev/null || true
systemctl restart pulse-queue 2>/dev/null || true
echo "✓ PHP-FPM status: $(systemctl is-active php-fpm)"
echo "✓ pulse-queue status: $(systemctl is-active pulse-queue 2>/dev/null || echo 'não instalado')"

"""
    
    cmd = [
        "sshpass", "-p", vps["pass"],
        "ssh", "-o", "StrictHostKeyChecking=no", "-o", "ConnectTimeout=20",
        f"root@{vps['host']}",
        remote_script
    ]
    
    proc = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True)
    for line in proc.stdout:
        print(line, end='', flush=True)
    proc.wait()
    
    if proc.returncode == 0:
        print(f"\n✅ {vps['name']} ATUALIZADA COM SUCESSO!")
    else:
        print(f"\n⚠️ Código de saída em {vps['name']}: {proc.returncode}")

if __name__ == '__main__':
    for vps in VPS_LIST:
        update_vps(vps)
    print("\n" + "="*70)
    print(" TODAS AS VPS FORAM PROCESSADAS!")
    print("="*70)
