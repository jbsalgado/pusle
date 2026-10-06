// lib/core/widgets/pulse_product_image.dart
//
// Widget de exibição de imagens de produtos Offline-First.
// Prioriza o arquivo salvo localmente no smartphone, com fallback para rede e placeholder elegante.

import 'dart:io';
import 'package:flutter/material.dart';

class PulseProductImage extends StatelessWidget {
  final String? fotoLocalPath;
  final String? fotoUrl;
  final String nome;
  final double width;
  final double height;
  final double borderRadius;
  final BoxFit fit;
  final double iconSize;

  const PulseProductImage({
    super.key,
    required this.nome,
    this.fotoLocalPath,
    this.fotoUrl,
    this.width = 50,
    this.height = 50,
    this.borderRadius = 12,
    this.fit = BoxFit.cover,
    this.iconSize = 24,
  });

  @override
  Widget build(BuildContext context) {
    // 1. Prioridade Offline: arquivo salvo localmente no disco
    if (fotoLocalPath != null && fotoLocalPath!.trim().isNotEmpty) {
      final file = File(fotoLocalPath!);
      if (file.existsSync()) {
        return ClipRRect(
          borderRadius: BorderRadius.circular(borderRadius),
          child: Image.file(
            file,
            width: width,
            height: height,
            fit: fit,
            errorBuilder: (_, __, ___) => _buildFallbackOuNetwork(),
          ),
        );
      }
    }

    return _buildFallbackOuNetwork();
  }

  Widget _buildFallbackOuNetwork() {
    // 2. Prioridade Online: URL da imagem remota (com suporte a cache do flutter)
    if (fotoUrl != null && fotoUrl!.trim().isNotEmpty && (fotoUrl!.startsWith('http://') || fotoUrl!.startsWith('https://'))) {
      return ClipRRect(
        borderRadius: BorderRadius.circular(borderRadius),
        child: Image.network(
          fotoUrl!,
          width: width,
          height: height,
          fit: fit,
          loadingBuilder: (context, child, loadingProgress) {
            if (loadingProgress == null) return child;
            return Container(
              width: width,
              height: height,
              decoration: BoxDecoration(
                color: const Color(0xFF10B981).withOpacity(0.08),
                borderRadius: BorderRadius.circular(borderRadius),
              ),
              child: Center(
                child: SizedBox(
                  width: iconSize * 0.6,
                  height: iconSize * 0.6,
                  child: const CircularProgressIndicator(
                    strokeWidth: 2,
                    color: Color(0xFF10B981),
                  ),
                ),
              ),
            );
          },
          errorBuilder: (_, __, ___) => _buildPlaceholder(),
        ),
      );
    }

    // 3. Fallback: Placeholder elegante com ícone de catálogo
    return _buildPlaceholder();
  }

  Widget _buildPlaceholder() {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        color: const Color(0xFF10B981).withOpacity(0.12),
        borderRadius: BorderRadius.circular(borderRadius),
        border: Border.all(
          color: const Color(0xFF10B981).withOpacity(0.2),
          width: 1,
        ),
      ),
      child: Center(
        child: Icon(
          Icons.inventory_2_outlined,
          color: const Color(0xFF10B981),
          size: iconSize,
        ),
      ),
    );
  }
}
