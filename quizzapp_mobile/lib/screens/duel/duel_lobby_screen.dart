import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../services/api_service.dart';
import '../../services/websocket_service.dart';
import '../../widgets/error_box.dart';

/// Duel lobby: create a room or join by code.
class DuelLobbyScreen extends StatefulWidget {
  const DuelLobbyScreen({super.key});

  @override
  State<DuelLobbyScreen> createState() => _DuelLobbyScreenState();
}

class _DuelLobbyScreenState extends State<DuelLobbyScreen> {
  final _codeCtrl = TextEditingController();
  bool _connecting = false;
  String? _error;
  String? _waitingCode;

  @override
  void dispose() {
    _codeCtrl.dispose();
    super.dispose();
  }

  Future<void> _connectWs() async {
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
    } catch (e) {
      setState(() {
        _error = 'Connexion au serveur de duel échouée.';
        _connecting = false;
      });
      return;
    }
    setState(() => _connecting = false);
  }

  Future<void> _createRoom() async {
    await _connectWs();
    if (_error != null || !mounted) return;

    final ws = context.read<WebSocketService>();

    ws.messages.firstWhere((m) => m['type'] == 'room_created').then((msg) {
      if (!mounted) return;
      final code = msg['code'] as String;
      setState(() => _waitingCode = code);

      ws.messages
          .firstWhere((m) => m['type'] == 'player_joined' || m['type'] == 'category_selection_start')
          .then((_) {
        if (mounted) context.go('/duel/pick/$code');
      });
    });

    ws.createRoom();
  }

  Future<void> _joinRoom() async {
    final code = _codeCtrl.text.trim().toUpperCase();
    if (code.isEmpty) {
      setState(() => _error = 'Veuillez saisir un code de salle.');
      return;
    }

    await _connectWs();
    if (_error != null || !mounted) return;

    final ws = context.read<WebSocketService>();

    ws.messages
        .firstWhere((m) => m['type'] == 'room_joined' || m['type'] == 'error' || m['type'] == 'category_selection_start')
        .then((msg) {
      if (!mounted) return;
      if (msg['type'] == 'error') {
        setState(() => _error = msg['message'] as String? ?? 'Erreur lors de l\'accès à la salle.');
      } else {
        context.go('/duel/pick/$code');
      }
    });

    ws.joinRoom(code);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('⚔️ Duel en Direct'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go('/home'),
        ),
      ),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              _ActionCard(
                icon: Icons.add_circle_outline_rounded,
                title: 'Créer une salle de duel',
                subtitle: 'Générez un code et invitez un ami',
                color: const Color(0xFF6D28D9),
                onTap: _connecting ? null : _createRoom,
                loading: _connecting && _waitingCode == null,
              ),
              if (_waitingCode != null) ...[
                const SizedBox(height: 24),
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: const Color(0xFF1E1B4B),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: const Color(0xFF6D28D9), width: 2),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFF6D28D9).withOpacity(0.3),
                        blurRadius: 15,
                      ),
                    ],
                  ),
                  child: Column(
                    children: [
                      const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          SizedBox(
                            width: 14,
                            height: 14,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.amberAccent),
                          ),
                          SizedBox(width: 10),
                          Text('En attente de l\'adversaire...', style: TextStyle(color: Colors.white70)),
                        ],
                      ),
                      const SizedBox(height: 16),
                      Text(
                        _waitingCode!,
                        style: const TextStyle(
                          fontSize: 42,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 10,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Donnez ce code à votre adversaire pour démarrer',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: Colors.white54, fontSize: 12),
                      ),
                    ],
                  ),
                ),
              ],
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
                  prefixIcon: Icon(Icons.pin, color: Colors.white54),
                ),
              ),
              const SizedBox(height: 12),
              _ActionCard(
                icon: Icons.login_rounded,
                title: 'Rejoindre la partie',
                subtitle: 'Entrez dans la salle créée par votre ami',
                color: const Color(0xFF4F46E5),
                onTap: _connecting ? null : _joinRoom,
                loading: _connecting && _waitingCode == null,
              ),
              if (_error != null) ...[
                const SizedBox(height: 18),
                ErrorBox(message: _error!),
              ],
            ],
          ),
        ),
      ),
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
      color: color.withOpacity(0.18),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: color.withOpacity(0.5)),
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
                  : Icon(icon, color: color, size: 36),
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
