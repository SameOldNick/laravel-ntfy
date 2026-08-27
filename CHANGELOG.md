# Changelog

All notable changes are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-08-26

### Added

- Native Laravel notification channel (`NtfyChannel`) for sending ntfy push notifications via `Notification`/`Notifiable`.
- Standalone `Ntfy` service and `Ntfy` facade for sending messages directly without a notifiable.
- `NtfyNotifiable` trait with a morph-one `ntfyConfiguration()` relationship for per-user destination resolution.
- `NtfyConfiguration` model and migration for storing per-user ntfy destinations (server, topic, credentials).
- `NtfyNotification` contract for defining ntfy message payloads.
- `MessageBuilder` for fluently constructing ntfy messages (title, body, priority, tags, actions, etc.).
- `MessageResponse` and `ServerInfo` DTOs for handling ntfy API responses.
- HTTP client with configurable server URL, authentication (basic/token), timeouts, SSL verification, and retry support.
- `NtfyFake` for testing notifications without hitting a real ntfy server.
- Configurable `config/ntfy.php` with environment-variable-driven defaults.
- English translations (`lang/en/messages.php`).
- Comprehensive Pest test suite (unit and feature) with PHPStan and Pint configured.
