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
  List<CategoryModel> _allCategories = [];
  List<CategoryModel> _filteredCategories = [];
  final _searchCtrl = TextEditingController();
  int? _myUserId;
  bool _myTurn = false;
  bool _picking = false;
  int _picksDone = 0;
  int _totalPicks = 6;
  String _statusMessage = 'Préparation de la sélection des catégories...';
  List<String> _pickedNames = [];
  StreamSubscription<Map<String, dynamic>>? _sub;

  @override
  void initState() {
    super.initState();
    _loadUserAndCategories();
    _listenWs();
  }

  Future<void> _loadUserAndCategories() async {
    final api = context.read<ApiService>();
    try {
      final profile = await api.getProfile();
      final user = profile['user'] as Map<String, dynamic>?;
      if (user != null && mounted) {
        setState(() {
          _myUserId = user['id'] as int?;
        });
      }
    } catch (_) {}

    try {
      final rows = await api.getCategories();
      if (mounted) {
        final list = rows.map(CategoryModel.fromJson).toList();
        setState(() {
          _allCategories = list;
          _filteredCategories = list;
        });
      }
    } catch (_) {}
  }

  void _filterCategories(String query) {
    if (query.trim().isEmpty) {
      setState(() => _filteredCategories = _allCategories);
    } else {
      final q = query.toLowerCase();
      setState(() {
        _filteredCategories = _allCategories.where((c) {
          return c.name.toLowerCase().contains(q) ||
              (c.parentName != null && c.parentName!.toLowerCase().contains(q)) ||
              (c.description != null && c.description!.toLowerCase().contains(q));
        }).toList();
      });
    }
  }

  void _listenWs() {
    final ws = context.read<WebSocketService>();
    _sub = ws.messages.listen((msg) {
      if (!mounted) return;
      final type = msg['type'];
      switch (type) {
        case 'category_selection_start':
          final currentPicker = msg['current_picker'] as int?;
          final isTurn = msg['your_turn'] == true ||
              (_myUserId != null && currentPicker == _myUserId);

          if (msg['categories'] != null && msg['categories'] is List) {
            final list = (msg['categories'] as List)
                .map((c) => CategoryModel.fromJson(c as Map<String, dynamic>))
                .toList();
            if (list.isNotEmpty) {
              setState(() {
                _allCategories = list;
                _filteredCategories = list;
              });
            }
          }

          setState(() {
            _myTurn = isTurn;
            _picking = false;
            _picksDone = (msg['picks_done'] as int?) ?? 0;
            _totalPicks = (msg['total_picks'] as int?) ?? 6;
            _statusMessage = _myTurn
                ? 'C\'est votre tour de choisir une catégorie !'
                : 'L\'adversaire choisit une catégorie...';
          });
          break;

        case 'category_picked':
          final currentPicker = msg['current_picker'] as int?;
          final isTurn = msg['your_turn'] == true ||
              msg['next_is_me'] == true ||
              (_myUserId != null && currentPicker == _myUserId);

          final catObj = msg['category'] as Map<String, dynamic>?;
          if (catObj != null && catObj['name'] != null) {
            _pickedNames.add(catObj['name'] as String);
          }

          setState(() {
            _picking = false;
            _picksDone = (msg['picks_done'] as int?) ?? (_picksDone + 1);
            _totalPicks = (msg['total_picks'] as int?) ?? _totalPicks;
            _myTurn = isTurn;
            _statusMessage = _myTurn
                ? 'À votre tour de choisir !'
                : 'L\'adversaire choisit une catégorie...';
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
  }

  void _pickCategory(int catId) {
    if (!_myTurn || _picking) return;
    setState(() {
      _picking = true;
      _statusMessage = 'Sélection en cours d\'envoi...';
    });
    context.read<WebSocketService>().pickCategory(widget.roomCode, catId);
  }

  @override
  void dispose() {
    _sub?.cancel();
    _searchCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Salle ${widget.roomCode} — Tirage'),
        automaticallyImplyLeading: false,
      ),
      body: Column(
        children: [
          // Banner Status
          AnimatedContainer(
            duration: const Duration(milliseconds: 300),
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            decoration: BoxDecoration(
              color: _myTurn ? const Color(0xFF6D28D9).withValues(alpha: 0.3) : const Color(0xFF1E1B4B),
              border: Border(
                bottom: BorderSide(
                  color: _myTurn ? Colors.amberAccent : Colors.white12,
                  width: _myTurn ? 2 : 1,
                ),
              ),
            ),
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    FaIcon(
                      _myTurn ? FontAwesomeIcons.handPointRight : FontAwesomeIcons.hourglassHalf,
                      color: _myTurn ? Colors.amberAccent : Colors.white70,
                      size: 18,
                    ),
                    const SizedBox(width: 10),
                    Flexible(
                      child: Text(
                        _statusMessage,
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: _myTurn ? Colors.amberAccent : Colors.white,
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
                    color: _myTurn ? Colors.amberAccent : const Color(0xFF6D28D9),
                    minHeight: 8,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  '$_picksDone / $_totalPicks catégories choisies (3 chacun)',
                  style: const TextStyle(color: Colors.white54, fontSize: 12),
                ),
              ],
            ),
          ),

          // Search Field
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: TextField(
              controller: _searchCtrl,
              onChanged: _filterCategories,
              decoration: InputDecoration(
                hintText: 'Rechercher une catégorie…',
                hintStyle: const TextStyle(color: Colors.white38, fontSize: 14),
                prefixIcon: const Padding(
                  padding: EdgeInsets.all(12),
                  child: FaIcon(FontAwesomeIcons.magnifyingGlass, size: 14, color: Colors.white54),
                ),
                suffixIcon: _searchCtrl.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear, size: 18),
                        onPressed: () {
                          _searchCtrl.clear();
                          _filterCategories('');
                        },
                      )
                    : null,
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                fillColor: const Color(0xFF1E1B4B),
                filled: true,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
              ),
            ),
          ),

          // Grid of Categories
          Expanded(
            child: _filteredCategories.isEmpty
                ? const Center(
                    child: Text('Aucune catégorie trouvée.', style: TextStyle(color: Colors.white54)),
                  )
                : GridView.builder(
                    padding: const EdgeInsets.all(16),
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      childAspectRatio: 1.3,
                      crossAxisSpacing: 10,
                      mainAxisSpacing: 10,
                    ),
                    itemCount: _filteredCategories.length,
                    itemBuilder: (ctx, i) {
                      final cat = _filteredCategories[i];
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

  static IconData getCategoryIcon(String slug) {
    switch (slug) {
      case 'histoire':
        return FontAwesomeIcons.landmark;
      case 'geographie':
        return FontAwesomeIcons.earthEurope;
      case 'sciences':
      case 'sciences-nature':
        return FontAwesomeIcons.flask;
      case 'cinema':
        return FontAwesomeIcons.film;
      case 'informatique':
        return FontAwesomeIcons.code;
      case 'litterature':
        return FontAwesomeIcons.bookOpen;
      case 'musique':
        return FontAwesomeIcons.music;
      case 'sport':
        return FontAwesomeIcons.futbol;
      case 'mythologie':
        return FontAwesomeIcons.shieldHalved;
      case 'art-peinture':
        return FontAwesomeIcons.palette;
      default:
        return FontAwesomeIcons.layerGroup;
    }
  }

  @override
  Widget build(BuildContext context) {
    final color = _colors[category.id % _colors.length];
    return AnimatedOpacity(
      opacity: enabled ? 1.0 : 0.45,
      duration: const Duration(milliseconds: 200),
      child: Card(
        color: enabled ? color.withValues(alpha: 0.22) : const Color(0xFF1E1B4B),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(
            color: enabled ? color : Colors.white12,
            width: enabled ? 2 : 1,
          ),
        ),
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: enabled ? onTap : null,
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                FaIcon(getCategoryIcon(category.slug), color: color, size: 24),
                const SizedBox(height: 8),
                Text(
                  category.name,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: enabled ? Colors.white : Colors.white60,
                    fontSize: 13,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                if (category.parentName != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(
                      category.parentName!,
                      style: const TextStyle(color: Colors.white38, fontSize: 10),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
