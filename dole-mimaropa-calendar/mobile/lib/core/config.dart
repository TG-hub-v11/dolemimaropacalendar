/// API base URL is injected at build time: flutter run --dart-define=API_BASE_URL=https://host/api/v1
/// No credentials or secrets belong in this app.
const apiBaseUrl = String.fromEnvironment("API_BASE_URL", defaultValue: "http://10.0.2.2:8000/api/v1");
const orgTimeZone = "Asia/Manila";
