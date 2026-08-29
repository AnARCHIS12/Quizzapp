class AppConfig {
  static const String defaultServerUrl = 'https://quizzapp.revlibertaire.com';
  static const String appName = 'QuizzApp';
  static const String version = '1.0.0';
  static const String tokenKey = 'auth_token';
  static const String serverUrlKey = 'server_url';

  static String wsUrl(String serverUrl) {
    final uri = Uri.parse(serverUrl);
    final scheme = uri.scheme == 'https' ? 'wss' : 'ws';
    final port = (uri.port == 80 || uri.port == 443 || uri.port == 0) ? '' : ':${uri.port}';
    return '$scheme://${uri.host}$port/ws';
  }
}
