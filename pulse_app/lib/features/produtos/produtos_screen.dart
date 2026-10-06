// lib/features/produtos/produtos_screen.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'dart:convert';
import '../../core/database/app_database.dart';
import '../../core/widgets/pulse_product_image.dart';
import '../../features/auth/auth_service.dart';

class ProdutosScreen extends StatefulWidget {
  const ProdutosScreen({super.key});

  @override
  State<ProdutosScreen> createState() => _ProdutosScreenState();
}

class _ProdutosScreenState extends State<ProdutosScreen> {
  final _busca = TextEditingController();
  String _termo = '';

  @override
  void dispose() {
    _busca.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.read<AuthService>();
    final db = context.read<AppDatabase>();

    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1E293B),
        title: const Text('Produtos', style: TextStyle(color: Colors.white)),
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(56),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: TextField(
              controller: _busca,
              style: const TextStyle(color: Colors.white),
              decoration: InputDecoration(
                hintText: 'Buscar por nome, código, referência...',
                hintStyle: const TextStyle(color: Colors.white38),
                prefixIcon: const Icon(Icons.search, color: Colors.white38),
                filled: true,
                fillColor: Colors.white.withOpacity(0.07),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
              ),
              onChanged: (v) => setState(() => _termo = v.trim()),
            ),
          ),
        ),
      ),
      body: FutureBuilder<List<Produto>>(
        future: _termo.length >= 2
            ? db.produtoDao.buscarProdutos(auth.tenantId!, _termo)
            : db.produtoDao.getProdutosPorTenant(auth.tenantId!),
        builder: (ctx, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          final lista = snap.data ?? [];
          if (lista.isEmpty) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.inventory_2_outlined,
                      color: Colors.white24, size: 56),
                  const SizedBox(height: 12),
                  Text(
                    _termo.isNotEmpty
                        ? 'Nenhum produto encontrado'
                        : 'Nenhum produto sincronizado',
                    style: const TextStyle(color: Colors.white38, fontSize: 14),
                  ),
                ],
              ),
            );
          }
          return ListView.builder(
            padding: const EdgeInsets.all(12),
            itemCount: lista.length,
            itemBuilder: (_, i) => _ProdutoCard(produto: lista[i]),
          );
        },
      ),
    );
  }
}

class _ProdutoCard extends StatelessWidget {
  final Produto produto;
  const _ProdutoCard({required this.produto});

  @override
  Widget build(BuildContext context) {
    final variantes = produto.variantesJson != null
        ? (jsonDecode(produto.variantesJson!) as List).length
        : 0;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.04),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.white.withOpacity(0.08)),
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        leading: PulseProductImage(
          nome: produto.nome,
          fotoLocalPath: produto.fotoLocalPath,
          fotoUrl: produto.fotoUrl,
          width: 50,
          height: 50,
        ),
        title: Text(
          produto.nome,
          style: const TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w500,
            fontSize: 14,
          ),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (produto.codigoBarras != null)
              Text(produto.codigoBarras!,
                  style: const TextStyle(color: Colors.white38, fontSize: 11)),
            Row(
              children: [
                if (produto.emPromocao && produto.precoPromocional > 0) ...[
                  Text(
                    'R\$ ${produto.precoPromocional.toStringAsFixed(2)}',
                    style: const TextStyle(
                      color: Colors.greenAccent,
                      fontWeight: FontWeight.bold,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(width: 4),
                  Text(
                    'R\$ ${produto.precoVendaSugerido.toStringAsFixed(2)}',
                    style: const TextStyle(
                      color: Colors.white24,
                      fontSize: 11,
                      decoration: TextDecoration.lineThrough,
                    ),
                  ),
                ] else
                  Text(
                    'R\$ ${produto.precoVendaSugerido.toStringAsFixed(2)}',
                    style: const TextStyle(
                      color: Colors.white70,
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                  ),
              ],
            ),
          ],
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              '${produto.estoqueAtual}',
              style: TextStyle(
                color: produto.estoqueAtual > 0 ? Colors.greenAccent : Colors.redAccent,
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
            Text(
              produto.unidadeMedida,
              style: const TextStyle(color: Colors.white38, fontSize: 10),
            ),
            if (variantes > 0)
              Text(
                '$variantes vars',
                style: const TextStyle(
                  color: Color(0xFF60A5FA),
                  fontSize: 10,
                ),
              ),
          ],
        ),
      ),
    );
  }
}
