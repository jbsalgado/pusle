// lib/features/auth/loja_selection_screen.dart
//
// Tela para o operador selecionar em qual loja deseja trabalhar,
// exibida quando o mesmo usuário possui vínculo com mais de uma loja.

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'auth_service.dart';
import 'models/loja_acesso.dart';
import '../../core/database/app_database.dart';
import '../sync/sync_initial_screen.dart';
import '../dashboard/dashboard_screen.dart';

class LojaSelectionScreen extends StatefulWidget {
  const LojaSelectionScreen({super.key});

  @override
  State<LojaSelectionScreen> createState() => _LojaSelectionScreenState();
}

class _LojaSelectionScreenState extends State<LojaSelectionScreen> {
  bool _selecionando = false;
  String? _lojaSelecionadaId;

  Future<void> _onSelecionarLoja(LojaAcesso loja) async {
    setState(() {
      _selecionando = true;
      _lojaSelecionadaId = loja.lojaId;
    });

    final auth = context.read<AuthService>();

    try {
      await auth.selecionarLoja(loja);

      if (!mounted) return;

      if (auth.precisaSyncInicial) {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const SyncInitialScreen()),
        );
      } else {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const DashboardScreen()),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Erro ao selecionar loja: $e'),
            backgroundColor: Colors.redAccent,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _selecionando = false;
          _lojaSelecionadaId = null;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthService>();
    final db = context.read<AppDatabase>();
    final lojas = auth.lojasDisponiveis;

    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF0F172A), Color(0xFF1E3A5F)],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              _buildHeader(auth.nomeUsuario ?? 'Usuário'),
              Expanded(
                child: lojas.isEmpty
                    ? _buildEmptyState()
                    : ListView.separated(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 20, vertical: 16),
                        itemCount: lojas.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 12),
                        itemBuilder: (ctx, index) {
                          final loja = lojas[index];
                          final isCurrent = _lojaSelecionadaId == loja.lojaId;
                          return _LojaCard(
                            loja: loja,
                            db: db,
                            isLoading: _selecionando && isCurrent,
                            onTap: _selecionando ? null : () => _onSelecionarLoja(loja),
                          );
                        },
                      ),
              ),
              _buildFooter(auth),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeader(String nomeUsuario) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(24, 28, 24, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: const Color(0xFF3B82F6).withOpacity(0.15),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                      color: const Color(0xFF3B82F6).withOpacity(0.3)),
                ),
                child: const Icon(Icons.storefront_rounded,
                    color: Color(0xFF60A5FA), size: 24),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Olá, ${nomeUsuario.split(' ').first}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        letterSpacing: -0.3,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Selecione a loja para operar',
                      style: TextStyle(
                        color: Colors.white.withOpacity(0.6),
                        fontSize: 13,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.05),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.white.withOpacity(0.08)),
            ),
            child: Row(
              children: [
                Icon(Icons.info_outline_rounded,
                    size: 16, color: Colors.blue.shade300),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'Você está vinculado a múltiplas lojas. Os produtos e vendas ficam isolados.',
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.7),
                      fontSize: 12,
                      height: 1.3,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.store_mall_directory_outlined,
                size: 64, color: Colors.white.withOpacity(0.3)),
            const SizedBox(height: 16),
            const Text(
              'Nenhuma loja encontrada',
              style: TextStyle(
                  color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Text(
              'Entre em contato com o administrador para vincular seu usuário a uma loja.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.white.withOpacity(0.5), fontSize: 13),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFooter(AuthService auth) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          TextButton.icon(
            icon: const Icon(Icons.refresh_rounded, size: 18, color: Colors.white54),
            label: const Text('Atualizar Lojas',
                style: TextStyle(color: Colors.white54, fontSize: 13)),
            onPressed: () => auth.atualizarLojasDoServidor(),
          ),
          TextButton.icon(
            icon: const Icon(Icons.logout_rounded, size: 18, color: Colors.redAccent),
            label: const Text('Sair',
                style: TextStyle(color: Colors.redAccent, fontSize: 13)),
            onPressed: () => auth.logout(),
          ),
        ],
      ),
    );
  }
}

class _LojaCard extends StatelessWidget {
  final LojaAcesso loja;
  final AppDatabase db;
  final bool isLoading;
  final VoidCallback? onTap;

  const _LojaCard({
    required this.loja,
    required this.db,
    this.isLoading = false,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return FutureBuilder(
      future: db.catalogDao.getTenantById(loja.lojaId),
      builder: (context, snapshot) {
        final tenant = snapshot.data;
        final jaSincronizado = tenant?.ultimaSync != null;

        return Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: onTap,
            borderRadius: BorderRadius.circular(16),
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.06),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: Colors.white.withOpacity(0.12),
                  width: 1,
                ),
              ),
              child: Row(
                children: [
                  _buildLogo(),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                loja.nomeExibicao,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 15,
                                  fontWeight: FontWeight.w600,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            _buildPapelBadge(loja.papel),
                          ],
                        ),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            Icon(
                              jaSincronizado
                                  ? Icons.check_circle_outline_rounded
                                  : Icons.cloud_download_outlined,
                              size: 13,
                              color: jaSincronizado
                                  ? Colors.greenAccent
                                  : Colors.orangeAccent,
                            ),
                            const SizedBox(width: 4),
                            Text(
                              jaSincronizado
                                  ? 'Pronta para uso offline'
                                  : 'Requer sync inicial',
                              style: TextStyle(
                                color: jaSincronizado
                                    ? Colors.greenAccent.shade100
                                    : Colors.orangeAccent.shade100,
                                fontSize: 11,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  if (isLoading)
                    const SizedBox(
                      width: 24,
                      height: 24,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Color(0xFF60A5FA),
                      ),
                    )
                  else
                    const Icon(Icons.arrow_forward_ios_rounded,
                        size: 16, color: Colors.white30),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Widget _buildLogo() {
    if (loja.logoUrl != null && loja.logoUrl!.isNotEmpty) {
      return ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: Image.network(
          loja.logoUrl!,
          width: 44,
          height: 44,
          fit: BoxFit.cover,
          errorBuilder: (_, __, ___) => _buildPlaceholderIcon(),
        ),
      );
    }
    return _buildPlaceholderIcon();
  }

  Widget _buildPlaceholderIcon() {
    return Container(
      width: 44,
      height: 44,
      decoration: BoxDecoration(
        color: const Color(0xFF3B82F6).withOpacity(0.12),
        borderRadius: BorderRadius.circular(12),
      ),
      child: const Icon(Icons.storefront_rounded,
          color: Color(0xFF60A5FA), size: 24),
    );
  }

  Widget _buildPapelBadge(String papel) {
    Color bg;
    Color fg;
    String label;

    switch (papel.toLowerCase()) {
      case 'dono':
        bg = Colors.amber.withOpacity(0.18);
        fg = Colors.amber.shade300;
        label = 'Dono';
        break;
      case 'administrador':
        bg = Colors.purple.withOpacity(0.18);
        fg = Colors.purple.shade200;
        label = 'Admin';
        break;
      case 'cobrador':
        bg = Colors.teal.withOpacity(0.18);
        fg = Colors.teal.shade200;
        label = 'Cobrador';
        break;
      case 'vendedor':
      default:
        bg = Colors.blue.withOpacity(0.18);
        fg = Colors.blue.shade200;
        label = 'Vendedor';
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: fg,
          fontSize: 10,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}
