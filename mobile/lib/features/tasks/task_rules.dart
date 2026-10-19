/// Mirrors backend TaskStatus transitions for the UI only. The server remains the authority.
const taskTransitions = <String, List<String>>{
  "pending": ["in_progress", "blocked", "cancelled"],
  "in_progress": ["blocked", "completed", "cancelled", "pending"],
  "blocked": ["in_progress", "cancelled"],
  "completed": ["in_progress"],
  "cancelled": <String>[],
};

const statusLabels = <String, String>{
  "pending": "Pending",
  "in_progress": "In progress",
  "blocked": "Blocked",
  "completed": "Completed",
  "cancelled": "Cancelled",
};

bool isOpenStatus(String s) => s != "completed" && s != "cancelled";

/// Overdue = due date before today (local date) and not finished.
bool isOverdue(Map<String, dynamic> task, DateTime now) {
  final due = task["due_date"] as String?;
  if (due == null || !isOpenStatus(task["status"] as String)) return false;
  final d = DateTime.parse(due);
  return d.isBefore(DateTime(now.year, now.month, now.day));
}
