import "package:flutter/material.dart";
import "package:flutter_riverpod/flutter_riverpod.dart";
import "package:go_router/go_router.dart";
import "../../data/providers.dart";

String? validateEmail(String? v) =>
    (v == null || !RegExp(r"^[^@\s]+@[^@\s]+\.[^@\s]+$").hasMatch(v.trim())) ? "Enter a valid email address" : null;
String? validatePassword(String? v) => (v == null || v.isEmpty) ? "Password is required" : null;

class LoginPage extends ConsumerStatefulWidget {
  const LoginPage({super.key});
  @override
  ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final _form = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _busy = false;
  String? _error;

  Future<void> _submit() async {
    if (!_form.currentState!.validate()) return;
    setState(() { _busy = true; _error = null; });
    try {
      await ref.read(apiClientProvider).login(_email.text.trim(), _password.text, "mobile");
      if (mounted) context.go("/home");
    } catch (_) {
      setState(() => _error = "Sign-in failed. Check your credentials and connection.");
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text("DOLE MIMAROPA Calendar")),
        body: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: Form(
              key: _form,
              child: ListView(padding: const EdgeInsets.all(24), shrinkWrap: true, children: [
                TextFormField(controller: _email, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: "Email"), validator: validateEmail, autofillHints: const [AutofillHints.username]),
                const SizedBox(height: 16),
                TextFormField(controller: _password, obscureText: true, decoration: const InputDecoration(labelText: "Password"), validator: validatePassword, autofillHints: const [AutofillHints.password]),
                if (_error != null) Padding(padding: const EdgeInsets.only(top: 12), child: Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error))),
                const SizedBox(height: 24),
                FilledButton(onPressed: _busy ? null : _submit, child: _busy ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2)) : const Text("Sign in")),
              ]),
            ),
          ),
        ),
      );
}
