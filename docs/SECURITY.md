# Security notes
- Authorization is enforced on the server (policies + VisibilityService); the app UI is not a security boundary.
- Tokens: Sanctum personal access tokens, 14-day expiry, revoked on logout/deactivation; stored in platform secure storage.
- Local SQLite cache is NOT encrypted yet. A lost or shared phone could expose cached schedules/tasks. Before production: add SQLCipher or minimise cached fields, enforce device lock, and add remote token revocation.
- No claim of Data Privacy Act compliance; requires review by the organization.
- Push notifications and identity-provider SSO are not configured or tested.
