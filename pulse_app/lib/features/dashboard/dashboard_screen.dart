// lib/features/dashboard/dashboard_screen.dart
//
// Tela principal após login/sync. Menu de navegação com:
//   - Nova Venda
//   - Histórico
//   - Produtos
//   - Clientes
//   - Status de conectividade e pendentes offline

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/network/connectivity_service.dart';
import '../../core/sync/sync_service.dart';
import '../../core/database/app_database.dart';
import '../../features/auth/auth_service.dart';
import '../vendas/nova_venda_screen.dart';
import '../vendas/historico_screen.dart';
import '../produtos/produtos_screen.dart';
import '../clientes/clientes_screen.dart';
import '../sync/sync_initial_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  int _tabIndex = 0;

  final List<Widget> _telas = const [
    _HomeTab(),
    NovaVendaScreen(),
    HistoricoScreen(),
    ProdutosScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      body: _telas[_tabIndex],
      bottomNavigationBar: _buildBottomBar(),
    );
  }

  Widget _buildBottomBar() {
    return Consumer<SyncService>(
      builder: (context, sync, _) {
        return Container(
          decoration: BoxDecoration(
            color: const Color(0xFF1E293B),
            border: Border(
              top: BorderSide(color: Colors.white.withOpacity(0.08)),
            ),
          ),
          child: BottomNavigationBar(
            currentIndex: _tabIndex,
            onTap: (i) => setState(() => _tabIndex = i),
            backgroundColor: Colors.transparent,
            elevation: 0,
            type: BottomNavigationBarType.fixed,
            selectedItemColor: const Color(0xFF3B82F6),
            unselectedItemColor: Colors.white38,
            selectedFontSize: 11,
            unselectedFontSize: 11,
            items: const [
              BottomNavigationBarItem(
                icon: Icon(Icons.home_outlined),
                activeIcon: Icon(Icons.home_rounded),
                label: 'Início',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.add_shopping_cart_outlined),
                activeIcon: Icon(Icons.add_shopping_cart_rounded),
                label: 'Nova Venda',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.receipt_long_outlined),
                activeIcon: Icon(Icons.receipt_long_rounded),
                label: 'Vendas',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.inventory_2_outlined),
                activeIcon: Icon(Icons.inventory_2_rounded),
                label: 'Produtos',
              ),
            ],
          ),
        );
      },
    );
  }
}

// ─── Home Tab ─────────────────────────────────────────────────

class _HomeTab extends StatelessWidget {
  const _HomeTab();

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthService>();
    final connectivity = context.watch<ConnectivityService>();
    final sync = context.watch<SyncService>();

    return CustomScrollView(
      slivers: [
        _buildAppBar(context, auth, connectivity),
        SliverPadding(
          padding: const EdgeInsets.all(16),
          sliver: SliverList(
            delegate: SliverChildListDelegate([
              // Banner de status offline
              if (connectivity.isOffline)
                _buildOfflineBanner(context),

              // Card de pendentes
              _buildPendentesCard(context, auth, sync),

              const SizedBox(height: 20),

              // Menu rápido
              _buildMenuRapido(context),

              const SizedBox(height: 20),

              // Ações rápidas
              _buildAcoesRapidas(context, sync, auth),
            ]),
          ),
        ),
      ],
    );
  }

  Widget _buildAppBar(
    BuildContext context,
    AuthService auth,
    ConnectivityService connectivity,
  ) {
    return SliverAppBar(
      backgroundColor: const Color(0xFF1E293B),
      pinned: true,
      expandedHeight: 135,
      flexibleSpace: FlexibleSpaceBar(
        titlePadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        title: Row(
          children: [
            Expanded(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Olá, ${auth.nomeUsuario?.split(' ').first ?? 'Vendedor'}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 3),
                  InkWell(
                    onTap: auth.lojasDisponiveis.length > 1
                        ? () => _mostrarSeletorLojas(context, auth)
                        : null,
                    borderRadius: BorderRadius.circular(6),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.storefront_rounded,
                            size: 13, color: Color(0xFF60A5FA)),
                        const SizedBox(width: 4),
                        Flexible(
                          child: Text(
                            auth.nomeLojaAtiva ?? 'Loja Ativa',
                            style: const TextStyle(
                              color: Color(0xFF93C5FD),
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        if (auth.lojasDisponiveis.length > 1) ...[
                          const SizedBox(width: 2),
                          const Icon(Icons.arrow_drop_down_rounded,
                              size: 16, color: Color(0xFF93C5FD)),
                        ],
                      ],
                    ),
                  ),
                ],
              ),
            ),
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                  decoration: BoxDecoration(
                    color: connectivity.isOnline
                        ? Colors.green.withOpacity(0.15)
                        : Colors.orange.withOpacity(0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 6,
                        height: 6,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: connectivity.isOnline
                              ? Colors.greenAccent
                              : Colors.orangeAccent,
                        ),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        connectivity.isOnline ? 'Online' : 'Offline',
                        style: TextStyle(
                          fontSize: 10,
                          color: connectivity.isOnline
                              ? Colors.greenAccent
                              : Colors.orangeAccent,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.logout_rounded,
                      color: Colors.white54, size: 18),
                  onPressed: () => _confirmarLogout(context),
                  tooltip: 'Sair',
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  void _mostrarSeletorLojas(BuildContext context, AuthService auth) {
    if (auth.lojasDisponiveis.length <= 1) return;

    showModalBottomSheet(
      context: context,
      backgroundColor: const Color(0xFF1E293B),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Alternar Loja',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded,
                          color: Colors.white54),
                      onPressed: () => Navigator.pop(ctx),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                const Text(
                  'Escolha a loja que deseja operar agora:',
                  style: TextStyle(color: Colors.white54, fontSize: 13),
                ),
                const SizedBox(height: 16),
                Flexible(
                  child: ListView.separated(
                    shrinkWrap: true,
                    itemCount: auth.lojasDisponiveis.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (c, i) {
                      final loja = auth.lojasDisponiveis[i];
                      final isSelected = loja.lojaId == auth.tenantId;

                      return ListTile(
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                          side: BorderSide(
                            color: isSelected
                                ? const Color(0xFF3B82F6)
                                : Colors.white.withOpacity(0.08),
                          ),
                        ),
                        tileColor: isSelected
                            ? const Color(0xFF3B82F6).withOpacity(0.12)
                            : Colors.white.withOpacity(0.04),
                        leading: Container(
                          width: 38,
                          height: 38,
                          decoration: BoxDecoration(
                            color: const Color(0xFF3B82F6).withOpacity(0.15),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Icon(Icons.storefront_rounded,
                              color: Color(0xFF60A5FA), size: 20),
                        ),
                        title: Text(
                          loja.nomeExibicao,
                          style: TextStyle(
                            color: Colors.white,
                            fontWeight: isSelected
                                ? FontWeight.bold
                                : FontWeight.w500,
                            fontSize: 14,
                          ),
                        ),
                        subtitle: Text(
                          'Papel: ${loja.papel}',
                          style: TextStyle(
                              color: Colors.white.withOpacity(0.5),
                              fontSize: 11),
                        ),
                        trailing: isSelected
                            ? const Icon(Icons.check_circle_rounded,
                                color: Color(0xFF3B82F6), size: 20)
                            : null,
                        onTap: () async {
                          Navigator.pop(ctx);
                          if (!isSelected) {
                            await auth.trocarLoja(loja);
                            if (context.mounted && auth.precisaSyncInicial) {
                              Navigator.of(context).push(
                                MaterialPageRoute(
                                    builder: (_) =>
                                        const SyncInitialScreen()),
                              );
                            }
                          }
                        },
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildOfflineBanner(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.orange.withOpacity(0.12),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.orange.withOpacity(0.3)),
      ),
      child: Row(
        children: const [
          Icon(Icons.wifi_off_rounded, color: Colors.orangeAccent, size: 20),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              'Modo offline ativo. As vendas serão sincronizadas assim que reconectar.',
              style: TextStyle(color: Colors.orangeAccent, fontSize: 13, height: 1.4),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPendentesCard(
    BuildContext context,
    AuthService auth,
    SyncService sync,
  ) {
    return StreamBuilder<int>(
      stream: context
          .read<AppDatabase>()
          .vendaDao
          .watchCountPendentes(auth.tenantId ?? ''),
      builder: (context, snapshot) {
        final count = snapshot.data ?? 0;
        if (count == 0) return const SizedBox.shrink();

        return Container(
          margin: const EdgeInsets.only(bottom: 16),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: const Color(0xFF1D4ED8).withOpacity(0.15),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFF3B82F6).withOpacity(0.4)),
          ),
          child: Row(
            children: [
              Container(
                width: 42, height: 42,
                decoration: BoxDecoration(
                  color: const Color(0xFF3B82F6).withOpacity(0.2),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.cloud_upload_outlined,
                    color: Color(0xFF60A5FA), size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '$count ${count == 1 ? 'venda pendente' : 'vendas pendentes'} de sincronização',
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                    ),
                    if (sync.sincronizando)
                      const Text(
                        'Enviando...',
                        style: TextStyle(color: Color(0xFF60A5FA), fontSize: 12),
                      ),
                  ],
                ),
              ),
              if (!sync.sincronizando)
                TextButton(
                  onPressed: () => sync.sincronizarAoReconectar(),
                  child: const Text('Sincronizar',
                      style: TextStyle(color: Color(0xFF60A5FA))),
                ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildMenuRapido(BuildContext context) {
    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      mainAxisSpacing: 12,
      crossAxisSpacing: 12,
      childAspectRatio: 1.6,
      children: [
        _MenuCard(
          icon: Icons.add_shopping_cart_rounded,
          label: 'Nova Venda',
          color: const Color(0xFF3B82F6),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const NovaVendaScreen()),
          ),
        ),
        _MenuCard(
          icon: Icons.people_outline_rounded,
          label: 'Clientes',
          color: const Color(0xFF8B5CF6),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const ClientesScreen()),
          ),
        ),
        _MenuCard(
          icon: Icons.inventory_2_outlined,
          label: 'Produtos',
          color: const Color(0xFF10B981),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const ProdutosScreen()),
          ),
        ),
        _MenuCard(
          icon: Icons.receipt_long_outlined,
          label: 'Histórico',
          color: const Color(0xFFF59E0B),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const HistoricoScreen()),
          ),
        ),
      ],
    );
  }

  Widget _buildAcoesRapidas(
    BuildContext context,
    SyncService sync,
    AuthService auth,
  ) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Ações rápidas',
          style: TextStyle(
            color: Colors.white70,
            fontSize: 13,
            fontWeight: FontWeight.w600,
            letterSpacing: 0.5,
          ),
        ),
        const SizedBox(height: 12),
        _AcaoTile(
          icon: Icons.sync_rounded,
          label: 'Sincronizar agora',
          sublabel: sync.sincronizando ? sync.mensagem : 'Atualizar dados do servidor',
          onTap: sync.sincronizando
              ? null
              : () => sync.sincronizarAoReconectar(),
          loading: sync.sincronizando,
        ),
        const SizedBox(height: 10),
        _AcaoTile(
          icon: Icons.inventory_rounded,
          label: 'Sincronização Final (Fechamento)',
          sublabel: 'Enviar vendas pendentes e fechar expediente',
          onTap: sync.sincronizando
              ? null
              : () => _executarSincronizacaoFinal(context, sync, auth),
          loading: false,
        ),
      ],
    );
  }

  void _executarSincronizacaoFinal(
    BuildContext context,
    SyncService sync,
    AuthService auth,
  ) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        title: Row(
          children: const [
            Icon(Icons.inventory_rounded, color: Color(0xFF10B981)),
            SizedBox(width: 8),
            Text('Fechamento do Dia', style: TextStyle(color: Colors.white, fontSize: 18)),
          ],
        ),
        content: const Text(
          'Deseja realizar a Sincronização Final do dia?\n\n'
          '• Todas as vendas offline serão enviadas ao servidor.\n'
          '• O estoque e catálogo de produtos serão atualizados.',
          style: TextStyle(color: Colors.white70, height: 1.4),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancelar'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF10B981),
            ),
            onPressed: () async {
              Navigator.pop(ctx);
              final tenantId = auth.tenantId;
              if (tenantId == null) return;

              final res = await sync.sincronizarFinal(tenantId);
              if (!context.mounted) return;

              showDialog(
                context: context,
                builder: (_) => AlertDialog(
                  backgroundColor: const Color(0xFF1E293B),
                  title: Row(
                    children: [
                      Icon(
                        res.erros.isEmpty ? Icons.check_circle : Icons.warning_amber_rounded,
                        color: res.erros.isEmpty ? Colors.greenAccent : Colors.orangeAccent,
                      ),
                      const SizedBox(width: 8),
                      const Text('Fechamento Concluído', style: TextStyle(color: Colors.white, fontSize: 17)),
                    ],
                  ),
                  content: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('• Vendas enviadas: ${res.vendasEnviadas}', style: const TextStyle(color: Colors.white)),
                      Text('• Produtos atualizados: ${res.produtosBaixados}', style: const TextStyle(color: Colors.white)),
                      Text('• Clientes atualizados: ${res.clientesBaixados}', style: const TextStyle(color: Colors.white)),
                      if (res.erros.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Text(
                          'Avisos:\n${res.erros.join('\n')}',
                          style: const TextStyle(color: Colors.orangeAccent, fontSize: 12),
                        ),
                      ],
                    ],
                  ),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(context),
                      child: const Text('OK'),
                    ),
                  ],
                ),
              );
            },
            child: const Text('Confirmar Fechamento'),
          ),
        ],
      ),
    );
  }

  void _confirmarLogout(BuildContext context) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        title: const Text('Sair', style: TextStyle(color: Colors.white)),
        content: const Text(
          'Deseja sair? Os dados offline serão mantidos.',
          style: TextStyle(color: Colors.white70),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
          TextButton(
            onPressed: () async {
              Navigator.pop(context);
              await context.read<AuthService>().logout();
              if (!context.mounted) return;
              Navigator.of(context).pushNamedAndRemoveUntil('/', (_) => false);
            },
            child: const Text('Sair', style: TextStyle(color: Colors.redAccent)),
          ),
        ],
      ),
    );
  }
}

// ─── Componentes reutilizáveis ──────────────────────────────────

class _MenuCard extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  const _MenuCard({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withOpacity(0.25)),
        ),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Icon(icon, color: color, size: 26),
            Text(
              label,
              style: TextStyle(
                color: color,
                fontWeight: FontWeight.w600,
                fontSize: 13,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _AcaoTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final String sublabel;
  final VoidCallback? onTap;
  final bool loading;

  const _AcaoTile({
    required this.icon,
    required this.label,
    required this.sublabel,
    this.onTap,
    this.loading = false,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.04),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: Colors.white.withOpacity(0.08)),
        ),
        child: Row(
          children: [
            Container(
              width: 40, height: 40,
              decoration: BoxDecoration(
                color: const Color(0xFF3B82F6).withOpacity(0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: loading
                  ? const Padding(
                      padding: EdgeInsets.all(10),
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Color(0xFF60A5FA),
                      ),
                    )
                  : Icon(icon, color: const Color(0xFF60A5FA), size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label,
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w500,
                        fontSize: 14,
                      )),
                  Text(sublabel,
                      style: const TextStyle(
                        color: Colors.white38,
                        fontSize: 12,
                      )),
                ],
              ),
            ),
            const Icon(Icons.chevron_right_rounded,
                color: Colors.white24, size: 20),
          ],
        ),
      ),
    );
  }
}
