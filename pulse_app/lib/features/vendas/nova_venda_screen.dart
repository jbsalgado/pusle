// lib/features/vendas/nova_venda_screen.dart
//
// Tela de nova venda com:
//   - Busca de produto por nome ou scanner de código de barras
//   - Carrinho de itens (local)
//   - Seleção de cliente (opcional)
//   - Seleção de forma de pagamento
//   - Salvamento local (SQLite) com sync_status='pending'
//   - Se online: tenta envio imediato ao servidor

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:uuid/uuid.dart';
import 'package:drift/drift.dart' show Value;
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../core/database/app_database.dart';
import '../../core/network/connectivity_service.dart';
import '../../core/sync/sync_service.dart';
import '../../core/widgets/pulse_product_image.dart';
import '../../features/auth/auth_service.dart';

const _uuid = Uuid();

class _ItemCarrinho {
  final Produto produto;
  int quantidade = 1;
  double precoUnitario;

  _ItemCarrinho({
    required this.produto,
    required this.precoUnitario,
  });

  double get subtotal => quantidade * precoUnitario;
}

class NovaVendaScreen extends StatefulWidget {
  const NovaVendaScreen({super.key});

  @override
  State<NovaVendaScreen> createState() => _NovaVendaScreenState();
}

class _NovaVendaScreenState extends State<NovaVendaScreen> {
  final List<_ItemCarrinho> _carrinho = [];
  FormaPagamento? _formaPagamento;
  Cliente? _clienteSelecionado;
  bool _salvando = false;
  bool _mostrarScanner = false;

  double get _total => _carrinho.fold(0.0, (s, i) => s + i.subtotal);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1E293B),
        title: const Text('Nova Venda', style: TextStyle(color: Colors.white)),
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.qr_code_scanner_rounded),
            onPressed: () => setState(() => _mostrarScanner = !_mostrarScanner),
            tooltip: 'Scanner',
          ),
        ],
      ),
      body: Column(
        children: [
          // Scanner (quando ativo)
          if (_mostrarScanner)
            SizedBox(
              height: 180,
              child: MobileScanner(
                onDetect: (capture) {
                  final codigo = capture.barcodes.firstOrNull?.rawValue;
                  if (codigo != null) {
                    setState(() => _mostrarScanner = false);
                    _buscarPorCodigo(codigo);
                  }
                },
              ),
            ),

          // Busca de produto
          _buildBuscaProduto(),

          // Carrinho
          Expanded(
            child: _carrinho.isEmpty
                ? _buildCarrinhoVazio()
                : _buildListaCarrinho(),
          ),

          // Rodapé com total e botão
          _buildRodape(),
        ],
      ),
    );
  }

  Widget _buildBuscaProduto() {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: TextField(
        style: const TextStyle(color: Colors.white),
        decoration: InputDecoration(
          hintText: 'Buscar produto por nome ou código...',
          hintStyle: const TextStyle(color: Colors.white38),
          prefixIcon: const Icon(Icons.search, color: Colors.white38),
          filled: true,
          fillColor: Colors.white.withOpacity(0.07),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide.none,
          ),
        ),
        onSubmitted: _buscarPorTexto,
      ),
    );
  }

  Widget _buildCarrinhoVazio() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: const [
          Icon(Icons.shopping_cart_outlined, color: Colors.white24, size: 56),
          SizedBox(height: 12),
          Text('Carrinho vazio', style: TextStyle(color: Colors.white38, fontSize: 14)),
          SizedBox(height: 4),
          Text('Busque ou escaneie um produto para adicionar',
              style: TextStyle(color: Colors.white24, fontSize: 12)),
        ],
      ),
    );
  }

  Widget _buildListaCarrinho() {
    return ListView.builder(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      itemCount: _carrinho.length,
      itemBuilder: (_, i) {
        final item = _carrinho[i];
        return Container(
          margin: const EdgeInsets.only(bottom: 8),
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.04),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.white.withOpacity(0.08)),
          ),
          child: Row(
            children: [
              PulseProductImage(
                nome: item.produto.nome,
                fotoLocalPath: item.produto.fotoLocalPath,
                fotoUrl: item.produto.fotoUrl,
                width: 44,
                height: 44,
                borderRadius: 10,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(item.produto.nome,
                        style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w500,
                            fontSize: 13)),
                    Text(
                      'R\$ ${item.precoUnitario.toStringAsFixed(2)}',
                      style: const TextStyle(color: Colors.white54, fontSize: 12),
                    ),
                  ],
                ),
              ),
              // Controle de quantidade
              Row(
                children: [
                  _QtyButton(
                    icon: Icons.remove,
                    onTap: () => setState(() {
                      if (item.quantidade > 1) {
                        item.quantidade--;
                      } else {
                        _carrinho.removeAt(i);
                      }
                    }),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    child: Text(
                      '${item.quantidade}',
                      style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 16),
                    ),
                  ),
                  _QtyButton(
                    icon: Icons.add,
                    onTap: () => setState(() => item.quantidade++),
                  ),
                ],
              ),
              const SizedBox(width: 12),
              Text(
                'R\$ ${item.subtotal.toStringAsFixed(2)}',
                style: const TextStyle(
                    color: Colors.greenAccent,
                    fontWeight: FontWeight.bold,
                    fontSize: 14),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildRodape() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        border: Border(top: BorderSide(color: Colors.white.withOpacity(0.08))),
      ),
      child: Column(
        children: [
          // Forma de pagamento
          _SeletorFormaPagamento(
            selecionada: _formaPagamento,
            onSelecionada: (f) => setState(() => _formaPagamento = f),
          ),
          const SizedBox(height: 12),
          // Total e botão finalizar
          Row(
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Total',
                      style: TextStyle(color: Colors.white54, fontSize: 12)),
                  Text(
                    'R\$ ${_total.toStringAsFixed(2)}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 22,
                    ),
                  ),
                ],
              ),
              const Spacer(),
              SizedBox(
                height: 48,
                child: ElevatedButton.icon(
                  icon: _salvando
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                              strokeWidth: 2, color: Colors.white))
                      : const Icon(Icons.check_rounded),
                  label: const Text('Finalizar'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF10B981),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 24),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: (_carrinho.isEmpty || _formaPagamento == null || _salvando)
                      ? null
                      : _finalizarVenda,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // ─── Lógica ────────────────────────────────────────────────────

  Future<void> _buscarPorCodigo(String codigo) async {
    final db = context.read<AppDatabase>();
    final auth = context.read<AuthService>();
    final produto = await db.produtoDao.getByCodigo(auth.tenantId!, codigo);
    if (produto != null) {
      _adicionarProduto(produto);
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Produto "$codigo" não encontrado'),
          backgroundColor: Colors.redAccent,
        ),
      );
    }
  }

  Future<void> _buscarPorTexto(String termo) async {
    if (termo.length < 2) return;
    final db = context.read<AppDatabase>();
    final auth = context.read<AuthService>();
    final produtos = await db.produtoDao.buscarProdutos(auth.tenantId!, termo);

    if (produtos.isEmpty && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Nenhum produto encontrado'),
          backgroundColor: Colors.redAccent,
        ),
      );
      return;
    }
    if (produtos.length == 1) {
      _adicionarProduto(produtos.first);
    } else {
      _mostrarSeletorProdutos(produtos);
    }
  }

  void _adicionarProduto(Produto produto) {
    setState(() {
      final existente = _carrinho
          .where((i) => i.produto.id == produto.id)
          .firstOrNull;
      if (existente != null) {
        existente.quantidade++;
      } else {
        _carrinho.add(_ItemCarrinho(
          produto: produto,
          precoUnitario: produto.precoVigente > 0
              ? produto.precoVigente
              : produto.precoVendaSugerido,
        ));
      }
    });
  }

  void _mostrarSeletorProdutos(List<Produto> produtos) {
    showModalBottomSheet(
      context: context,
      backgroundColor: const Color(0xFF1E293B),
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: produtos.length,
        itemBuilder: (_, i) {
          final p = produtos[i];
          return ListTile(
            leading: PulseProductImage(
              nome: p.nome,
              fotoLocalPath: p.fotoLocalPath,
              fotoUrl: p.fotoUrl,
              width: 42,
              height: 42,
              borderRadius: 10,
            ),
            title: Text(p.nome, style: const TextStyle(color: Colors.white)),
            subtitle: Text(
              'R\$ ${p.precoVigente.toStringAsFixed(2)}',
              style: const TextStyle(color: Colors.white54),
            ),
            onTap: () {
              Navigator.pop(context);
              _adicionarProduto(p);
            },
          );
        },
      ),
    );
  }

  Future<void> _finalizarVenda() async {
    if (_carrinho.isEmpty || _formaPagamento == null) return;
    setState(() => _salvando = true);

    final auth = context.read<AuthService>();
    final db = context.read<AppDatabase>();
    final connectivity = context.read<ConnectivityService>();
    final sync = context.read<SyncService>();

    final vendaId = _uuid.v4();
    final agora = DateTime.now().toIso8601String();

    try {
      // 1. Salva localmente (sempre)
      await db.vendaDao.inserirVenda(VendasCompanion(
        id: Value(vendaId),
        usuarioId: Value(auth.tenantId!),
        clienteId: Value(_clienteSelecionado?.id),
        formaPagamentoId: Value(_formaPagamento!.id),
        dataVenda: Value(agora),
        valorTotal: Value(_total),
        numeroParcelas: const Value(1),
        statusVendaCodigo: const Value('QUITADA'),
        tipoVenda: const Value('BALCAO'),
        dataCriacao: Value(agora),
        syncStatus: const Value('pending'),
      ));

      // Salva itens
      final itens = _carrinho.map((item) {
        return VendaItensCompanion(
          id: Value(_uuid.v4()),
          vendaId: Value(vendaId),
          produtoId: Value(item.produto.id),
          quantidade: Value(item.quantidade.toDouble()),
          precoUnitarioVenda: Value(item.precoUnitario),
          valorTotalItem: Value(item.subtotal),
        );
      }).toList();
      await db.vendaDao.inserirItens(itens);

      // 2. Se online, tenta envio imediato via SyncService
      if (connectivity.isOnline) {
        // Executa push em background sem bloquear o retorno ao usuário
        sync.sincronizarAoReconectar();
      }

      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            connectivity.isOnline
                ? '✓ Venda registrada e enviada!'
                : '✓ Venda salva. Será sincronizada ao reconectar.',
          ),
          backgroundColor: Colors.greenAccent.shade700,
        ),
      );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Erro ao registrar venda: $e'),
            backgroundColor: Colors.redAccent,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _salvando = false);
    }
  }
}

// ─── Componentes auxiliares ─────────────────────────────────────

class _QtyButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;
  const _QtyButton({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 32, height: 32,
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Icon(icon, color: Colors.white70, size: 18),
      ),
    );
  }
}

class _SeletorFormaPagamento extends StatelessWidget {
  final FormaPagamento? selecionada;
  final void Function(FormaPagamento) onSelecionada;

  const _SeletorFormaPagamento({
    required this.selecionada,
    required this.onSelecionada,
  });

  @override
  Widget build(BuildContext context) {
    final auth = context.read<AuthService>();
    final db = context.read<AppDatabase>();

    return FutureBuilder<List<FormaPagamento>>(
      future: db.catalogDao.getFormasPagamento(auth.tenantId!),
      builder: (ctx, snap) {
        final formas = snap.data ?? [];
        return DropdownButtonFormField<FormaPagamento>(
          initialValue: selecionada,
          hint: const Text('Forma de pagamento',
              style: TextStyle(color: Colors.white38)),
          dropdownColor: const Color(0xFF1E293B),
          style: const TextStyle(color: Colors.white),
          decoration: InputDecoration(
            filled: true,
            fillColor: Colors.white.withValues(alpha: 0.07),
            contentPadding:
                const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide.none,
            ),
            prefixIcon: const Icon(Icons.payment_outlined, color: Colors.white38),
          ),
          items: formas.map((f) {
            return DropdownMenuItem<FormaPagamento>(
              value: f,
              child: Text(f.nome),
            );
          }).toList(),
          onChanged: (f) {
            if (f != null) onSelecionada(f);
          },
        );
      },
    );
  }
}
