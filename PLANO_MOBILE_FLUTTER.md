# Plano de Implementação: App Android Pulse (Flutter)

Este documento descreve a estratégia para encapsular o projeto Pulse em um aplicativo nativo Android usando Flutter.

## 1. Visão Geral
O aplicativo funcionará como um 'Shell' (WebView de alta performance) para a aplicação web existente, integrando recursos nativos:
- **URL Alvo**: `https://top-construcoes.catalogo.cloud/`
- **Diretório do App**: `/srv/http/pulse/pulse_app`
- **Notificações**: Implementação via **Foreground Service** + **Polling API em PHP** (Consulta direta à tabela `sys_notificacoes_app`), eliminando dependência de Firebase/Google.
- **Scanner Híbrido**: Suporte tanto para câmera (via plugin nativo) quanto para leitores físicos (USB/Bluetooth via eventos de teclado).
- **Impressão Térmica**: Conexão direta via Bluetooth/Wi-Fi.

## 2. Arquitetura do Flutter
- **Motor**: `flutter_inappwebview`.
- **Bridge JS**: Canal de comunicação `PulseBridge` para o frontend web chamar funções do sistema Android.
- **Serviços**:
    - `PrinterService`: Gerencia pareamento e envio de dados para impressoras térmicas. 
    - `ScannerService`: Abre uma interface de câmera nativa sobreposta ao WebView para bips rápidos.

## 3. Próximos Passos
1. **Inicialização do Projeto**: Executar `flutter create pulse_app` dentro de `/srv/http/pulse/`.
2. **Desenvolvimento do Shell**: Implementar o WebView com suporte a permissões Android.
3. **Ponte de Comunicação**: Criar os handlers no Dart para escutar pedidos de impressão e scan vindo do JavaScript.
4. **Build e Teste**: Gerar APK para validação.

## 4. Questões em Aberto
- **Persistência**: Ao evitar o Firebase, o Android exige que o app mostre um ícone permanente na barra de status para poder "ouvir" notificações com o app fechado. Isso é aceitável para você?
