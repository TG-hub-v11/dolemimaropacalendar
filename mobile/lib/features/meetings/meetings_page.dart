import "package:flutter/material.dart";

class MeetingsPage extends StatelessWidget {
  const MeetingsPage({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text("Meetings")),
        body: const Center(
          child: Padding(
            padding: EdgeInsets.all(24),
            child: Text("Meetings, minutes and action items are not available yet.\nMeeting events already appear in the Calendar.", textAlign: TextAlign.center),
          ),
        ),
      );
}
