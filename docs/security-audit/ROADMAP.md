# DotProject security audit and remediation roadmap

Audit date: 2026-10-09. Branch audited: `dotproject_plus-module-integration-in-the-core` (commit `8788e077`).
Scope: OWASP Top 10 with emphasis on SQL injection, XSS, CSRF and authorization in the legacy PHP 8 code base.
Method: manual data-flow review of every module (six parallel reviewers, one per module group), core framework read by hand,
patches verified in the Docker sandbox under `docker/`.

Detailed per-file findings (file:line, source, sink, fix) are in the `findings-*.md` files next to this document:

| File | Scope | CRIT | HIGH | MED | LOW |
|---|---|---|---|---|---|
| findings-core.md | index.php, classes/, includes/, style/, install/, admin, public, system, groups | 1 | 9 | 7 | 3 |
| findings-groupB.md | companies, contacts, departments, history, links, smartsearch*, backup, dataimport, messages, communication, annotations, informer, help | 0 | 23 | 13 | 3 |
| findings-groupC.md | projects, tasks, tasks_template, calendar, files, forums, projectdesigner, macroprojects, igantt, ical, closure, initiating, monitoringandcontrol | 1 | 10 | 11 | 3 |
| findings-groupD.md | helpdesk, ticketsmith, bugspray, mantis, eventum, tracIntegration, journal, reports, gallery2, hosting, mngdocument | 0 | 17 | 15 | 2 |
| findings-groupE.md | dotproject_plus, timecard, timeplanning, timesheet, timetrack, human_resources, resources, resource_m, holiday, mileagelog, testing | 2 | 52 | 20 | 8 |
| findings-groupF.md | costs, earnings, finances, invoices, payments, registers, unitcost, inventory, opportunities, risks | 0 | 16 | 16 | 4 |

Roughly 130 HIGH items. They are not 130 independent bugs: almost all of them are instances of six framework-level root causes,
listed next. Fixing the root causes first collapses most of the module work into mechanical, low-risk edits.

## 1. Root causes (fix these first)

1. **No CSRF protection, and write handlers run on GET.** `index.php` includes `modules/<m>/<dosql>.php` for any request that carries
   `dosql=`. Every create/update/delete in the application is a one-link CSRF. (Fixed on devel; ported to `security/core-hardening`, see section 4.)
2. **`index.php` computes `$canAccess` but never enforces it** before including the dosql handler or the module view. Any logged-in
   account can call any module's write handler. Most dosql files rely on that check and have none of their own: `modules/system/*`
   (system config, preferences of any user, roles and ACL grants: privilege escalation), companies, contacts, departments, costs, risks,
   invoices, opportunities, hosting, mngdocument, eventum, ticketsmith, every dotproject_plus/timeplanning/timetrack/timesheet/HR handler.
3. **`dPgetCleanParam()` is used as if it sanitised SQL, JavaScript and paths.** It only runs htmLawed (HTML tags). Quotes pass through.
   Hundreds of `addWhere("col = $value")`, `addOrder($sort)`, `db_exec("... $value ...")` calls take request values that only went
   through this helper. The `DBQuery` class supports binding (`addWhere('col = ?', [$v])`) and quoting (`$q->quote()`), but 1284 of
   1809 `addWhere` calls interpolate.
4. **Session state is treated as trusted.** Filters, sort columns and ids are stored raw with `$AppUI->setState()` from `$_GET`/`$_POST`
   and interpolated into SQL in another file (helpdesk, inventory, earnings, invoices, payments, timecard, mileagelog, testing, tasks search).
   PHP 8 string comparison makes the old `if ($id > 0)` guards useless: `"1 OR 1=1" > 0` is true.
5. **Output escaping is inconsistent and the helpers are weak.** `$AppUI->___()` and `dPformSafe()` use `ENT_COMPAT`, so a single quote
   survives and breaks every `onclick="f('...')"` pattern; `dPformSafe($v, DP_FORM_URI)` does no HTML escaping at all. Many views echo
   DB fields raw into HTML, attributes and `<script>` blocks (stored XSS by any user who can create a contact, link, message, task, WBS item...).
6. **Legacy and vendored code reachable in the web root** with no `DP_BASE_DIR` guard: pre-auth `unserialize($_GET[...])` in the
   monitoringandcontrol chart scripts, wireit example scripts (pre-auth reflected XSS and file write), XML-RPC debugger/demo servers,
   the stale `modules/dotproject_plus/dotproject_plus/` copy with `root/root` DB credentials, duplicated PHPXMLRPC trees.

Also confirmed in core:

- **Secrets in git:** `includes/config.php` is tracked and contains a real database password (since commit `b5e892e4`, 2017). Rotate the
  password, `git rm --cached` the file, add it to `.gitignore`, and treat history as compromised.
- **Installer guard only checks `mode=install`:** `install/do_install_db.php` with `mode=upgrade` is unauthenticated and rewrites
  `includes/config.php` from POST values concatenated into PHP source (CRITICAL while `install/` is deployed).
- **Passwords are unsalted MD5**, reset passwords come from `rand()` seeded with microtime, session cookie has no HttpOnly/SameSite/Secure.
- **`locales/core.php` evals translation files** that `modules/system/translate_save.php` writes from POST with only `addslashes` (RCE path).
- **`makeFileNameSafe()`** strips `../` once; `....//` bypasses it (used by `invoices/reports.php`).

## 2. Worst individual findings (fix in Phase 1 regardless of root-cause work)

| Sev | Where | What |
|---|---|---|
| CRITICAL | `modules/monitoringandcontrol/grafico/line_Graph_*.php` | Pre-auth `unserialize(urldecode($_GET[...]))`, no bootstrap. PHP object injection. |
| CRITICAL | `install/do_install_db.php` | Unauthenticated config rewrite / outbound DB connection via `mode=upgrade`. |
| CRITICAL | `modules/timeplanning/js/jsLibraries/wireit/lib/inputex/examples/` | Pre-auth reflected XSS (`echo.php`, `default.php`) and unauthenticated file write (`TaskManager/store.php`). |
| HIGH (RCE) | `modules/mngdocument/do_file_aed.php` | Any logged-in user uploads any file name to `<webroot>/SGD/`, no extension check, no permission check. |
| HIGH (RCE) | `modules/gallery2/configure.php` + `index.php:254` | Any user sets `gallery_folder`; module then `require_once`s `<folder>/embed.php`. |
| HIGH (RCE) | `modules/system/translate_save.php` + `locales/core.php` | Writes POST into `.inc` files that are `eval`'d on every request. |
| HIGH (LFI) | `modules/dotproject_plus/projects_tab.planning_and_monitoring.php:866`, `projects_tab.execution.php:139` | `include_once DP_BASE_DIR . $_GET['show_external_page']` for any project viewer. |
| HIGH (priv-esc) | `modules/system/roles/do_role_aed.php`, `do_perms_aed.php`, `do_systemconfig_aed.php`, `do_preference_aed.php` | No permission check: any user edits roles/ACLs, system config, other users' preferences. |
| HIGH (data loss) | `timeplanning/do_project_activities_aed.php`, `dotproject_plus/do_delete_activity.php`, `history/addedit.php`, `communication/addedit_*.php`, `holiday/addedit.php`, `hosting/do_hosting_aed.php`, `timetrack/dosql.php` | Raw id in `DELETE ... WHERE id = <input>`; `1 OR 1=1` wipes the table, several via GET. |
| HIGH (data read) | `timecard/vw_timecard.php:31`, `helpdesk/view.php:41`, `inventory/index.php:37`, `contacts/select_contact_company.php`, `messages/index.php`, `history/index.php`, `mileagelog/*`, `resource_m/index.php`, `finances/*` | UNION-able SELECTs on raw request ids. |
| HIGH (XSS) | `public/selector.php`, `color_selector.php`, `calendar.php`, `date_format.php`, `helpdesk/selector.php` | `callback` / `field` emitted inside `<script>`; whitelist to `[A-Za-z0-9_.]+`. |
| MEDIUM | `style/*/login.php`, `lostpass.php` | `redirect` echoed unescaped into an attribute on the pre-auth login page. |

## 3. Remediation roadmap

Each phase is one or more atomic PRs against the feature branch. Every PR must: build in `docker/`, pass `docker/reset-db.sh`,
log in as `admin/passwd` and `worker/worker`, reproduce the exploit before and show it blocked after (curl), and leave
`docker compose logs web` free of new `PHP Fatal`/`Warning` lines on the touched pages.

### Phase 0: make `security/core-hardening` shippable (branch already exists, see section 4)
- `classes/ratelimiter.class.php` `isAllowed()`: compare `count($attempts)`, not the array. Today every login is blocked (same bug on devel).
- Widen `users.user_password` to `VARCHAR(255)` in `db/dotproject.sql` and add the `ALTER` to `db/upgrade_latest.php` (devel commit `3b874f53`). Without it bcrypt hashes are truncated and users are locked out after the transparent MD5 upgrade.
- The ported password-reset flow (`includes/sendpass.php`, `modules/public/do_reset_password.php`, `reset_password.php`) stores the token in `users.user_custom`, a column that does not exist. Either add `user_reset_token`/`user_reset_expiry` columns plus upgrade step, or drop the token flow and keep devel's final simpler version (new random password emailed, hashed with `password_hash`).
- Add `echo $AppUI->getCsrfMeta()` to `style/dp-grey-theme/header.php` (the other four themes were patched by the cherry-pick).
- Convert the three GET-based dosql callers to POST: `modules/dotproject_plus/do_show_feedback.php:30`, `modules/ticketsmith/common.inc.php:405`, and remove `modules/dotproject_plus/dotproject_plus/` (dead copy).
- Remove the cherry-picked `SECURITY_AUDIT.md` (devel deleted it later).
- Also port devel's session cookie block (`session_set_cookie_params([... 'samesite' => 'Lax'])`); the cherry-pick brought the positional form without SameSite.
- Verify: login works for both accounts twice (second login exercises the rehashed password), a `dosql` POST without token is refused, a `dosql` GET is refused, hash in DB starts with `$2y$`.

### Phase 1: core gates (small diffs, highest leverage)
1. `index.php`: redirect to `m=public&a=access_denied` when `!$canAccess` before the dosql include and before the module view include. Expect a few modules that were silently relying on the gap (check `worker` can still open companies/projects/tasks/calendar/files/contacts/forums).
2. `install/`: abort every entry point when `includes/config.php` exists and the DB is populated, regardless of `mode`; write config values with `var_export()`; fix the `DP_BASE_URL` undefined-constant fatal in `check_upgrade.php` (installer cannot run on this branch at all); fix `lib/phpgacl/gacl_api.class.php:849` `count(null)` (fresh installs crash before permissions are created).
3. Delete or guard pre-auth files: `monitoringandcontrol/grafico/line_Graph_*.php` (replace `unserialize` with `json_decode` and add the normal bootstrap), wireit `examples/` and `backend/`, `xmlrpc/` debugger and demo servers, `tracIntegration/xmlrpc`, mantis PHPXMLRPC copies. Add the `if (!defined('DP_BASE_DIR')) die()` guard to every remaining module PHP file that lacks it (`grep -L "DP_BASE_DIR" modules -r --include=*.php`).
4. Secrets: rotate the DB password, `git rm --cached includes/config.php`, add to `.gitignore`, keep `config-dist.php` as the template.
5. Output helpers: switch `$AppUI->___()`, `dPformSafe()` and `check_plain` paths to `ENT_QUOTES`; make `dPformSafe($v, DP_FORM_URI)` also HTML-escape; fix `makeFileNameSafe()` to loop or use `basename()` + realpath containment.
6. Escape `redirect` on `style/*/login.php` and `lostpass.php`; whitelist `callback`/`field`/`table` in the `modules/public` selectors and `helpdesk/selector.php`.
7. `locales/core.php`: stop `eval`ing; load translations with `include` of a `return array(...)` file written through `var_export()`, and gate `translate_save.php` like `translate.php` (`$canEdit && user_type == 1`), validating `$lang` against the locale directory list.

### Phase 2: authorization in every dosql handler (one PR per module group)
Add at the top of each handler the same check its `addedit.php` already does (`getPermission($m,'edit'|'add'|'delete', $id)` or `getDenyEdit`/`canDelete`), and `intval()` the primary key before `bind()`/`load()`/`delete()`. Remove `canDelete()` overrides that `return true` (human_resources classes) and restore the commented-out checks (history, initiating, HR). Module order by exposure:
system (roles, perms, config, preferences, syskeys) → companies, contacts, departments → dotproject_plus + timeplanning → timetrack, timesheet, human_resources, holiday → costs, risks, invoices, opportunities, payments, registers → hosting, mngdocument, eventum, ticketsmith, helpdesk → forums, initiating, projectdesigner, igantt, messages, communication, links, dataimport.

### Phase 3: SQL injection
1. Add two helpers to `includes/main_functions.php`: `dPgetIntParam($arr,$name,$def)` and `dPvalidateOrder($value, array $allowed, $default)`; use them at every request boundary.
2. Project-view and company-view tab files must stop re-reading `project_id`/`company_id` from `$_GET`; use the already intval'd variable from the parent view (dotproject_plus, timeplanning, closure tabs).
3. `$AppUI->setState()` call sites: cast or whitelist before storing (helpdesk list, inventory, earnings, invoices, payments, timecard, mileagelog, testing, tasks `searchtext`, ticketsmith `type`).
4. Shared timeplanning model/controller layer (`modules/timeplanning/model/*.class.php`, `control/*.class.php`): `intval()` every id in `addWhere` (about 12 methods, closes ~20 HIGH rows in dotproject_plus and timeplanning at once).
5. Convert remaining interpolations in the touched files to `addWhere('col = ?', [$v])` or `$q->quote($v)`; for legacy raw-SQL modules (earnings `inv_aed.php`, ticketsmith `common.inc.php`, helpdesk reports) wrap values with `db_escape()` as the minimum, or retire the module (see Phase 6).

### Phase 4: XSS
1. Views listed under "Stored XSS" in the findings files: wrap echoed DB fields with `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')` (or `$AppUI->___()` once it uses ENT_QUOTES); for values inside JS strings use `json_encode()`.
2. Link-type fields (`links.link_url`, `contact_url`, `company_primary_url`, `human_resource_lattes_url`): validate scheme is http/https/mailto at store time and at render time.
3. Reflected parameters echoed into attributes (`helpdesk/list.php` search, `ticketsmith/index.php` type, `communication/addedit.php`, `timesheet/index.php` wk): escape or cast.
4. Add a small automated check: a PHPUnit test (see `tests/`) that stores a `<script>` payload through the public API of each model class and asserts the list/view output is escaped, extended module by module.

### Phase 5: file handling and RCE sinks
- `mngdocument`: require `getPermission('mngdocument','add')`, store uploads outside the web root (or under `files/` with the same `uniqid` scheme the files module uses) with an extension allow-list; fix the `$actual` SQL and `unlink` chain.
- `gallery2/configure.php`: permission check and realpath containment for `gallery_folder`.
- `dotproject_plus` `show_external_page`: whitelist of known page names; never include a request-supplied path.
- `invoices/reports.php` and every other `makeFileNameSafe` caller: `basename()` plus directory containment.
- `timecard/configure.php`, `mileagelog/configure.php`, `helpdesk/configure.php`, `eventum/configure.php`: write config as data (`var_export`), not interpolated PHP.
- `classes/authenticator.class.php` PostNuke path: replace `unserialize` with `json_decode` and verify an HMAC, or remove the method.

### Phase 6: dead and broken code (reduces attack surface and audit noise)
Candidates confirmed dead or fatal on PHP 8: `modules/smartsearchns`, `modules/bugspray`, `modules/mantis`, `modules/tracIntegration`, `xmlrpc/` demos, `modules/dotproject_plus/dotproject_plus/`, `modules/unitcost`, `modules/install`, `modules/ticketsmith/admin.php`, `modules/eventum/evlink`, PHP4-style constructors in helpdesk/eventum/testing/mngdocument (fix the SQL sinks in those classes before fixing the constructors, otherwise currently masked bugs go live). Decide per module with the owner; delete rather than patch where the module is unused.

### Phase 7: hardening and regression safety
- `Content-Security-Policy` header (start with `script-src 'self'` plus a nonce for inline blocks), `X-Content-Type-Options`, `X-Frame-Options`.
- PHPStan at level 2 with a baseline, in CI, to catch new raw `$_GET`/`$_POST` use via a custom rule or `grep` guard.
- Keep `docker/` in the repo and add a `make audit-smoke` target that runs the login, CSRF and permission curl checks.

## 4. State of the remediation branch `security/core-hardening`

Created from `dotproject_plus-module-integration-in-the-core` (`8788e077`). Four devel commits were cherry-picked with `-x`:

| Commit on this branch | Origin on devel | Content |
|---|---|---|
| `aca8d1b6` | `b24299e0` | file-based `RateLimiter` for login and password reset |
| `4b2f09e4` | `1e06ce2b` | `password_hash`, token-based password reset, parameterised user lookups |
| `40010a72` | `f3f60992` | CSRF token helpers in `CAppUI`, session cookie flags, admin/tasks/files hardening (conflicts resolved: kept the token reset flow in `sendpass.php` and `authenticator.class.php`, took devel's `do_user_aed.php`) |
| `fb2ae946` | `fc9050d7` | `verifyCsrfToken()` on every `dosql`, POST-only `dosql`, `getCsrfMeta()` in four theme headers, `chpwd.php` fix |

Known-broken items on the branch are listed in Phase 0. Nothing has been pushed; no PR has been opened.
Working tree extras, untracked: `docker/` (sandbox) and `docs/security-audit/` (this report).

## 5. Sandbox

See `docker/README.md`. Quick start:

    cd docker && docker compose up -d --build && ./reset-db.sh
    # http://127.0.0.1:8089  admin/passwd (administrator), worker/worker (Project worker role)

The repo is mounted read-only; code changes are live immediately. `docker/config.php` replaces `includes/config.php` inside the container.
