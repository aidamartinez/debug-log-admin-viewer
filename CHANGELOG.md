# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2025-03-27
### Added
- Important disclaimer about wp-config.php modifications and backup recommendations
- Enhanced UI for backup location display
- One-click copy functionality for backup location path

### Changed
- Label text change for brevity


## [1.0.0] - 2025-03-12
### Added
- WordPress debug settings management interface
- Advanced debug log viewer with filtering and search capabilities
- Automatic wp-config.php backup system (keeps 5 most recent backups)
- Security features:
  - Automatic backup creation before wp-config.php modifications
  - Proper file permissions management
  - Nonce verification for all actions
  - Capability checks for administrative functions
- Log viewer features:
  - Error type filtering
  - Pagination (100 entries per page)
  - Copy to clipboard functionality
  - Stack trace display
  - Clear log file option
- Support for all WordPress debug constants:
  - WP_DEBUG
  - WP_DEBUG_LOG
  - WP_DEBUG_DISPLAY

[1.0.1]: https://github.com/aidamartinez/debug-log-admin-viewer/releases/tag/v1.0.1
[1.0.0]: https://github.com/aidamartinez/debug-log-admin-viewer/releases/tag/v1.0.0
