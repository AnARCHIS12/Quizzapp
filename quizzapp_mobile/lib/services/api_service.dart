import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

class ApiService {
  static const _storage = FlutterSecureStorage();

  Future<String> get _serverUrl async =>
      await _storage.read(key: 'server_url') ?? 'https://quizzapp.revlibertaire.com';

  Future<String?> get _token async => await _storage.read(key: 'auth_token');

  Future<Map<String, String>> get _headers async {
    final token = await _token;
    return {
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  Future<Uri> _uri(String path) async => Uri.parse('${await _serverUrl}$path');

  Map<String, dynamic> _parse(http.Response res) {
    final body = json.decode(res.body) as Map<String, dynamic>;
    if (res.statusCode >= 400) {
      throw ApiException(body['error'] as String? ?? 'Erreur (${res.statusCode})', res.statusCode);
    }
    return body;
  }

  Future<void> saveServerUrl(String url) async {
    await _storage.write(key: 'server_url', value: url.trimRight().replaceAll(RegExp(r'/+$'), ''));
  }

  Future<String> getServerUrl() async => _serverUrl;

  Future<Map<String, dynamic>> ping(String serverUrl) async {
    final url = serverUrl.trimRight().replaceAll(RegExp(r'/+$'), '');
    final res = await http.get(Uri.parse('$url/api/ping')).timeout(const Duration(seconds: 8));
    return _parse(res);
  }

  Future<Map<String, dynamic>> login(String login, String password) async {
    final res = await http.post(await _uri('/api/auth/login'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'login': login, 'password': password}));
    final data = _parse(res);
    await _storage.write(key: 'auth_token', value: data['token'] as String);
    return data;
  }

  Future<Map<String, dynamic>> register(String username, String email, String password) async {
    final res = await http.post(await _uri('/api/auth/register'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'username': username, 'email': email, 'password': password}));
    final data = _parse(res);
    await _storage.write(key: 'auth_token', value: data['token'] as String);
    return data;
  }

  Future<void> logout() async => _storage.delete(key: 'auth_token');

  Future<bool> hasToken() async {
    final t = await _token;
    return t != null && t.isNotEmpty;
  }

  Future<List<Map<String, dynamic>>> getCategories() async {
    final res = await http.get(await _uri('/api/categories'), headers: await _headers);
    final data = _parse(res);
    return List<Map<String, dynamic>>.from(data['categories'] as List);
  }

  Future<Map<String, dynamic>> getProfile() async {
    final res = await http.get(await _uri('/api/profile'), headers: await _headers);
    return _parse(res);
  }

  Future<String> getWsToken() async {
    final res = await http.get(await _uri('/api/ws-token'), headers: await _headers);
    return (_parse(res))['token'] as String;
  }
}

class ApiException implements Exception {
  final String message;
  final int statusCode;
  const ApiException(this.message, this.statusCode);
  @override
  String toString() => message;
}
