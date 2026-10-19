import "package:flutter_riverpod/flutter_riverpod.dart";
import "../core/token_store.dart";
import "api_client.dart";

final tokenStoreProvider = Provider((_) => TokenStore());
final apiClientProvider = Provider((ref) => ApiClient(ref.read(tokenStoreProvider)));
