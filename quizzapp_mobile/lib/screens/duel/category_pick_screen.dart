import 'dart:async';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../services/api_service.dart';
import '../../services/websocket_service.dart';
import '../../models/models.dart';

/// Écran de sélection alternée des catégories (3 choix chacun = 6 catégories au total)
class CategoryPickScreen extends StatefulWidget {
  final String roomCode;
  const CategoryPickScreen({super.key, required this.roomCode});

  @override
  State<CategoryPickScreen> createState() => _CategoryPickScreenState();
}

class _CategoryPickScreenState extends State<CategoryPickScreen> {
  List<CategoryModel> _categories = [];
  bool _myTurn = false;
  bool _picking = false;
  int _picksDone = 0;
  int _totalPicks = 6;
  String _statusMessage = 'Préparation de la sélection des catégories...';
  StreamSubscription<Map<String, dynamic>>? _sub;

  @override
  void initState() {
    super.initState();
    _loadCategories();
    _listenWs();
  }

  Future<void> _loadCategories() async {
    try {
      final rows = await context.read<ApiService>().getCategories();
      if (mounted) {
        setState(() {
          _categories = rows
              .map(CategoryModel.fromJson)
              .where((c) => c.isSubcategory)
              .toList();
          if (_categories.isEmpty) {
            _categories = rows.map(CategoryModel.fromJson).toList();
          }
        });
      }
    } catch (_) {}
  }

  void _listenWs() {
    final ws = context.read<WebSocketService>();
    _sub = ws.messages.listen((msg) {
      if (!mounted) return;
      switch (msg['type']) {
        case 'category_selection_start':
          setState(() {
            _myTurn = msg['your_turn'] == true || msg['your_turn'] == 'true';
            _picksDone = (msg['picks_done'] as int?) ?? 0;
            _totalPicks = (msg['total_picks'] as int?) ?? 6;
            _statusMessage = _myTurn
                ? 'C\'est votre tour de choisir une catégorie !'
                : 'L\'adversaire est en train de choisir...';
          });
          break;
        case 'category_picked':
          setState(() {
            _picking = false;
            _picksDone = (msg['picks_done'] as int?) ?? (_picksDone + 1);
            _myTurn = msg['next_is_me'] == true || msg['your_turn'] == true;
            _statusMessage = _myTurn
                ? 'À votre tour de choisir !'
                : 'L\'adversaire est en train de choisir...';
          });
          break;
        case 'generating_questions':
          if (mounted) context.go('/duel/generating/${widget.roomCode}');
          break;
        case 'selection_complete':
        case 'new_question':
          if (mounted) context.go('/duel/play/${widget.roomCode}');
          break;
        case 'player_ready':
          setState(() => _statusMessage = 'Joueurs prêts ! Démarrage du tirage...');
          break;
      }
    });

    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<WebSocketService>().setReady();
    });
  }

  void _pickCategory(int catId) {
    if (!_myTurn || _picking) return;
    setState(() => _picking = true);
    context.read<WebSocketService>().pickCategory(catId);
  }

  @override
  void dispose() {
    _sub?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Salle ${widget.roomCode} — Sélection'),
        automaticallyImplyLeading: false,
      ),
      body: Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(18),
            decoration: const BoxDecoration(
              color: Color(0xFF1E1B4B),
              border: Border(bottom: BorderSide(color: Colors.white12)),
            ),
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    FaIcon(
                      _myTurn ? FontAwesomeIcons.handPointRight : FontAwesomeIcons.hourglassHalf,
                      color: _myTurn ? Colors.amberAccent : Colors.white70,
                      size: 16,
                    ),
                    const SizedBox(width: 8),
                    Flexible(
                      child: Text(
                        _statusMessage,
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.bold,
                          color: _myTurn ? Colors.amberAccent : Colors.white70,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                ClipRRect(
                  borderRadius: BorderRadius.circular(6),
                  child: LinearProgressIndicator(
                    value: _totalPicks > 0 ? (_picksDone / _totalPicks).clamp(0.0, 1.0) : 0,
                    backgroundColor: Colors.white12,
                    color: const Color(0xFF6D28D9),
                    minHeight: 8,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  '$_picksDone / $_totalPicks catégories choisies (3 chacun)',
                  style: const TextStyle(color: Colors.white38, fontSize: 12),
                ),
              ],
            ),
          ),
          Expanded(
            child: _categories.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : GridView.builder(
                    padding: const EdgeInsets.all(16),
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      childAspectRatio: 1.35,
                      crossAxisSpacing: 10,
                      mainAxisSpacing: 10,
                    ),
                    itemCount: _categories.length,
                    itemBuilder: (ctx, i) {
                      final cat = _categories[i];
                      return _CategoryTile(
                        category: cat,
                        enabled: _myTurn && !_picking,
                        onTap: () => _pickCategory(cat.id),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}

class _CategoryTile extends StatelessWidget {
  final CategoryModel category;
  final bool enabled;
  final VoidCallback onTap;

  const _CategoryTile({
    required this.category,
    required this.enabled,
    required this.onTap,
  });

  static const _colors = [
    Color(0xFF6D28D9),
    Color(0xFF4F46E5),
    Color(0xFF0EA5E9),
    Color(0xFF10B981),
    Color(0xFFF59E0B),
    Color(0xFFEF4444),
    Color(0xFF8B5CF6),
    Color(0xFF14B8A6),
  ];

  @override
  Widget build(BuildContext context) {
    final color = _colors[category.id % _colors.length];
    return AnimatedOpacity(
      opacity: enabled ? 1.0 : 0.4,
      duration: const Duration(milliseconds: 200),
      child: Card(
        color: color.withOpacity(0.18),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(14),
          side: BorderSide(
            color: enabled ? color : Colors.white12,
            width: enabled ? 2 : 1,
          ),
        ),
        child: InkWell(
          borderRadius: BorderRadius.circular(14),
          onTap: enabled ? onTap : null,
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                FaIcon(FontAwesomeIcons.layerGroup, color: color, size: 24),
                const SizedBox(height: 6),
                Text(
                  category.name,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: enabled ? Colors.white : Colors.white54,
                    fontSize: 13,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                if (category.parentName != null)
                  Text(
                    category.parentName!,
                    style: const TextStyle(color: Colors.white38, fontSize: 10),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
