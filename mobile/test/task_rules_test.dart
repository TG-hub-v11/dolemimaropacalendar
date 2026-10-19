import "package:flutter_test/flutter_test.dart";
import "package:dole_calendar/features/tasks/task_rules.dart";
import "package:dole_calendar/features/auth/login_page.dart";

void main() {
  test("transitions mirror server rules", () {
    expect(taskTransitions["pending"], contains("in_progress"));
    expect(taskTransitions["pending"], isNot(contains("completed")));
    expect(taskTransitions["cancelled"], isEmpty);
  });
  test("overdue detection", () {
    final now = DateTime(2026, 10, 19);
    expect(isOverdue({"due_date": "2026-10-18", "status": "pending"}, now), isTrue);
    expect(isOverdue({"due_date": "2026-10-19", "status": "pending"}, now), isFalse);
    expect(isOverdue({"due_date": "2026-10-01", "status": "completed"}, now), isFalse);
    expect(isOverdue({"due_date": null, "status": "pending"}, now), isFalse);
  });
  test("login validators", () {
    expect(validateEmail("a@b.co"), isNull);
    expect(validateEmail("nope"), isNotNull);
    expect(validatePassword(""), isNotNull);
  });
}
