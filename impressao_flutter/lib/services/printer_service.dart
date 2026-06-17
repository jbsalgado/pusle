import 'dart:async';
import 'dart:io';
import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:flutter_bluetooth_serial/flutter_bluetooth_serial.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:printing/printing.dart';
import 'package:image/image.dart' as img;
import 'package:http/http.dart' as http;
import '../engine/esc_pos_engine.dart';


enum ConnectionType { none, bluetooth, wifi }


class PrinterService extends ChangeNotifier {
  // Singleton
  static final PrinterService _instance = PrinterService._internal();
  factory PrinterService() => _instance;
  PrinterService._internal() {
    tryAutoConnect();
  }

  ConnectionType _connectionType = ConnectionType.none;

  BluetoothConnection? _bluetoothConnection;
  Socket? _wifiSocket;
  String? _connectedAddress;
  Timer? _disconnectTimer;
  
  // Timeout matching Kotlin (5 mins)
  static const Duration _timeoutDuration = Duration(minutes: 5);

  bool get isConnected => _connectionType != ConnectionType.none;
  String? get connectedAddress => _connectedAddress;
  ConnectionType get connectionType => _connectionType;

  final EscPosEngine _engine = EscPosEngine();

  // --- Bluetooth Methods ---

  Future<List<BluetoothDevice>> getBondedDevices() async {
    try {
      return await FlutterBluetoothSerial.instance.getBondedDevices();
    } catch (e) {
      debugPrint("Error getting bonded devices: $e");
      return [];
    }
  }

  Future<void> connectBluetooth(String address) async {
    if (_connectionType == ConnectionType.bluetooth && _connectedAddress == address) {
      return; // Already connected
    }
    await disconnect();

    try {
      // Common Android Bluetooth Issue: Discovery interferes with connection
      await FlutterBluetoothSerial.instance.cancelDiscovery();
      
      _bluetoothConnection = await BluetoothConnection.toAddress(address).timeout(
          const Duration(seconds: 10), 
          onTimeout: () => throw TimeoutException("Connection timed out"));

      _connectionType = ConnectionType.bluetooth;
      _connectedAddress = address;
      _saveLastPrinter(address);
      _resetDisconnectTimer();
      notifyListeners();

    } catch (e) {
      debugPrint("Error connecting Bluetooth: $e");
      // Force cleanup
      _bluetoothConnection = null;
      _connectionType = ConnectionType.none;
      notifyListeners();
      rethrow;
    }
  }

  // --- Wi-Fi Methods ---

  Future<void> connectWifi(String ip, int port) async {
    if (_connectionType == ConnectionType.wifi && _connectedAddress == "$ip:$port") {
      return;
    }
    await disconnect();

    try {
      _wifiSocket = await Socket.connect(ip, port, timeout: const Duration(seconds: 5));
      _connectionType = ConnectionType.wifi;
      _connectedAddress = "$ip:$port";
      _resetDisconnectTimer();
      notifyListeners();
      
      // Listen for socket closure
      _wifiSocket!.done.then((_) {
        disconnect();
      });
    } catch (e) {
      debugPrint("Error connecting Wi-Fi: $e");
      await disconnect();
      rethrow;
    }
  }

  // --- Common Methods ---

  Future<void> disconnect() async {
    _disconnectTimer?.cancel();
    
    try {
      await _bluetoothConnection?.finish(); // Closes securely
    } catch (e) { debugPrint("Error closing BT: $e"); }
    
    try {
      await _wifiSocket?.close();
    } catch (e) { debugPrint("Error closing Wifi: $e"); }

    _bluetoothConnection = null;
    _wifiSocket = null;
    _connectionType = ConnectionType.none;
    _connectedAddress = null;
    notifyListeners();
  }

  Future<void> printText(String text) async {
    if (!isConnected) throw Exception("Printer not connected");
    _resetDisconnectTimer(); // Reset timer on activity

    try {
      Uint8List data = _engine.getBytes(text);
      await _sendData(data);
    } catch (e) {
      debugPrint("Error printing: $e");
      disconnect(); // Disconnect on error
      rethrow;
    }
  }

  Future<void> printPdf(String path) async {
    if (!isConnected) throw Exception("Printer not connected");
    _resetDisconnectTimer();

    try {
      final file = File(path);
      final content = await file.readAsBytes();
      
      // Rasterize PDF using Android's native renderer (via printing package)
      await for (final page in Printing.raster(content, dpi: 300)) { // Increased DPI for better text clarity
        
        // Convert PdfRaster to image.Image
        final pngBytes = await page.toPng();
        final img.Image? image = img.decodePng(pngBytes);
        
        if (image != null) {
            await printImage(image);
        }
      }

    } catch (e) {
      debugPrint("Error printing PDF: $e");
      rethrow;
    }
  }

  Future<void> printImage(img.Image sourceImage) async {
    if (!isConnected) throw Exception("Printer not connected");
    _resetDisconnectTimer();

    try {
      // 1. Constants
      const int printerWidth = 384; // Standard for 58mm
      const int chunkHeight = 20;   // Reduced to 20 to prevent buffer overflow

      // 2. Resize logic to fit standard width (384px)
      img.Image processed = sourceImage;
      if (processed.width > printerWidth) {
        processed = img.copyResize(processed, width: printerWidth, interpolation: img.Interpolation.cubic);
      }
      
      // 3. Create a standardized canvas (384px width)
      // This ensures stability as some printers fail with non-standard widths (like 13 bytes/line)
      final canvas = img.Image(width: printerWidth, height: processed.height);
      img.fill(canvas, color: img.ColorRgb8(255, 255, 255));
      
      // 4. Center the image on the canvas
      final int xOffset = (printerWidth - processed.width) ~/ 2;
      img.compositeImage(canvas, processed, dstX: xOffset);
      
      final finalImage = canvas; // Use this standardized image for chunking

      // 4. Chunking (Split into strips)
      final int totalHeight = finalImage.height;
      
      // Init printer once
      await _sendData(Uint8List.fromList([
         EscPosEngine.esc, '@'.codeUnitAt(0)
      ]));
      await Future.delayed(const Duration(milliseconds: 50));

      for (int y = 0; y < totalHeight; y += chunkHeight) {
        int h = chunkHeight;
        if (y + h > totalHeight) {
          h = totalHeight - y;
        }

        // Crop the strip
        final strip = img.copyCrop(finalImage, x: 0, y: y, width: finalImage.width, height: h);
        
        // Get ESC/POS bytes for this strip
        Uint8List chunkData = _engine.getImageBytes(strip);
        
        // Send
        await _sendData(chunkData);
        
        // Anti-buffer-overflow delay
        await Future.delayed(const Duration(milliseconds: 50));
      }
      
      // Feed after image
       await _sendData(Uint8List.fromList([
         EscPosEngine.lf, EscPosEngine.lf, EscPosEngine.lf
      ]));

    } catch (e) {
        debugPrint("Error printing image: $e");
        rethrow;
    }
  }

  Future<void> printImageFromUrl(String url) async {
    if (!isConnected) throw Exception("Printer not connected");
    
    try {
      debugPrint("Downloading image from: $url");
      final response = await http.get(Uri.parse(url));
      
      if (response.statusCode == 200) {
        final img.Image? image = img.decodeImage(response.bodyBytes);
        if (image != null) {
           await printImage(image);
        } else {
           throw Exception("Failed to decode image");
        }
      } else {
        throw Exception("Failed to download image: ${response.statusCode}");
      }
    } catch (e) {
      debugPrint("Error outputting image from URL: $e");
      // Don't rethrow to avoid blocking text print if logo fails
    }
  }

  Future<void> _sendData(Uint8List data) async {
      if (_connectionType == ConnectionType.bluetooth) {
        _bluetoothConnection!.output.add(data);
        await _bluetoothConnection!.output.allSent;
      } else if (_connectionType == ConnectionType.wifi) {
        _wifiSocket!.add(data);
        await _wifiSocket!.flush();
      }
  }

  void _resetDisconnectTimer() {
    _disconnectTimer?.cancel();
    _disconnectTimer = Timer(_timeoutDuration, () {
      debugPrint("Auto-disconnecting due to inactivity");
      disconnect();
    });
  }

  Future<void> _saveLastPrinter(String address) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('last_printer_address', address);
  }

  Future<bool> tryAutoConnect() async {
    if (isConnected) return true;
    
    try {
      final prefs = await SharedPreferences.getInstance();
      final lastAddress = prefs.getString('last_printer_address');
      
      if (lastAddress != null && lastAddress.isNotEmpty) {
        debugPrint("Attempting auto-connect to $lastAddress");
        
        // Optimistic connection attempt (don't wait for bonded list if possible, or wait with timeout)
        try {
           await connectBluetooth(lastAddress);
           return true;
        } catch (e) {
           debugPrint("Auto-connect failed: $e");
           return false;
        }
      }
    } catch (e) {
      debugPrint("Error in auto-connect: $e");
    }
    return false;
  }
}

