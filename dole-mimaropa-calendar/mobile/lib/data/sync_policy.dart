/// Pure, testable sync rules.
enum OpStatus { pending, conflict, failed }

/// Bounded exponential backoff: 5s, 10s, 20s ... capped at 15 minutes.
Duration backoff(int attempts) {
  final secs = 5 * (1 << attempts.clamp(0, 10));
  return Duration(seconds: secs > 900 ? 900 : secs);
}

/// Retry transient failures only; 4xx (except 408/429) are permanent, 409 is a conflict for the user to review.
bool isRetryable(int? statusCode) =>
    statusCode == null || statusCode >= 500 || statusCode == 408 || statusCode == 429;

bool isConflict(int? statusCode) => statusCode == 409;

/// Server record is newer than the version the offline edit was based on: never silently overwrite.
bool hasVersionConflict({required int baseVersion, required int serverVersion}) => serverVersion > baseVersion;
