import "package:path/path.dart" as p;
import "package:sqflite/sqflite.dart";

/// Local cache + outbox. `sync_state` distinguishes confirmed server rows from pending local edits.
/// RISK: data at rest on the device is not encrypted by default; see docs/SECURITY.md (lost/shared phone).
class LocalDb {
  static Future<Database> open() async => openDatabase(
        p.join(await getDatabasesPath(), "dole_calendar.db"),
        version: 1,
        onCreate: (db, _) async {
          await db.execute("""CREATE TABLE tasks(
            id INTEGER PRIMARY KEY, client_uuid TEXT UNIQUE NOT NULL, json TEXT NOT NULL,
            server_version INTEGER, sync_state TEXT NOT NULL DEFAULT synced)""");
          await db.execute("""CREATE TABLE events(
            id INTEGER PRIMARY KEY, client_uuid TEXT UNIQUE NOT NULL, json TEXT NOT NULL,
            server_version INTEGER, sync_state TEXT NOT NULL DEFAULT synced)""");
          await db.execute("""CREATE TABLE outbox(
            operation_id TEXT PRIMARY KEY, method TEXT NOT NULL, path TEXT NOT NULL, body TEXT,
            base_version INTEGER, attempts INTEGER NOT NULL DEFAULT 0, next_attempt_at INTEGER NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT pending, last_error TEXT, created_at INTEGER NOT NULL)""");
        },
      );
}
