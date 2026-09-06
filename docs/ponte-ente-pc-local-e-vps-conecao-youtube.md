# Walkthrough: Pulse Audio Bridge (Go Residential Worker)

Implementamos com sucesso a arquitetura completa do **Cenário 2: Pulse Audio Bridge em Go**, permitindo que o SaaS na nuvem extraia áudios e trilhas do YouTube através de uma ponte residencial rápida, imune a bloqueios anti-bot de Data Center (`Sign in to confirm you're not a bot`) e sem exigir contas do Google ou cookies.

---

## 🏗️ Arquitetura Implementada

```
+-----------------------------------------------------------------------------------+
|                           SUA MÁQUINA / LOCALHOST                                 |
|                                                                                   |
|   +---------------------------------------------------------------------------+   |
|   |                     pulse-bridge (Go Worker v1.0)                         |   |
|   |  - IP Residencial (livre de bloqueios e captchas do YouTube)              |   |
|   |  - Conexão ativa reversa (não precisa abrir portas no roteador)           |   |
|   |  - Binário único e estático (~10 MB, Linux e Windows)                     |   |
|   +---------------------------------------------------------------------------+   |
+----------------------------------------|------------------------------------------+
                                         | Long-Polling HTTPS com Token Secreto
                                         v
+-----------------------------------------------------------------------------------+
|                              VPS DE PRODUÇÃO                                      |
|                       (https://catalogos.oncode.app.br)                           |
|                                                                                   |
|   1. Lojista cola link no Studio (/vendas/produto-video/studio)                  |
|   2. AudioProcessorService detecta que a Bridge está ONLINE                       |
|   3. BridgeController despacha a tarefa de download                               |
|   4. Worker local baixa via yt-dlp residencial em segundos                        |
|   5. Worker envia o MP3 via POST Multipart para a VPS                             |
|   6. VPS salva o MP3, registra em prest_trilhas_sonoras e devolve para o vídeo!   |
+-----------------------------------------------------------------------------------+
```

---

## 🛠️ Componentes Criados e Atualizados

### 1. Backend na VPS
- **Controller**: [`modules/api/controllers/BridgeController.php`](file:///srv/http/pulse/modules/api/controllers/BridgeController.php)
  - `actionStatus`: Consulta em tempo real se há algum worker Go ativo (`online: true/false`).
  - `actionPing`: Heartbeat seguro com token `X-Bridge-Secret`.
  - `actionPoll`: Long-polling de tarefas pendentes (`job_*.json`).
  - `actionSubmit`: Recebe o `.mp3` finalizado, salva em `uploads/audio/youtube/` e notifica conclusão.
  - `actionFail`: Reporta falhas de vídeos indisponíveis sem travar a fila.
  - `dispatchJob()`: Método síncrono que cria o job e aguarda a conclusão do worker.
- **Configuração**: Adicionado `pulse_bridge_secret` em [`config/params.php`](file:///srv/http/pulse/config/params.php).
- **Integração no Motor de Áudio**: [`modules/vendas/services/AudioProcessorService.php`](file:///srv/http/pulse/modules/vendas/services/AudioProcessorService.php)
  - **Prioridade 1:** Se o Bridge Go estiver online, despacha o download para ele.
  - **Prioridade 2:** Se o Bridge estiver offline, tenta o download via cookies locais da VPS.
  - **Prioridade 3:** Orientação amigável para envio de MP3 direto.

### 2. Microserviço em Go (Multiplataforma)
- **Código-Fonte**: [`services/pulse-bridge/main.go`](file:///srv/http/pulse/services/pulse-bridge/main.go)
  - Escrito em Go puro (sem dependências externas).
  - Flags de inicialização: `-server`, `-secret`, `-temp`, `-ytdlp`.
- **Binários Pré-Compilados Prontos para Uso**:
  - Linux: [`services/pulse-bridge/pulse-bridge`](file:///srv/http/pulse/services/pulse-bridge/pulse-bridge) (10.3 MB)
  - Windows: [`services/pulse-bridge/pulse-bridge.exe`](file:///srv/http/pulse/services/pulse-bridge/pulse-bridge.exe) (10.4 MB)
- **Download Direto pelo Navegador**:
  - Disponibilizados publicamente no SaaS em:
    - `https://catalogos.oncode.app.br/downloads/bridge/pulse-bridge-windows.exe`
    - `https://catalogos.oncode.app.br/downloads/bridge/pulse-bridge-linux`

### 3. Interface no Studio de Vídeos
- Em [`modules/vendas/views/produto-video/studio.php`](file:///srv/http/pulse/modules/vendas/views/produto-video/studio.php):
  - No modal de Importar Áudio do YouTube, adicionado badge de status em tempo real:
    - `🟢 Motor Residencial Ativo (Pulse Bridge Go)`
    - `☁️ Motor Nuvem VPS (Bridge offline)`
  - Botões diretos para baixar o executável do Bridge para Windows ou Linux com 1 clique.

---

## 🧪 Testes e Validação Real em Produção

O teste foi executado **diretamente contra a VPS de produção (`catalogos.oncode.app.br`)** com o worker conectado:

```text
=== TESTANDO INTEGRAÇÃO DO PULSE BRIDGE GO ===

1. Verificando status da Bridge...
   Online: SIM (Ativo)
   Último sinal: 4s atrás

2. Solicitando download via AudioProcessorService::downloadYoutubeAudio...
   URL: https://www.youtube.com/watch?v=kXYiU_JCYtU
   Resultado:
Array
(
    [success] => 1
    [cached] => 
    [via_bridge] => 1
    [id] => b84ebcec-843d-4344-a45f-937a9f366a9d
    [titulo] => Numb (Official Music Video) [4K UPGRADE] – Linkin Park
    [arquivo] => uploads/audio/youtube/yt_kXYiU_JCYtU.mp3
    [duracao] => 187
    [url] => /uploads/audio/youtube/yt_kXYiU_JCYtU.mp3
    [youtube_id] => kXYiU_JCYtU
)

   Tempo total de processamento: 13.84s
   Arquivo físico gerado em: /srv/http/alex-birds/pulse-plus/web/uploads/audio/youtube/yt_kXYiU_JCYtU.mp3
   Tamanho do arquivo: 2897.5 KB

=== TESTE CONCLUÍDO COM SUCESSO! ===
```

### Log de Execução do Worker em Go
```text
[Bridge] 📥 Nova tarefa recebida: Job=job_kXYiU_JCYtU_1788580499 | Vídeo=kXYiU_JCYtU
[Bridge] 🌐 URL: https://www.youtube.com/watch?v=kXYiU_JCYtU
[Bridge] 🔍 Obtendo informações do vídeo com IP residencial...
[Bridge] 🎵 Título: Numb (Official Music Video) [4K UPGRADE] – Linkin Park (Duração: 187.0s)
[Bridge] ⬇️ Baixando áudio e convertendo para MP3...
[Bridge] ⬆️ Enviando MP3 finalizado para a VPS...
[Bridge] ✅ Concluído e enviado à VPS em 13.4s!
```

---

## 🚀 Como Usar no Dia a Dia (Qualquer Computador)

1. **No seu computador atual (Linux):**
   ```bash
   cd /srv/http/pulse/services/pulse-bridge
   ./pulse-bridge -server=https://catalogos.oncode.app.br
   ```

2. **Se você mudar para outro computador (Windows ou Mac/Linux):**
   - Baixe o arquivo `pulse-bridge.exe` direto pelo modal do Studio no navegador.
   - Abra o terminal (Prompt de Comando / PowerShell) e execute:
     ```powershell
     .\pulse-bridge.exe -server=https://catalogos.oncode.app.br
     ```
   - O worker conecta na hora e já começa a atender o SaaS.
