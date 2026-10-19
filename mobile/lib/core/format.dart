import "package:intl/intl.dart";

String dateLabel(DateTime d) => DateFormat("EEE, MMM d, y").format(d);
String timeLabel(DateTime d) => DateFormat("h:mm a").format(d);

/// Human-readable message from a Dio error or any exception, without leaking internals.
String friendlyError(Object e) {
  final s = e.toString();
  if (s.contains("SocketException") || s.contains("connection") || s.contains("Connection")) {
    return "Cannot reach the server. Check your connection and try again.";
  }
  return "Something went wrong. Please try again.";
}
