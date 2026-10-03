# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.1.4] - 2026-10-03

### Fixed
- Luma form: name and email are now required fields for guests, so an empty or invalid submit is stopped in the browser with focus on the first invalid field instead of posting and showing only a generic message (the fields stay optional for logged-in customers, whose guest fields are hidden). The result message is a polite live region and its close button has a label.
