import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:web_socket_channel/web_socket_channel.dart';
import '../config/app_config.dart';

class WebSocketService {
  WebSocketChannel? _channel;
  final _controller = StreamController<Map<String, dynamic>>.broadcast();

  Stream<Map<String, dynamic>> get messages => _controller.stream;
  bool get isConnected => _channel != null;

  Future<void> connect(String serverUrl, String jwtToken) async {
    disconnect();
    final wsUrl = AppConfig.wsUrl(serverUrl);
    final uri = Uri.parse('$wsUrl?token=${Uri.encodeComponent(jwtToken)}');
    debugPrint('[WebSocket] Connecting to $wsUrl');

    try {
      _channel = WebSocketChannel.connect(uri);
      await _channel!.ready;
      debugPrint('[WebSocket] Connected successfully');

      _channel!.stream.listen(
        (raw) {
          try {
            final data = json.decode(raw as String) as Map<String, dynamic>;
            debugPrint('[WebSocket IN] ${data['type']}');
            _controller.add(data);
          } catch (e) {
            debugPrint('[WebSocket] JSON decode error: $e');
          }
        },
        onDone: () {
          debugPrint('[WebSocket] Connection closed');
          _controller.add({'type': 'disconnected'});
          _channel = null;
        },
        onError: (e) {
          debugPrint('[WebSocket] Error: $e');
          _controller.add({'type': 'error', 'message': e.toString()});
          _channel = null;
        },
      );
    } catch (e) {
      debugPrint('[WebSocket] Connect error: $e');
      _channel = null;
      rethrow;
    }
  }

  void disconnect() {
    _channel?.sink.close();
    _channel = null;
  }

  void _send(Map<String, dynamic> payload) {
    if (_channel != null) {
      debugPrint('[WebSocket OUT] ${payload['action']}');
      _channel!.sink.add(json.encode(payload));
    } else {
      debugPrint('[WebSocket OUT] Cannot send, channel is null');
    }
  }

  void createRoom() => _send({'action': 'create'});

  void joinRoom(String code) =>
      _send({'action': 'join', 'room_code': code, 'code': code});

  void setReady(String roomCode) =>
      _send({'action': 'ready', 'room_code': roomCode});

  void pickCategory(String roomCode, int categoryId) =>
      _send({'action': 'pick_category', 'room_code': roomCode, 'category_id': categoryId});

  void submitAnswer({
    required String roomCode,
    required int questionIndex,
    required int? answerId,
    required double timeSpentSeconds,
  }) =>
      _send({
        'action': 'submit_answer',
        'room_code': roomCode,
        'question_index': questionIndex,
        'answer': answerId ?? -1,
        'time_spent': timeSpentSeconds,
      });

  void playAgain(String roomCode) =>
      _send({'action': 'play_again', 'room_code': roomCode});

  void dispose() {
    disconnect();
    _controller.close();
  }
}
