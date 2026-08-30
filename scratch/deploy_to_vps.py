import pty
import os
import time
import subprocess

def run_ssh_command(cmd_on_vps):
    ssh_cmd = f"ssh -o StrictHostKeyChecking=no root@2.25.182.204 '{cmd_on_vps}'"
    print(f"Executando no VPS: {cmd_on_vps}")
    
    master, slave = pty.openpty()
    proc = subprocess.Popen(ssh_cmd, shell=True, stdin=slave, stdout=master, stderr=master, close_fds=True)
    os.close(slave)
    
    output = ""
    password_sent = False
    
    start_time = time.time()
    while proc.poll() is None:
        try:
            data = os.read(master, 1024).decode('utf-8', errors='ignore')
            if data:
                output += data
                print(data, end='')
                if 'password:' in data.lower() and not password_sent:
                    os.write(master, b"@#Jbs992888872Jbs@#\n")
                    password_sent = True
        except Exception:
            break
        time.sleep(0.1)
        if time.time() - start_time > 45:
            proc.kill()
            break

    try:
        data = os.read(master, 4096).decode('utf-8', errors='ignore')
        output += data
        print(data, end='')
    except Exception:
        pass

    os.close(master)
    return output

if __name__ == '__main__':
    print("=== Atualizando código na VPS e executando migration ===")
    
    # 1. Localizar pasta do projeto no servidor VPS
    find_out = run_ssh_command("find /srv /var /var/www -name 'yii' 2>/dev/null")
    print("Arquivos yii encontrados:", find_out)
    
    # Executa git pull e yii migrate no diretório do projeto na VPS
    cmds = "cd /srv/http/pulse || cd /var/www/html/pulse || cd /var/www/pulse; git pull origin main; php yii migrate --interactive=0"
    run_ssh_command(cmds)
    
    print("\n=== Atualização concluída ===")
