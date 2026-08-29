import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../services/api_service.dart';
import '../models/models.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> with SingleTickerProviderStateMixin {
  Map<String, dynamic>? _data;
  bool _loading = true;
  late TabController _tabController;

  static const List<String> _avatarPresets = [
    'https://api.dicebear.com/7.x/bottts/png?seed=Rocket',
    'https://api.dicebear.com/7.x/bottts/png?seed=Felix',
    'https://api.dicebear.com/7.x/bottts/png?seed=Cosmo',
    'https://api.dicebear.com/7.x/bottts/png?seed=Nova',
    'https://api.dicebear.com/7.x/bottts/png?seed=Astro',
    'https://api.dicebear.com/7.x/bottts/png?seed=Pixel',
    'https://api.dicebear.com/7.x/bottts/png?seed=Cyber',
    'https://api.dicebear.com/7.x/bottts/png?seed=Spark',
    'https://api.dicebear.com/7.x/bottts/png?seed=Shadow',
    'https://api.dicebear.com/7.x/bottts/png?seed=Vortex',
    'https://api.dicebear.com/7.x/bottts/png?seed=Titan',
    'https://api.dicebear.com/7.x/bottts/png?seed=Zenith',
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _load();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final data = await context.read<ApiService>().getProfile();
      if (mounted) {
        setState(() {
          _data = data;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _changeAvatar() async {
    final newAvatar = await showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      backgroundColor: const Color(0xFF1E1B4B),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        final seedCtrl = TextEditingController();
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                left: 20,
                right: 20,
                top: 20,
                bottom: MediaQuery.of(context).viewInsets.bottom + 20,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Choisir un Avatar',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close, color: Colors.white70),
                        onPressed: () => Navigator.of(ctx).pop(),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  const Text(
                    'Sélectionnez un style d\'avatar ou tapez un pseudo pour en générer un unique :',
                    style: TextStyle(color: Colors.white54, fontSize: 13),
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: seedCtrl,
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      hintText: 'Générer via mot-clé (ex: Dragon, Ninja)…',
                      hintStyle: const TextStyle(color: Colors.white38, fontSize: 13),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                      suffixIcon: IconButton(
                        icon: const FaIcon(FontAwesomeIcons.wandMagicSparkles, size: 14, color: Colors.amberAccent),
                        onPressed: () {
                          if (seedCtrl.text.trim().isNotEmpty) {
                            final customUrl = 'https://api.dicebear.com/7.x/bottts/png?seed=${Uri.encodeComponent(seedCtrl.text.trim())}';
                            Navigator.of(ctx).pop(customUrl);
                          }
                        },
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    height: 220,
                    child: GridView.builder(
                      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 4,
                        crossAxisSpacing: 10,
                        mainAxisSpacing: 10,
                      ),
                      itemCount: _avatarPresets.length,
                      itemBuilder: (context, index) {
                        final url = _avatarPresets[index];
                        return InkWell(
                          borderRadius: BorderRadius.circular(16),
                          onTap: () => Navigator.of(ctx).pop(url),
                          child: Container(
                            decoration: BoxDecoration(
                              color: const Color(0xFF0F0E2B),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: const Color(0xFF6D28D9).withValues(alpha: 0.5)),
                            ),
                            padding: const EdgeInsets.all(6),
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(12),
                              child: Image.network(
                                url,
                                fit: BoxFit.cover,
                                errorBuilder: (_, __, ___) => const Center(
                                  child: FaIcon(FontAwesomeIcons.robot, color: Colors.white54),
                                ),
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );

    if (newAvatar != null && newAvatar.isNotEmpty && mounted) {
      try {
        await context.read<ApiService>().updateAvatar(newAvatar);
        _load();
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Avatar mis à jour avec succès !')),
          );
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Impossible de mettre à jour l\'avatar.')),
          );
        }
      }
    }
  }

  Future<void> _logout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1E1B4B),
        title: const Text('Déconnexion', style: TextStyle(color: Colors.white)),
        content: const Text(
          'Voulez-vous vous déconnecter de votre compte ?',
          style: TextStyle(color: Colors.white70),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Annuler', style: TextStyle(color: Colors.white54)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.redAccent),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('Déconnexion'),
          ),
        ],
      ),
    );

    if (confirm == true && mounted) {
      await context.read<ApiService>().logout();
      if (mounted) context.go('/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = _data != null ? UserModel.fromJson(_data!['user'] as Map<String, dynamic>) : null;
    final stats = _data?['stats'] as Map<String, dynamic>? ?? {};
    final achievements = List<Map<String, dynamic>>.from(_data?['achievements'] as List? ?? []);
    final history = List<Map<String, dynamic>>.from(_data?['history'] as List? ?? []);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Mon Profil'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go('/home'),
        ),
        actions: [
          IconButton(
            icon: const FaIcon(FontAwesomeIcons.arrowRightFromBracket, size: 17),
            tooltip: 'Déconnexion',
            onPressed: _logout,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : user == null
              ? const Center(child: Text('Erreur de chargement du profil', style: TextStyle(color: Colors.white54)))
              : Column(
                  children: [
                    // Profile Header Card
                    Container(
                      padding: const EdgeInsets.all(20),
                      margin: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF6D28D9), Color(0xFF1E1B4B)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(24),
                        border: Border.all(color: const Color(0xFF6D28D9).withValues(alpha: 0.5)),
                      ),
                      child: Column(
                        children: [
                          Row(
                            children: [
                              GestureDetector(
                                onTap: _changeAvatar,
                                child: Stack(
                                  children: [
                                    Container(
                                      width: 72,
                                      height: 72,
                                      decoration: BoxDecoration(
                                        shape: BoxShape.circle,
                                        border: Border.all(color: Colors.amberAccent, width: 2.5),
                                        color: const Color(0xFF0F0E2B),
                                      ),
                                      child: ClipOval(
                                        child: (user.avatarUrl != null && user.avatarUrl!.isNotEmpty)
                                            ? Image.network(
                                                user.avatarUrl!,
                                                fit: BoxFit.cover,
                                                errorBuilder: (_, __, ___) => Center(
                                                  child: Text(
                                                    user.username.isNotEmpty ? user.username[0].toUpperCase() : '?',
                                                    style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: Colors.white),
                                                  ),
                                                ),
                                              )
                                            : Center(
                                                child: Text(
                                                  user.username.isNotEmpty ? user.username[0].toUpperCase() : '?',
                                                  style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: Colors.white),
                                                ),
                                              ),
                                      ),
                                    ),
                                    Positioned(
                                      bottom: 0,
                                      right: 0,
                                      child: Container(
                                        padding: const EdgeInsets.all(4),
                                        decoration: const BoxDecoration(
                                          color: Color(0xFF6D28D9),
                                          shape: BoxShape.circle,
                                        ),
                                        child: const FaIcon(FontAwesomeIcons.pen, size: 10, color: Colors.white),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              const SizedBox(width: 16),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      user.username,
                                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      user.email,
                                      style: const TextStyle(color: Colors.white54, fontSize: 12),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    const SizedBox(height: 8),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                                      decoration: BoxDecoration(
                                        color: Colors.amber.withValues(alpha: 0.2),
                                        borderRadius: BorderRadius.circular(12),
                                        border: Border.all(color: Colors.amberAccent.withValues(alpha: 0.5)),
                                      ),
                                      child: Text(
                                        'NIVEAU ${user.level}',
                                        style: const TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold, fontSize: 11),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 16),
                          // XP Progress
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text('Progression XP', style: TextStyle(color: Colors.white70, fontSize: 12)),
                              Text('${user.xp} / ${user.level * 100} XP', style: const TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold, fontSize: 12)),
                            ],
                          ),
                          const SizedBox(height: 6),
                          ClipRRect(
                            borderRadius: BorderRadius.circular(6),
                            child: LinearProgressIndicator(
                              value: (user.xp / (user.level * 100)).clamp(0.0, 1.0),
                              backgroundColor: Colors.white12,
                              color: Colors.amberAccent,
                              minHeight: 7,
                            ),
                          ),
                        ],
                      ),
                    ),

                    // Tabs
                    TabBar(
                      controller: _tabController,
                      indicatorColor: const Color(0xFF6D28D9),
                      indicatorWeight: 3,
                      labelColor: Colors.white,
                      unselectedLabelColor: Colors.white54,
                      labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                      tabs: const [
                        Tab(text: 'Statistiques'),
                        Tab(text: 'Succès'),
                        Tab(text: 'Historique'),
                      ],
                    ),

                    // Tab View
                    Expanded(
                      child: TabBarView(
                        controller: _tabController,
                        children: [
                          _buildStatsTab(stats, user),
                          _buildAchievementsTab(achievements),
                          _buildHistoryTab(history),
                        ],
                      ),
                    ),
                  ],
                ),
    );
  }

  Widget _buildStatsTab(Map<String, dynamic> stats, UserModel user) {
    final totalPlayed = stats['total_played'] ?? user.totalPlayed;
    final correctCount = stats['correct_count'] ?? user.correctCount;
    final successRate = stats['success_rate'] ?? 0.0;
    final avgTime = stats['average_time'] ?? 0.0;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            childAspectRatio: 1.35,
            children: [
              _StatTile(
                icon: FontAwesomeIcons.gamepad,
                label: 'Quiz Joués',
                value: '$totalPlayed',
                color: const Color(0xFF6D28D9),
              ),
              _StatTile(
                icon: FontAwesomeIcons.bullseye,
                label: 'Taux de Réussite',
                value: '$successRate%',
                color: Colors.green,
              ),
              _StatTile(
                icon: FontAwesomeIcons.circleCheck,
                label: 'Bonnes Réponses',
                value: '$correctCount',
                color: const Color(0xFF0EA5E9),
              ),
              _StatTile(
                icon: FontAwesomeIcons.stopwatch,
                label: 'Temps Moyen / Rép.',
                value: '${avgTime}s',
                color: Colors.amberAccent,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildAchievementsTab(List<Map<String, dynamic>> achievements) {
    if (achievements.isEmpty) {
      return const Center(child: Text('Aucun succès pour le moment.', style: TextStyle(color: Colors.white54)));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: achievements.length,
      itemBuilder: (ctx, i) {
        final ach = achievements[i];
        final unlocked = ach['is_unlocked'] == true;

        return Card(
          margin: const EdgeInsets.only(bottom: 10),
          color: unlocked ? const Color(0xFF6D28D9).withValues(alpha: 0.18) : const Color(0xFF1E1B4B).withValues(alpha: 0.5),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
            side: BorderSide(
              color: unlocked ? const Color(0xFF6D28D9) : Colors.white10,
              width: unlocked ? 1.5 : 1,
            ),
          ),
          child: ListTile(
            leading: Container(
              width: 44,
              height: 44,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: unlocked ? Colors.amber.withValues(alpha: 0.2) : Colors.white10,
                shape: BoxShape.circle,
                border: Border.all(color: unlocked ? Colors.amberAccent : Colors.transparent),
              ),
              child: FaIcon(
                unlocked ? FontAwesomeIcons.trophy : FontAwesomeIcons.lock,
                size: 18,
                color: unlocked ? Colors.amberAccent : Colors.white38,
              ),
            ),
            title: Text(
              ach['name'] as String? ?? 'Succès',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                color: unlocked ? Colors.white : Colors.white54,
              ),
            ),
            subtitle: Text(
              ach['description'] as String? ?? '',
              style: const TextStyle(color: Colors.white38, fontSize: 12),
            ),
            trailing: unlocked
                ? const FaIcon(FontAwesomeIcons.circleCheck, color: Colors.greenAccent, size: 18)
                : null,
          ),
        );
      },
    );
  }

  Widget _buildHistoryTab(List<Map<String, dynamic>> history) {
    if (history.isEmpty) {
      return const Center(child: Text('Aucun duel terminé pour l\'instant.', style: TextStyle(color: Colors.white54)));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: history.length,
      itemBuilder: (ctx, i) {
        final h = history[i];
        final title = h['quiz_title'] as String? ?? 'Duel Thématique';
        final score = h['score'] ?? 0;
        final winner = h['winner_name'] as String? ?? '';
        final date = h['created_at'] as String? ?? '';

        return Card(
          margin: const EdgeInsets.only(bottom: 10),
          color: const Color(0xFF1E1B4B),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
            side: const BorderSide(color: Colors.white10),
          ),
          child: ListTile(
            leading: Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFF6D28D9).withValues(alpha: 0.2),
                shape: BoxShape.circle,
              ),
              child: const FaIcon(FontAwesomeIcons.gamepad, size: 16, color: Color(0xFF6D28D9)),
            ),
            title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
            subtitle: Text(
              'Vainqueur : $winner · $date',
              style: const TextStyle(color: Colors.white54, fontSize: 12),
            ),
            trailing: Text(
              '$score pts',
              style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.amberAccent, fontSize: 15),
            ),
          ),
        );
      },
    );
  }
}

class _StatTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color color;

  const _StatTile({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF1E1B4B),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          FaIcon(icon, color: color, size: 20),
          const Spacer(),
          Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
          const SizedBox(height: 2),
          Text(label, style: const TextStyle(color: Colors.white54, fontSize: 11)),
        ],
      ),
    );
  }
}
