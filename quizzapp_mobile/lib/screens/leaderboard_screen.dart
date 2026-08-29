import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../services/api_service.dart';

class LeaderboardScreen extends StatefulWidget {
  const LeaderboardScreen({super.key});

  @override
  State<LeaderboardScreen> createState() => _LeaderboardScreenState();
}

class _LeaderboardScreenState extends State<LeaderboardScreen> {
  List<Map<String, dynamic>> _leaderboard = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final rows = await context.read<ApiService>().getLeaderboard();
      if (mounted) {
        setState(() {
          _leaderboard = rows;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            FaIcon(FontAwesomeIcons.trophy, color: Colors.amberAccent, size: 18),
            SizedBox(width: 8),
            Text('Classement Général'),
          ],
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go('/home'),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _leaderboard.isEmpty
              ? const Center(child: Text('Aucun joueur classé pour le moment.', style: TextStyle(color: Colors.white54)))
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _leaderboard.length,
                    itemBuilder: (ctx, i) {
                      final p = _leaderboard[i];
                      final rank = i + 1;
                      Color? rankColor;
                      if (rank == 1) rankColor = Colors.amberAccent;
                      if (rank == 2) rankColor = const Color(0xFFCBD5E1); // Silver
                      if (rank == 3) rankColor = const Color(0xFFD97706); // Bronze

                      return Card(
                        margin: const EdgeInsets.only(bottom: 10),
                        color: rank <= 3 ? const Color(0xFF6D28D9).withValues(alpha: 0.15) : const Color(0xFF1E1B4B),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                          side: BorderSide(
                            color: rank <= 3 ? (rankColor ?? Colors.white24) : Colors.white12,
                            width: rank <= 3 ? 1.5 : 1,
                          ),
                        ),
                        child: ListTile(
                          leading: Container(
                            width: 36,
                            height: 36,
                            alignment: Alignment.center,
                            decoration: BoxDecoration(
                              color: rankColor != null ? rankColor.withValues(alpha: 0.2) : Colors.white10,
                              shape: BoxShape.circle,
                            ),
                            child: Text(
                              '$rank',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: rankColor ?? Colors.white70,
                                fontSize: 15,
                              ),
                            ),
                          ),
                          title: Text(
                            p['username'] as String? ?? 'Joueur',
                            style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white),
                          ),
                          subtitle: Text(
                            'Niveau ${p['level']} · ${p['total_played'] ?? 0} parties',
                            style: const TextStyle(color: Colors.white54, fontSize: 12),
                          ),
                          trailing: Text(
                            '${p['xp']} XP',
                            style: const TextStyle(
                              color: Colors.amberAccent,
                              fontWeight: FontWeight.bold,
                              fontSize: 15,
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
    );
  }
}
