// lib/core/network/api_client.dart
//
// Cliente HTTP centralizado com:
//   - JWT Bearer Token em toda requisição autenticada
//   - Timeout padrão configurável
//   - Tratamento de erros HTTP padronizado
//   - Detecção de 401 → força re-login

import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Exceção de autenticação expirada — o app deve redirecionar ao login
class AuthExpiredException implements Exception {
  final String message;
  AuthExpiredException([this.message = 'Sessão expirada. Faça login novamente.']);
  @override
  String toString() => message;
}

/// Exceção de API com código de status e mensagem do servidor
class ApiException implements Exception {
  final int statusCode;
  final String message;
  final Map<String, dynamic>? body;
  ApiException(this.statusCode, this.message, [this.body]);
  @override
  String toString() => 'ApiException($statusCode): $message';
}

class ApiClient {
  static const _storage = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
    iOptions: IOSOptions(accessibility: KeychainAccessibility.first_unlock),
  );

  static const _tokenKey = 'pulse_jwt_token';
  static const _serverUrlKey = 'pulse_server_url';
  static const _tenantIdKey = 'pulse_tenant_id';
  static const _lojaAtivaIdKey = 'pulse_loja_ativa_id';
  static const _lojasJsonKey = 'pulse_lojas_json';

  static const Duration _timeout = Duration(seconds: 30);

  // ─── Armazenamento seguro ──────────────────────────────────────

  static Future<void> salvarCredenciais({
    required String token,
    required String serverUrl,
    required String tenantId,
    String? lojaAtivaId,
  }) async {
    await _storage.write(key: _tokenKey, value: token);
    await _storage.write(key: _serverUrlKey, value: serverUrl);
    await _storage.write(key: _tenantIdKey, value: tenantId);
    if (lojaAtivaId != null && lojaAtivaId.isNotEmpty) {
      await _storage.write(key: _lojaAtivaIdKey, value: lojaAtivaId);
    }
  }

  static Future<String?> getToken() => _storage.read(key: _tokenKey);
  static Future<String?> getServerUrl() => _storage.read(key: _serverUrlKey);
  static Future<String?> getTenantId() => _storage.read(key: _tenantIdKey);

  static Future<String?> getLojaAtivaId() => _storage.read(key: _lojaAtivaIdKey);
  static Future<void> setLojaAtivaId(String lojaId) =>
      _storage.write(key: _lojaAtivaIdKey, value: lojaId);

  static Future<void> salvarLojasJson(String jsonStr) =>
      _storage.write(key: _lojasJsonKey, value: jsonStr);
  static Future<String?> getLojasJson() => _storage.read(key: _lojasJsonKey);

  static Future<bool> estaAutenticado() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }

  static Future<void> limparCredenciais() async {
    await _storage.deleteAll();
  }

  // ─── Helpers de headers ───────────────────────────────────────

  static Future<Map<String, String>> _headersAuth() async {
    final token = await getToken();
    final lojaId = await getLojaAtivaId();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
      if (lojaId != null && lojaId.isNotEmpty) 'X-Loja-Id': lojaId,
    };
  }

  static Map<String, String> get _headersPublic => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

  // ─── Buscar Lojas Disponíveis ─────────────────────────────────

  static Future<List<Map<String, dynamic>>> buscarMinhasLojas() async {
    final resp = await get('/api/mobile/minhas-lojas');
    final data = resp['data'] as Map<String, dynamic>?;
    final items = data?['lojas'] as List? ?? [];
    return items.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  // ─── Resolução de URL ──────────────────────────────────────────

  static Future<Uri> _resolveUrl(String path, {Map<String, String>? params}) async {
    var serverUrl = await getServerUrl();
    serverUrl ??= 'https://top-construcoes.catalogo.cloud';
    serverUrl = serverUrl.trimRight().replaceAll(RegExp(r'/$'), '');
    final uri = Uri.parse('$serverUrl$path');
    return params != null ? uri.replace(queryParameters: params) : uri;
  }

  // ─── GET autenticado ──────────────────────────────────────────

  static Future<Map<String, dynamic>> get(
    String path, {
    Map<String, String>? params,
  }) async {
    final url = await _resolveUrl(path, params: params);
    debugPrint('[API] GET $url');

    try {
      final response = await http
          .get(url, headers: await _headersAuth())
          .timeout(_timeout);
      return _parseResponse(response);
    } on SocketException {
      throw const SocketException('Sem conexão com a internet.');
    }
  }

  // ─── POST autenticado ─────────────────────────────────────────

  static Future<Map<String, dynamic>> post(
    String path,
    Map<String, dynamic> body, {
    bool requireAuth = true,
  }) async {
    final url = await _resolveUrl(path);
    debugPrint('[API] POST $url');

    try {
      final headers =
          requireAuth ? await _headersAuth() : _headersPublic;
      final response = await http
          .post(url, headers: headers, body: jsonEncode(body))
          .timeout(_timeout);
      return _parseResponse(response);
    } on SocketException {
      throw const SocketException('Sem conexão com a internet.');
    }
  }

  // ─── Login (sem JWT) ──────────────────────────────────────────

  static Future<Map<String, dynamic>> login(
    String username,
    String password,
  ) async {
    return post(
      '/api/auth/login',
      {'username': username, 'password': password},
      requireAuth: false,
    );
  }

  // ─── Parser de resposta ───────────────────────────────────────

  static Map<String, dynamic> _parseResponse(http.Response response) {
    debugPrint('[API] Status: ${response.statusCode}');

    if (response.statusCode == 401) {
      throw AuthExpiredException();
    }

    Map<String, dynamic> body = {};
    try {
      body = jsonDecode(utf8.decode(response.bodyBytes)) as Map<String, dynamic>;
    } catch (_) {
      body = {'raw': response.body};
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return body;
    }

    String message = (body['message'] ?? body['name'] ?? '').toString();
    if (message.isEmpty || message == 'null') {
      if (response.statusCode == 400) {
        message = 'Requisição inválida (400). Verifique a URL do servidor informada.';
      } else if (response.statusCode == 404) {
        message = 'Servidor ou endpoint não encontrado (404). Verifique a URL do servidor.';
      } else if (response.statusCode == 500) {
        message = 'Erro interno do servidor (500). Tente novamente mais tarde.';
      } else if (response.statusCode == 502 || response.statusCode == 503) {
        message = 'Servidor temporariamente indisponível (${response.statusCode}).';
      } else {
        message = 'Erro de comunicação com o servidor (${response.statusCode}).';
      }
    }
    throw ApiException(response.statusCode, message, body);
  }
}
