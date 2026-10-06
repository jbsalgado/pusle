// lib/core/sync/media_sync_manager.dart
//
// Gerenciador de download e cache local de imagens de produtos.
// Permite que o catálogo do app funcione 100% offline com fotos salvas no disco.

import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';

import '../database/app_database.dart';

class MediaSyncManager {
  static const int maxConcurrentDownloads = 3;

  /// Obtém o diretório local permanente para fotos de produtos
  static Future<Directory> getMediaDirectory() async {
    final docsDir = await getApplicationDocumentsDirectory();
    final mediaDir = Directory('${docsDir.path}/pulse_media/produtos');
    if (!await mediaDir.exists()) {
      await mediaDir.create(recursive: true);
    }
    return mediaDir;
  }

  /// Sincroniza fotos dos produtos pendentes para o armazenamento offline
  static Future<int> sincronizarFotosProdutos({
    required String tenantId,
    required AppDatabase db,
    String? baseUrl,
    void Function(int processados, int total)? onProgress,
  }) async {
    try {
      final produtos = await db.produtoDao.getProdutosPorTenant(tenantId);
      final comFoto = produtos.where((p) => p.fotoUrl != null && p.fotoUrl!.trim().isNotEmpty).toList();

      if (comFoto.isEmpty) {
        return 0;
      }

      final mediaDir = await getMediaDirectory();
      int processados = 0;
      int baixados = 0;

      // Executa em lotes controlados (concorrência máxima de 3)
      for (int i = 0; i < comFoto.length; i += maxConcurrentDownloads) {
        final fim = (i + maxConcurrentDownloads < comFoto.length) ? i + maxConcurrentDownloads : comFoto.length;
        final lote = comFoto.sublist(i, fim);

        await Future.wait(lote.map((produto) async {
          try {
            final ext = _extrairExtensao(produto.fotoUrl!);
            final arquivoLocal = File('${mediaDir.path}/${produto.id}$ext');

            // 1. Se o arquivo já existe no disco com dados válidos, apenas garante o path no SQLite
            if (await arquivoLocal.exists() && (await arquivoLocal.length()) > 0) {
              if (produto.fotoLocalPath != arquivoLocal.path) {
                await db.produtoDao.atualizarFotoLocal(produto.id, arquivoLocal.path);
              }
              return;
            }

            // 2. Monta a URL de download
            String urlDownload = produto.fotoUrl!;
            if (!urlDownload.startsWith('http://') && !urlDownload.startsWith('https://')) {
              final base = (baseUrl != null && baseUrl.isNotEmpty)
                  ? baseUrl.replaceAll(RegExp(r'/+$'), '')
                  : 'https://catalogos.oncode.app.br';
              final path = urlDownload.replaceFirst(RegExp(r'^/+'), '');
              urlDownload = '$base/$path';
            }

            // 3. Efetua o download com timeout
            final response = await http
                .get(Uri.parse(urlDownload))
                .timeout(const Duration(seconds: 15));

            if (response.statusCode == 200 && response.bodyBytes.isNotEmpty) {
              await arquivoLocal.writeAsBytes(response.bodyBytes);
              await db.produtoDao.atualizarFotoLocal(produto.id, arquivoLocal.path);
              baixados++;
            } else {
              debugPrint('[MediaSync] Falha HTTP ${response.statusCode} para ${produto.nome}: $urlDownload');
            }
          } catch (e) {
            debugPrint('[MediaSync] Erro ao baixar foto de ${produto.nome}: $e');
          } finally {
            processados++;
            onProgress?.call(processados, comFoto.length);
          }
        }));
      }

      debugPrint('[MediaSync] Concluído: $baixados novas fotos salvas de $processados produtos verificados.');
      return baixados;
    } catch (e, st) {
      debugPrint('[MediaSync] Erro geral na sincronização de mídia: $e\n$st');
      return 0;
    }
  }

  static String _extrairExtensao(String url) {
    try {
      final uri = Uri.parse(url);
      final path = uri.path.toLowerCase();
      if (path.endsWith('.png')) return '.png';
      if (path.endsWith('.webp')) return '.webp';
      if (path.endsWith('.gif')) return '.gif';
      if (path.endsWith('.jpeg')) return '.jpeg';
      return '.jpg';
    } catch (_) {
      return '.jpg';
    }
  }
}
