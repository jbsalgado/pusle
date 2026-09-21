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
        
        echo "[1/3] git fetch & pull..."
        git fetch origin main
        git pull origin main
        
        if [ -f yii ]; then
            echo "[2/3] Verificando migrations..."
            php yii migrate --interactive=0 2>&1 || true
            
            echo "[3/3] Limpando cache do Yii..."
            php yii cache/flush-schema --interactive=0 2>&1 || true
            php yii cache/flush-all --interactive=0 2>&1 || true
            rm -rf runtime/cache/* 2>/dev/null || true
        fi
        
        echo "✓ Versão atual do repositório:"
        git log -1 --format="%h - %an: %s (%ci)"
    else
        echo "Aviso: Diretório $d não encontrado ou não é git."
    fi
done
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
