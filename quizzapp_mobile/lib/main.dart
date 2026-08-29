import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'services/api_service.dart';
import 'services/websocket_service.dart';
import 'screens/server_screen.dart';
import 'screens/login_screen.dart';
import 'screens/register_screen.dart';
import 'screens/privacy_policy_screen.dart';
import 'screens/home_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/leaderboard_screen.dart';
import 'screens/quiz/solo_quiz_screen.dart';
import 'screens/duel/duel_lobby_screen.dart';
import 'screens/duel/category_pick_screen.dart';
import 'screens/duel/generating_screen.dart';
import 'screens/duel/duel_game_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(
    MultiProvider(
      providers: [
        Provider<ApiService>(create: (_) => ApiService()),
        Provider<WebSocketService>(
          create: (_) => WebSocketService(),
          dispose: (_, ws) => ws.dispose(),
        ),
      ],
      child: const QuizzApp(),
    ),
  );
}

class QuizzApp extends StatelessWidget {
  const QuizzApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: 'QuizzApp',
      debugShowCheckedModeBanner: false,
      theme: _buildTheme(),
      routerConfig: _buildRouter(),
    );
  }

  // ─── Theme Sombre Premium Violet/Indigo ──────────────────────────────────────

  ThemeData _buildTheme() {
    const primary = Color(0xFF6D28D9);   // violet-700
    const secondary = Color(0xFF4F46E5); // indigo-600
    const surface = Color(0xFF1E1B4B);   // indigo-950
    const background = Color(0xFF0F0E2B);

    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      colorScheme: const ColorScheme.dark(
        primary: primary,
        secondary: secondary,
        surface: surface,
        onPrimary: Colors.white,
        onSecondary: Colors.white,
      ),
      scaffoldBackgroundColor: background,
      appBarTheme: const AppBarTheme(
        backgroundColor: surface,
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primary,
          foregroundColor: Colors.white,
          minimumSize: const Size.fromHeight(52),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
          elevation: 2,
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: surface,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: primary, width: 2),
        ),
        labelStyle: const TextStyle(color: Colors.white70),
      ),
      cardTheme: CardThemeData(
        color: surface,
        elevation: 4,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
      ),
    );
  }

  // ─── Router & Redirection Globale ──────────────────────────────────────────

  GoRouter _buildRouter() {
    return GoRouter(
      initialLocation: '/server',
      redirect: _globalRedirect,
      routes: [
        GoRoute(path: '/server', builder: (_, __) => const ServerScreen()),
        GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
        GoRoute(path: '/register', builder: (_, __) => const RegisterScreen()),
        GoRoute(path: '/privacy', builder: (_, __) => const PrivacyPolicyScreen()),
        GoRoute(path: '/home', builder: (_, __) => const HomeScreen()),
        GoRoute(path: '/profile', builder: (_, __) => const ProfileScreen()),
        GoRoute(path: '/leaderboard', builder: (_, __) => const LeaderboardScreen()),
        GoRoute(
          path: '/quiz/solo',
          builder: (_, state) {
            final extra = state.extra as Map<String, dynamic>? ?? {};
            return SoloQuizScreen(
              categoryId: extra['categoryId'] as int? ?? 1,
              categoryName: extra['categoryName'] as String? ?? 'Culture Générale',
              subCategory: extra['subCategory'] as String?,
            );
          },
        ),
        GoRoute(path: '/duel', builder: (_, __) => const DuelLobbyScreen()),
        GoRoute(
          path: '/duel/pick/:code',
          builder: (_, state) => CategoryPickScreen(
            roomCode: state.pathParameters['code']!,
          ),
        ),
        GoRoute(
          path: '/duel/generating/:code',
          builder: (_, state) => GeneratingScreen(
            roomCode: state.pathParameters['code']!,
          ),
        ),
        GoRoute(
          path: '/duel/play/:code',
          builder: (_, state) => DuelGameScreen(
            roomCode: state.pathParameters['code']!,
          ),
        ),
      ],
    );
  }

  // Redirection d'authentification et de serveur
  Future<String?> _globalRedirect(BuildContext context, GoRouterState state) async {
    const storage = FlutterSecureStorage();
    final serverUrl = await storage.read(key: 'server_url');
    final token = await storage.read(key: 'auth_token');

    final loc = state.matchedLocation;
    final isAuthRoute = loc == '/login' || loc == '/register' || loc == '/server' || loc == '/privacy';

    // 1. Pas de serveur configuré -> écran serveur
    if (serverUrl == null || serverUrl.isEmpty) {
      return loc == '/server' ? null : '/server';
    }

    // 2. Pas de token -> écran login (sauf si déjà sur register/server/privacy)
    if (token == null || token.isEmpty) {
      return isAuthRoute ? null : '/login';
    }

    // 3. Connecté mais sur une page d'auth -> accueil
    if (loc == '/login' || loc == '/register' || loc == '/server') {
      return '/home';
    }

    return null; // Navigation autorisée
  }
}
