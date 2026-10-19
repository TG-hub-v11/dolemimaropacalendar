import "package:flutter/material.dart";
import "package:flutter_riverpod/flutter_riverpod.dart";
import "../../data/providers.dart";

class MorePage extends ConsumerWidget {
  const MorePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final me = ref.watch(meProvider).value;
    Widget soon(IconData i, String t) => ListTile(leading: Icon(i), title: Text(t), subtitle: const Text("Not available yet"), enabled: false);
    return Scaffold(
      appBar: AppBar(title: const Text("More")),
      body: ListView(children: [
        if (me != null)
          ListTile(leading: const CircleAvatar(child: Icon(Icons.person)), title: Text("${me["name"]}"), subtitle: Text("${me["email"]}")),
        const Divider(),
        soon(Icons.campaign, "Announcements"),
        soon(Icons.notifications, "Notifications"),
        soon(Icons.bar_chart, "Reports"),
        soon(Icons.settings, "Settings"),
        const Divider(),
        ListTile(
          leading: const Icon(Icons.logout),
          title: const Text("Sign out"),
          onTap: () async {
            final ok = await showDialog<bool>(
              context: context,
              builder: (ctx) => AlertDialog(
                title: const Text("Sign out?"),
                actions: [
                  TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text("Cancel")),
                  FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text("Sign out")),
                ],
              ),
            );
            if (ok == true) await ref.read(sessionProvider.notifier).signOut();
          },
        ),
      ]),
    );
  }
}
