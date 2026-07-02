# Changelog

All notable changes to Static Push are documented here.

## [1.1.0] — 2026-06-10

### Changed
- **GitHub push is now atomic** — all files are committed in a single git commit using the GitHub Git Tree API (create blobs → build tree → create commit → update ref). Previously each file was pushed as a separate commit via the Contents API, which triggered one Cloudflare Pages deployment per file. Now every push produces exactly one commit and one deployment.
- Generate result now shows page count, asset count, and elapsed time.
- Push result now shows the commit SHA as a clickable link to GitHub.

### Added
- **Detailed log panel** — a collapsible dark-terminal log panel appears after Generate and Push, showing every step with error/warning lines highlighted.
- **Status cycling messages** — the status bar cycles through contextual messages while an operation is running (e.g. "Creating file blobs on GitHub…", "Building git tree…").
- Crawler now reports elapsed time and appends a completion summary to the log.
- Push log includes branch name, commit SHA, blob count, and duration at each step.
- Errors and warnings from crawl are surfaced in the log panel with colour-coded lines.
- Branch is auto-created on first push if it does not exist yet.

### Fixed
- Multiple Cloudflare Pages deployments triggered by a single WordPress push (caused by per-file commits). Now always exactly one deployment per push.

---

## [1.0.0] — 2026-06-10

### Added
- Initial release.
- Full site crawler — crawls all posts, pages, categories, tags, and linked assets.
- GitHub push via Contents API.
- ZIP download of static output.
- SEO file generation — `sitemap.xml`, `robots.txt`, `404.html`, `.htaccess`.
- Site analysis — plugin and theme static-compatibility hints.
- Configurable base URL, crawl depth, and exclude paths.
- Admin UI with progress bar and status bar.
