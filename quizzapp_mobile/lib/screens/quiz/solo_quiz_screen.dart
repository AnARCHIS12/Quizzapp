import 'dart:async';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../services/api_service.dart';
import '../../models/models.dart';
import '../../widgets/error_box.dart';

class SoloQuizScreen extends StatefulWidget {
  final int categoryId;
  final String categoryName;
  final String? subCategory;

  const SoloQuizScreen({
    super.key,
    required this.categoryId,
    required this.categoryName,
    this.subCategory,
  });

  @override
  State<SoloQuizScreen> createState() => _SoloQuizScreenState();
}

class _SoloQuizScreenState extends State<SoloQuizScreen> {
  bool _loading = true;
  String? _error;
  String _title = '';
  List<QuestionModel> _questions = [];
  int _currentIndex = 0;
  int _score = 0;
  int _correctCount = 0;
  int? _selectedAnswerId;
  bool _answered = false;
  bool _correct = false;
  int _timeLeft = 25;
  Timer? _timer;
  DateTime? _startTime;

  // Results state
  bool _finished = false;
  bool _submitting = false;
  Map<String, dynamic>? _submitResult;

  @override
  void initState() {
    super.initState();
    _loadQuiz();
  }

  Future<void> _loadQuiz() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final res = await context.read<ApiService>().generateSoloQuiz(
            categoryId: widget.categoryId,
            categoryName: widget.categoryName,
            subCategory: widget.subCategory,
            count: 10,
          );

      final rawQuestions = (res['questions'] as List? ?? [])
          .map((q) => QuestionModel.fromJson(q as Map<String, dynamic>))
          .toList();

      if (rawQuestions.isEmpty) {
        throw Exception('Aucune question disponible pour ce thème.');
      }

      if (mounted) {
        setState(() {
          _title = res['title'] as String? ?? widget.categoryName;
          _questions = rawQuestions;
          _loading = false;
        });
        _startQuestion();
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Impossible de charger le quiz. Veuillez réessayer.';
          _loading = false;
        });
      }
    }
  }

  void _startQuestion() {
    _timer?.cancel();
    setState(() {
      _selectedAnswerId = null;
      _answered = false;
      _correct = false;
      _timeLeft = 25;
      _startTime = DateTime.now();
    });

    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (_answered || !mounted) {
        t.cancel();
        return;
      }
      if (_timeLeft <= 1) {
        t.cancel();
        _submitAnswer(null);
      } else {
        setState(() => _timeLeft--);
      }
    });
  }

  void _submitAnswer(int? answerId) {
    if (_answered) return;
    _timer?.cancel();

    final currentQ = _questions[_currentIndex];
    bool isCorrect = false;

    if (answerId != null) {
      final selected = currentQ.answers.firstWhere(
        (a) => a.id == answerId,
        orElse: () => const AnswerModel(id: -1, text: '', isCorrect: false),
      );
      isCorrect = selected.isCorrect ?? false;
    }

    setState(() {
      _selectedAnswerId = answerId;
      _answered = true;
      _correct = isCorrect;
      if (isCorrect) {
        _score += currentQ.points;
        _correctCount++;
      }
    });
  }

  void _nextQuestion() {
    if (_currentIndex < _questions.length - 1) {
      setState(() => _currentIndex++);
      _startQuestion();
    } else {
      _finishQuiz();
    }
  }

  Future<void> _finishQuiz() async {
    setState(() {
      _finished = true;
      _submitting = true;
    });

    try {
      final res = await context.read<ApiService>().submitSoloScore(
            quizId: 0,
            score: _score,
            correctCount: _correctCount,
            totalQuestions: _questions.length,
            timeSpent: 25.0 * _questions.length,
          );
      if (mounted) {
        setState(() {
          _submitResult = res;
          _submitting = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Scaffold(
        body: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: const Color(0xFF6D28D9).withValues(alpha: 0.2),
                  shape: BoxShape.circle,
                ),
                child: const FaIcon(FontAwesomeIcons.robot, color: Colors.amberAccent, size: 40),
              ),
              const SizedBox(height: 24),
              const Text(
                'Génération de votre Quiz Solo…',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
              ),
              const SizedBox(height: 8),
              Text(
                'Thématique : ${widget.categoryName}',
                style: const TextStyle(color: Colors.white54, fontSize: 13),
              ),
              const SizedBox(height: 20),
              const SizedBox(
                width: 160,
                child: LinearProgressIndicator(color: Color(0xFF6D28D9), backgroundColor: Colors.white12),
              ),
            ],
          ),
        ),
      );
    }

    if (_error != null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Quiz')),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                ErrorBox(message: _error!),
                const SizedBox(height: 20),
                ElevatedButton.icon(
                  onPressed: _loadQuiz,
                  icon: const FaIcon(FontAwesomeIcons.rotateRight, size: 14),
                  label: const Text('Réessayer'),
                ),
                TextButton(
                  onPressed: () => context.go('/home'),
                  child: const Text('Retour à l\'accueil'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    if (_finished) return _buildFinishedScreen();

    final currentQ = _questions[_currentIndex];

    return Scaffold(
      appBar: AppBar(
        title: Text(_title.isNotEmpty ? _title : widget.categoryName),
        leading: IconButton(
          icon: const Icon(Icons.close),
          onPressed: () => context.go('/home'),
        ),
      ),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Question ${_currentIndex + 1} / ${_questions.length}',
                    style: const TextStyle(color: Colors.white70, fontWeight: FontWeight.bold),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: _timeLeft <= 5 ? Colors.redAccent.withValues(alpha: 0.25) : const Color(0xFF6D28D9).withValues(alpha: 0.25),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: _timeLeft <= 5 ? Colors.redAccent : const Color(0xFF6D28D9)),
                    ),
                    child: Row(
                      children: [
                        FaIcon(FontAwesomeIcons.stopwatch, size: 12, color: _timeLeft <= 5 ? Colors.redAccent : Colors.amberAccent),
                        const SizedBox(width: 6),
                        Text(
                          '${_timeLeft}s',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 13,
                            color: _timeLeft <= 5 ? Colors.redAccent : Colors.amberAccent,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            LinearProgressIndicator(
              value: (_currentIndex + 1) / _questions.length,
              backgroundColor: Colors.white12,
              color: const Color(0xFF6D28D9),
              minHeight: 6,
            ),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  children: [
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(22),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1E1B4B),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: Colors.white12),
                      ),
                      child: Text(
                        currentQ.text,
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w600,
                          color: Colors.white,
                          height: 1.4,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ),
                    const SizedBox(height: 20),
                    ...currentQ.answers.map((ans) {
                      Color border = Colors.white12;
                      Color bg = const Color(0xFF1E1B4B);

                      if (_answered && ans.isCorrect == true) {
                        border = Colors.green;
                        bg = Colors.green.withValues(alpha: 0.25);
                      } else if (_answered && _selectedAnswerId == ans.id && ans.isCorrect == false) {
                        border = Colors.redAccent;
                        bg = Colors.redAccent.withValues(alpha: 0.25);
                      } else if (_selectedAnswerId == ans.id) {
                        border = const Color(0xFF6D28D9);
                        bg = const Color(0xFF6D28D9).withValues(alpha: 0.3);
                      }

                      return Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        width: double.infinity,
                        child: InkWell(
                          borderRadius: BorderRadius.circular(14),
                          onTap: _answered ? null : () => _submitAnswer(ans.id),
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 150),
                            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
                            decoration: BoxDecoration(
                              color: bg,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: border, width: 1.5),
                            ),
                            child: Text(
                              ans.text,
                              style: const TextStyle(color: Colors.white, fontSize: 15),
                            ),
                          ),
                        ),
                      );
                    }),
                    if (_answered && currentQ.explanation != null && currentQ.explanation!.isNotEmpty) ...[
                      const SizedBox(height: 12),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: _correct ? Colors.green.shade900.withValues(alpha: 0.3) : Colors.red.shade900.withValues(alpha: 0.3),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: _correct ? Colors.green : Colors.redAccent),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            FaIcon(
                              _correct ? FontAwesomeIcons.circleCheck : FontAwesomeIcons.circleInfo,
                              color: _correct ? Colors.greenAccent : Colors.redAccent,
                              size: 16,
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                currentQ.explanation!,
                                style: const TextStyle(color: Colors.white, fontSize: 13, height: 1.3),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                    if (_answered) ...[
                      const SizedBox(height: 24),
                      ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF6D28D9),
                          minimumSize: const Size(double.infinity, 50),
                        ),
                        onPressed: _nextQuestion,
                        icon: const FaIcon(FontAwesomeIcons.arrowRight, size: 14),
                        label: Text(
                          _currentIndex < _questions.length - 1 ? 'Question suivante' : 'Voir les résultats',
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFinishedScreen() {
    final successRate = (_correctCount / _questions.length * 100).toInt();
    final xpEarned = _submitResult?['xp_earned'] ?? (_score ~/ 10 + 15);
    final levelUp = _submitResult?['level_up'] == true;
    final newLevel = _submitResult?['new_level'];

    return Scaffold(
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              FaIcon(
                successRate >= 60 ? FontAwesomeIcons.trophy : FontAwesomeIcons.faceSmile,
                size: 64,
                color: Colors.amberAccent,
              ),
              const SizedBox(height: 20),
              const Text(
                'Quiz Terminé !',
                style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: Colors.white),
              ),
              const SizedBox(height: 8),
              Text(
                '$_correctCount / ${_questions.length} réponses correctes ($successRate%)',
                style: const TextStyle(color: Colors.white70, fontSize: 15),
              ),
              const SizedBox(height: 24),
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: const Color(0xFF1E1B4B),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: const Color(0xFF6D28D9)),
                ),
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                      children: [
                        Column(
                          children: [
                            const Text('Score', style: TextStyle(color: Colors.white54, fontSize: 12)),
                            const SizedBox(height: 4),
                            Text('$_score pts', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.white)),
                          ],
                        ),
                        Container(width: 1, height: 40, color: Colors.white12),
                        Column(
                          children: [
                            const Text('XP Gagné', style: TextStyle(color: Colors.white54, fontSize: 12)),
                            const SizedBox(height: 4),
                            Text('+$xpEarned XP', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.amberAccent)),
                          ],
                        ),
                      ],
                    ),
                    if (levelUp && newLevel != null) ...[
                      const SizedBox(height: 16),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                        decoration: BoxDecoration(
                          color: Colors.amber.withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.amberAccent),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const FaIcon(FontAwesomeIcons.star, color: Colors.amberAccent, size: 14),
                            const SizedBox(width: 8),
                            Text(
                              'NIVEAU SUPÉRIEUR : NIVEAU $newLevel !',
                              style: const TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold, fontSize: 12),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 32),
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(minimumSize: const Size(double.infinity, 50)),
                onPressed: _loadQuiz,
                icon: const FaIcon(FontAwesomeIcons.rotateRight, size: 14),
                label: const Text('Rejouer ce thème'),
              ),
              const SizedBox(height: 12),
              TextButton(
                onPressed: () => context.go('/home'),
                child: const Text('Retour à l\'accueil', style: TextStyle(color: Colors.white70)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
