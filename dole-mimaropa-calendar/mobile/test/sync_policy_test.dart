import "package:flutter_test/flutter_test.dart";
import "package:dole_calendar/data/sync_policy.dart";

void main() {
  test("backoff grows and is capped", () {
    expect(backoff(0), const Duration(seconds: 5));
    expect(backoff(1), const Duration(seconds: 10));
    expect(backoff(20), const Duration(minutes: 15));
  });
  test("retry rules", () {
    expect(isRetryable(null), isTrue);
    expect(isRetryable(503), isTrue);
    expect(isRetryable(422), isFalse);
    expect(isConflict(409), isTrue);
  });
  test("version conflict is detected", () {
    expect(hasVersionConflict(baseVersion: 2, serverVersion: 3), isTrue);
    expect(hasVersionConflict(baseVersion: 3, serverVersion: 3), isFalse);
  });
}
