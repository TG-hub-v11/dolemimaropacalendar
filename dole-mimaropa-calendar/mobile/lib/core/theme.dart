import "package:flutter/material.dart";

const _blue = Color(0xFF0B3C8C);
const _yellow = Color(0xFFF2B705);
const _red = Color(0xFFC62828);

ThemeData buildTheme(Brightness b) {
  final scheme = ColorScheme.fromSeed(seedColor: _blue, brightness: b, primary: _blue, secondary: _yellow, error: _red);
  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    scaffoldBackgroundColor: b == Brightness.light ? const Color(0xFFF4F6F9) : null,
    appBarTheme: AppBarTheme(backgroundColor: scheme.primary, foregroundColor: scheme.onPrimary),
    inputDecorationTheme: const InputDecorationTheme(border: OutlineInputBorder()),
  );
}
