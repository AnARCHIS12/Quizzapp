import 'package:flutter_test/flutter_test.dart';
import 'package:quizzapp_mobile/main.dart';

void main() {
  testWidgets('App renders correctly', (WidgetTester tester) async {
    await tester.pumpWidget(const QuizzApp());
    expect(find.byType(QuizzApp), findsOneWidget);
  });
}
