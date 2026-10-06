// lib/core/network/connectivity_service.dart
//
// Monitora o status de conectividade em tempo real.
// Usa connectivity_plus para stream reativo.
// O SyncService e os Repositories consultam este serviço para decidir
// se devem usar a API remota ou o SQLite local.

import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';

enum ConexaoStatus { online, offline }

class ConnectivityService extends ChangeNotifier {
  static final ConnectivityService _instance = ConnectivityService._internal();
  factory ConnectivityService() => _instance;
  ConnectivityService._internal();

  ConexaoStatus _status = ConexaoStatus.offline;
  StreamSubscription<List<ConnectivityResult>>? _subscription;

  ConexaoStatus get status => _status;
  bool get isOnline => _status == ConexaoStatus.online;
  bool get isOffline => _status == ConexaoStatus.offline;

  /// Inicia o monitoramento de conectividade.
  /// Deve ser chamado uma vez no `main()` antes do runApp.
  Future<void> inicializar() async {
    // Verifica status atual imediatamente
    final results = await Connectivity().checkConnectivity();
    _atualizarStatus(results);

    // Escuta mudanças futuras
    _subscription = Connectivity()
        .onConnectivityChanged
        .listen(_atualizarStatus);
  }

  void _atualizarStatus(List<ConnectivityResult> results) {
    final temConexao = results.any(
      (r) => r == ConnectivityResult.wifi ||
             r == ConnectivityResult.mobile ||
             r == ConnectivityResult.ethernet,
    );

    final novoStatus =
        temConexao ? ConexaoStatus.online : ConexaoStatus.offline;

    if (novoStatus != _status) {
      _status = novoStatus;
      debugPrint('[Connectivity] Status: ${_status.name.toUpperCase()}');
      notifyListeners();
    }
  }

  @override
  void dispose() {
    _subscription?.cancel();
    super.dispose();
  }
}
