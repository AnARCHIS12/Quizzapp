import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../services/api_service.dart';
import '../models/models.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  List<CategoryModel> _categories = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadCategories();
  }

  Future<void> _loadCategories() async {
    try {
      final rows = await context.read<ApiService>().getCategories();
      if (mounted) {
        setState(() {
          _categories = rows.map(CategoryModel.fromJson).toList();
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _showCategoryDetails(CategoryModel category) {
    final subcategories = _categories.where((c) => c.parentId == category.id).toList();

    showModalBottomSheet(
      context: context,
      backgroundColor: const Color(0xFF1E1B4B),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        return Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF6D28D9).withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: const Color(0xFF6D28D9).withValues(alpha: 0.5)),
                    ),
                    child: FaIcon(
                      _CategoryCard.getCategoryIcon(category.slug),
                      color: const Color(0xFF6D28D9),
                      size: 24,
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          category.name,
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          '${subcategories.length} sous-thématiques disponibles',
                          style: const TextStyle(color: Colors.white54, fontSize: 12),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              if (subcategories.isNotEmpty) ...[
                const Text(
                  'Sous-catégories sélectionnables en duel :',
                  style: TextStyle(
                    color: Colors.white70,
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                  ),
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: subcategories.map((sub) {
                    return Chip(
                      backgroundColor: const Color(0xFF6D28D9).withValues(alpha: 0.15),
                      side: BorderSide(color: const Color(0xFF6D28D9).withValues(alpha: 0.4)),
                      label: Text(
                        sub.name,
                        style: const TextStyle(color: Colors.white, fontSize: 12),
                      ),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 20),
              ] else ...[
                const Text(
                  'Catégorie générale avec questions variées générées par l\'IA.',
                  style: TextStyle(color: Colors.white60, fontSize: 13),
                ),
                const SizedBox(height: 20),
              ],
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF6D28D9),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: () {
                  Navigator.of(ctx).pop();
                  context.go('/duel');
                },
                icon: const FaIcon(FontAwesomeIcons.gamepad, size: 16),
                label: const Text(
                  'Lancer un duel multijoueur',
                  style: TextStyle(fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final topLevel = _categories.where((c) => !c.isSubcategory).toList();

    return Scaffold(
      appBar: AppBar(
        title: const Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            FaIcon(FontAwesomeIcons.solidStar, color: Colors.amberAccent, size: 18),
            SizedBox(width: 8),
            Text('QuizzApp', style: TextStyle(fontWeight: FontWeight.bold)),
          ],
        ),
        actions: [
          IconButton(
            icon: const FaIcon(FontAwesomeIcons.solidUser, size: 18),
            tooltip: 'Profil',
            onPressed: () => context.go('/profile'),
          ),
        ],
      ),
      body: Column(
        children: [
          Container(
            width: double.infinity,
            margin: const EdgeInsets.all(16),
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF6D28D9), Color(0xFF4F46E5)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFF6D28D9).withValues(alpha: 0.3),
                  blurRadius: 15,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Row(
                  children: [
                    FaIcon(FontAwesomeIcons.gamepad, color: Colors.white, size: 20),
                    SizedBox(width: 10),
                    Text(
                      'Duel en temps réel',
                      style: TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                const Text(
                  '18 questions générées par IA · Catégories au choix',
                  style: TextStyle(color: Colors.white70),
                ),
                const SizedBox(height: 18),
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: const Color(0xFF6D28D9),
                  ),
                  onPressed: () => context.go('/duel'),
                  icon: const FaIcon(FontAwesomeIcons.play, size: 16),
                  label: const Text('Lancer un duel', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
              ],
            ),
          ),
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Row(
              children: [
                FaIcon(FontAwesomeIcons.layerGroup, size: 16, color: Colors.white70),
                SizedBox(width: 8),
                Text(
                  'Catégories disponibles',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: _loadCategories,
                    child: GridView.builder(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 2,
                        childAspectRatio: 1.4,
                        crossAxisSpacing: 12,
                        mainAxisSpacing: 12,
                      ),
                      itemCount: topLevel.length,
                      itemBuilder: (ctx, i) => _CategoryCard(
                        category: topLevel[i],
                        onTap: () => _showCategoryDetails(topLevel[i]),
                      ),
                    ),
                  ),
          ),
        ],
      ),
    );
  }
}

class _CategoryCard extends StatelessWidget {
  final CategoryModel category;
  final VoidCallback onTap;

  const _CategoryCard({
    required this.category,
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
        return FontAwesomeIcons.shapes;
    }
  }

  @override
  Widget build(BuildContext context) {
    final color = _colors[category.id % _colors.length];
    return Card(
      color: color.withValues(alpha: 0.18),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: color.withValues(alpha: 0.4)),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              FaIcon(getCategoryIcon(category.slug), color: color, size: 26),
              const SizedBox(height: 8),
              Text(
                category.name,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                  fontSize: 14,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
