# DotProject security audit and remediation roadmap

Audit date: 2026-10-09. Branch audited: `dotproject_plus-module-integration-in-the-core` (commit `8788e077`).
Re-checked against `devel` (`12a5359f`) the same day, when the remediation moved there (section 4). Line numbers in the
`findings-*.md` files refer to `8788e077` and may have shifted on devel.
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
| ~~CRITICAL~~ | `modules/monitoringandcontrol/grafico/line_Graph_*.php` | Pre-auth `unserialize(urldecode($_GET[...]))`, no bootstrap. PHP object injection. **Gone on devel** (removed with jpgraph in `625878bf`). |
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

### Phase 0: make the security fixes shippable (DONE, 2026-10-09)
All items verified in `docker/` and covered by `docker/smoke.sh`. Commits on `security/devel-hardening` (original hash on `security/core-hardening` in brackets):

| Commit | Fix |
|---|---|
| `b783502d` (`f03b3a5f`) | `RateLimiter::isAllowed()` compared an array to an int, so every login was refused. |
| `1d37be8f` (`22145e4a`) | `user_password` widened to `VARCHAR(255)`, reset token columns added (schema + `upgrade_latest.sql` entry `20261009`). `dPhashPassword()` keeps MD5 on a database that has not been upgraded, instead of truncating bcrypt hashes. On devel the fresh schema was already 255 wide, but upgraded installs were not. |
| `f0fc1a45` (`e9fb33fc`) | Token password reset works while logged out. Devel had gone back to emailing a new password, which let anyone who knows a user name and email lock that user out; the token flow replaces it again. Works while logged out (`index.php?resetpass=1`), stores the token hash in the new columns, escapes output, single use, expires after 1 h, rate-limited. |
| `55d95d85` (`ec739a4a`) | **New finding:** `dPacl::checkLogin()` auto-repair put any user with no role into the Administrator group at login. Limited to `user_id` 1. |
| `ddb74471` (`6753fc9b`) | CSRF actually enforced: `dosql` is POST-only; the token is added server-side to every POST form (`CAppUI::injectCsrfToken()` output filter) and client-side for JavaScript-built forms; the ported client script had a syntax error and never ran; `dp-grey-theme` had no meta tag. Feedback rating and ticketsmith reattach moved to POST. |
| `8c7d24c3` (`846c90a3`) | Removed the stale nested `modules/dotproject_plus/dotproject_plus/` copy (81 files, `root/root` credentials). |
| (`ef97d117`) | Session cookie `SameSite=Lax`, `Secure` on HTTPS; removed the stale `SECURITY_AUDIT.md`. Already on devel, not re-applied. |
| `3128031d` | **New finding on devel:** developer scripts committed to the web root ran over HTTP. `install_timeplanning.php` ran the timeplanning installer for anyone, writing to the database and appending to locale files. All eight (`benchmark_*`, `test_*`, `fix_locales`, `install_timeplanning`) now refuse to run outside the CLI; `output.txt`/`test_out.txt` untracked. |

New items found during Phase 0, scheduled below:
- Write handlers routed as views bypass the `dosql` CSRF check: `a=domodsql` (module install/remove from GET links in `system/viewmods.php`), `a=do_*_aed` in monitoringandcontrol and costs, `a=dosql_timesheet`, `a=do_task_bulk_aed`. See Phase 1, item 8.
- `modules/contacts/addedit.php:113` warns on PHP 8 (`$userDeleteProtect` undefined). `base.php` forces `display_errors=1`, so the warning lands inside a `<script>` block and the contact form cannot be submitted. See Phase 1, item 9.
- `modules/public/chpwd.php` runs passwords through `dPgetCleanParam()` and `db_escape()` before hashing, so a password containing a quote or `<` no longer matches at login.
- The dotproject_plus installer does not create the `feedback_evaluation` table that the feedback feature uses.

### Phase 1: core gates (DONE except item 4, 2026-10-09)
Branch `security/phase1-core-gates`, stacked on `security/devel-hardening`. Every item reproduced the exploit in `docker/` before the fix and shows it blocked after; `smoke.sh` 17/17 after each commit.

| # | Commit | Fix |
|---|---|---|
| 9 | `e3d2d9f2` | `base.php` logs errors instead of printing them (`display_errors=0`); `$userDeleteProtect` defaulted. The contact form works again. |
| 2 | `707204ea` | Fresh installs: `gacl_api::add_acl()` `count(null)` fatal fixed; gacl id sequences start after the seeded ids (schema and `upgrade_latest.sql`). Before, modules installed later never got ACL objects. `reset-db.sh` now uses the real installer. |
| 1 | `2fe1746e` | `index.php` enforces `$canAccess` before dosql and the view. `public` stays open; own account view and own preferences stay allowed. Worker could overwrite system config and admin's preferences before. |
| 8 | `59184748` | Actions named `do_*`, `dosql*`, `domodsql` need POST + CSRF token; module page commands are POST forms; `domodsql` checks system edit permission. |
| 2 | `55ed9bf6` | Installer locked once `config.php` has DB settings: requests must carry the same DB settings, password included. Before, `install/db.php` showed the DB password to anyone and `do_install_db.php` dumped the database or rewrote the config. Config written with `var_export()`. The installer still crashed with `Undefined constant DP_BASE_URL` on any installer-written config; that test had run against a stale bind mount. Fixed afterwards in the installer hotfix (see below). |
| 3 | `cfbdb240` | Removed `tracIntegration/xmlrpc` (debugger, demos, tests) and wireit `backend/php` and `lib/inputex/examples` (anonymous file write in `TaskManager/store.php`). |
| 3 | `0626dfa0` | `DP_BASE_DIR` guard added to 282 module files. Vendored libraries, mantis side copies and `unitcost/patch_203` left for Phase 6. |
| 6 | `e44bbfc6` | Login/lostpass `redirect` escaped (pre-auth attribute injection); selector `callback`/`field` limited to `[A-Za-z0-9_.]`; `public/selector.php` `table` whitelisted. |
| 7 | `682b5604` | Translation save: admin only, module/lang whitelisted, entries written with `var_export()`. Every translation `eval` (core and four module loaders) runs only after `dPisTranslationSource()` accepts the source. Before, an admin save ran code for every user and `module=../../files/x` wrote outside `locales/`. |
| 5 | `7e5b6713` | `___()` uses `ENT_QUOTES`; `dPformSafe(.., DP_FORM_URI)` HTML-escapes (company URL attribute breakout); `makeFileNameSafe()` loops (`....//` bypass). |

Item 4 is still open and needs the owner: `includes/config.php` is tracked (now `dotproject`/`dotproject`). Untracking it deletes the file on any server that deploys with `git pull`, so first move those servers to an untracked config, then `git rm --cached includes/config.php`, add it to `.gitignore`, keep `config-dist.php` as the template, and rotate the DB password.

Installer hotfix (after Phase 1): `install/check_upgrade.php` and `install/db.php` define `DP_BASE_URL`, so the installer runs against a real `includes/config.php`; `smoke.sh` now checks the installer (password hidden, wrong password refused, upgrade runs).

New items found during Phase 1:
- Role creation is broken for everyone: `CRole::store()` calls `insertRole()` on a null `$perms` (`modules/system/roles/roles.class.php:65`).
- PHP 8 fatals in module installers (`annotations`, `holiday`, `gallery2`, `registers`, `timetrack` setup: `ADORecordSet_empty & int`; `timeplanning` setup writes into `locales/`) and on 21 index/addedit pages (communication, dataimport, earnings, hosting, informer, inventory, links, mantis, opportunities, payments, projectdesigner, testing, timecard). `CLink::delete()` signature mismatch kills the links module. Candidates for Phase 6.
- `locales/*/common.inc` contains entries like `"no $table"=>"no $table"`, which interpolate undefined variables (the `Undefined variable $table` warnings in the log). `modules/timeplanning/locales/pt_br.inc` starts with a UTF-8 BOM and never parsed.

### Phase 2: authorization in every write handler (DONE, 2026-10-09)
Branch `security/phase2-handler-authz`. Each fix reproduced the exploit in `docker/` before and shows it blocked after; `smoke.sh` (now 24 checks, with a `guest/guest` read-only account) passes after each commit.

| Commit | Fix |
|---|---|
| `aabab845` | `index.php`: dosql and `a=do*` requests need the module's delete permission for `del=1`, otherwise add or edit. 79 of 104 handlers checked nothing; the Guest role (view only) could create, change and delete anything. |
| `dcbe86e7` | `dPacl::checkModuleItem()` read a scalar as a row: per-record denies were ignored, and per-record allows threw a TypeError on PHP 8. Per-record permissions work now. |
| `b37ed3b1` | `dPrequireWritePermission()` in the core handlers (companies, contacts, departments, projects, tasks, files, folders, forums, events, links, resources), with the permission name their addedit page uses; forum posts check the stored forum. |
| `5e0449c5` | `dPrequireProjectEdit()` in dotproject_plus (13 handlers), timeplanning (5) and monitoringandcontrol (5): edit on the project, and every task, WBS item, log, minute, meeting, change request, baseline or responsibility named must belong to it. Copy-project needs view on the source. Quality items check the task; user cost rates need admin edit. |
| `44afd46c` | `CDpObject::delete()` pasted the bound key into SQL: worker sent `company_id=3' OR '1'='1` and emptied the companies table. Now a bound parameter (`canDelete()` quotes it). |
| `2855e15d` | The project-scoped handlers keep the ids they check as integers (`dPintList()` for lists), so check and SQL see the same value. |
| `aa4750dc` | human_resources classes use `human_resources` as permission name; their `canDelete()` overrides no longer return true. |

Not changed: a per-record *allow* beyond the role's module rights (e.g. guest may edit project 5) is still refused by the module-level rule; such allows crashed on PHP 8 before, so nothing relies on them. Handlers in closure, initiating, costs, risks, invoices, opportunities, payments, registers, hosting, mngdocument, eventum, ticketsmith, helpdesk and the remaining add-on modules rely on the module-level rule; their records are not per-record ACL items. Raw ids in their SQL are Phase 3.

New items found during Phase 2:
- `monitoringandcontrol/control/controller_respons.class.php`: `count()` on a string, a PHP 8 fatal; responsibility rows cannot be inserted.
- Human resources role creation through `do_role_aed.php` fails silently (store error not surfaced).

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

## 4. State of the remediation branch

Work moved from `security/core-hardening` (PR #243, against the stale `dotproject_plus-module-integration-in-the-core`) to
`security/devel-hardening`, which starts from `devel` at `12a5359f`:

- `1fc4fe81` merges `dotproject_plus-module-integration-in-the-core`. Its 9 commits were already on devel in other form, so the tree is unchanged; the merge only records them.
- The audit docs, sandbox and Phase 0 commits were cherry-picked with `-x`. Conflicts were resolved in favour of devel's code, keeping the Phase 0 behaviour (column-width guard, token reset, POST-only `dosql` message).
- Devel's own security work (`f3f60992`, `fc9050d7`, PRs #236-#242) was already on devel, so the four devel commits PR #243 had cherry-picked are not repeated.

Verified in `docker/` on the new branch: `smoke.sh` 17/17; company forms (button, `form.submit()` and JavaScript-built) save under all five themes. The contact form still fails because of Phase 1 item 9.

## 5. Sandbox

See `docker/README.md`. Quick start:

    cd docker && docker compose up -d --build && ./reset-db.sh && ./smoke.sh
    # http://127.0.0.1:8089  admin/passwd (administrator), worker/worker (Project worker role)

The repo is mounted read-only; code changes are live immediately. `docker/config.php` replaces `includes/config.php` inside the container.
