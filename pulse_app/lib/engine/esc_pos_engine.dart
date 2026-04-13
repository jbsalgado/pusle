import 'dart:convert';
import 'dart:typed_data';
import 'package:image/image.dart' as img;

class EscPosEngine {
  static const int esc = 0x1B;
  static const int gs = 0x1D;
  static const int lf = 0x0A;

  /// Converts text to ESC/POS byte commands using ISO-8859-1 charset.
  Uint8List getBytes(String text) {
    final List<int> bytes = [];

    // Init printer (ESC @)
    bytes.add(esc);
    bytes.add('@'.codeUnitAt(0));

    // Convert text to bytes (ISO-8859-1)
    List<int> textBytes = latin1.encode(text);
    bytes.addAll(textBytes);

    // Feed paper (3 lines)
    bytes.addAll([lf, lf, lf]);

    // Cut paper (GS V 66 0)
    bytes.addAll([gs, 'V'.codeUnitAt(0), 66, 0]);

    return Uint8List.fromList(bytes);
  }

  /// Generator for raster bit image commands (GS v 0)
  /// Expects an image already resized to printer width (max 384/576 dots)
  Uint8List getImageBytes(img.Image image) {
    // 1. Convert to Grayscale
    img.Image gray = img.grayscale(image);

    // 2. Simple thresholding to B/W (Monochrome)
    final int width = gray.width;
    final int height = gray.height;
    // Calculate bytes per line (width / 8, rounded up)
    final int bytesPerLine = (width + 7) ~/ 8; 
    
    List<int> buffer = [];

    // Center alignment
    buffer.add(esc);
    buffer.add('a'.codeUnitAt(0));
    buffer.add(1);

    // GS v 0 m xL xH yL yH d1...dk
    buffer.add(gs);
    buffer.add('v'.codeUnitAt(0));
    buffer.add('0'.codeUnitAt(0)); // CORREÇÃO: Padrão '0' (0x30/48)
    buffer.add(0); // m=0 (normal mode)

    // xL, xH (bytes per line)
    buffer.add(bytesPerLine % 256);
    buffer.add(bytesPerLine ~/ 256);

    // yL, yH (height in dots)
    buffer.add(height % 256);
    buffer.add(height ~/ 256);

    // Dithering simples (Floyd-Steinberg kernel simplificado)
    // Para evitar array 2D complexo, usamos apenas threshold dinâmico ou Bayer matrix se possível.
    // Mas para manter performance e simplicidade no engine, vamos usar um Ordered Dither 4x4 básico.
    
    const List<int> bayerMatrix = [
       0, 8, 2, 10,
       12, 4, 14, 6,
       3, 11, 1, 9,
       15, 7, 13, 5
    ];

    bool shouldPrintDot(int x, int y, int luminance) {
        // Map luminance (0-255) to 0-16 range roughly
        // 4x4 matrix, values 0-15.
        // If luminance is high (white), we check against higher threshold?
        // High luminance = White (Paper). Low luminance = Black (Dot).
        // Standard: If val < threshold -> Black.
        
        // Normalize lum to 0-255. 0=Black, 255=White.
        // Matrix value M[x%4][y%4] * 16 gives threshold 0-240.
        
        int matrixVal = bayerMatrix[(y % 4) * 4 + (x % 4)];
        int threshold = matrixVal * 17; // 16 * 16 = 256 approx
        
        return luminance < threshold;
    }

    // Raster data
    for (int y = 0; y < height; y++) {
      for (int i = 0; i < bytesPerLine; i++) {
        int byte = 0;
        for (int bit = 0; bit < 8; bit++) {
          int x = i * 8 + bit;
          if (x < width) {
             final pixel = gray.getPixel(x, y);
             int lum = pixel.luminance.toInt();
             
             // Contrast Boost: Darken pixels to make text bolder on thermal paper
             // Thermal printers often print weak greys. We push greys towards black.
             if (lum < 200) { // If not pure white
                lum = (lum * 0.7).toInt(); // Darken by 30%
             }
             
             // Use Bayer Ordered Dithering
             if (shouldPrintDot(x, y, lum)) {
                byte |= (1 << (7 - bit));
             }
          }
        }
        buffer.add(byte);
      }
    }

    // Feed lines REMOVED to allow chunking without gaps
    // buffer.addAll([lf, lf, lf]);
    
    // Reset alignment REMOVED
    // buffer.add(esc);
    // buffer.add('a'.codeUnitAt(0));
    // buffer.add(0);

    return Uint8List.fromList(buffer);
  }

  /// Toggle bold mode (ESC E n)
  Uint8List bold(bool on) {
    return Uint8List.fromList([
      esc,
      'E'.codeUnitAt(0),
      on ? 1 : 0,
    ]);
  }

  /// Set alignment (ESC a n)
  /// 0: Left, 1: Center, 2: Right
  Uint8List align(int align) {
    return Uint8List.fromList([
      esc,
      'a'.codeUnitAt(0),
      align,
    ]);
  }
}
