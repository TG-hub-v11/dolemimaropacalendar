import "package:dio/dio.dart";
import "package:uuid/uuid.dart";
import "../core/config.dart";
import "../core/token_store.dart";

class ApiClient {
  ApiClient(this._tokens, {Dio? dio})
      : dio = dio ?? Dio(BaseOptions(baseUrl: apiBaseUrl, connectTimeout: const Duration(seconds: 15), receiveTimeout: const Duration(seconds: 30), headers: {"Accept": "application/json"})) {
    this.dio.interceptors.add(InterceptorsWrapper(onRequest: (o, h) async {
      final t = await _tokens.read();
      if (t != null) o.headers["Authorization"] = "Bearer $t";
      h.next(o);
    }));
  }
  final TokenStore _tokens;
  final Dio dio;

  /// Mutations carry a stable operation id so server-side retries are deduplicated.
  Options idempotent(String operationId) => Options(headers: {"Idempotency-Key": operationId});
  static String newOperationId() => const Uuid().v4();

  Future<String> login(String email, String password, String device) async {
    final r = await dio.post("/auth/login", data: {"email": email, "password": password, "device_name": device});
    final token = r.data["data"]["token"] as String;
    await _tokens.write(token);
    return token;
  }

  Future<void> logout() async {
    try { await dio.post("/auth/logout"); } finally { await _tokens.clear(); }
  }
}
