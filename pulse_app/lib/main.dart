// lib/main.dart
//
// Entry point do Pulse App – App de Vendas Offline/Online
//
// Arquitetura:
//   - drift (SQLite)       → banco local
//   - ConnectivityService  → monitora internet (singleton)
//   - AuthService          → JWT + sessão
//   - SyncService          → pull/push sincronizador
//   - Provider             → state management
//
// Fluxo de inicialização:
//   1. ConnectivityService.inicializar()
//   2. AuthService.verificarSessao()
//   3. Se autenticado + sem sync → SyncInitialScreen
//   4. Se autenticado + com sync → DashboardScreen
//   5. Se não autenticado → LoginScreen

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'core/database/app_database.dart';
import 'core/network/connectivity_service.dart';
import 'core/sync/sync_service.dart';
import 'features/auth/auth_service.dart';
import 'features/auth/login_screen.dart';
import 'features/auth/loja_selection_screen.dart';
import 'features/sync/sync_initial_screen.dart';
import 'features/dashboard/dashboard_screen.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Inicializa conectividade antes de tudo
  final connectivity = ConnectivityService();
  await connectivity.inicializar();

  runApp(PulseApp(connectivity: connectivity));
}

class PulseApp extends StatelessWidget {
  final ConnectivityService connectivity;

  const PulseApp({super.key, required this.connectivity});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        // Banco de dados SQLite (drift)
        Provider<AppDatabase>(
          create: (_) => AppDatabase(),
          dispose: (_, db) => db.close(),
        ),

        // Serviço de conectividade (já inicializado)
        ChangeNotifierProvider<ConnectivityService>.value(
          value: connectivity,
        ),

        // Auth Service
        ChangeNotifierProxyProvider<AppDatabase, AuthService>(
          create: (ctx) => AuthService(ctx.read<AppDatabase>()),
          update: (ctx, db, prev) => prev ?? AuthService(db),
        ),

        // Sync Service (depende de DB e Connectivity)
        ChangeNotifierProxyProvider2<AppDatabase, ConnectivityService, SyncService>(
          create: (ctx) => SyncService(
            ctx.read<AppDatabase>(),
            ctx.read<ConnectivityService>(),
          ),
          update: (ctx, db, conn, prev) =>
              prev ?? SyncService(db, conn),
        ),
      ],
      child: MaterialApp(
        title: 'Pulse Vendas',
        debugShowCheckedModeBanner: false,
        theme: _buildTheme(),
        home: const _RootPage(),
      ),
    );
  }

  ThemeData _buildTheme() {
    return ThemeData(
      brightness: Brightness.dark,
      scaffoldBackgroundColor: const Color(0xFF0F172A),
      colorScheme: const ColorScheme.dark(
        primary: Color(0xFF3B82F6),
        secondary: Color(0xFF10B981),
        surface: Color(0xFF1E293B),
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: Color(0xFF1E293B),
        foregroundColor: Colors.white,
        elevation: 0,
        titleTextStyle: TextStyle(
          color: Colors.white,
          fontSize: 17,
          fontWeight: FontWeight.w600,
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFF3B82F6),
          foregroundColor: Colors.white,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white.withValues(alpha: 0.07),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
      ),
      snackBarTheme: const SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.all(Radius.circular(12)),
        ),
      ),
    );
  }
}

// ─── Roteador inicial ──────────────────────────────────────────

class _RootPage extends StatefulWidget {
  const _RootPage();

  @override
  State<_RootPage> createState() => _RootPageState();
}

class _RootPageState extends State<_RootPage> {
  bool _verificando = true;

  @override
  void initState() {
    super.initState();
    _verificarSessao();
  }

  Future<void> _verificarSessao() async {
    await context.read<AuthService>().verificarSessao();
    if (mounted) setState(() => _verificando = false);
  }

  @override
  Widget build(BuildContext context) {
    if (_verificando) {
      return const Scaffold(
        backgroundColor: Color(0xFF0F172A),
        body: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              CircularProgressIndicator(color: Color(0xFF3B82F6)),
              SizedBox(height: 20),
              Text(
                'Pulse Vendas',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
        ),
      );
    }

    final auth = context.watch<AuthService>();

    if (!auth.isAutenticado) {
      return const LoginScreen();
    }

    if (auth.precisaSelecionarLoja) {
      return const LojaSelectionScreen();
    }

    if (auth.precisaSyncInicial) {
      return const SyncInitialScreen();
    }

    return const DashboardScreen();
  }
}
