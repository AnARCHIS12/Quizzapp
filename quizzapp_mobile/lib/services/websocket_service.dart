import 'dart:async';
import 'dart:convert';
import 'package:web_socket_channel/web_socket_channel.dart';
import '../config/app_config.dart';

class WebSocketService {
  WebSocketChannel? _channel;
  final _controller = StreamController<Map<String, dynamic>>.broadcast();

  Stream<Map<String, dynamic>> get messages => _controller.stream;
  bool get isConnected => _channel != null;

  Future<void> connect(String serverUrl, String jwtToken) async {
    final wsUrl = AppConfig.wsUrl(serverUrl);
    final uri = Uri.parse('$wsUrl?token=${Uri.encodeComponent(jwtToken)}');
    _channel = WebSocketChannel.connect(uri);
    _channel!.stream.listen(
      (raw) {
        try { _controller.add(json.decode(raw as String) as Map<String, dynamic>); } catch (_) {}
      },
      onDone: () { _controller.add({'type': 'disconnected'}); _channel = null; },
      onError: (e) { _controller.add({'type': 'error', 'message': e.toString()}); _channel = null; },
    );
  }

  void disconnect() { _channel?.sink.close(); _channel = null; }

  void _send(Map<String, dynamic> payload) {
    if (_channel != null) _channel!.sink.add(json.encode(payload));
  }

  void createRoom() => _send({'action': 'create'});
  void joinRoom(String code) => _send({'action': 'join', 'code': code});
  void setReady() => _send({'action': 'ready'});
  void pickCategory(int categoryId) => _send({'action': 'pick_category', 'category_id': categoryId});
  void submitAnswer({required int questionIndex, required int answerId, required int timeMs}) =>
      _send({'action': 'answer', 'question_index': questionIndex, 'answer_id': answerId, 'time_ms': timeMs});
  void playAgain() => _send({'action': 'play_again'});

  void dispose() { disconnect(); _controller.close(); }
}
