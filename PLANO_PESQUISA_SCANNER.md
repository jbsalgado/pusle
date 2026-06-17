# Plano de Implementação: Busca e Scanner no Orçamento

Este documento detalha o plano para implementar a busca inteligente e leitor de código de barras (Webcam + Físico) no módulo de Orçamentos.

## 1. Interface (HTML)
- **Biblioteca:** Incluir `https://unpkg.com/html5-qrcode` no `<head>` do `index.html`.
- **Botão Scanner:** Adicionar botão com ícone de câmera (SVG) ao lado do input `busca-produto`.
- **Modal de Camera:** Criar um modal oculto com o ID `modal-scanner` contendo a div reservada para o vídeo da câmera.

## 2. Lógica de Busca (JS)
- **Leitor Físico:** Implementar um "Global Listener" no `app.js` que detecta entradas rápidas de teclado (Barcode Scanner USB/Bluetooth).
- **Scanner Webcam:**
  - Função `abrirScannerWebcam()`: Inicia a câmera e o processamento de imagem.
  - Função `pararScannerWebcam()`: Desliga a câmera e limpa os recursos.
- **Processamento:** Ao detectar um código, o sistema limpará o campo de busca, inserirá o código e disparará a função `filtrarProdutos()`.

## 3. Experiência do Usuário (UX)
- **Feedback:** Mostrar um "toast" ou alerta visual quando um código for lido com sucesso.
- **Auto-Foco:** Se a busca retornar apenas 1 produto via código de barras, o sistema focará na quantidade desse item.

---
**Status:** Aguardando Aprovação para Iniciar Execução.
