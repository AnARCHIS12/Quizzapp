class UserModel {
  final int id;
  final String username;
  final String email;
  final String? avatarUrl;
  final int roleId;
  final int level;
  final int xp;
  final int totalPlayed;
  final int correctCount;

  const UserModel({required this.id, required this.username, required this.email,
    this.avatarUrl, required this.roleId, required this.level, required this.xp,
    this.totalPlayed = 0, this.correctCount = 0});

  factory UserModel.fromJson(Map<String, dynamic> j) => UserModel(
    id: j['id'] as int, username: j['username'] as String, email: j['email'] as String,
    avatarUrl: j['avatar_url'] as String?, roleId: j['role_id'] as int? ?? 2,
    level: j['level'] as int? ?? 1, xp: j['xp'] as int? ?? 0,
    totalPlayed: j['total_played'] as int? ?? 0, correctCount: j['correct_count'] as int? ?? 0);
}

class CategoryModel {
  final int id;
  final String name;
  final String slug;
  final String? description;
  final int? parentId;
  final String? parentName;

  const CategoryModel({required this.id, required this.name, required this.slug,
    this.description, this.parentId, this.parentName});

  factory CategoryModel.fromJson(Map<String, dynamic> j) => CategoryModel(
    id: int.parse(j['id'].toString()), name: j['name'] as String, slug: j['slug'] as String,
    description: j['description'] as String?,
    parentId: j['parent_id'] != null ? int.tryParse(j['parent_id'].toString()) : null,
    parentName: j['parent_name'] as String?);

  bool get isSubcategory => parentId != null;
}

class AnswerModel {
  final int id;
  final String text;
  final bool? isCorrect;

  const AnswerModel({required this.id, required this.text, this.isCorrect});

  factory AnswerModel.fromJson(Map<String, dynamic> j) => AnswerModel(
    id: int.parse(j['id'].toString()), text: j['answer_text'] as String,
    isCorrect: j['is_correct'] != null ? j['is_correct'].toString() == '1' : null);
}

class QuestionModel {
  final int index;
  final String text;
  final String type;
  final int points;
  final List<AnswerModel> answers;
  final String? explanation;

  const QuestionModel({required this.index, required this.text, required this.type,
    required this.points, required this.answers, this.explanation});

  factory QuestionModel.fromWsEvent(Map<String, dynamic> j) => QuestionModel(
    index: j['question_index'] as int? ?? 0, text: j['question'] as String,
    type: j['type'] as String? ?? 'qcm', points: j['points'] as int? ?? 10,
    answers: (j['answers'] as List<dynamic>? ?? [])
        .map((a) => AnswerModel.fromJson(a as Map<String, dynamic>)).toList(),
    explanation: j['explanation'] as String?);
}
