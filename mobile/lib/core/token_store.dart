import "package:flutter_secure_storage/flutter_secure_storage.dart";

/// Tokens live only in platform secure storage (Keychain / Keystore-backed).
class TokenStore {
  TokenStore([FlutterSecureStorage? s]) : _s = s ?? const FlutterSecureStorage();
  final FlutterSecureStorage _s;
  static const _k = "api_token";
  Future<String?> read() => _s.read(key: _k);
  Future<void> write(String t) => _s.write(key: _k, value: t);
  Future<void> clear() => _s.delete(key: _k);
}
