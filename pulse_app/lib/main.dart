import 'dart:async';
import 'dart:io';
import 'dart:convert';
import 'dart:isolate';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bluetooth_serial/flutter_bluetooth_serial.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:flutter_foreground_task/flutter_foreground_task.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:path_provider/path_provider.dart';
import 'services/printer_service.dart';
import 'package:http/http.dart' as http;

// Gerenciador de tarefas em segundo plano (para notificações sem Firebase)
@pragma('vm:entry-point')
void startCallback() {
  FlutterForegroundTask.setTaskHandler(NotificationTaskHandler());
}

class NotificationTaskHandler extends TaskHandler {
  @override
  void onStart(DateTime timestamp, SendPort? sendPort) {}

  @override
  void onRepeatEvent(DateTime timestamp, SendPort? sendPort) async {
    // URL convertida para o endpoint interno
    final url = Uri.parse("https://top-construcoes.catalogo.cloud/api/notificacao");
    
    try {
      // Nota: Em produção, o token JWT deve ser passado aqui. 
      // Por simplicidade na demonstração, assume que o app gerencia a sessão.
      final response = await http.get(url, headers: {"Accept": "application/json"});
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] && data['data'] != null && data['data'].isNotEmpty) {
           for (var notif in data['data']) {
             FlutterForegroundTask.updateService(
               notificationTitle: notif['titulo'], 
               notificationText: notif['mensagem']
             );
             // Opcional: Marcar como lida imediatamente ou esperar o clique
           }
        }
      }
    } catch (e) {
      debugPrint("Erro no polling: $e");
    }
  }

  @override
  void onDestroy(DateTime timestamp, SendPort? sendPort) {}
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  _initForegroundTask();
  
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => PrinterService()),
      ],
      child: const PulseApp(),
    ),
  );
}

void _initForegroundTask() {
  FlutterForegroundTask.init(
    androidNotificationOptions: AndroidNotificationOptions(
      channelId: 'pulse_notification_channel',
      channelName: 'Pulse Notifications',
      channelDescription: 'Monitoramento de alertas Pulse',
      channelImportance: NotificationChannelImportance.LOW,
      priority: NotificationPriority.LOW,
      iconData: const NotificationIconData(
        resType: ResourceType.mipmap,
        resPrefix: ResourcePrefix.ic,
        name: 'launcher',
      ),
    ),
    iosNotificationOptions: const IOSNotificationOptions(
      showNotification: true,
      playSound: false,
    ),
    foregroundTaskOptions: const ForegroundTaskOptions(
      interval: 5000,
      isOnceEvent: false,
      autoRunOnBoot: true,
      allowWakeLock: true,
      allowWifiLock: true,
    ),
  );
}

class PulseApp extends StatelessWidget {
  const PulseApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Pulse Mobile',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primarySwatch: Colors.blue,
        useMaterial3: true,
      ),
      home: const PulseHomePage(),
    );
  }
}

class PulseHomePage extends StatefulWidget {
  const PulseHomePage({super.key});

  @override
  State<PulseHomePage> createState() => _PulseHomePageState();
}

class _PulseHomePageState extends State<PulseHomePage> {
  final GlobalKey webViewKey = GlobalKey();
  InAppWebViewController? webViewController;
  
  final String targetUrl = "https://top-construcoes.catalogo.cloud/";
  
  // Acumulador para scanner de hardware (USB/Bluetooth)
  String _barcodeBuffer = "";
  DateTime? _lastKeyPress;

  @override
  void initState() {
    super.initState();
    _requestPermissions();
    _startService();
  }

  Future<void> _requestPermissions() async {
    await [
      Permission.camera,
      Permission.bluetooth,
      Permission.bluetoothScan,
      Permission.bluetoothConnect,
      Permission.location,
      Permission.notification,
    ].request();
  }

  Future<void> _startService() async {
    if (await FlutterForegroundTask.isRunningService) {
      return;
    }
    
    await FlutterForegroundTask.startService(
      notificationTitle: 'Pulse Ativo',
      notificationText: 'Monitorando notificações em tempo real',
      callback: startCallback,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: KeyboardListener(
          focusNode: FocusNode()..requestFocus(),
          autofocus: true,
          onKeyEvent: _handleHardwareKey,
          child: Stack(
            children: [
              InAppWebView(
                key: webViewKey,
                initialUrlRequest: URLRequest(url: WebUri(targetUrl)),
                initialSettings: InAppWebViewSettings(
                  javaScriptEnabled: true,
                  useWideViewPort: true,
                  loadWithOverviewMode: true,
                  domStorageEnabled: true,
                  databaseEnabled: true,
                ),
                onWebViewCreated: (controller) {
                  webViewController = controller;
                  _setupJavaScriptBridge(controller);
                },
              ),
            ],
          ),
        ),
      ),
      floatingActionButton: FloatingActionButton(
        mini: true,
        child: const Icon(Icons.print),
        onPressed: () => _showPrinterSettings(),
      ),
    );
  }

  // Lógica para Scanner de Hardware (USB/BT que simulam teclado)
  void _handleHardwareKey(KeyEvent event) {
    if (event is KeyDownEvent) {
      final now = DateTime.now();
      
      // Se demorar muito entre as teclas, limpa o buffer (provavelmente digitação manual)
      if (_lastKeyPress != null && now.difference(_lastKeyPress!).inMilliseconds > 100) {
        _barcodeBuffer = "";
      }
      _lastKeyPress = now;

      if (event.logicalKey == LogicalKeyboardKey.enter) {
        if (_barcodeBuffer.isNotEmpty) {
          _sendBarcodeToWeb(_barcodeBuffer);
          _barcodeBuffer = "";
        }
      } else if (event.character != null) {
        _barcodeBuffer += event.character!;
      }
    }
  }

  void _sendBarcodeToWeb(String code) {
    webViewController?.evaluateJavascript(source: "if(window.executarBuscaPorCodigo) window.executarBuscaPorCodigo('$code');");
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Lido via Hardware: $code"), duration: const Duration(seconds: 1)));
  }

  void _setupJavaScriptBridge(InAppWebViewController controller) {
    controller.addJavaScriptHandler(
      handlerName: 'PulseBridge',
      callback: (args) async {
        final String action = args[0];
        final dynamic data = args.length > 1 ? args[1] : null;

        switch (action) {
          case 'print':
            return await _handlePrint(data);
          case 'scanBarcode':
            return await _showScanner();
          case 'notify':
            _showLocalNotification(data);
            return {"success": true};
          case 'shareImage':
            return await _handleShareImage(data);
          default:
            return {"error": "Unknown action: $action"};
        }
      },
    );
  }

  void _showLocalNotification(dynamic data) {
    String title = "Notificação Pulse";
    String body = data.toString();
    if (data is Map) {
      title = data['title'] ?? title;
      body = data['body'] ?? body;
    }
    FlutterForegroundTask.updateService(notificationTitle: title, notificationText: body);
  }

  Future<Map<String, dynamic>> _handleShareImage(dynamic data) async {
    try {
      final String base64String = data is String ? data : data['base64'];
      final String fileName = data is Map ? (data['fileName'] ?? 'comprovante.png') : 'comprovante.png';
      
      // Decodifica Base64 (remove prefixo data:image/png;base64, se existir)
      final String cleanBase64 = base64String.contains(',') ? base64String.split(',').last : base64String;
      final bytes = base64Decode(cleanBase64);
      
      // Salva em arquivo temporário
      final tempDir = await getTemporaryDirectory();
      final file = File('${tempDir.path}/$fileName');
      await file.writeAsBytes(bytes);
      
      // Compartilha usando o menu nativo do Android
      await Share.shareXFiles([XFile(file.path)], text: 'Segue o comprovante de venda. Obrigado pela preferência!');
      
      return {"success": true};
    } catch (e) {
      return {"success": false, "error": e.toString()};
    }
  }

  Future<Map<String, dynamic>> _handlePrint(dynamic data) async {
    final printerService = Provider.of<PrinterService>(context, listen: false);
    if (!printerService.isConnected) return {"success": false, "error": "Impressora não conectada"};
    try {
      await printerService.printText(data is String ? data : data['text']);
      return {"success": true};
    } catch (e) {
      return {"success": false, "error": e.toString()};
    }
  }

  Future<String?> _showScanner() async {
    String? result;
    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => SizedBox(
        height: MediaQuery.of(context).size.height * 0.7,
        child: Column(
          children: [
            Expanded(child: MobileScanner(onDetect: (cap) {
              if (cap.barcodes.isNotEmpty) {
                result = cap.barcodes.first.rawValue;
                Navigator.pop(context);
              }
            })),
            TextButton(onPressed: () => Navigator.pop(context), child: const Text("Fechar")),
          ],
        ),
      ),
    );
    return result;
  }

  void _showPrinterSettings() {
    showDialog(context: context, builder: (context) => const PrinterSettingsDialog());
  }
}

class PrinterSettingsDialog extends StatelessWidget {
  const PrinterSettingsDialog({super.key});
  @override
  Widget build(BuildContext context) {
    final service = Provider.of<PrinterService>(context);
    return AlertDialog(
      title: const Text("Impressora"),
      content: FutureBuilder<List<BluetoothDevice>>(
        future: service.getBondedDevices(),
        builder: (context, snapshot) {
          if (!snapshot.hasData) return const CircularProgressIndicator();
          return Column(
            mainAxisSize: MainAxisSize.min,
            children: snapshot.data!.map((d) => ListTile(
              title: Text(d.name ?? "Inexistente"),
              onTap: () async {
                await service.connectBluetooth(d.address);
                if (context.mounted) Navigator.pop(context);
              },
            )).toList(),
          );
        },
      ),
    );
  }
}
