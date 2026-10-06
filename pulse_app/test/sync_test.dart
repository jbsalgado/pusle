import 'package:flutter_test/flutter_test.dart';
import 'package:pulse_app/core/sync/sync_service.dart';

void main() {
  group('SyncResultado Tests', () {
    test('SyncResultado sem erros', () {
      final res = SyncResultado(
        produtosBaixados: 120,
        clientesBaixados: 45,
        categoriasBaixadas: 8,
        formasBaixadas: 4,
        vendasEnviadas: 5,
        vendasComErro: 0,
        erros: [],
      );

      expect(res.produtosBaixados, 120);
      expect(res.vendasEnviadas, 5);
      expect(res.temErros, isFalse);
    });

    test('SyncResultado com erros', () {
      final res = SyncResultado(
        vendasEnviadas: 3,
        vendasComErro: 2,
        erros: ['Falha ao sincronizar venda X'],
      );

      expect(res.temErros, isTrue);
      expect(res.vendasComErro, 2);
      expect(res.erros.length, 1);
    });
  });
}
