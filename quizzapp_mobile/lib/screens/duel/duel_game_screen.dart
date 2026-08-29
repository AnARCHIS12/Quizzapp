import 'dart:async';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../services/websocket_service.dart';
import '../../models/models.dart';

/// Écran principal du duel multijoueur en temps réel
class DuelGameScreen extends StatefulWidget {
  final String roomCode;
  const DuelGameScreen({super.key, required this.roomCode});

  @override
  State<DuelGameScreen> createState() => _DuelGameScreenState();
}

class _DuelGameScreenState extends State<DuelGameScreen> {
  StreamSubscription<Map<String, dynamic>>? _sub;

  QuestionModel? _question;
  int _myScore = 0;
  int _opponentScore = 0;
  final String _opponentName = 'Adversaire';
  int? _selectedAnswerId;
  bool _answered = false;
  bool _correct = false;
  int _questionIndex = 0;
  int _totalQuestions = 18;
  DateTime? _questionStart;
  bool _gameOver = false;
  String _gameOverMessage = '';

  int _timeLeft = 20;
  Timer? _countdownTimer;

  @override
  void initState() {
    super.initState();
    _listenWs();
  }

  void _listenWs() {
    _sub = context.read<WebSocketService>().messages.listen((msg) {
      if (!mounted) return;
      switch (msg['type']) {
        case 'new_question':
          _startQuestion(msg);
          break;
        case 'question_feedback':
          _showFeedback(msg);
          break;
        case 'game_over':
          _showGameOver(msg);
          break;
        case 'score_update':
          setState(() {
            _myScore = (msg['my_score'] as int?) ?? _myScore;
            _opponentScore = (msg['opponent_score'] as int?) ?? _opponentScore;
          });
          break;
      }
    });
  }

  void _startQuestion(Map<String, dynamic> msg) {
    _countdownTimer?.cancel();
    setState(() {
      _question = QuestionModel.fromWsEvent(msg);
      _questionIndex = (msg['question_index'] as int?) ?? _questionIndex;
      _totalQuestions = (msg['total'] as int?) ?? _totalQuestions;
      _selectedAnswerId = null;
      _answered = false;
      _correct = false;
      _questionStart = DateTime.now();
      _timeLeft = 20;
    });
    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (_answered || !mounted) {
        t.cancel();
        return;
      }
      if (_timeLeft <= 1) {
        t.cancel();
        if (!_answered) _submitAnswer(null);
      } else {
        setState(() => _timeLeft--);
      }
    });
  }

  void _showFeedback(Map<String, dynamic> msg) {
    _countdownTimer?.cancel();
    setState(() {
      _answered = true;
      _correct = msg['correct'] == true;
      _myScore = (msg['my_score'] as int?) ?? _myScore;
      _opponentScore = (msg['opponent_score'] as int?) ?? _opponentScore;
    });
  }

  void _showGameOver(Map<String, dynamic> msg) {
    _countdownTimer?.cancel();
    setState(() {
      _gameOver = true;
      _myScore = (msg['my_score'] as int?) ?? _myScore;
      _opponentScore = (msg['opponent_score'] as int?) ?? _opponentScore;
      final isWinner = _myScore > _opponentScore;
      final isDraw = _myScore == _opponentScore;
      _gameOverMessage = isDraw
          ? 'Égalité parfaite !'
          : isWinner
              ? 'Victoire écrasante !'
              : 'Défaite, tentez votre revanche !';
    });
  }

  void _submitAnswer(int? answerId) {
    if (_answered || _question == null) return;
    setState(() {
      _selectedAnswerId = answerId;
      _answered = true;
    });
    final elapsedMs = _questionStart != null
        ? DateTime.now().difference(_questionStart!).inMilliseconds
        : 0;
    context.read<WebSocketService>().submitAnswer(
          roomCode: widget.roomCode,
          questionIndex: _questionIndex,
          answerId: answerId,
          timeSpentSeconds: elapsedMs / 1000.0,
        );
  }

  @override
  void dispose() {
    _sub?.cancel();
    _countdownTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_gameOver) return _buildGameOver();
    if (_question == null) return _buildWaiting();
    return _buildQuestion();
  }

  Widget _buildWaiting() {
    return const Scaffold(
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            CircularProgressIndicator(),
            SizedBox(height: 18),
            Text(
              'Lancement du duel en cours…',
              style: TextStyle(color: Colors.white70, fontSize: 16),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildQuestion() {
    final q = _question!;
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            _ScoreBar(
              myScore: _myScore,
              opponentScore: _opponentScore,
              opponent: _opponentName,
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Question ${_questionIndex + 1} / $_totalQuestions',
                    style: const TextStyle(
                      color: Colors.white70,
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                    ),
                  ),
                  _TimerBadge(seconds: _timeLeft, answered: _answered),
                ],
              ),
            ),
            LinearProgressIndicator(
              value: _totalQuestions > 0 ? (_questionIndex + 1) / _totalQuestions : 0,
              backgroundColor: Colors.white12,
              color: const Color(0xFF6D28D9),
              minHeight: 5,
            ),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  children: [
                    const SizedBox(height: 8),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(22),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1E1B4B),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: Colors.white12),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withOpacity(0.2),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Text(
                        q.text,
                        style: const TextStyle(
                          fontSize: 18,
                          color: Colors.white,
                          height: 1.4,
                          fontWeight: FontWeight.w600,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ),
                    const SizedBox(height: 20),
                    ...q.answers.map(
                      (ans) => _AnswerButton(
                        answer: ans,
                        selected: _selectedAnswerId == ans.id,
                        answered: _answered,
                        onTap: () => _submitAnswer(ans.id),
                      ),
                    ),
                    if (_answered && q.explanation != null && q.explanation!.isNotEmpty) ...[
                      const SizedBox(height: 12),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: _correct
                              ? Colors.green.shade900.withOpacity(0.3)
                              : Colors.red.shade900.withOpacity(0.3),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: _correct ? Colors.green : Colors.redAccent,
                            width: 1,
                          ),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            FaIcon(
                              _correct ? FontAwesomeIcons.circleCheck : FontAwesomeIcons.circleInfo,
                              color: _correct ? Colors.greenAccent : Colors.redAccent,
                              size: 18,
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                q.explanation!,
                                style: const TextStyle(color: Colors.white, fontSize: 13, height: 1.3),
                              ),
                            ),
                          ],
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

  Widget _buildGameOver() {
    final isWin = _myScore > _opponentScore;
    final isDraw = _myScore == _opponentScore;

    return Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              FaIcon(
                isWin
                    ? FontAwesomeIcons.trophy
                    : isDraw
                        ? FontAwesomeIcons.handshake
                        : FontAwesomeIcons.faceFrown,
                size: 64,
                color: isWin
                    ? Colors.amberAccent
                    : isDraw
                        ? Colors.blueAccent
                        : Colors.redAccent,
              ),
              const SizedBox(height: 20),
              Text(
                _gameOverMessage,
                style: const TextStyle(
                  fontSize: 26,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  _ScorePill(label: 'Vous', score: _myScore),
                  const SizedBox(width: 24),
                  const Text('VS', style: TextStyle(color: Colors.white38, fontWeight: FontWeight.bold)),
                  const SizedBox(width: 24),
                  _ScorePill(label: _opponentName, score: _opponentScore),
                ],
              ),
              const SizedBox(height: 36),
              ElevatedButton.icon(
                onPressed: () {
                  context.read<WebSocketService>().playAgain(widget.roomCode);
                  context.go('/duel/pick/${widget.roomCode}');
                },
                icon: const FaIcon(FontAwesomeIcons.rotateRight, size: 16),
                label: const Text('Rejouer un duel'),
              ),
              const SizedBox(height: 12),
              TextButton(
                onPressed: () {
                  context.read<WebSocketService>().disconnect();
                  context.go('/home');
                },
                child: const Text('Retour à l\'accueil', style: TextStyle(color: Colors.white60)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ScoreBar extends StatelessWidget {
  final int myScore;
  final int opponentScore;
  final String opponent;

  const _ScoreBar({
    required this.myScore,
    required this.opponentScore,
    required this.opponent,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
      decoration: const BoxDecoration(
        color: Color(0xFF1E1B4B),
        border: Border(bottom: BorderSide(color: Colors.white10)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            children: [
              const Text('Vous', style: TextStyle(color: Colors.white60, fontSize: 12)),
              const SizedBox(height: 2),
              Text(
                '$myScore',
                style: const TextStyle(
                  fontSize: 26,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
            ],
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: const Color(0xFF6D28D9).withOpacity(0.3),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Row(
              children: [
                FaIcon(FontAwesomeIcons.gamepad, size: 13, color: Colors.amberAccent),
                SizedBox(width: 6),
                Text('DUEL', style: TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold, fontSize: 12)),
              ],
            ),
          ),
          Column(
            children: [
              Text(opponent, style: const TextStyle(color: Colors.white60, fontSize: 12)),
              const SizedBox(height: 2),
              Text(
                '$opponentScore',
                style: const TextStyle(
                  fontSize: 26,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _TimerBadge extends StatelessWidget {
  final int seconds;
  final bool answered;
  const _TimerBadge({required this.seconds, required this.answered});

  @override
  Widget build(BuildContext context) {
    final color = answered
        ? Colors.white24
        : (seconds <= 5 ? Colors.redAccent : const Color(0xFF6D28D9));
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
      decoration: BoxDecoration(
        color: color.withOpacity(0.25),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withOpacity(0.5)),
      ),
      child: Row(
        children: [
          FaIcon(FontAwesomeIcons.stopwatch, color: color, size: 14),
          const SizedBox(width: 6),
          Text(
            '${seconds}s',
            style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 13),
          ),
        ],
      ),
    );
  }
}

class _AnswerButton extends StatelessWidget {
  final AnswerModel answer;
  final bool selected;
  final bool answered;
  final VoidCallback onTap;

  const _AnswerButton({
    required this.answer,
    required this.selected,
    required this.answered,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    Color borderColor;
    Color bgColor;

    if (answered && answer.isCorrect == true) {
      borderColor = Colors.green;
      bgColor = Colors.green.withOpacity(0.25);
    } else if (selected && answered && answer.isCorrect == false) {
      borderColor = Colors.redAccent;
      bgColor = Colors.redAccent.withOpacity(0.25);
    } else if (selected) {
      borderColor = const Color(0xFF6D28D9);
      bgColor = const Color(0xFF6D28D9).withOpacity(0.35);
    } else {
      borderColor = Colors.white12;
      bgColor = const Color(0xFF1E1B4B);
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      width: double.infinity,
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: answered ? null : onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
          decoration: BoxDecoration(
            color: bgColor,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: borderColor, width: 1.5),
          ),
          child: Text(
            answer.text,
            style: const TextStyle(color: Colors.white, fontSize: 15, height: 1.3),
          ),
        ),
      ),
    );
  }
}

class _ScorePill extends StatelessWidget {
  final String label;
  final int score;
  const _ScorePill({required this.label, required this.score});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(label, style: const TextStyle(color: Colors.white54, fontSize: 13)),
        const SizedBox(height: 4),
        Text(
          '$score',
          style: const TextStyle(
            fontSize: 32,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
      ],
    );
  }
}
