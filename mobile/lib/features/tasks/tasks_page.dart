import "package:dio/dio.dart";
import "package:flutter/material.dart";
import "package:flutter_riverpod/flutter_riverpod.dart";
import "package:go_router/go_router.dart";
import "../../core/format.dart";
import "../../data/api_client.dart";
import "../../data/providers.dart";
import "../shell/async_view.dart";
import "task_rules.dart";

class TasksPage extends ConsumerStatefulWidget {
  const TasksPage({super.key});
  @override
  ConsumerState<TasksPage> createState() => _TasksPageState();
}

class _TasksPageState extends ConsumerState<TasksPage> {
  String _filter = "open";

  @override
  Widget build(BuildContext context) {
    final tasks = ref.watch(tasksProvider);
    final perms = (ref.watch(meProvider).value?["permissions"] as List?)?.cast<String>() ?? const <String>[];
    return Scaffold(
      appBar: AppBar(title: const Text("Tasks")),
      floatingActionButton: perms.contains("task.manage")
          ? FloatingActionButton.extended(onPressed: () => _create(context), icon: const Icon(Icons.add), label: const Text("New task"))
          : null,
      body: Column(children: [
        Padding(
          padding: const EdgeInsets.all(8),
          child: Wrap(spacing: 8, children: [
            for (final f in const {"open": "Open", "overdue": "Overdue", "done": "Completed", "all": "All"}.entries)
              ChoiceChip(label: Text(f.value), selected: _filter == f.key, onSelected: (_) => setState(() => _filter = f.key)),
          ]),
        ),
        Expanded(
          child: AsyncView<List<Json>>(
            value: tasks,
            onRetry: () => ref.invalidate(tasksProvider),
            builder: (all) {
              final now = DateTime.now();
              final list = all.where((t) {
                final s = t["status"] as String;
                switch (_filter) {
                  case "open":
                    return isOpenStatus(s);
                  case "overdue":
                    return isOverdue(t, now);
                  case "done":
                    return s == "completed";
                  default:
                    return true;
                }
              }).toList();
              if (list.isEmpty) return const Center(child: Text("No tasks here."));
              return RefreshIndicator(
                onRefresh: () async => ref.invalidate(tasksProvider),
                child: ListView.separated(
                  itemCount: list.length,
                  separatorBuilder: (_, __) => const Divider(height: 1),
                  itemBuilder: (_, i) {
                    final t = list[i];
                    final over = isOverdue(t, now);
                    return ListTile(
                      title: Text(t["title"] as String),
                      subtitle: Text("${statusLabels[t["status"]] ?? t["status"]} · ${t["priority"]}"
                          "${t["due_date"] != null ? " · due ${t["due_date"]}" : ""}"),
                      trailing: over ? Icon(Icons.warning_amber, color: Theme.of(context).colorScheme.error) : const Icon(Icons.chevron_right),
                      onTap: () => context.push("/tasks/${t["id"]}"),
                    );
                  },
                ),
              );
            },
          ),
        ),
      ]),
    );
  }

  Future<void> _create(BuildContext context) async {
    final me = ref.read(meProvider).value;
    final officeId = me?["primary_office_id"];
    final title = TextEditingController();
    final desc = TextEditingController();
    DateTime? due;
    final created = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setS) => AlertDialog(
          title: const Text("New task"),
          content: SingleChildScrollView(
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              TextField(controller: title, decoration: const InputDecoration(labelText: "Title")),
              const SizedBox(height: 8),
              TextField(controller: desc, maxLines: 3, decoration: const InputDecoration(labelText: "Description")),
              const SizedBox(height: 8),
              OutlinedButton.icon(
                icon: const Icon(Icons.event),
                label: Text(due == null ? "Set due date" : dateLabel(due!)),
                onPressed: () async {
                  final d = await showDatePicker(context: ctx, initialDate: DateTime.now(), firstDate: DateTime.now(), lastDate: DateTime.now().add(const Duration(days: 1095)));
                  if (d != null) setS(() => due = d);
                },
              ),
            ]),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text("Cancel")),
            FilledButton(
                onPressed: () {
                  if (title.text.trim().isEmpty) return;
                  Navigator.pop(ctx, true);
                },
                child: const Text("Create")),
          ],
        ),
      ),
    );
    if (created != true || !mounted) return;
    try {
      final opId = ApiClient.newOperationId();
      await ref.read(apiClientProvider).dio.post(
        "/tasks",
        data: {
          "client_uuid": opId,
          "responsible_office_id": officeId,
          "title": title.text.trim(),
          "description": desc.text.trim().isEmpty ? null : desc.text.trim(),
          if (due != null) "due_date": due!.toIso8601String().substring(0, 10),
        },
        options: ref.read(apiClientProvider).idempotent(opId),
      );
      ref.invalidate(tasksProvider);
    } on DioException catch (e) {
      if (mounted) {
        final msg = e.response?.statusCode == 403 ? "You are not allowed to create tasks in this office." : friendlyError(e);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
      }
    }
  }
}
