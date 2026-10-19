import "package:flutter/material.dart";
import "package:flutter_riverpod/flutter_riverpod.dart";
import "../../core/format.dart";
import "../../data/providers.dart";
import "../shell/async_view.dart";

/// Agenda view: events grouped by day. Month/week/day grids are not implemented yet.
class CalendarPage extends ConsumerWidget {
  const CalendarPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final events = ref.watch(eventsProvider);
    return Scaffold(
      appBar: AppBar(title: const Text("Calendar")),
      body: AsyncView<List<Json>>(
        value: events,
        onRetry: () => ref.invalidate(eventsProvider),
        builder: (list) {
          if (list.isEmpty) return const Center(child: Text("No events to show."));
          final byDay = <DateTime, List<Json>>{};
          for (final e in list) {
            final d = e["is_all_day"] == true && e["start_date"] != null
                ? DateTime.parse(e["start_date"] as String)
                : DateTime.parse(e["starts_at"] as String).toLocal();
            byDay.putIfAbsent(DateTime(d.year, d.month, d.day), () => []).add(e);
          }
          final days = byDay.keys.toList()..sort();
          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(eventsProvider),
            child: ListView(children: [
              for (final day in days) ...[
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 4),
                  child: Text(dateLabel(day), style: Theme.of(context).textTheme.titleSmall),
                ),
                for (final e in byDay[day]!)
                  ListTile(
                    leading: Icon(e["status"] == "cancelled" ? Icons.event_busy : Icons.event),
                    title: Text(e["title"] as String,
                        style: e["status"] == "cancelled" ? const TextStyle(decoration: TextDecoration.lineThrough) : null),
                    subtitle: Text(e["is_all_day"] == true
                        ? "All day"
                        : "${timeLabel(DateTime.parse(e["starts_at"] as String).toLocal())}${e["venue"] != null ? " · ${e["venue"]}" : ""}"),
                    trailing: e["status"] == "cancelled" ? const Chip(label: Text("Cancelled")) : null,
                  ),
              ],
            ]),
          );
        },
      ),
    );
  }
}
