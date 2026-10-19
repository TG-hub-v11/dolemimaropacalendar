import "package:flutter_riverpod/flutter_riverpod.dart";
import "../core/token_store.dart";
import "api_client.dart";

final tokenStoreProvider = Provider((_) => TokenStore());
final apiClientProvider = Provider((ref) => ApiClient(ref.read(tokenStoreProvider)));

enum AuthStatus { unknown, signedOut, signedIn }

class SessionNotifier extends Notifier<AuthStatus> {
  @override
  AuthStatus build() {
    _init();
    return AuthStatus.unknown;
  }

  Future<void> _init() async {
    final t = await ref.read(tokenStoreProvider).read();
    state = t == null ? AuthStatus.signedOut : AuthStatus.signedIn;
  }

  Future<void> signIn(String email, String password) async {
    await ref.read(apiClientProvider).login(email, password, "mobile");
    state = AuthStatus.signedIn;
  }

  Future<void> signOut() async {
    try {
      await ref.read(apiClientProvider).logout();
    } catch (_) {
      await ref.read(tokenStoreProvider).clear();
    }
    state = AuthStatus.signedOut;
  }
}

final sessionProvider = NotifierProvider<SessionNotifier, AuthStatus>(SessionNotifier.new);

typedef Json = Map<String, dynamic>;

final meProvider = FutureProvider.autoDispose<Json>((ref) async {
  final r = await ref.read(apiClientProvider).dio.get("/me");
  return Map<String, dynamic>.from(r.data["data"] as Map);
});

final tasksProvider = FutureProvider.autoDispose<List<Json>>((ref) async {
  final r = await ref.read(apiClientProvider).dio.get("/tasks", queryParameters: {"per_page": 100});
  return (r.data["data"] as List).map((e) => Map<String, dynamic>.from(e as Map)).toList();
});

final eventsProvider = FutureProvider.autoDispose<List<Json>>((ref) async {
  final from = DateTime.now().subtract(const Duration(days: 1)).toUtc().toIso8601String();
  final r = await ref.read(apiClientProvider).dio.get("/events", queryParameters: {"per_page": 200, "from": from});
  return (r.data["data"] as List).map((e) => Map<String, dynamic>.from(e as Map)).toList();
});
