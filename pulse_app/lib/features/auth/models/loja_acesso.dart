// lib/features/auth/models/loja_acesso.dart
//
// Representa uma loja à qual o usuário tem permissão de acesso.

class LojaAcesso {
  final String lojaId;
  final String nome;
  final String? nomeFantasia;
  final String? logoUrl;
  final String papel; // 'dono', 'vendedor', 'cobrador', 'administrador'
  final String? colaboradorId;
  final bool ehVendedor;
  final bool ehCobrador;
  final bool ehDono;

  const LojaAcesso({
    required this.lojaId,
    required this.nome,
    this.nomeFantasia,
    this.logoUrl,
    required this.papel,
    this.colaboradorId,
    this.ehVendedor = false,
    this.ehCobrador = false,
    this.ehDono = false,
  });

  String get nomeExibicao => (nomeFantasia != null && nomeFantasia!.isNotEmpty)
      ? nomeFantasia!
      : nome;

  factory LojaAcesso.fromJson(Map<String, dynamic> json) {
    return LojaAcesso(
      lojaId: json['loja_id']?.toString() ?? '',
      nome: json['nome']?.toString() ?? 'Loja',
      nomeFantasia: json['nome_fantasia']?.toString(),
      logoUrl: json['logo_url']?.toString(),
      papel: json['papel']?.toString() ?? 'colaborador',
      colaboradorId: json['colaborador_id']?.toString(),
      ehVendedor: json['eh_vendedor'] == true,
      ehCobrador: json['eh_cobrador'] == true,
      ehDono: json['eh_dono'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'loja_id': lojaId,
      'nome': nome,
      'nome_fantasia': nomeFantasia,
      'logo_url': logoUrl,
      'papel': papel,
      'colaborador_id': colaboradorId,
      'eh_vendedor': ehVendedor,
      'eh_cobrador': ehCobrador,
      'eh_dono': ehDono,
    };
  }
}
