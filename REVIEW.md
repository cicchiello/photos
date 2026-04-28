# Code Review — Photos App

## Critical (Security)

### 1. No real session/auth — cookie is trivially forgeable
**Files:** All PHP files that check `$_COOKIE['login_user']`

The app treats the `login_user` cookie as proof of identity. Any user can open browser devtools, set `login_user=joe`, and the server accepts it. There is no server-side session table, no signed token, and no way to distinguish a real login from a forged cookie. Everything downstream of this check is unprotected.

**Fix:** Use PHP's `session_start()` + `$_SESSION`, or generate a random token at login, store it server-side in CouchDB, and validate it on every request.

---

### 2. `commit_password.php`, `commit_email.php`, `commit_name.php` — no auth check at all
**Files:** `www/commit_password.php`, `www/commit_email.php`, `www/commit_name.php`

These files mutate user data directly from `$_POST` with zero authentication check. Anyone can POST to `commit_password.php?id=<any-user-id>&pswd=hacked` and change that user's password without being logged in.

```php
// commit_password.php — no cookie/session check anywhere
writePassword($_POST['id'], $_POST['pswd']);
```

**Fix:** Add a session/auth check before executing any write.

---

### 3. IDOR — any logged-in user can mutate any other user's data
**Files:** `www/commit_password.php`, `www/commit_email.php`, `www/commit_name.php`

Even after fixing issue #2, the `id` field is accepted from `$_POST` without verifying it matches the logged-in user. A logged-in attacker can change another user's password by supplying a different `id`.

**Fix:** After authenticating, look up the `id` for the logged-in user from the database and ignore the `id` from `$_POST`.

---

### 4. Username injection in CouchDB URL
**File:** `www/login_action.php:26`

```php
$usersUrl = $DbViewBase.'/users?key="user:'.$_POST['uname'].'"';
```

`$_POST['uname']` is injected into the URL with no encoding. A username containing `"` or URL metacharacters can break the JSON key format or manipulate the CouchDB query. Should be `urlencode(json_encode('user:'.$_POST['uname']))`.

---

### 5. Plaintext credentials in source control
**File:** `py/config.ini`

```ini
DbPswd = joes-photos
```

A real database password is committed to the repo. Anyone with repo access has the CouchDB password.

**Fix:** Move credentials to environment variables or a secrets manager; add `py/config.ini` to `.gitignore`.

---

### 6. Backup script has hardcoded placeholder credentials
**File:** `bin/backup.bsh:16`

```bash
${prog} -db ${server}/${db} -creds foo:bar -dir -dir ${dir}
```

`foo:bar` are placeholder credentials — the backup is likely failing silently or using wrong credentials. Also note `-dir` appears twice (likely a typo bug).

---

## High (Security)

### 7. Weak password hashing — SHA-256, no salt
**Files:** `www/login_action.php:32`, `www/photos_utils.php:387`

```php
$pswd = hash('sha256', $_POST['pswd']);
```

SHA-256 without a salt is fast and rainbow-table-searchable. PHP has built-in bcrypt/argon2 support.

**Fix:** On password creation use `password_hash($pswd, PASSWORD_DEFAULT)`. On login use `password_verify($input, $storedHash)`.

---

### 8. No authentication on `image_download.php` and broken check on `image_info.php`
**Files:** `www/image_download.php`, `www/image_info.php`

`image_download.php` has no cookie/session check at all. `image_info.php` has an auth check but the `else` branch (line 210–212) tries to set `onload` after the `<body>` tag is already rendered with `onload="init()"`, so the redirect to login never actually fires.

---

### 9. `www/config.ini` is in the webroot
**File:** `www/config.ini`

If the web server doesn't explicitly block `.ini` files, `https://yoursite.com/config.ini` serves the database URL, session timeout, and other config directly. Apache doesn't block `.ini` files by default.

**Fix:** Move the config file outside the webroot, or add to `.htaccess`:
```
<Files "config.ini">
    Require all denied
</Files>
```

---

### 10. Error reporting enabled in production
**Files:** `www/index.php`, `www/image_info.php`, `www/image_download.php`, `www/imgArrayTbl.php`, `www/logout_action.php`, `www/commit_*.php`, `www/name_action.php`

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

Errors in production leak internal file paths, database URLs, and stack traces to the browser.

**Fix:** Remove these lines (or gate them on an env variable). Log errors to a file instead.

---

### 11. Scratch files stored under webroot and never cleaned up
**File:** `www/image_download.php:74`, `www/config.ini`

```ini
scratchPath = ./tmp
```

Downloaded images are written to `www/tmp/<id>` and never deleted. This path is under the webroot, so any file written there is potentially accessible via `https://yoursite.com/tmp/<id>`.

**Fix:** Move `scratchPath` outside the webroot, and delete the temp file after the download link is triggered.

---

### 12. CouchDB URL exposed in browser HTML
**File:** `www/imgArrayTbl.php:312-319`

```php
echo $DbBase;  // rendered into a hidden <span>
```

The full internal CouchDB URL (with host and port) is sent to every browser that loads the image grid. Any logged-in user can read it from page source.

---

## Medium

### 13. `addTag.php` / `deleteTag.php` trust the `login_user` cookie for attribution
**Files:** `www/addTag.php:8`, `www/deleteTag.php:8`

```php
$username = $_COOKIE['login_user'] ?? null;
```

Since the cookie is forgeable (issue #1), anyone can attribute tags or deletions to any username. Fixing issue #1 (real sessions) resolves this too.

---

### 14. No CSRF protection on any state-changing operations
All tag adds, tag deletes, and profile mutations rely on GET/POST with no CSRF token. A malicious page can silently trigger these actions against a logged-in user.

---

### 15. Template literal bug in `tags.js`
**File:** `www/tags.js:55`

```js
str += '<span class="pillButton" style="background-color:${userTagColor};color:black">';
```

This is a regular string, not a template literal — `${userTagColor}` is emitted literally and is not valid CSS. User tags in the left panel always render with a broken `background-color`.

**Fix:** Change to a template literal:
```js
str += `<span class="pillButton" style="background-color:${userTagColor};color:black">`;
```

---

### 16. `realFileSize` uses shell backticks with a path from the database
**File:** `www/photos_utils.php:22`

```php
$size = trim(`stat -L -c%s '$path'`);
```

`$path` comes from CouchDB document data. If a document's path value were ever set to something like `'; rm -rf /var/www; echo '`, this would execute arbitrary shell commands. Even though it's not direct user input today, it's a latent command injection.

**Fix:** Use `filesize($path)` or `stat($path)` PHP functions instead.

---

### 17. `writeEmail/writePassword/writeName` always re-read config (dead code check)
**File:** `www/photos_utils.php:367,379,392`

```php
if (!isset($ini)) {
    $ini = parse_ini_file("./config.ini");
}
```

`$ini` is a local variable that's never set before this check, so the condition is always true and this is redundant dead code. Not harmful, but confusing.

---

## Low / Quality

### 18. `image_download.php:95` — missing semicolon
```php
$downloadName = basename($doc['paths'][0])   // missing ;
```
PHP will produce a parse error if execution ever reaches this line (it currently doesn't, because the page redirects first, but it's a latent bug).

### 19. `logout_action.php` — error reporting left on
`logout_action.php:12-14` has `display_errors = 1` enabled. Low risk since this page does very little, but inconsistent with the intent to hide errors.

### 20. `bin/backup.bsh:16` — duplicate `-dir` flag
```bash
${prog} -db ${server}/${db} -creds foo:bar -dir -dir ${dir}
```
`-dir` appears twice. Likely a copy-paste typo. Actual behavior depends on how `backup.py` handles duplicate flags.
