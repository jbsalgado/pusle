// lib/features/sync/sync_initial_screen.dart
//
// Tela de sincronização inicial obrigatória.
// Executada logo após o login, antes do acesso ao app.
// Mostra progresso real com animação de barra e log de etapas.

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/sync/sync_service.dart';
import '../../core/network/connectivity_service.dart';
import '../../features/auth/auth_service.dart';
import '../dashboard/dashboard_screen.dart';

class SyncInitialScreen extends StatefulWidget {
  const SyncInitialScreen({super.key});

  @override
  State<SyncInitialScreen> createState() => _SyncInitialScreenState();
}

class _SyncInitialScreenState extends State<SyncInitialScreen>
    with SingleTickerProviderStateMixin {
  bool _concluido = false;
  bool _erro = false;
  String _mensagemErro = '';

  late AnimationController _pulseCtrl;

  @override
  void initState() {
    super.initState();
    _pulseCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    )..repeat(reverse: true);

    WidgetsBinding.instance.addPostFrameCallback((_) => _iniciarSync());
  }

  Future<void> _iniciarSync() async {
    final connectivity = context.read<ConnectivityService>();
    if (!connectivity.isOnline) {
      setState(() {
        _erro = true;
        _mensagemErro =
            'Sem conexão com a internet.\nA sincronização inicial é obrigatória para o primeiro uso.';
      });
      return;
    }

    setState(() { _erro = false; });

    final auth = context.read<AuthService>();
    final sync = context.read<SyncService>();
    final resultado = await sync.sincronizarInicial(auth.tenantId!);

    if (!mounted) return;

    if (resultado.erros.isNotEmpty && resultado.produtosBaixados == 0) {
      setState(() {
        _erro = true;
        _mensagemErro = resultado.erros.first;
      });
      return;
    }

    auth.marcarSyncInicialConcluida();
    setState(() => _concluido = true);

    await Future.delayed(const Duration(milliseconds: 1200));
    if (!mounted) return;

    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => const DashboardScreen()),
    );
  }

  @override
  void dispose() {
    _pulseCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
          ),
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(32),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                _buildIcon(),
                const SizedBox(height: 40),
                _buildConteudo(),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildIcon() {
    if (_erro) {
      return Container(
        width: 80, height: 80,
        decoration: BoxDecoration(
          color: Colors.red.withOpacity(0.15),
          shape: BoxShape.circle,
        ),
        child: const Icon(Icons.wifi_off_rounded, color: Colors.redAccent, size: 40),
      );
    }
    if (_concluido) {
      return Container(
        width: 80, height: 80,
        decoration: BoxDecoration(
          color: Colors.green.withOpacity(0.15),
          shape: BoxShape.circle,
        ),
        child: const Icon(Icons.check_circle_outline_rounded,
            color: Colors.greenAccent, size: 40),
      );
    }
    return AnimatedBuilder(
      animation: _pulseCtrl,
      builder: (_, __) => Container(
        width: 80, height: 80,
        decoration: BoxDecoration(
          color: Color.lerp(
            const Color(0xFF3B82F6).withOpacity(0.15),
            const Color(0xFF3B82F6).withOpacity(0.30),
            _pulseCtrl.value,
          ),
          shape: BoxShape.circle,
        ),
        child: const Icon(Icons.sync_rounded, color: Color(0xFF60A5FA), size: 40),
      ),
    );
  }

  Widget _buildConteudo() {
    if (_erro) {
      return Column(
        children: [
          const Text(
            'Falha na sincronização',
            style: TextStyle(
              color: Colors.white,
              fontSize: 22,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            _mensagemErro,
            textAlign: TextAlign.center,
            style: const TextStyle(color: Colors.white60, fontSize: 14, height: 1.5),
          ),
          const SizedBox(height: 32),
          ElevatedButton.icon(
            icon: const Icon(Icons.refresh_rounded),
            label: const Text('Tentar novamente'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF3B82F6),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
            onPressed: _iniciarSync,
          ),
        ],
      );
    }

    if (_concluido) {
      return Column(
        children: const [
          Text(
            'Pronto!',
            style: TextStyle(
              color: Colors.white,
              fontSize: 24,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 8),
          Text(
            'Dados sincronizados com sucesso.',
            style: TextStyle(color: Colors.white60, fontSize: 14),
          ),
        ],
      );
    }

    return Consumer<SyncService>(
      builder: (context, sync, _) {
        return Column(
          children: [
            Text(
              'Sincronização inicial',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 22,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Baixando os dados necessários\npara uso offline do app.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.white54, fontSize: 14, height: 1.5),
            ),
            const SizedBox(height: 40),
            // Barra de progresso
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: LinearProgressIndicator(
                value: sync.progresso,
                minHeight: 8,
                backgroundColor: Colors.white.withOpacity(0.1),
                valueColor: const AlwaysStoppedAnimation(Color(0xFF3B82F6)),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              sync.mensagem,
              style: const TextStyle(color: Colors.white60, fontSize: 13),
            ),
            const SizedBox(height: 6),
            Text(
              '${(sync.progresso * 100).toInt()}%',
              style: const TextStyle(
                color: Color(0xFF60A5FA),
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        );
      },
    );
  }
}
