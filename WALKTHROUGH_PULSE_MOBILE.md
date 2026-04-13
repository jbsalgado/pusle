# Walkthrough - Integração Nativa WhatsApp Pulse

Este documento detalha a solução implementada para o compartilhamento de comprovantes de venda como imagem diretamente para o WhatsApp via App Android.

## 1. Problema Resolvido
O compartilhamento via web anteriormente tentava abrir o WhatsApp Web ou enviava apenas texto. Agora, o sistema detecta se está rodando dentro do App Pulse e utiliza uma ponte nativa para compartilhar a **imagem real** do comprovante.

## 2. Alterações Realizadas

### App Android (Flutter)
- **Caminho**: `pulse_app/lib/main.dart`
- **Funcionalidade**: Adicionado o handler `shareImage` que:
  1. Recebe a imagem em Base64 do frontend.
  2. Salva em um diretório temporário do Android.
  3. Aciona o menu de compartilhamento nativo para envio direto ao WhatsApp.

### Venda Direta (JavaScript)
- **Caminho**: `web/venda-direta/js/pix.js`
- **Funcionalidade**:
  - Novo botão **WhatsApp** adicionado à modal de finalização de venda.
  - Lógica de detecção: `window.flutter_inappwebview`.
  - Se estiver no app, converte o Blob do comprovante em Base64 e chama o Flutter.
  - Se estiver no navegador comum, faz o download do arquivo como fallback.

## 3. Como Gerar o APK
Como o ambiente do servidor possui restrições de execução (Timeout de Snap), siga estes passos no seu computador local:

1. Puxe as atualizações do servidor.
2. Entre na pasta `pulse_app`.
3. Execute:
   ```bash
   flutter pub get
   flutter build apk --release
   ```
4. O APK estará em: `build/app/outputs/flutter-apk/app-release.apk`.

## 4. Como Testar
1. Instale o APK no seu celular.
2. Abra o App e vá no módulo de **Venda Direta**.
3. Realize uma venda.
4. Na tela de comprovante, clique no botão **WhatsApp**.
5. O menu de contatos do WhatsApp deve abrir já com a imagem anexada.

---
*Documentação gerada pela Antigravity em 13/04/2026*
