import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:flutter_bluetooth_serial/flutter_bluetooth_serial.dart';
import 'package:app_links/app_links.dart';
import 'package:receive_sharing_intent/receive_sharing_intent.dart';
import 'dart:io';
import 'package:image/image.dart' as img;
import 'services/printer_service.dart';


void main() {
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => PrinterService()),
      ],
      child: const MyApp(),
    ),
  );
}

class MyApp extends StatefulWidget {
  const MyApp({super.key});

  @override
  State<MyApp> createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> {
  final _appLinks = AppLinks();
  StreamSubscription<Uri>? _linkSubscription;
  StreamSubscription? _intentDataStreamSubscription;
  final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

  @override
  void initState() {
    super.initState();
    _initDeepLinks();
    _initShareIntent();
  }

  void _initShareIntent() {
    // For sharing or opening while app is running
    _intentDataStreamSubscription = ReceiveSharingIntent.instance.getMediaStream().listen((List<SharedMediaFile> value) {
      if (value.isNotEmpty) {
          _handleSharedContent(value.first.path);
      }
    }, onError: (err) {
      debugPrint("getLinkStream error: $err");
    });

    // For sharing or opening while app is closed
    ReceiveSharingIntent.instance.getInitialMedia().then((List<SharedMediaFile> value) {
      if (value.isNotEmpty) {
        _handleSharedContent(value.first.path);
      }
    });
  }






  void _handleSharedContent(String path) {
    // CRITICAL FIX: Ignore Deep Link schemes if they accidentally trigger this handler
    // CRITICAL FIX: Ignore Deep Link schemes if they accidentally trigger this handler
    // Handles both standard URI and potential modifications
    final String lowerPath = path.toLowerCase().trim();
    if (lowerPath.startsWith('printapp:') || 
        lowerPath.contains('printapp://') || 
        lowerPath.contains('print?data=')) { 
        debugPrint("Ignored Deep Link in Shared Handler: $path");
        return; 
    }

     if (path.isNotEmpty && navigatorKey.currentContext != null) {
      final context = navigatorKey.currentContext!;
      final printerService = Provider.of<PrinterService>(context, listen: false);
      
      void tryPrint() {
          if (printerService.isConnected) {
            Future printTask;
            final lowerPath = path.toLowerCase();
            
            if (lowerPath.endsWith('.pdf')) {
                printTask = printerService.printPdf(path);
            } else if (lowerPath.endsWith('.png') || lowerPath.endsWith('.jpg') || lowerPath.endsWith('.jpeg')) {
                // Handle Image File Sharing
                printTask = (() async {
                   final file = File(path);
                   final bytes = await file.readAsBytes();
                   final image = img.decodeImage(bytes);
                   if (image != null) {
                       await printerService.printImage(image);
                   } else {
                       throw Exception("Failed to decode image file");
                   }
                })();
            } else {
                printTask = printerService.printText(path); // Treat as text content/path
            }

            printTask.then((_) {
                _showToast("Compartilhamento impresso!");
            }).catchError((e) {
                _showToast("Erro ao imprimir: $e");
            });

          } else {
             _showToast("Conectando automaticamente...");
             
             // Force auto-connect attempt
             printerService.tryAutoConnect().then((success) {
                 if (success && printerService.isConnected) {
                     final lowerPath = path.toLowerCase();
                     if (lowerPath.endsWith('.pdf')) {
                        printerService.printPdf(path);
                     } else if (lowerPath.endsWith('.png') || lowerPath.endsWith('.jpg') || lowerPath.endsWith('.jpeg')) {
                         final file = File(path);
                         file.readAsBytes().then((bytes) {
                             final image = img.decodeImage(bytes);
                             if (image != null) printerService.printImage(image);
                         });
                     } else {
                        printerService.printText(path);
                     }
                 } else {
                     _showToast("Não foi possível conectar automaticamente. Verifique a impressora.");
                 }
             });
          }
      }
      
      tryPrint();
    }
  }


  void _initDeepLinks() {
    _linkSubscription = _appLinks.uriLinkStream.listen((uri) {
        _handleDeepLink(uri);
    });
  }

  void _handleDeepLink(Uri uri) {
    debugPrint("DeepLink received: $uri");
    
    // Case 1: Custom Scheme printapp://print?data=...&logo=...
    if (uri.scheme == 'printapp') {
        final rawData = uri.queryParameters['data'];
        final logoUrl = uri.queryParameters['logo'];
        
        if (rawData != null) {
             // Garante decodificação de percent-encoding (%20, %0A, etc)
             String textToPrint = rawData;
             try {
                textToPrint = Uri.decodeComponent(rawData);
             } catch (e) {
                // Fallback
             }
             
             // Logo URL já vem decodificada corretamente pelo queryParameters
             // Não aplicar decodeComponent novamente para evitar quebrar %20 em espaços
             final String? finalLogoUrl = logoUrl;

             _printDataOrText(textToPrint, logoUrl: finalLogoUrl);
        }
    }
    // Case 2: Open File (file:// or content://)
    else if (uri.scheme == 'file' || uri.scheme == 'content') {
        String path = uri.toFilePath(); 
        if (path.isEmpty && uri.scheme == 'content') {
           _showToast("Abrindo arquivo via content scheme...");
           path = uri.toString();
        }
        _handleSharedContent(path); 
    }
  }

  void _printDataOrText(String data, {String? logoUrl}) {
    if (navigatorKey.currentContext != null) {
      final context = navigatorKey.currentContext!;
      final printerService = Provider.of<PrinterService>(context, listen: false);
      if (printerService.isConnected) {
        
        Future<void> printFlow() async {
            if (logoUrl != null && logoUrl.isNotEmpty) {
                try {
                    await printerService.printImageFromUrl(logoUrl);
                } catch (e) {
                    debugPrint("Logo print failed: $e");
                }
            }
            await printerService.printText(data);
        }

        printFlow().then((_) {
            _showToast("Impresso com sucesso!");
        }).catchError((e) {
            _showToast("Erro ao imprimir: $e");
        });

      } else {
        _showToast("Conecte-se a uma impressora primeiro.");
      }
    }
  }

  void _showToast(String message) {
     if (navigatorKey.currentContext != null) {
        ScaffoldMessenger.of(navigatorKey.currentContext!).showSnackBar(SnackBar(content: Text(message)));
     }
  }

  @override
  void dispose() {
    _linkSubscription?.cancel();
    _intentDataStreamSubscription?.cancel();
    super.dispose();
  }


  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: navigatorKey,
      title: 'Thermal Print Driver',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        useMaterial3: true,
      ),
      home: const HomePage(),
    );
  }
}

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  List<BluetoothDevice> _devices = [];
  BluetoothDevice? _selectedDevice;
  bool _isLoading = false;

  final TextEditingController _ipController = TextEditingController(text: "192.168.0.100");
  final TextEditingController _portController = TextEditingController(text: "9100");

  @override
  void initState() {
    super.initState();
    _checkPermissions();
  }

  Future<void> _checkPermissions() async {
    // Android 12+ requires different permissions
    Map<Permission, PermissionStatus> statuses = await [
      Permission.bluetooth,
      Permission.bluetoothScan,
      Permission.bluetoothConnect,
      Permission.location,
    ].request();

    if (statuses.values.every((status) => status.isGranted)) {
      _loadDevices();
    } else {
      if (mounted) {
           ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Permissões necessárias não concedidas")));
      }
    }
  }

  Future<void> _loadDevices() async {
    setState(() => _isLoading = true);
    try {
      final list = await Provider.of<PrinterService>(context, listen: false).getBondedDevices();
      setState(() {
        _devices = list;
        if (_devices.isNotEmpty) {
          _selectedDevice = _devices.first;
        }
      });
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final printerService = Provider.of<PrinterService>(context);

    return Scaffold(
      appBar: AppBar(title: const Text("Thermal Print Driver")),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Status Card
            Card(
              color: printerService.isConnected ? Colors.green[100] : Colors.grey[200],
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  children: [
                    Text(
                      printerService.isConnected 
                          ? "Conectado (${printerService.connectionType.name})"
                          : "Desconectado",
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                    ),
                    if (printerService.isConnected)
                      Text(printerService.connectedAddress ?? ""),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),

            // Bluetooth Section
            const Text("Bluetooth", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            DropdownButton<BluetoothDevice>(
              isExpanded: true,
              value: _selectedDevice,
              hint: const Text("Selecione um dispositivo"),
              items: _devices.map((d) {
                return DropdownMenuItem(
                  value: d,
                  child: Text("${d.name ?? "Unknown"} (${d.address})"),
                );
              }).toList(),
              onChanged: (val) => setState(() => _selectedDevice = val),
            ),
            ElevatedButton(
              onPressed: _isLoading || printerService.isConnected 
                  ? null 
                  : () {
                      if (_selectedDevice != null) {
                        _connectBT(printerService, _selectedDevice!.address);
                      }
                    },
              child: const Text("Conectar Bluetooth"),
            ),

            const SizedBox(height: 20),
            const Divider(),
            const SizedBox(height: 10),

            // Wi-Fi Section
            const Text("Wi-Fi", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _ipController,
                    decoration: const InputDecoration(labelText: "IP"),
                  ),
                ),
                const SizedBox(width: 10),
                SizedBox(
                  width: 80,
                  child: TextField(
                    controller: _portController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: "Porta"),
                  ),
                ),
              ],
            ),
            ElevatedButton(
              onPressed: printerService.isConnected 
                  ? null 
                  : () {
                      final ip = _ipController.text;
                      final port = int.tryParse(_portController.text);
                      if (ip.isNotEmpty && port != null) {
                        _connectWifi(printerService, ip, port);
                      }
                    },
              child: const Text("Conectar Wi-Fi"),
            ),

            const SizedBox(height: 20),
            const Divider(),
            
            // Actions
            if (printerService.isConnected)
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
                onPressed: () => printerService.disconnect(),
                child: const Text("Desconectar"),
              ),
              
            const SizedBox(height: 10),
            ElevatedButton(
              onPressed: printerService.isConnected
                  ? () => printerService.printText("Teste de Impressao Flutter\n\nOk!\n")
                  : null,
              child: const Text("Imprimir Teste"),
            ),
          ],
        ),
      ),
    );
  }

  void _connectBT(PrinterService service, String address) async {
    try {
      await service.connectBluetooth(address);
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erro: $e")));
    }
  }

  void _connectWifi(PrinterService service, String ip, int port) async {
    try {
      await service.connectWifi(ip, port);
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erro: $e")));
    }
  }
}
