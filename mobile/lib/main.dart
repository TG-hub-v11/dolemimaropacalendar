import "package:flutter/material.dart";
import "package:flutter_riverpod/flutter_riverpod.dart";
import "package:go_router/go_router.dart";
import "core/theme.dart";
import "data/providers.dart";
import "features/auth/login_page.dart";
import "features/calendar/calendar_page.dart";
import "features/home/home_page.dart";
import "features/meetings/meetings_page.dart";
import "features/more/more_page.dart";
import "features/shell/shell_page.dart";
import "features/tasks/task_detail_page.dart";
import "features/tasks/tasks_page.dart";

final routerProvider = Provider<GoRouter>((ref) {
  final status = ref.watch(sessionProvider);
  return GoRouter(
    initialLocation: "/home",
    redirect: (context, state) {
      final loc = state.matchedLocation;
      switch (status) {
        case AuthStatus.unknown:
          return loc == "/splash" ? null : "/splash";
        case AuthStatus.signedOut:
          return loc == "/login" ? null : "/login";
        case AuthStatus.signedIn:
          return (loc == "/login" || loc == "/splash") ? "/home" : null;
      }
    },
    routes: [
      GoRoute(path: "/splash", builder: (_, __) => const Scaffold(body: Center(child: CircularProgressIndicator()))),
      GoRoute(path: "/login", builder: (_, __) => const LoginPage()),
      GoRoute(path: "/tasks/:id", builder: (_, s) => TaskDetailPage(taskId: int.parse(s.pathParameters["id"]!))),
      StatefulShellRoute.indexedStack(
        builder: (_, __, shell) => ShellPage(shell: shell),
        branches: [
          StatefulShellBranch(routes: [GoRoute(path: "/home", builder: (_, __) => const HomePage())]),
          StatefulShellBranch(routes: [GoRoute(path: "/calendar", builder: (_, __) => const CalendarPage())]),
          StatefulShellBranch(routes: [GoRoute(path: "/tasks", builder: (_, __) => const TasksPage())]),
          StatefulShellBranch(routes: [GoRoute(path: "/meetings", builder: (_, __) => const MeetingsPage())]),
          StatefulShellBranch(routes: [GoRoute(path: "/more", builder: (_, __) => const MorePage())]),
        ],
      ),
    ],
  );
});

void main() => runApp(const ProviderScope(child: App()));

class App extends ConsumerWidget {
  const App({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => MaterialApp.router(
        title: "DOLE MIMAROPA Calendar",
        theme: buildTheme(Brightness.light),
        darkTheme: buildTheme(Brightness.dark),
        routerConfig: ref.watch(routerProvider),
      );
}
