# Changelog

All notable changes are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0] - 2026-11-10

### Added

- `ntfy.model.class`, `ntfy.model.name`, `ntfy.model.type`, `ntfy.model.id`, and `ntfy.model.local_key` config options, so the configuration model used by `NtfyNotifiable` can be replaced. A custom model must extend `Illuminate\Database\Eloquent\Model` and provide a `toServerInfo()` method plus an `enabled` flag.
- `ResolvesNtfyConfiguration` contract and `NtfyConfigurationResolver` service, bound in the container, for resolving the configuration model and the notifiable relationship. The resolver throws an `InvalidArgumentException` when the configured model class is not an Eloquent model.
- `enabled` flag on the `ntfy_configurations` table, allowing ntfy to be disabled per notifiable. The column is added by the new `add_enabled_to_ntfy_configurations_table` migration and defaults to `true`, so existing rows stay enabled (see [Upgrade notes](#upgrade-notes)).
- `enabled` attribute on `NtfyConfiguration`; it is mass assignable and cast to a boolean.
- Unit tests covering the configuration resolver, the configurable model class, and the per-notifiable `enabled` flag.

### Changed

- `NtfyChannel::isEnabled()` accepts an optional notifiable and now also honours the `enabled` flag of its stored configuration. Notifiables without an Eloquent configuration (and notifiables without a stored configuration) are still gated by the channel-level `ntfy.enabled` option only.
- The `NtfyNotifiable` trait resolves its relationship through the container instead of using `NtfyConfiguration` directly.

### Fixed

- Passing a notifiable that is not an Eloquent model to `NtfyChannel::send()` no longer throws an `InvalidArgumentException`; the channel-level setting remains the only gate for those notifiables.

### Upgrade notes

- Publish and run the package migrations again to add the `enabled` column to the `ntfy_configurations` table:

  ```bash
  php artisan vendor:publish --tag=ntfy-migrations
  php artisan migrate
  ```

  Delivery is unaffected until then (rows without an `enabled` value are treated as enabled), but the per-notifiable flag cannot be used before the column exists. Existing rows are migrated as enabled.

- Subclasses of `NtfyChannel` that override `isEnabled()` must accept the new optional argument: `isEnabled(?object $notifiable = null): bool`.

## [2.1.0] - 2026-10-02

### Added

- `AuthMethod` enum (`Login`, `Token`, `None`) describing how a server authenticates requests.
- `ServerInfo::getAuthMethod()` to resolve the authentication method from the configured credentials.
- `ServerInfo::hasAuth()` to check whether any authentication information is present.
- `ServerInfo::hasUrl()` to validate that the server URL is a supported HTTP(S) URL.
- `ServerInfo::fromArray()` now accepts an optional `auth_method` key (an `AuthMethod` case or its string value), which is ignored because the method is derived from the credentials that are present.
- `InvalidServerUrlException`, thrown when a server URL is not a valid HTTP(S) URL; it extends `Ntfy\Exception\NtfyException`, so existing catch blocks continue to work.
- Unit tests covering `ServerInfo` construction (config, array, and factory methods), URL validation, and authentication detection.

### Changed

- `Ntfy::createClient()` now uses `ServerInfo::getAuthMethod()` instead of inlining credential checks.
- `NtfyConfiguration::auth_method` now returns an `AuthMethod` enum case instead of a string; the JSON form still serializes to the backed string value for API consumers.
- Authentication checks treat empty-string credentials as unset, so blank config values no longer select an authentication method.

### Fixed

- `Client` no longer sends empty `X-Tags` or `X-Actions` headers when the corresponding arrays are empty.

## [2.0.0] - 2026-08-30

### Added

- Attachment support via `MessageBuilder`: `attachStorage()`, `attachFile()`, `attachContent()`, and `removeAttachment()` methods.
- `MessageWithAttachment` DTO; `MessageBuilder::build()` now returns `Message|MessageWithAttachment` when an attachment is present.
- Binary publish (HTTP `PUT`) support in `Client` for sending attachments as the request body with the appropriate ntfy headers.
- `FakeMessageResponse` DTO with `attachment()` and `getAttachmentContent()` accessors; `NtfyFake` now records attachment details for test assertions.
- Composer `artisan` script for running Testbench commands.

### Changed

- **Breaking:** renamed `MessageBuilder::attach()` to `attachURL()` to align with the underlying `Ntfy\Message` method.
- `NtfyChannel`, `NtfyNotification`, `Ntfy` service, and `Client` now accept `Message|MessageWithAttachment`.
- Added type hints to the `MessageBuilder::__call()` method parameters.

### Fixed

- `NtfyNotifiable::resolveNtfyRoute()` now passes the notifiable instance to the default callback when no ntfy configuration is found.

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
