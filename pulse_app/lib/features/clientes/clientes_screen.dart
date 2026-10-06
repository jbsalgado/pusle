// lib/features/clientes/clientes_screen.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/database/app_database.dart';
import '../../features/auth/auth_service.dart';

class ClientesScreen extends StatefulWidget {
  const ClientesScreen({super.key});

  @override
  State<ClientesScreen> createState() => _ClientesScreenState();
}

class _ClientesScreenState extends State<ClientesScreen> {
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
        title: const Text('Clientes', style: TextStyle(color: Colors.white)),
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(56),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: TextField(
              controller: _busca,
              style: const TextStyle(color: Colors.white),
              decoration: InputDecoration(
                hintText: 'Nome, CPF ou telefone...',
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
      body: _termo.length < 2
          ? const Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.people_outline_rounded,
                      color: Colors.white24, size: 56),
                  SizedBox(height: 12),
                  Text(
                    'Digite ao menos 2 caracteres para buscar',
                    style: TextStyle(color: Colors.white38, fontSize: 14),
                  ),
                ],
              ),
            )
          : FutureBuilder<List<Cliente>>(
              future: db.clienteDao.buscar(auth.tenantId!, _termo),
              builder: (ctx, snap) {
                if (snap.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator());
                }
                final lista = snap.data ?? [];
                if (lista.isEmpty) {
                  return const Center(
                    child: Text(
                      'Nenhum cliente encontrado',
                      style: TextStyle(color: Colors.white38),
                    ),
                  );
                }
                return ListView.builder(
                  padding: const EdgeInsets.all(12),
                  itemCount: lista.length,
                  itemBuilder: (_, i) => _ClienteCard(cliente: lista[i]),
                );
              },
            ),
    );
  }
}

class _ClienteCard extends StatelessWidget {
  final Cliente cliente;
  const _ClienteCard({required this.cliente});

  @override
  Widget build(BuildContext context) {
    final iniciais = cliente.nomeCompleto.isNotEmpty
        ? cliente.nomeCompleto.trim().split(' ').take(2).map((s) => s[0]).join()
        : '?';

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.04),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.white.withOpacity(0.08)),
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
        leading: CircleAvatar(
          backgroundColor: const Color(0xFF8B5CF6).withOpacity(0.2),
          child: Text(
            iniciais.toUpperCase(),
            style: const TextStyle(
              color: Color(0xFFA78BFA),
              fontWeight: FontWeight.bold,
              fontSize: 14,
            ),
          ),
        ),
        title: Text(
          cliente.nomeCompleto,
          style: const TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w500,
            fontSize: 14,
          ),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (cliente.cpf != null)
              Text('CPF: ${cliente.cpf}',
                  style: const TextStyle(color: Colors.white38, fontSize: 12)),
            if (cliente.telefone != null)
              Text(cliente.telefone!,
                  style: const TextStyle(color: Colors.white54, fontSize: 12)),
          ],
        ),
      ),
    );
  }
}
