import pty
import os
import time
import subprocess
import sys

def run_ssh_command(cmd_on_vps):
    ssh_cmd = f"ssh -o StrictHostKeyChecking=no root@2.25.182.204 '{cmd_on_vps}'"
    print(f"Executando no VPS: {ssh_cmd}")
    
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
        if time.time() - start_time > 30:
            proc.kill()
            break

    # Read remaining
    try:
        data = os.read(master, 4096).decode('utf-8', errors='ignore')
        output += data
        print(data, end='')
    except Exception:
        pass

    os.close(master)
    return output

if __name__ == '__main__':
    # 1. Procurar onde está o projeto na VPS
    out = run_ssh_command("find / -name 'ClientesController.php' 2>/dev/null")
    print("\nCaminhos encontrados:", out)
