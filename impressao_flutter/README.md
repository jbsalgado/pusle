# Thermal Print Driver (Flutter)

Este projeto é uma reimplementação em Flutter do aplicativo "ThermalPrintDriver", projetado para funcionar como um driver universal de impressão térmica via Bluetooth e Wi-Fi.

## Funcionalidades

- **Conexão Bluetooth Classico (SPP)**: Suporte para maioria das impressoras térmicas genéricas.
- **Conexão Wi-Fi**: Impressão direta via Socket TCP/IP.
- **Deep Linking**: Acione impressões de sistemas externos via URL (`printapp://print?data=...`).
- **Conversão Automática**: Texto para comandos byte ESC/POS.

## Configuração Inicial

### Requisitos

- Flutter SDK 3.x+
- Dispositivo Android (Devido ao uso de Bluetooth SPP, suporte a iOS é limitado sem hardware MFi específico).

### Instalação

1. Clone o repositório ou navegue até a pasta `impressao_flutter`.
2. Instale as dependências:
   ```bash
   flutter pub get
   ```
3. Execute no dispositivo:
   ```bash
   flutter run
   ```

## Uso

### Conexão Bluetooth

1. Pareie sua impressora térmica nas configurações Bluetooth do Android.
2. Abra o app e conceda as permissões solicitadas.
3. Selecione a impressora na lista e clique em "Conectar Bluetooth".

### Conexão Wi-Fi

1. Insira o IP e Porta da impressora (Padrão: 9100).
2. Clique em "Conectar Wi-Fi".

### Deep Linking (Integração Web)

Para imprimir a partii de um site ou outro app, utilize o link:

```
printapp://print?data=TextoParaImprimir
```

Exemplo em HTML:

```html
<a href="printapp://print?data=Recibo%20de%20Teste%0Avalor:%2010,00"
  >Imprimir Recibo</a
>
```

## Estrutura do Projeto

- `lib/main.dart`: UI principal e lógica de Deep Link.
- `lib/services/printer_service.dart`: Gerenciamento de conexões BT/Wi-Fi.
- `lib/engine/esc_pos_engine.dart`: Conversor de texto para bytes ESC/POS.
