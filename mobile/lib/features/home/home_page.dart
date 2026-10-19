import "package:flutter/material.dart";
import "package:flutter_riverpod/flutter_riverpod.dart";
import "../../core/format.dart";
import "../../data/providers.dart";
import "../shell/async_view.dart";
import "../tasks/task_rules.dart";

class HomePage extends ConsumerWidget {
  const HomePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final tasks = ref.watch(tasksProvider);
    final events = ref.watch(eventsProvider);
    final me = ref.watch(meProvider).value;
    void refresh() {
      ref.invalidate(tasksProvider);
      ref.invalidate(eventsProvider);
    }

    return Scaffold(
      appBar: AppBar(title: const Text("Home"), actions: [IconButton(onPressed: refresh, icon: const Icon(Icons.refresh))]),
      body: AsyncView<List<Json>>(
        value: tasks,
        onRetry: refresh,
        builder: (all) {
          final now = DateTime.now();
          final myId = me?["id"];
          final mine = all.where((t) => t["assignee_id"] == myId && isOpenStatus(t["status"] as String)).toList();
          final pending = all.where((t) => t["status"] == "pending").length;
          final overdue = all.where((t) => isOverdue(t, now)).length;
          final soon = all.where((t) {
            final due = t["due_date"] as String?;
            if (due == null || !isOpenStatus(t["status"] as String)) return false;
            final d = DateTime.parse(due);
            return !isOverdue(t, now) && d.isBefore(now.add(const Duration(days: 7)));
          }).length;
          final upcoming = (events.value ?? []).where((e) => e["status"] != "cancelled").take(5).toList();
          return RefreshIndicator(
            onRefresh: () async => refresh(),
            child: ListView(padding: const EdgeInsets.all(16), children: [
              if (me != null) Text("Hello, ${me["name"]}", style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 12),
              Wrap(spacing: 12, runSpacing: 12, children: [
                _Stat("Assigned to me", mine.length, Icons.person),
                _Stat("Pending", pending, Icons.hourglass_empty),
                _Stat("Overdue", overdue, Icons.warning_amber, alert: overdue > 0),
                _Stat("Due in 7 days", soon, Icons.event_available),
              ]),
              const SizedBox(height: 20),
              Text("Upcoming events", style: Theme.of(context).textTheme.titleMedium),
              if (upcoming.isEmpty) const Padding(padding: EdgeInsets.all(12), child: Text("No upcoming events.")),
              for (final e in upcoming)
                ListTile(
                  leading: const Icon(Icons.event),
                  title: Text(e["title"] as String),
                  subtitle: Text(_when(e)),
                ),
              const SizedBox(height: 12),
              const Card(
                child: ListTile(
                  leading: Icon(Icons.cloud_done),
                  title: Text("Online"),
                  subtitle: Text("Showing live server data. Offline editing is not available yet."),
                ),
              ),
            ]),
          );
        },
      ),
    );
  }

  static String _when(Json e) {
    if (e["is_all_day"] == true && e["start_date"] != null) return "${dateLabel(DateTime.parse(e["start_date"] as String))} · all day";
    final s = DateTime.parse(e["starts_at"] as String).toLocal();
    return "${dateLabel(s)} · ${timeLabel(s)}";
  }
}

class _Stat extends StatelessWidget {
  const _Stat(this.label, this.value, this.icon, {this.alert = false});
  final String label;
  final int value;
  final IconData icon;
  final bool alert;

  @override
  Widget build(BuildContext context) {
    final color = alert ? Theme.of(context).colorScheme.error : Theme.of(context).colorScheme.primary;
    return SizedBox(
      width: 160,
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Icon(icon, color: color),
            const SizedBox(height: 8),
            Text("$value", style: Theme.of(context).textTheme.headlineMedium?.copyWith(color: color)),
            Text(label),
          ]),
        ),
      ),
    );
  }
}
