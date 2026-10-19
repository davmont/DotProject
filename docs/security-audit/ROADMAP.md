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

Item 4 done afterwards (`security/untrack-config`): `includes/config.php` only held placeholder credentials (as `config-dist.php` does), so nothing to rotate. It is no longer tracked and is in `.gitignore`; `config-dist.php` stays the sample. This also matters for the installer lock: a tracked placeholder config made fresh installs refuse the real database settings.

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

### Phase 3: SQL injection (DONE, 2026-10-09)
Branch `security/phase3-sqli`. The findings files were re-triaged against devel first (about 140 sinks still open, ~20 of them missed by the audit). Each group was checked with a time-based and SQL-error probe (baseline-aware, record id as prefix) before and after the fix; sinks the sandbox cannot reach were reviewed, and each commit lists which is which.

| Commit | Scope |
|---|---|
| `1f9bdd8a` | `dPgetIntParam()`, `dPvalidateOrder()`; admin user list filter, session logout ids, preference names, billing codes. |
| `6bb041eb` | companies sort, departments delete, history filter/ids, links search, dataimport, communication. |
| `1fae81d0` | task search and filters, project and macroproject department/company filters, report dates and user filters, forum sort, closure tab, tasks_template. |
| `ea43ccf0` | dotproject_plus and timeplanning project tabs (re-read raw `project_id`), viewgantt, feedback controller, resource_m, timecard, timetrack, holiday, mileagelog, testing; casts in 22 timeplanning model/controller methods. |
| `0c01f7c3` | Sort whitelists in earnings, invoices, payments, registers, opportunities; earnings `inv_aed.php` hand-built SQL; finances filters and budget update; inventory filters; costs second-order. |
| `498ec9e0` | helpdesk list filters and ids, ticketsmith, hosting, mngdocument, eventum, reports, journal. |

Left for later phases:
- Dead or uninstallable modules keep their sinks: bugspray, tracIntegration, mantis (kept for a later revival; see "Nice to have"). helpdesk, holiday, mileagelog, timetrack, registers and others fail to install on PHP 8, so their fixes are review-only.
- `communication/addedit_channel.php` and `addedit_frequency.php` delete rows on a plain GET (CSRF; ids are now integers).
- earnings' hand-written SQL uses unprefixed table names and fails on prefixed installs.
- XSS noticed on the way: helpdesk `sort_header()` echoes `company_id`/`project_id` from the request into links (Phase 4).

### Phase 4: XSS (DONE, 2026-10-09)
Branch `security/phase4-xss`, stacked on `security/phase3-sqli`. The findings were re-triaged against current code first (most stored-XSS rows still open, plus many sinks the audit missed). Each group was checked by storing or sending a marker value (`zQ"'<i>Qz`) and confirming it came back raw before and escaped after; unreachable modules were reviewed.

New helpers in `includes/main_functions.php`: `dPhtml()` (HTML text and quoted attributes, in the install's character set), `dPjs()` / `dPjsAttr()` (JavaScript string literals for `<script>` and event attributes), `dPsafeUrl()` (link targets limited to relative, http, https, mailto, ftp). Covered by `tests/EscapeTest.php`.

| Commit | Scope |
|---|---|
| `b2be7686` | files, forums (also casts post_message ids, an SQL injection), calendar, closure, initiating, project/task lists; helpers. |
| `8e4fcdb6` | contacts (incl. `contact_url` scheme check), companies, departments, history, links (URL check), smartsearch, theme search box, dataimport, messages, communication, annotations, informer. |
| `5f95ca8e` | title-bar crumbs (all modules), public selectors (contact selector callback/ids, onclick instead of `javascript:` links), calendar `uts` and date preference, admin user/session/log pages, translate `lang`. |
| `bd2f4b39` | dotproject_plus `show_external_page`: removed an `include_once` of a request-supplied path (LFI). |
| `a1d6f2ae` | helpdesk, ticketsmith (type whitelist; search fixed for PHP 8 and its field/depth/sort whitelisted), hosting, journal, mngdocument, gallery2. |
| `f82cc11a` | dotproject_plus and timeplanning tabs (JS literals via `dPjs`), human_resources (CV URL check), timecard, timetrack, timesheet, holiday, resources, resource_m, testing. |
| `a7dfba5e` | risks (`vw` whitelist), earnings (short tags, variable-variable bug), inventory, costs, opportunities, registers, invoices, unitcost. |

Left for later phases:
- Stored values are still not filtered on input (`bind()` keeps HTML); output escaping is the defence. Rich-text fields that are meant to hold HTML (timeplanning minutes) are shown as text.
- Modules that do not install on PHP 8 in the sandbox (helpdesk, holiday, mileagelog, timetrack, registers, links pages) were fixed by review only.
- A Content-Security-Policy header (Phase 7) would contain whatever escaping misses.

### Phase 5: file handling and RCE sinks (DONE, 2026-10-11)
Branch `security/phase5-files`, stacked on `security/phase4-xss`. The planned items were re-checked against current code and the sweep was widened to every upload, include, file read/write, `eval`, `unserialize` and shell call; three of the worst findings (files, backup, PostNuke) were not in the audit. Each fix reproduced the problem in `docker/` before and shows it blocked after; modules that fatal on PHP 8 were fixed by review, as each commit says.

| Commit | Fix |
|---|---|
| `c92861fb` | Files: `do_file_aed.php` bound `file_real_filename`/`file_size` from POST, so a worker read `includes/config.php` (DB password) through `fileviewer.php`, and delete unlinked any file. Storage fields now come from the record or the upload; every path uses an int project and `basename()`; the indexer quotes its shell argument. |
| `ba77a066` | Files: `return nwords` fatal ended every text upload with HTTP 500 (bug fix needed to test uploads). |
| `3ddbffff` | `index.php`: `a=configure` pages need system edit, POSTs need the CSRF token. timecard, mileagelog, projectdesigner, helpdesk write settings with `var_export()`; before, a tokenless POST through an admin's browser wrote PHP into `timecard/config.php`. |
| `624b9e29` | `dPexternalAppFile()` (tests in `tests/ExternalAppFileTest.php`): gallery2's `embed.php` include and eventum's `config.inc.php` must be real local files outside `files/`/`SGD/`; changing the eventum directory needs system edit (worker could set it before); generated `dp_config.php` uses `var_export()`. |
| `28093f95` | mngdocument: uploads went to `<webroot>/SGD/<user's name>` and ran as PHP. Now `files/mngdocument/<id>`, downloaded through a permission-checked `documentview` page as an attachment; deletes by id; addedit escapes. PHP 8 fixes so the module stores records at all (wrong table since 2013). Operators: delete any `SGD/` directory. |
| `17ca4da5` | Backup: worker (module edit via the Project worker role) downloaded a full dump with password hashes and ran SQL through restore. Backup and restore now need system edit. |
| `13e9ae85` | dataimport: target table/field checked on the server (projects, tasks, existing columns) when the map is saved and used. |
| `2085047d` | invoices reports: only report files from the directory listing are included (last `makeFileNameSafe()` caller); `report_type` URL-encoded in report form actions. |
| `9392f601` | PostNuke auth: the `check` was md5 of the data itself, so anyone could log in as any user (account created on the fly). Now HMAC-SHA256 with `$dPconfig['postnuke_secret']` (documented in `config-dist.php`); no secret, no PostNuke logins. **The PostNuke bridge must be changed to send the HMAC.** |
| `f4395a61` | opportunities: `unserialize()` with `allowed_classes => false`. |
| `222e3412` | vCard import verifies the CSRF token (it is a view that stores contacts); help page `hid` limited to a file name. |

`show_external_page` was already closed in Phase 4 (`bd2f4b39`).

Left for later phases:
- eventum still `eval()`s `define(APP_...)` lines from the admin-chosen Eventum config; unreachable on PHP 8 while `CEventumConfig` keeps its PHP 4 constructor. Replace with literal parsing when the module is revived (see "Nice to have").
- Modules fatal on PHP 8 before their fixed code runs: dataimport, opportunities, projectdesigner (configure), gallery2, mileagelog/helpdesk (do not install).
- `CustomFieldsParser` has no callers (only `tests/CustomFieldsParserTest.php`) and unserializes field config. `EventQueue` unserializes data the application wrote itself.
- `fileviewer.php` sends the stored `file_type` as Content-Type with `attachment`; adding `nosniff` belongs with the Phase 7 headers.

### Phase 6: dead and broken code (DONE, 2026-10-12)
Rescoped 2026-10-12 with the owner: modules that are dormant on PHP 8 stay in the tree and will be revived later; their open issues are under "Nice to have" below. Branch `security/phase6-dead-code`, stacked on `security/phase5-files`. Each change was checked in `docker/` before and after; admin and worker crawls of every module page are identical to Phase 5.

| Commit | Change |
|---|---|
| `85d09223` | Removed `modules/monitoringandcontrol/jpgraph/` (699 PHP files, 9.6 MB): unused since the charts moved to Chart.js (`625878bf`); its examples and barcode demo app answered over HTTP. |
| `2c96d63f` | Removed `modules/ticketsmith/admin.php`: no permission check, no links to it, HTTP 500 for everyone. |
| `ce6f1b40` | **Security:** smart search listed other users' private events (privacy filter set in a PHP 4 constructor that PHP 8 never calls). |
| `c80b0445` | `CRole` constructor: the roles page was empty, role creation was HTTP 500, and the new-user form offered no role, so admins could not create users through the UI. |
| `4f7c61f2` | PHP 4 constructors in core classes: task log tab, adding task logs and system lookup values were HTTP 500 (`CTaskLog`, `CSysKey`, `CSysVal`, `CTasksTemplate`); custom fields were never loaded, shown or saved (`CustomFields`, `CustomOptionList`, `CustomField*`). |
| `8699985c` | Quoted 141 bare-word array keys (`$row[key]`, an Error on PHP 8) in monitoringandcontrol, earnings and annotations; a tokenizer scan reports none left. |

Checked and left as is:
- Direct HTTP access: every one of the 1191 PHP files without a `DP_BASE_DIR` guard was requested in `docker/`. Apart from the removed jpgraph demos, each one dies on its first statements or prints static text (`public/index.php`), so no guards were added. Denying direct requests to `modules/**/*.php` at the web server is listed under Phase 7.
- Root-level developer scripts that are tracked are CLI-only already (`3128031d`); `gen_locale*.php`, `find_missing_labels.php` and the label `.txt` files are untracked and git-ignored, so they are not deployed from the repository. `queuescanner.php` processes due queue events (reminder mails) for anyone who requests it over HTTP; it only runs them early. Restricting it to the CLI is listed under Phase 7, since a cron job may fetch it over HTTP.

### Phase 7a: hardening and regression safety (DONE, 2026-10-15)
Branch `security/phase7a-hardening` from `devel`. Each change reproduced in `docker/` first; `make audit-smoke` passes (41 checks) and the admin crawl of 253 pages is identical to `devel`.

| Commit | Change |
|---|---|
| `60b5a24f` | **Security:** PDF reports were written to `files/temp/temp<user_id>.pdf` and linked directly: on a host that ignores `.htaccess`, anyone could download another user's last report (`temp1.pdf` is the admin's). Reports are now served by `fileviewer.php?report=pdf` (login required, file chosen by the session user). `files/.htaccess` used `Options -All`, which Apache 2.4 refuses, so every link under `files/` was a 500. |
| `90ad93c5` | `.htaccess` rules: included-only directories (`modules`, `lib`, `classes`, `functions`, `includes`, `style`, `locales`, `db`, `misc`, `xmlrpc`) refuse `.php`/`.inc`; `vendor`, `tests`, `docs`, `docker` refuse everything; root refuses `queuescanner.php`, developer scripts, manifests and Markdown. Before, `lib/phpgacl/admin` and `tests/` printed fatals with server paths, and `vendor/`, `docs/` (this audit) and `docker/` were readable. nginx equivalent in `docs/deployment-security.md`. No page, sub-request or AJAX call used a refused path (static scan, crawl, browser load of every page). |
| `f47dafe3` | Removed `install/docs/phpinfo.php`: `phpinfo()` for anyone. |
| `223bb2fb` | `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin` on every entry point and the installer. |
| `d10c1e31` | `CDate::isWorkingDay()` loaded the dormant holiday module's PEAR code (fatal on PHP 8), so task reminders and `next_working_day()` crashed; holidays now count only when that module is active. |
| `cabed2fd` | `queuescanner.php` is CLI-only (404 over HTTP), takes the site URL from `--base-url`/`DP_BASE_URL` (reminder links were `http://./...` from cron) and skips overlapping runs with a lock. Cron line in `docs/deployment-security.md`. Owner decision: cron, never HTTP. |
| `94ab0e41` | Forum "view PDF file" did not parse on PHP 8 (`=& new`), used an undefined `DB_BASE_URL` and unprefixed table names. |
| `8b76a9d9` | PHPStan level 2, baseline of 1513 existing findings (`phpstan.neon.dist`, `phpstan-baseline.neon`). Classes whose signatures are fatal on PHP 8 (all dormant or never loaded) are excluded. |
| `7195437b` | `scripts/check-raw-input.sh`: fails when a file indexes `$_GET`/`$_POST`/`$_REQUEST`/`$_COOKIE` more often than `scripts/raw-input-baseline.txt` records (1176 reads in 246 files today). |
| `949aab75` | `make check` (lint, phpstan, raw-input) and `make audit-smoke`; `.github/workflows/ci.yml` runs all four on pushes and PRs to `devel` and `master`. |

New items found during Phase 7a (pre-existing, unchanged by it):
- HTTP 500 on core admin pages under PHP 8: custom fields add/edit (`arraySelect()` by-reference argument, `modules/system/custom_field_addedit.php:172`), role view (`each()` in `lib/phpgacl/gacl_api.class.php:661`), translation management (`modules/system/translate.php:139`), contact vCard export (`count(null)` in `lib/PEAR/Contact_Vcard_Build.php`), project stats report (division by zero, `modules/projects/reports/stats.php:99`).
- Pages request `/style/tasks.css`, which does not exist (404).

All of these are fixed in PR #254 (`fix/php8-admin-pages`, based on `devel`), which also found that saving a role permission crashed (`each()` in `add_acl()`/`edit_acl()`).

### Phase 7b and later: Content-Security-Policy
Owner decision 2026-10-15: implement the full CSP now, in several PRs. Inventory of active code: ~1,300 inline event handlers in ~430 files, ~1,200 `javascript:` URLs, ~300 inline `<script>` blocks; `eval`-style code in xajax, overlib and the timeplanning/monitoringandcontrol libraries.
- **7b, groundwork (DONE, 2026-10-15):** branch `security/phase7b-csp`, stacked on Phase 7a.
  | Commit | Change |
  |---|---|
  | `3bb107a9` | `js/dp-actions.js`: delegated handling of `data-dp-*` attributes (call with JSON args, per-event calls, confirm, href, submit); `eval`/`Function`/`setTimeout`/`setInterval` refused; 22 Jest tests; `make js-test` in CI. |
  | `be3eecfa` | `dPcspNonce()`, `dPcspPolicy()`, `Content-Security-Policy-Report-Only` on every `index.php` page; `csp-report.php` (POST only, 64 KB, log capped at 5 MB, URL reduced to `m`/`a`/`u`); `scripts/csp-report-summary.sh`; `dPaction()` builds escaped attributes. `report-uri` only: with `report-to` present Chrome ignores `report-uri`, and no Reporting API delivery arrived in testing. |
  | `c5dca345` | `docs/csp.md`: conversion guide with before/after examples. |

  First inventory (admin, 250 pages, 41 distinct `m`/`a`): 816 reports, 634 `script-src-elem` (inline scripts) and 182 `script-src-attr` (handlers, `javascript:` URLs). Chrome reports a repeated violation once per page, so these are lower bounds; the static counts above are the full size. Nothing blocked, no page errors.
- **7c, core and themes (DONE, 2026-10-16):** branch `security/phase7c-csp-core`.
  | Commit | Change |
  |---|---|
  | `cd4dbb06` | **Security:** stored XSS in the Gantt charts (projects, tasks, macroprojects): frappe-gantt writes the task name with `innerHTML`, and names went in raw, so `<image href=x onerror=...>` ran on page load. Values are HTML-escaped and drawn from a JSON block by `js/dp-gantt.js`. |
  | `e145ccb1` | `dp-actions.js`: `href="#"` links never jump; dotted names (`window.open`) keep their owner as `this`. |
  | `e0908b0c` | Core and theme templates without inline JavaScript: CSRF helper moved to `js/dp-ui.js`; tabs choose the active panel on the server and link through `dPaction()` (CTabBox and the five theme overrides); title-block delete/help links; "New Item" selects; dp-grey-theme tab hooks in `tabs.js`; the `db_adodb.php` reload script carries the nonce. |
  | `99d65ba5` | Login and lost-password pages (five themes): `autofocus`, and "forgot password" is a submit button (`formnovalidate`, after the login button). |
  | `ef4b1307` | material theme no longer loads Roboto from Google Fonts (CSP, and each visitor's IP went to Google). |
  | `7777835c` | `'report-sample'` in the policy; smoke test checks the login page has no inline JavaScript. |

  Verified in `docker/` in all five themes (75 browser checks: tabs, help window, delete through `form.submit()` with the CSRF token, New Item, Gantt view buttons, login/lost-password, no CSP violation on those pages). Browser load of 240 admin pages: 816 reports before, 210 after, all from module pages. Browsers only report an inline handler when it runs, so the static count below is the real to-do list.

- **What is left (static count, active modules, vendored libraries and docs excluded):** 545 inline handlers, 648 `javascript:` URLs and 210 inline `<script>` blocks in 33 modules. Largest: public 234 (selectors and calendar pop-ups), dotproject_plus 190, tasks 147, macroprojects 112, monitoringandcontrol 96, projects 92, finances 64, calendar 44, system 43, forums 41, files 41, timeplanning 37, admin 35.
- **7d, public and the core modules (DONE, 2026-10-17):** branch `security/phase7d-csp-modules`. public, admin, system, companies, contacts, departments, calendar, files, forums, history, smartsearch, resources and ticketsmith have no inline JavaScript left (`scripts/check-inline-js.sh --list` empty).
  - Groundwork: `scripts/check-inline-js.sh` (CI job `inline-js`) fails when a file gains inline JavaScript; its baseline is 1272 lines in 262 files after 7d (1454 before, with the case-insensitive pattern). `dp-actions.js` handles `<area>` links and more events (dblclick, key, mouse down/up, reset). `dPaction()`/`dPjs()` work in every locale charset (`dPtoUtf8()`; `dPjs()` used to throw in big5 and WINDOWS-1257 locales).
  - Pop-ups (public): the `?callback=` / `?field=` names are looked up as property paths by `public.module.js` instead of being written into scripts as code; `eval`-like names are refused.
  - **Security fixes found during the conversion:** stored XSS in the theme "Welcome" header (contact name, every page); in admin user view/edit (contact fields, signature); in calendar (event title/description, clash names), files (file/folder/checkout forms, `redirect` from Referer), forums (name/description) and ticketsmith (own name/e-mail); reflected XSS in custom field add/edit (`module`, `field_id`, `field_extratags`); `contacts/select_contact_company.php` accepted any `table_name`.
  - Verified in `docker/`: browser checks of every `data-dp-call` on 338 pages (all resolve, all arguments valid JSON); click tests of the pop-ups (selector, calendar, colour, contacts, change password), System > Modules, users, custom fields, filter selects, contacts, calendar, forums, smartsearch; crawl 249 pages, all 200; smoke test passes.
  - Left for later (admin-controlled data, noted by the conversion): `system/systemconfig.php` prints `config_value` raw into inputs; the colour selector prints the ProjectColors system values raw; calendar and files filter forms print `REQUEST_URI` into `action`.
- **7e:** tasks, projects, macroprojects.
- **7f, the add-on modules (DONE, 2026-10-17):** branch `security/phase7f-csp-addons`, stacked on 7d. dotproject_plus, monitoringandcontrol, timeplanning, igantt, finances, human_resources, costs, resource_m, initiating, closure, invoices, risks, messages, journal, mngdocument, timesheet and backup are converted. Inline-JS baseline: 1272 → 811 lines.
  - Left on purpose: dotproject_plus `changed_files_from_other_modules/` (reference copies nothing includes); four `$msg = "<script>…"` strings in `monitoringandcontrol/control/controller_resources_costs.class.php` that are never printed; the milonic menu definitions (below).
  - Also removed: `eval('document.form.x_' + field)` in the calendar helpers (closure, initiating, invoices), `setTimeout('string')` calls.
  - **Security fixes found during the conversion:** stored XSS in the igantt chart (task names, `</script>` break-out and popup), timeplanning WBS/role indentation (stored raw and written as HTML, also by dotproject_plus; now only `&nbsp;` runs, cleaned on read too), timeplanning minute form, initiating title, closure (participants, project name, lessons learned), invoices (terms, products), resource_m (task/project/contact names, pop-ups), dotproject_plus Gantt task filter; reflected XSS in finances (`expandedList`, export link; the page has no CSRF check), monitoringandcontrol (request ids in forms and scripts), dotproject_plus feedback forms (`REQUEST_URI`), resource_m `aff_style`; SQL injection in closure (`project_name` in `addWhere`).
  - Verified in `docker/` with all 17 add-ons installed and seed data: every `data-dp-call` on 361 pages resolves (one exception: `dialogCopyProject.dialog` on the Planning tab, where jQuery is not loaded at all, before or after); page errors identical to before (all pre-existing: `Calendar is not defined` on project/closure forms, `$ is not defined` on the Planning tab, timeplanning view HTTP 500, derivation tab `rowIndex`); click tests: resource_m ranges, finances display, budget/costs new buttons, initiating and invoices filters, closure participant move, igantt view modes, timeplanning WBS and organogram add, monitoringandcontrol minutes (new, move participants, back); smoke test passes.

- **7e, tasks / projects / macroprojects (DONE, 2026-10-17):** branch `security/phase7e-csp-tasks`. The three modules have no inline JavaScript left; inline-JS baseline 811 → 558 lines (what remains is in dormant modules and dotproject_plus, see 7g).
  - **Security fixes found during the conversion:** SQL injection in `tasks/viewgantt.php` (`project_id` from GET) and in `macroprojects/addproject.php` (filters, project ids); stored XSS in the task list rows and tooltips, task view, task log form and list, tasks-per-user, todo pickers, project view (name in the delete prompt and header, description), project and macroproject list tooltips, project/macroproject edit forms (URLs, colour, budgets), macroproject view, finances and reports, project task log list (description, translation `title`); reflected XSS in the gantt form actions (raw `$_GET`), `display_option`, tasks-per-user filters, macroproject finance `expandedList`; CSRF: `macroprojects/addproject.php` linked/unlinked projects on a GET link (now POST via the new `dpPost()` helper); `tasks/listtasks.php` listed tasks of every project (now the allowed-projects filter).
  - **Behaviour changes to know:** markup in task, log, project and macroproject descriptions now shows as text (line breaks kept); search boxes also submit when the field loses focus (their old handler referred to a form by a name it did not have and never worked); filter checkboxes submit on `change`.
  - Verified in `docker/`: browser check of 400 pages (every `data-dp-call` resolves except the known Planning-tab copy dialog; page errors identical to `devel` minus the seeded XSS payloads, which run on `devel` and not here; CSP reports 304 → 23); click tests of task edit (tabs, cancel, validation, save), task list filters, project edit (short name, cancel, colour picker), project delete prompt, details toggles, projects gantt navigation, macroproject edit/view; CSRF tests of add/remove project; `make check` and the smoke test pass.
- **7g, the last CSP blockers and the 7d/7f follow-ups (DONE, 2026-10-17):** branch `security/phase7g-csp-blockers`. Inline-JS baseline 811 → 793 lines ; 558 → 540 with 7e merged.
  | Commit | Change |
  |---|---|
  | `177991ed` | The milonic right-click menu is replaced by `js/dp-contextmenu.js` (menus defined from PHP, items run through `data-dp-call`, close on click/Escape/scroll; 6 Jest tests) on the company Projects tab and the dotproject_plus Planning and Execution tabs; the vendored library is removed. On the Planning tab the menu is now set up before `updateEAPItemsNumbers()`, which throws there. |
  | `8ae5c527` | timeplanning milestones: the last string `setTimeout()`. |
  | `2d0fe086` | **Security:** igantt `update_task_dates.php` takes POST only, checks the CSRF token (403 JSON otherwise) and edit permission on the task; `vw_igantt.php` checks view permission on the project and only shows its dependencies. |
  | `0154fe53` | monitoringandcontrol cost and schedule tabs: `preg_match()` instead of `eregi()` (fatal on PHP 8 when a date was posted). |
  | `6e703d1d`, `45853c3f` | Admin-controlled values escaped: system configuration values, colour selector (only hex colours reach `style`), `REQUEST_URI` in the calendar and files forms. |
  | `9d7bd0ef` | timeplanning MDP: task names show again (`$taskName` was undefined) and are written as text. |
  | `39e97d41`…`28f22214` | **Security:** monitoringandcontrol prints database values escaped in 19 files (baselines, responsibilities, corrective actions, minutes, costs, quality); request ids used in SQL are cast (`cmbBaseline`, user, cost ids). |
  | `cbcce67a` | monitoringandcontrol delete icons ask for confirmation (PR #259, merged in here to resolve the overlap). |

  - TinyMCE 3.3.9.3 tested under an enforced policy: editing and saving work; the one blocked item is an internal `javascript:` URL with no visible effect, so it is not a blocker.
  - `'unsafe-eval'`: xajax and overlib are no longer loaded and no string `setTimeout()` is left, so the enforcement step tries the policy without it.
  - Verified in `docker/` with all add-ons and seed data: browser check of 400 pages against `devel` (page errors identical except a seeded payload in a baseline name, which runs on `devel` and not here; every `data-dp-call` resolves except the known Planning-tab copy dialog); click tests: company and Planning context menus (items per row type, Edit navigates, no menu outside the table), delete confirmation (dismiss keeps the row, accept deletes it), igantt date change from the page (200) and without the token (403, also for GET), MDP names as text; `make check` and the smoke test pass.
- **7h, enforcement (DONE, 2026-10-18):** branch `security/phase7h-csp-enforce`, stacked on 7g. `index.php` sends an enforced `Content-Security-Policy` without `'unsafe-eval'`; `$dPconfig['csp_report_only'] = true` in `includes/config.php` switches back to report-only while a broken page is fixed (`docs/csp.md`, `docs/deployment-security.md`). The smoke test checks the header is enforced, has a fresh nonce and no `unsafe-` script source.
  - Verified in `docker/` with all add-ons and seed data: the 400-page browser check gives the same page errors enforced as report-only (all pre-existing, listed below); the only blocked items are jQuery 1.4.2's load-time feature test in finances/macroprojects (`window.scriptN=1`; nothing there evaluates scripts through jQuery) and TinyMCE's internal `javascript:""`, both without effect. Click tests of every phase pass enforced: themes (all five: login, lost password, tabs, help, New Item, delete with CSRF, Gantt), pop-ups, admin, calendar/forums, tasks/projects/macroprojects, add-ons, TinyMCE (editor loads, typing works, toolbar), context menus, delete confirmation, igantt, MDP, Gantt XSS.
  - Dormant modules still contain inline JavaScript (540 lines in 109 files, `scripts/check-inline-js.sh --list`) and will not work under the policy until they are converted at revival.
- **8, follow-ups and the dormant modules (DONE, 2026-10-19):** branch `security/phase8-followups`, stacked on 7h. No file has inline JavaScript any more: `scripts/inline-js-baseline.txt` is empty, so CI rejects any new inline handler, `javascript:` URL or script without the nonce.
  - **Dormant modules converted for the CSP** (helpdesk, opportunities, projectdesigner, timecard, bugspray, annotations, eventum, inventory, earnings, communication, mantis, mileagelog, testing, reports, timetrack, holiday, hosting, unitcost, registers, links, groups, informer, dataimport, smartsearchns, payments, gallery2, tracIntegration), including the `eval()` field lookups and string timers, which the policy (no `'unsafe-eval'`) would block. dotproject_plus `changed_files_from_other_modules/` (stale copies of core files) is removed.
  - **Security fixes in the dormant modules:** SQL injection in bugspray `list.php` (search, filters, sort), bugspray `item_id`/selector ids, inventory company filter, mantis `project_id`; script injection in timecard and bugspray JS arrays, bugspray selector callback, helpdesk scripts; stored/reflected XSS in opportunities, eventum, annotations, registers, earnings, projectdesigner, reports, links, groups, payments, mantis, inventory, helpdesk reports/configure, timecard, bugspray views; bugspray server-side `eval()` replaced.
  - **Dormant modules made to load on PHP 8:** method signatures compatible with `CDpObject` (every page was HTTP 500), `__construct` instead of PHP 4 constructors, installers (`$ok & exec()` TypeError, `TYPE=MyISAM`, smartsearchns class clash), holiday's bundled PEAR (static calls), and small fatals (annotations, earnings, informer, dataimport, timetrack). Installed and activated in `docker/`: 221 of their pages load and pass the enforced-CSP browser check (every `data-dp-call` resolves, no page errors, nothing refused).
  - **Active-module follow-ups:** dotproject_plus Planning/Execution tabs load jQuery/jQuery UI and a message helper (WBS save/delete/sort, autocomplete and the copy dialog never worked: `$`, alertify and `setAppMessage` were never loaded), WBS delete (`getWBSItemById()` missing; children matched `LIKE '1%'`, also deleting item 10), tabs with WBS ids that are not 0..n (fatal), PHP 4 constructors in dotproject_plus/igantt/timeplanning (instructional feedback objects were empty); monitoringandcontrol `do_pendencias.php` (fatal), baseline checked against the project, cost/schedule tabs `round()` fatal, cost messages via `setMsg()`; finances/macroprojects budget editor built with DOM methods (markup injection); `Calendar is not defined` on the forms (lib/calendar wrapper).
  - Verified in `docker/` (enforced CSP): 400-page check of the active modules (page errors only went down: `Calendar` and `$` errors gone; no unresolved `data-dp-call`, the copy dialog now resolves; the 21 refusals are jQuery 1.4.2's load-time feature test as before); click tests: Planning tab (jQuery loaded, copy dialog, WBS save through `$.ajax`, delete with confirm, subtree only), context menus, budget editors with a quote payload, cost/schedule tabs with own/foreign baseline, pendencias delete (own project deleted, other project refused), igantt; `make check` and the smoke test pass.
- Choices: `style-src 'unsafe-inline'` stays (inline styles are low risk); no `'unsafe-eval'` (dropped in 7h); vendored wireit docs/API pages are removed rather than converted; dormant modules are converted when revived (see below).

### Nice to have: dormant modules (to be revived later)
Phase 8 converted them for the CSP, fixed the XSS/SQL injection found on the way and made most of them load on PHP 8 (see above). What is left for a real revival:
- **Removed (2026-10-19, owner decision):** bugspray (an old copy of helpdesk) and smartsearchns (a fork of smartsearch; its two helpdesk search objects are in git history, port them to smartsearch if helpdesk is revived) as duplicates; groups (contact groups, 2004 alpha on the 1.x permissions table), tracIntegration (Trac integration) and eventum (Eventum helpdesk integration) as not useful. All remain in git history.
- **Table prefix:** mileagelog and the timecard weekly views use raw SQL with unprefixed table names (mileagelog's installer creates unprefixed tables), so they only work with an empty `dbprefix`.
- **Found by PHPStan once the fixed classes were analysed again** (baselined): helpdesk `mysql_insert_id()` and two unquoted array keys (`item_notify`, `item_status`: fatal on PHP 8); links and communication save handlers call an undefined `notify()` and read the protected `_message`; testing `getTaskName()` does not exist.
- **mantis**: its bundled XML-RPC library declares a class `Error`, which PHP 7+ already has (fatal); it also sends every user's name and password hash over plain HTTP to a hard-coded third-party host (`mantis.config.php`), and the bundled Mantis-side server stores the hash with an injectable `UPDATE`. Revival: configurable host, HTTPS only, never send password hashes, keep the Mantis-side code out of the web root.
- **unitcost**: `patch_203/` holds patched copies of dotProject 2.0.3 core files; redo them against the current core.
- **communication**: `addedit_channel.php`/`addedit_frequency.php` delete on GET.
- **Broken before the conversion, unchanged:** timetrack calendar link (opens the removed `calendar.php`), payments `showCalendar()`, earnings `delIt()` on the add/edit page, testing runtest date field, helpdesk add/edit timer fields, dotproject_plus `feedback_area.php` (not included anywhere), monitoringandcontrol cost-period validation (`checkUpdate`/`isValidDtBegin` are never called, overlapping periods are accepted).
- **Browser checks:** the converted pages were load-checked under the enforced CSP (all `data-dp-call` names resolve, no page errors); click through each module's forms when it is revived. Re-run the Phase 3/4/5 checks (`probe.sh`, marker check, direct-request scan) against it in `docker/`.
- **PHP 8 runtime errors in add-on modules that do run** (seen in the Phase 6 tab crawl): monitoringandcontrol quality tab (`count(null)` in `ControllerQuality::obterDadosGraficoPizza()`), a timeplanning task tab (`array_keys()` on a non-array), and undefined-variable warnings in monitoringandcontrol, timeplanning, human_resources and dotproject_plus tabs.

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
