// lib/features/vendas/historico_screen.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../core/database/app_database.dart';
import '../../features/auth/auth_service.dart';

class HistoricoScreen extends StatelessWidget {
  const HistoricoScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.read<AuthService>();
    final db = context.read<AppDatabase>();

    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1E293B),
        title: const Text('Histórico de Vendas',
            style: TextStyle(color: Colors.white)),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: FutureBuilder<List<Venda>>(
        future: db.vendaDao.getHistorico(auth.tenantId!, limit: 100),
        builder: (ctx, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          final lista = snap.data ?? [];
          if (lista.isEmpty) {
            return const Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.receipt_long_outlined,
                      color: Colors.white24, size: 56),
                  SizedBox(height: 12),
                  Text('Nenhuma venda registrada',
                      style: TextStyle(color: Colors.white38, fontSize: 14)),
                ],
              ),
            );
          }
          return ListView.builder(
            padding: const EdgeInsets.all(12),
            itemCount: lista.length,
            itemBuilder: (_, i) => _VendaCard(venda: lista[i]),
          );
        },
      ),
    );
  }
}

class _VendaCard extends StatelessWidget {
  final Venda venda;
  const _VendaCard({required this.venda});

  Color get _statusColor {
    switch (venda.syncStatus) {
      case 'synced':
        return Colors.greenAccent;
      case 'pending':
        return Colors.orangeAccent;
      case 'error':
        return Colors.redAccent;
      default:
        return Colors.blueAccent;
    }
  }

  String get _statusLabel {
    switch (venda.syncStatus) {
      case 'synced':
        return 'Sincronizada';
      case 'pending':
        return 'Pendente';
      case 'error':
        return 'Erro';
      default:
        return 'Enviando...';
    }
  }

  @override
  Widget build(BuildContext context) {
    String dataFormatada = '';
    try {
      final dt = DateTime.parse(venda.dataVenda);
      dataFormatada = DateFormat("dd/MM/yyyy 'às' HH:mm").format(dt);
    } catch (_) {
      dataFormatada = venda.dataVenda;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.04),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.white.withOpacity(0.08)),
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        leading: Container(
          width: 44, height: 44,
          decoration: BoxDecoration(
            color: const Color(0xFFF59E0B).withOpacity(0.1),
            borderRadius: BorderRadius.circular(10),
          ),
          child: const Icon(Icons.receipt_rounded,
              color: Color(0xFFF59E0B), size: 22),
        ),
        title: Row(
          children: [
            Expanded(
              child: Text(
                'R\$ ${venda.valorTotal.toStringAsFixed(2)}',
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: _statusColor.withOpacity(0.1),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: _statusColor.withOpacity(0.3)),
              ),
              child: Text(
                _statusLabel,
                style: TextStyle(
                  color: _statusColor,
                  fontSize: 10,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              dataFormatada,
              style: const TextStyle(color: Colors.white54, fontSize: 12),
            ),
            if (venda.syncError != null)
              Text(
                venda.syncError!,
                style: const TextStyle(color: Colors.redAccent, fontSize: 11),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
          ],
        ),
      ),
    );
  }
}
