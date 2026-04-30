# Code Review — Photos App

## Status

All critical and high issues from the original review have been fixed. Two items remain partially or intentionally open.

---

## Fixed Issues

| # | Severity | Issue | Resolution |
|---|----------|-------|------------|
| 1 | Critical | Cookie-based auth (forgeable) | Replaced with PHP sessions (`$_SESSION`) |
| 2 | Critical | `commit_*.php` — no auth check | Session check added before any write |
| 3 | Critical | IDOR on profile mutations | User ID now derived from session, not `$_POST` |
| 4 | Critical | Username injected into CouchDB URL | Fixed with `urlencode(json_encode(...))` |
| 5 | Critical | Plaintext CouchDB password in `py/config.ini` | Password removed; rotated; `config.ini` untracked from git |
| 6 | Critical | Backup script hardcoded `foo:bar` credentials | Removed; `-creds` made optional in `backup.py` |
| 7 | High | SHA-256 password hashing (no salt) | Migrated to bcrypt via `password_hash()`; transparent migration on login |
| 8 | High | No auth on `image_download.php`; broken check on `image_info.php` | Session checks added to both |
| 9 | High | `www/config.ini` web-accessible | Blocked via `.htaccess`; file removed from git tracking |
| 10 | High | `display_errors` on in production | Commented out across all production PHP files |
| 11 | High | Scratch files (`www/tmp`) under webroot | Blocked via `.htaccess` on production (see open items) |
| 12 | High | CouchDB URL exposed in page HTML | See open items |
| 13 | Medium | Tag attribution trusted forgeable cookie | Fixed by session switch (issue #1) |
| 14 | Medium | No CSRF protection | CSRF tokens added to all state-changing operations |
| 15 | Medium | Template literal bug in `tags.js:55` | Fixed — user tags now highlight blue correctly |
| 16 | Medium | Shell injection risk in `realFileSize()` | Fixed with `escapeshellarg()` |
| 17 | Medium | Dead `if (!isset($ini))` guards | Removed from `writeEmail/writePassword/writeName/writeUsername` |
| 18 | Low | Missing semicolon in `image_download.php` | Fixed |
| 19 | Low | `display_errors` on in `logout_action.php` | Fixed |
| 20 | Low | Duplicate `-dir` flag in `backup.bsh` | Fixed |

---

## Open Items

### Scratch path under webroot (resolved)
`www/tmp` is no longer used by the web app — `image_download.php` was rewritten to stream images directly from CouchDB to the browser without writing to disk. The `.htaccess` block on `tmp/` is now dead code but harmless. `scratchPath` in `config.ini` is still referenced but unused by the web layer.

### CouchDB URL in browser HTML
**File:** `www/imgArrayTbl.php`

The CouchDB base URL is rendered into the page as a hidden `<span>` so JavaScript can read it for direct tag fetches. Any logged-in user can see it in page source. This is a known, accepted exposure — the URL is required for the browser-side tag collection to work.

---

## CouchDB write protection

CouchDB is configured to reject unauthenticated writes. The PHP app and all Python scripts now send HTTP Basic Auth credentials on every write request.

Two distinct types of user records exist in CouchDB:

- **App user records** — stored in the `photos` database (`type: "user"`). These hold login credentials (bcrypt password hash), name, email, and username for the web app.
- **CouchDB `_users` records** — stored in the `_users` database. Used for CouchDB-level authentication. Currently one service account: `org.couchdb.user:photos` with role `photos-writer`, used by the PHP app and Python scripts to authenticate write requests.

The `validate_doc_update` design document enforces that only users with `photos-writer` (or `_admin`) role can write to the database.
