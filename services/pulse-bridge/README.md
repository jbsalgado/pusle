# Pulse Audio Bridge (Go Residential Worker)

O **Pulse Audio Bridge** é um microserviço leve em Go que atua como uma ponte residencial entre o seu computador local e a VPS do Pulse SaaS (`https://catalogos.oncode.app.br`), permitindo que a VPS extraia áudios do YouTube sem bloqueios anti-bot (`Sign in to confirm you're not a bot`).

---

## 🐧 No Linux (Configurado e Automático)

No seu notebook atual, o serviço já está configurado no **systemd** do usuário e inicia automaticamente sempre que o sistema ligar:

- **Status do serviço:**
  ```bash
  systemctl --user status pulse-bridge
  ```
- **Ver logs em tempo real:**
  ```bash
  journalctl --user -u pulse-bridge -f
  ```
- **Parar ou reiniciar:**
  ```bash
  systemctl --user restart pulse-bridge
  systemctl --user stop pulse-bridge
  ```

---

## 🪟 No Windows (Se Mudar de Computador)

Caso você mude para um notebook com Windows:

### Opção 1: Inicialização Automática com o Windows (Mais Simples)
1. Baixe o arquivo `pulse-bridge-windows.exe` (direto pelo Studio de Vídeos ou pela pasta `web/downloads/bridge/`).
2. Renomeie para `pulse-bridge.exe` e salve em uma pasta segura (ex: `C:\PulseBridge\pulse-bridge.exe`).
3. Crie um arquivo `iniciar.bat` na mesma pasta com o conteúdo:
   ```bat
   @echo off
   start "" "C:\PulseBridge\pulse-bridge.exe" -server=https://catalogos.oncode.app.br
   ```
4. Pressione `Win + R`, digite `shell:startup` e pressione Enter.
5. Crie um atalho do arquivo `iniciar.bat` dentro dessa pasta de Inicialização do Windows.
6. Pronto! Toda vez que o Windows ligar, ele iniciará o worker silenciosamente em segundo plano.
