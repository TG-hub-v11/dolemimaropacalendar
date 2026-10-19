import "package:flutter/material.dart";
import "package:flutter_riverpod/flutter_riverpod.dart";
import "package:go_router/go_router.dart";
import "core/theme.dart";
import "features/auth/login_page.dart";

final _router = GoRouter(routes: [
  GoRoute(path: "/", builder: (_, __) => const LoginPage()),
  // TODO Phase 3+: replace with a shell route (Home/Calendar/Tasks/Meetings/More)
  GoRoute(path: "/home", builder: (_, __) => const Scaffold(body: Center(child: Text("Signed in. Dashboard not implemented yet.")))),
]);

void main() => runApp(const ProviderScope(child: App()));

class App extends StatelessWidget {
  const App({super.key});
  @override
  Widget build(BuildContext context) => MaterialApp.router(
        title: "DOLE MIMAROPA Calendar",
        theme: buildTheme(Brightness.light),
        darkTheme: buildTheme(Brightness.dark),
        routerConfig: _router,
      );
}
