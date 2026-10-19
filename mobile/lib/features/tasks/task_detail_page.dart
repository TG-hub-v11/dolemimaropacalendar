import "package:dio/dio.dart";
import "package:flutter/material.dart";
import "package:flutter_riverpod/flutter_riverpod.dart";
import "../../core/format.dart";
import "../../data/api_client.dart";
import "../../data/providers.dart";
import "task_rules.dart";

class TaskDetailPage extends ConsumerStatefulWidget {
  const TaskDetailPage({super.key, required this.taskId});
  final int taskId;
  @override
  ConsumerState<TaskDetailPage> createState() => _TaskDetailPageState();
}

class _TaskDetailPageState extends ConsumerState<TaskDetailPage> {
  bool _busy = false;

  Future<void> _move(Json task, String next) async {
    String? notes;
    if (next == "completed") {
      notes = await _askNotes();
      if (notes == null) return;
    } else if (next == "cancelled") {
      final ok = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text("Cancel this task?"),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text("Keep")),
            FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text("Cancel task")),
          ],
        ),
      );
      if (ok != true) return;
    }
    setState(() => _busy = true);
    try {
      final opId = ApiClient.newOperationId();
      await ref.read(apiClientProvider).dio.post(
        "/tasks/${task["id"]}/transition",
        data: {"status": next, "version": task["version"], if (notes != null && notes.isNotEmpty) "notes": notes},
        options: ref.read(apiClientProvider).idempotent(opId),
      );
      ref.invalidate(tasksProvider);
    } on DioException catch (e) {
      final code = e.response?.statusCode;
      final msg = code == 403
          ? "You are not allowed to change this task."
          : code == 422
              ? "That change is not allowed or the task was updated by someone else. Pull to refresh and try again."
              : friendlyError(e);
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<String?> _askNotes() {
    final c = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text("Complete task"),
        content: TextField(controller: c, maxLines: 3, decoration: const InputDecoration(labelText: "Completion notes (optional)")),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text("Cancel")),
          FilledButton(onPressed: () => Navigator.pop(ctx, c.text.trim()), child: const Text("Mark completed")),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final tasks = ref.watch(tasksProvider);
    return Scaffold(
      appBar: AppBar(title: const Text("Task")),
      body: tasks.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text(friendlyError(e))),
        data: (list) {
          final matches = list.where((t) => t["id"] == widget.taskId);
          if (matches.isEmpty) return const Center(child: Text("Task not found or no longer visible."));
          final t = matches.first;
          final next = taskTransitions[t["status"]] ?? const <String>[];
          return ListView(padding: const EdgeInsets.all(16), children: [
            Text(t["title"] as String, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 8),
            Wrap(spacing: 8, children: [
              Chip(label: Text(statusLabels[t["status"]] ?? "${t["status"]}")),
              Chip(label: Text("Priority: ${t["priority"]}")),
              if (t["due_date"] != null) Chip(label: Text("Due ${t["due_date"]}")),
            ]),
            if ((t["description"] as String?)?.isNotEmpty ?? false) ...[
              const SizedBox(height: 12),
              Text(t["description"] as String),
            ],
            if (t["review_status"] == "awaiting")
              const Padding(padding: EdgeInsets.only(top: 12), child: Text("Awaiting review by a verifier.")),
            const SizedBox(height: 20),
            if (next.isNotEmpty) Text("Change status", style: Theme.of(context).textTheme.titleSmall),
            const SizedBox(height: 8),
            Wrap(spacing: 8, runSpacing: 8, children: [
              for (final s in next)
                FilledButton.tonal(onPressed: _busy ? null : () => _move(t, s), child: Text(statusLabels[s] ?? s)),
            ]),
          ]);
        },
      ),
    );
  }
}
