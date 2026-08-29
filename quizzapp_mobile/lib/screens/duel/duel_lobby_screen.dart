import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../services/api_service.dart';
import '../../services/websocket_service.dart';
import '../../widgets/error_box.dart';

/// Duel lobby: create a room, join by code, show players in room and ready button.
class DuelLobbyScreen extends StatefulWidget {
  const DuelLobbyScreen({super.key});

  @override
  State<DuelLobbyScreen> createState() => _DuelLobbyScreenState();
}

class _DuelLobbyScreenState extends State<DuelLobbyScreen> {
  final _codeCtrl = TextEditingController();
  bool _connecting = false;
  String? _error;
  String? _roomCode;
  List<Map<String, dynamic>> _players = [];
  bool _isMeReady = false;
  int? _myUserId;
  StreamSubscription<Map<String, dynamic>>? _sub;

  @override
  void initState() {
    super.initState();
    _loadUser();
    _listenWs();
  }

  Future<void> _loadUser() async {
    try {
      final profile = await context.read<ApiService>().getProfile();
      final u = profile['user'] as Map<String, dynamic>?;
      if (u != null && mounted) {
        setState(() => _myUserId = u['id'] as int?);
      }
    } catch (_) {}
  }

  void _listenWs() {
    final ws = context.read<WebSocketService>();
    _sub = ws.messages.listen((msg) {
      if (!mounted) return;
      final type = msg['type'];
      switch (type) {
        case 'room_created':
          final code = (msg['room_code'] ?? msg['code'] ?? '') as String;
          final players = List<Map<String, dynamic>>.from(msg['players'] as List? ?? []);
          setState(() {
            _roomCode = code;
            _players = players;
            _connecting = false;
            _error = null;
            _isMeReady = false;
          });
          break;

        case 'room_joined':
          final code = (msg['room_code'] ?? msg['code'] ?? _codeCtrl.text.trim().toUpperCase()) as String;
          final players = List<Map<String, dynamic>>.from(msg['players'] as List? ?? []);
          setState(() {
            _roomCode = code;
            _players = players;
            _connecting = false;
            _error = null;
            _isMeReady = false;
          });
          break;

        case 'player_joined':
        case 'player_left':
        case 'player_ready':
          if (msg['players'] != null) {
            final players = List<Map<String, dynamic>>.from(msg['players'] as List);
            setState(() {
              _players = players;
              if (_myUserId != null) {
                final me = players.firstWhere(
                  (p) => p['user_id'] == _myUserId,
                  orElse: () => <String, dynamic>{},
                );
                if (me.isNotEmpty) {
                  _isMeReady = me['is_ready'] == true || me['is_ready'] == 1 || me['is_ready'] == '1';
                }
              }
            });
          }
          break;

        case 'category_selection_start':
          if (_roomCode != null) {
            context.go('/duel/pick/$_roomCode');
          }
          break;

        case 'error':
          setState(() {
            _error = msg['message'] as String? ?? 'Erreur de connexion au salon.';
            _connecting = false;
          });
          break;
      }
    });
  }

  @override
  void dispose() {
    _sub?.cancel();
    _codeCtrl.dispose();
    super.dispose();
  }

  Future<bool> _connectWs() async {
    final api = context.read<ApiService>();
    final ws = context.read<WebSocketService>();
    setState(() {
      _connecting = true;
      _error = null;
    });
    try {
      final serverUrl = await api.getServerUrl();
      final token = await api.getWsToken();
      await ws.connect(serverUrl, token);
      return true;
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Impossible de contacter le serveur WebSocket.';
          _connecting = false;
        });
      }
      return false;
    }
  }

  Future<void> _createRoom() async {
    final connected = await _connectWs();
    if (!connected || !mounted) return;

    final ws = context.read<WebSocketService>();
    ws.createRoom();
  }

  Future<void> _joinRoom() async {
    final code = _codeCtrl.text.trim().toUpperCase();
    if (code.isEmpty) {
      setState(() => _error = 'Veuillez saisir un code de salle.');
      return;
    }

    final connected = await _connectWs();
    if (!connected || !mounted) return;

    final ws = context.read<WebSocketService>();
    ws.joinRoom(code);
  }

  void _markReady() {
    if (_roomCode == null || _isMeReady) return;
    setState(() => _isMeReady = true);
    context.read<WebSocketService>().setReady(_roomCode!);
  }

  void _leaveRoom() {
    context.read<WebSocketService>().disconnect();
    setState(() {
      _roomCode = null;
      _players = [];
      _isMeReady = false;
      _error = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            FaIcon(FontAwesomeIcons.gamepad, size: 18, color: Colors.white),
            SizedBox(width: 8),
            Text('Salon de Duel'),
          ],
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () {
            if (_roomCode != null) {
              _leaveRoom();
            }
            context.go('/home');
          },
        ),
      ),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: _roomCode != null ? _buildWaitingRoom() : _buildCreateOrJoin(),
        ),
      ),
    );
  }

  Widget _buildCreateOrJoin() {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        _ActionCard(
          icon: FontAwesomeIcons.circlePlus,
          title: 'Créer une salle de duel',
          subtitle: 'Générez un code et invitez un ami',
          color: const Color(0xFF6D28D9),
          onTap: _connecting ? null : _createRoom,
          loading: _connecting && _roomCode == null,
        ),
        const SizedBox(height: 20),
        const Row(
          children: [
            Expanded(child: Divider(color: Colors.white24)),
            Padding(
              padding: EdgeInsets.symmetric(horizontal: 14),
              child: Text('OU REJOINDRE', style: TextStyle(color: Colors.white38, fontSize: 11, letterSpacing: 1)),
            ),
            Expanded(child: Divider(color: Colors.white24)),
          ],
        ),
        const SizedBox(height: 20),
        TextField(
          controller: _codeCtrl,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 22,
            letterSpacing: 6,
            fontWeight: FontWeight.bold,
          ),
          textAlign: TextAlign.center,
          textCapitalization: TextCapitalization.characters,
          maxLength: 6,
          decoration: const InputDecoration(
            hintText: 'CODE SALLE',
            hintStyle: TextStyle(color: Colors.white24, letterSpacing: 4, fontSize: 16),
            counterText: '',
            prefixIcon: Padding(
              padding: EdgeInsets.all(14),
              child: FaIcon(FontAwesomeIcons.hashtag, size: 16, color: Colors.white54),
            ),
          ),
        ),
        const SizedBox(height: 12),
        _ActionCard(
          icon: FontAwesomeIcons.arrowRightToBracket,
          title: 'Rejoindre la partie',
          subtitle: 'Entrez dans la salle créée par votre ami',
          color: const Color(0xFF4F46E5),
          onTap: _connecting ? null : _joinRoom,
          loading: _connecting && _roomCode == null,
        ),
        if (_error != null) ...[
          const SizedBox(height: 18),
          ErrorBox(message: _error!),
        ],
      ],
    );
  }

  Widget _buildWaitingRoom() {
    final canStart = _players.length >= 2;

    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Room Code Container
        Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: const Color(0xFF1E1B4B),
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: const Color(0xFF6D28D9), width: 2),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF6D28D9).withValues(alpha: 0.3),
                blurRadius: 20,
              ),
            ],
          ),
          child: Column(
            children: [
              const Text('CODE DE LA SALLE', style: TextStyle(color: Colors.white54, fontSize: 12, letterSpacing: 2)),
              const SizedBox(height: 10),
              GestureDetector(
                onTap: () {
                  Clipboard.setData(ClipboardData(text: _roomCode!));
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Code de salle copié !')),
                  );
                },
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      _roomCode!,
                      style: const TextStyle(
                        fontSize: 42,
                        fontWeight: FontWeight.bold,
                        letterSpacing: 10,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(width: 10),
                    const FaIcon(FontAwesomeIcons.copy, size: 18, color: Colors.amberAccent),
                  ],
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                'Partagez ce code à votre adversaire pour qu\'il rejoigne',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.white54, fontSize: 12),
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),

        // Players List Card
        Container(
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            color: const Color(0xFF1E1B4B),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: Colors.white12),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Row(
                    children: [
                      FaIcon(FontAwesomeIcons.users, size: 14, color: Colors.white70),
                      SizedBox(width: 8),
                      Text('Joueurs connectés', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
                    ],
                  ),
                  Text('${_players.length} / 2', style: const TextStyle(color: Colors.white54, fontSize: 12)),
                ],
              ),
              const SizedBox(height: 14),
              if (_players.isEmpty)
                const Center(child: Text('En attente de connexion…', style: TextStyle(color: Colors.white38)))
              else
                ..._players.map((p) {
                  final isReady = p['is_ready'] == true || p['is_ready'] == 1 || p['is_ready'] == '1';
                  final username = p['username'] as String? ?? 'Joueur';
                  final isMe = p['user_id'] == _myUserId;

                  return Container(
                    margin: const EdgeInsets.only(bottom: 8),
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    decoration: BoxDecoration(
                      color: isReady ? Colors.green.withValues(alpha: 0.15) : Colors.white.withValues(alpha: 0.05),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: isReady ? Colors.green.withValues(alpha: 0.4) : Colors.white10),
                    ),
                    child: Row(
                      children: [
                        CircleAvatar(
                          radius: 16,
                          backgroundColor: const Color(0xFF6D28D9),
                          child: Text(
                            username.isNotEmpty ? username[0].toUpperCase() : '?',
                            style: const TextStyle(fontSize: 13, color: Colors.white, fontWeight: FontWeight.bold),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            isMe ? '$username (Vous)' : username,
                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: isReady ? Colors.green : Colors.white12,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              FaIcon(
                                isReady ? FontAwesomeIcons.check : FontAwesomeIcons.hourglassHalf,
                                size: 10,
                                color: isReady ? Colors.white : Colors.white70,
                              ),
                              const SizedBox(width: 6),
                              Text(
                                isReady ? 'Prêt ✓' : 'En attente',
                                style: TextStyle(
                                  color: isReady ? Colors.white : Colors.white70,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 11,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  );
                }),
            ],
          ),
        ),
        const SizedBox(height: 24),

        // Ready Button
        ElevatedButton.icon(
          style: ElevatedButton.styleFrom(
            backgroundColor: _isMeReady ? Colors.green : const Color(0xFF6D28D9),
            padding: const EdgeInsets.symmetric(vertical: 16),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          ),
          onPressed: _isMeReady ? null : _markReady,
          icon: FaIcon(
            _isMeReady ? FontAwesomeIcons.circleCheck : FontAwesomeIcons.bolt,
            size: 16,
            color: Colors.white,
          ),
          label: Text(
            _isMeReady
                ? 'Prêt — En attente des adversaires'
                : canStart
                    ? 'Signaler prêt !'
                    : 'Prêt (en attente du 2e joueur)',
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
          ),
        ),
        const SizedBox(height: 12),

        TextButton.icon(
          onPressed: _leaveRoom,
          icon: const FaIcon(FontAwesomeIcons.arrowRightFromBracket, size: 14, color: Colors.white54),
          label: const Text('Quitter le salon', style: TextStyle(color: Colors.white54)),
        ),

        if (_error != null) ...[
          const SizedBox(height: 16),
          ErrorBox(message: _error!),
        ],
      ],
    );
  }
}

class _ActionCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final Color color;
  final VoidCallback? onTap;
  final bool loading;

  const _ActionCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.color,
    this.onTap,
    this.loading = false,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      color: color.withValues(alpha: 0.18),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: color.withValues(alpha: 0.5)),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Row(
            children: [
              loading
                  ? const SizedBox(
                      width: 32,
                      height: 32,
                      child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                    )
                  : FaIcon(icon, color: color, size: 30),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      style: const TextStyle(color: Colors.white54, fontSize: 13),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
