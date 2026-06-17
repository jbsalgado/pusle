# RawBT Clone — Kotlin / Android Studio

Clone funcional do RawBT para impressoras térmicas Bluetooth ESC/POS.
Desenvolvido para Android 8+ (API 26), compatível com tablets TCL e celulares Android em geral.

---

## Funcionalidades

| Módulo | Descrição |
|---|---|
| `BluetoothPrinterManager` | Lista impressoras pareadas, conecta via SPP (UUID padrão), envia bytes |
| `EscPosBuilder` | Constrói comandos ESC/POS: texto, negrito, alinhamento, tamanho, imagem raster (GS v 0) |
| `PrinterSettings` | Persiste configurações via DataStore (papel, encoding, colunas, corte) |
| `ThermalPrintService` | Print Service nativo — aparece em Configurações → Impressão do Android |
| `MainActivity` | Lista impressoras pareadas, conecta, imprime página de teste |
| `PrintActivity` | Imprime texto livre, PDF e imagem; recebe compartilhamentos de outros apps |
| `SettingsActivity` | Configura largura do papel (58/80mm), encoding, modelo ESC/POS, colunas, corte auto |

---

## Como importar no Android Studio

1. Extraia o ZIP do projeto
2. Abra o Android Studio → **File → Open** → selecione a pasta `RawBTClone`
3. Aguarde o Gradle sincronizar (pode demorar na primeira vez)
4. Conecte seu tablet/celular via USB com **Depuração USB** ativada
5. Clique em **Run ▶** ou use `Shift+F10`

---

## Personalizar o pacote

Substitua `com.seuprefixo.rawbtclone` pelo seu pacote real em:
- `app/build.gradle.kts` → `applicationId`
- `AndroidManifest.xml` → `package`
- Todos os arquivos `.kt` → `package` no topo de cada arquivo

---

## Ativar como Print Service nativo

Após instalar o app:
1. Abra **Configurações** do Android
2. Vá em **Conexões → Mais → Impressão** (ou **Sistema → Avançado → Impressão**)
3. Toque em **RawBT Clone** e ative
4. Configure a impressora dentro do app
5. Qualquer app com opção "Imprimir" agora mostrará sua impressora!

---

## Encodings recomendados por tipo de impressora

| Impressora | Encoding |
|---|---|
| Maioria das térmicas genéricas | `CP860` |
| Epson / Bematech | `CP850` |
| Star | `CP437` |
| Caracteres especiais (acentos) | `ISO-8859-1` |

---

## Estrutura do projeto

```
RawBTClone/
├── app/src/main/
│   ├── AndroidManifest.xml
│   ├── java/com/seuprefixo/rawbtclone/
│   │   ├── bluetooth/
│   │   │   └── BluetoothPrinterManager.kt   ← Conexão BT / SPP
│   │   ├── escpos/
│   │   │   └── EscPosBuilder.kt             ← Comandos ESC/POS
│   │   ├── utils/
│   │   │   └── PrinterSettings.kt           ← DataStore / preferências
│   │   ├── service/
│   │   │   ├── ThermalPrintService.kt       ← Print Service nativo
│   │   │   └── ThermalPrinterDiscoverySession.kt
│   │   └── ui/
│   │       ├── main/
│   │       │   ├── MainActivity.kt
│   │       │   └── MainViewModel.kt
│   │       ├── print/
│   │       │   └── PrintActivity.kt
│   │       └── settings/
│   │           └── SettingsActivity.kt
│   └── res/
│       ├── layout/  (activity_main, activity_print, activity_settings)
│       ├── values/  (strings, colors, themes)
│       ├── menu/    (main_menu)
│       └── xml/     (print_service_config)
├── build.gradle.kts
├── settings.gradle.kts
└── gradle/libs.versions.toml
```
