import 'dart:async';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../services/websocket_service.dart';

/// Écran d'attente pendant la génération par IA des 18 questions
class GeneratingScreen extends StatefulWidget {
  final String roomCode;
  const GeneratingScreen({super.key, required this.roomCode});

  @override
  State<GeneratingScreen> createState() => _GeneratingScreenState();
}

class _GeneratingScreenState extends State<GeneratingScreen>
    with SingleTickerProviderStateMixin {
  late AnimationController _pulseCtrl;
  StreamSubscription<Map<String, dynamic>>? _sub;
  double _progress = 0.0;
  Timer? _progressTimer;

  @override
  void initState() {
    super.initState();

    _pulseCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1400),
    )..repeat(reverse: true);

    // Barre de progression fluide sur ~12 secondes
    _progressTimer = Timer.periodic(const Duration(milliseconds: 120), (_) {
      if (mounted && _progress < 0.96) {
        setState(() => _progress += 0.01);
      }
    });

    _sub = context.read<WebSocketService>().messages.listen((msg) {
      if (msg['type'] == 'selection_complete' || msg['type'] == 'new_question') {
        if (mounted) context.go('/duel/play/${widget.roomCode}');
      }
    });
  }

  @override
  void dispose() {
    _pulseCtrl.dispose();
    _progressTimer?.cancel();
    _sub?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(36),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              AnimatedBuilder(
                animation: _pulseCtrl,
                builder: (_, __) => Transform.scale(
                  scale: 0.95 + (_pulseCtrl.value * 0.1),
                  child: Container(
                    width: 110,
                    height: 110,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF6D28D9), Color(0xFF4F46E5)],
                      ),
                      borderRadius: BorderRadius.circular(30),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF6D28D9).withOpacity(0.5),
                          blurRadius: 25 + (_pulseCtrl.value * 15),
                          spreadRadius: 2,
                        ),
                      ],
                    ),
                    child: const Center(
                      child: FaIcon(FontAwesomeIcons.robot, size: 50, color: Colors.white),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 32),
              const Text(
                'Génération des 18 questions…',
                style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 12),
              const Text(
                'L\'intelligence artificielle rédige des questions uniques\nbasées sur vos catégories choisies',
                style: TextStyle(color: Colors.white60, fontSize: 14, height: 1.4),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 32),
              ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: LinearProgressIndicator(
                  value: _progress.clamp(0.0, 1.0),
                  backgroundColor: Colors.white12,
                  color: const Color(0xFF6D28D9),
                  minHeight: 10,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                '${(_progress * 100).toInt()}%',
                style: const TextStyle(color: Colors.white54, fontSize: 13, fontWeight: FontWeight.bold),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
