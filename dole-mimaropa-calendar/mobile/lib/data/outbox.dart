import "dart:convert";
import "package:dio/dio.dart";
import "package:sqflite/sqflite.dart";
import "api_client.dart";
import "sync_policy.dart";

class Outbox {
  Outbox(this._db, this._api);
  final Database _db;
  final ApiClient _api;

  Future<String> enqueue(String method, String path, Map<String, dynamic>? body, {int? baseVersion}) async {
    final id = ApiClient.newOperationId();
    await _db.insert("outbox", {
      "operation_id": id, "method": method, "path": path, "body": body == null ? null : jsonEncode(body),
      "base_version": baseVersion, "created_at": DateTime.now().millisecondsSinceEpoch,
    });
    return id;
  }

  Future<int> pendingCount() async =>
      Sqflite.firstIntValue(await _db.rawQuery("SELECT COUNT(*) FROM outbox WHERE status != ?", ["failed"])) ?? 0;

  /// Sends due operations in creation order. Stops at the first transient failure to preserve ordering.
  Future<void> flush({bool manual = false}) async {
    final now = DateTime.now().millisecondsSinceEpoch;
    final rows = await _db.query("outbox",
        where: "status = pending AND (next_attempt_at <= ? OR ?)", whereArgs: [now, manual ? 1 : 0], orderBy: "created_at");
    for (final r in rows) {
      final id = r["operation_id"] as String;
      try {
        await _api.dio.request(r["path"] as String,
            data: r["body"] == null ? null : jsonDecode(r["body"] as String),
            options: _api.idempotent(id).copyWith(method: r["method"] as String));
        await _db.delete("outbox", where: "operation_id = ?", whereArgs: [id]);
      } on DioException catch (e) {
        final code = e.response?.statusCode;
        if (isConflict(code)) {
          await _db.update("outbox", {"status": "conflict", "last_error": "Server has a newer version"}, where: "operation_id = ?", whereArgs: [id]);
        } else if (isRetryable(code)) {
          final attempts = (r["attempts"] as int) + 1;
          await _db.update("outbox", {
            "attempts": attempts, "last_error": e.message,
            "next_attempt_at": DateTime.now().add(backoff(attempts)).millisecondsSinceEpoch,
          }, where: "operation_id = ?", whereArgs: [id]);
          return;
        } else {
          await _db.update("outbox", {"status": "failed", "last_error": "HTTP $code"}, where: "operation_id = ?", whereArgs: [id]);
        }
      }
    }
  }
}
