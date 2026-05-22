# dotProject (davmont fork)

An actively maintained, modernized fork of the open-source [dotProject](http://www.dotproject.net/)
project-management system. This branch adapts the legacy LAMP application to PHP 8.x,
re-skins it with a Material-style theme, integrates third-party "dotmods" modules,
adds PMBOK 8th Edition features, and is undergoing a structured security and
performance hardening pass.

Upstream: <http://www.dotproject.net/> · Fork: <https://github.com/davmont/DotProject>

---

## Status

> ⚠️ **Work in progress.** This fork is "highly unstable" by design — it is being used
> as a vehicle to drag the dotProject core and a wide set of optional legacy "dotmods"
> modules forward into PHP 8.x and a consistent Material UI. Some optional modules may
> or may not work; see [Module status](#module-status) below.
>
> 🛡 **Security:** A read-only security audit ([SECURITY_AUDIT.md](SECURITY_AUDIT.md))
> identified 11 Critical / 18 High / 14 Medium / 8 Low findings. Mitigation is in
> progress (see [Recent security work](#recent-security-work)). **Do not deploy this
> branch to production until at least the Critical findings are remediated.**

Current release: **v2.3.0** — see [RELEASE_NOTES_v2.3.0.md](RELEASE_NOTES_v2.3.0.md).

---

## What's new in this fork

### UI modernization (v2.3.0)
- **Material theme as global default.** Cleaner card-based layouts, consistent colour
  palette, responsive forms.
- **Redesigned login & password recovery.** Centred CSS cards, smooth dropdowns, no
  more table-based layouts.
- **Calendar overhaul.** Month and week views rebuilt on a matrix layout with clean
  borders, current-day highlighting, and boxed event chips.
- **Restyled Contacts module** to match the new look-and-feel.

### PMBOK 8th Edition
- New task-level **Status**, **Principles**, and **Domains** fields and views,
  bringing project tracking in line with the 8th-edition guide.

### PHP 8.x compatibility
- Fixed `"array offset on false"` strict-typing errors in Forums (`addedit.php`,
  `post_message.php`).
- Stripped deprecated `mysql_pconnect` checks from the login flow.
- Ongoing cleanup of `Undefined index` / `Undefined offset` warnings.
- Refactored `preg_match`-based URL parsing to `parse_url`.

### Localization
- **Spanish (`es.inc`)** fully translated across the application's locales.

### Permissions & integrations
- Permission checks re-enabled and validated across the **History** and **Companies**
  modules.
- **Eventum** tracker path evaluation hardened.
- **GACL** self-repair on upgrade: auto-restores missing `gacl_acl_sections` table
  and reseeds missing user entries.

### Performance
- N+1 query loops converted to bulk inserts / ADOdb transactions across:
  Tasks (`tasks.class.php`), Tasks Assigned Users deletion, Resource Manager projects
  loop, ATA controller, Calendar event assignment, Minute Members insertion, Project
  Contacts/Departments, Contact↔Company upgrade, Project deletion, Task budget &
  child-count loading in finance tabs.
- Benchmark harnesses included: [benchmark_activities.php](benchmark_activities.php),
  [benchmark_n_plus_1.php](benchmark_n_plus_1.php),
  [benchmark_prefetch.php](benchmark_prefetch.php).

### Recent security work
Merged onto `devel`:
- **Token-based password reset** replacing the legacy plaintext-email flow
  ([includes/sendpass.php](includes/sendpass.php)).
- **MD5 → `password_hash()` migration** with progressive rehash on legacy logins.
- **SQL injection fixes** in user creation, password reset, calendar event
  delete/query, mileage log, CTesting delete, and timecard date validation.
- **XSS fixes** in the inventory utility and communication addedit views.
- **File-based rate limiter** ([classes/ratelimiter.class.php](classes/ratelimiter.class.php))
  on login and password-reset endpoints.
- **`echo db_error()` data-exposure leak** removed.
- **Custom-field duplicate-name validation** and stricter type validation.

> See [SECURITY_AUDIT.md](SECURITY_AUDIT.md) for the full audit, including
> known-outstanding Critical findings (merge conflict in `do_user_aed.php`,
> pre-auth SQLi in `includes/session.php`, missing CSRF tokens, LFI in the
> `dotproject_plus` tab files, RCE-capable `unserialize()` in monitoring chart
> endpoints, and unauthenticated `phpinfo.php`).

### Code health
- Deprecated `checkFlag()` removed from `includes/permissions.php`.
- HTML-entity hack in Calendar replaced with a proper decoding utility.
- Misleading TODO prefixes cleaned out of upgrade instructions.
- Obsolete `/misc` artefacts purged (`mime.types`, `cvs2cl/`, `postnuke/`,
  `holidays/`).

---

## Testing

Two test suites live alongside the application:

- **PHP — PHPUnit** in [tests/](tests/). Covers `CDate`, `CDpObject`, `CEvent`,
  `CHelpDeskItem`, `CustomFieldsParser`, `DBQuery`, `Filter`, `CAppUI`
  (`setMsg` / `getMsg` / `check_plain`), `SQLAuthenticator`, and UI helpers
  (`checkFileName`). Run with:
  ```
  php tests/cli_runner.php
  ```
- **JavaScript — Jest** for client-side helpers (jscalendar `%T` / `%R` / `%X` /
  `%r` format specifiers, etc.). Run with:
  ```
  npm install
  npm test
  ```

---

## Installation

Quick path:

1. Drop the source into your web root.
2. Point a browser at the `install/` directory and follow the wizard.
3. **Post-install:** remove or `Deny from all` the `install/` directory — and
   in particular delete [install/docs/phpinfo.php](install/docs/phpinfo.php)
   (audit finding C8).

Full upstream documentation:
<http://docs.dotproject.net/index.php?title=Installation>

**Requirements**
- PHP 8.x (PHP 8.3 tested; older 7.x may still work but is no longer the target).
- MySQL / MariaDB.
- Apache or nginx. Note: several legacy access controls rely on Apache
  `.htaccess` semantics — on nginx you must port equivalent `deny` rules
  (see audit findings C9, H9).

---

## Module status

dotProject ships **60+ functional modules**. The v2.3.0 sprint focused on the core
navigational framework, Calendar, Forums, Contacts, and Earnings. Many legacy modules
have **not yet been refactored or tested** against PHP 8.x and the Material theme —
expect `undefined index` warnings and pre-Material styling in:

- **Projects & Tasks** — `/projects`, `/tasks`, `/tasks_template`, `/projectdesigner`
- **Departments & HR** — `/departments`, `/human_resources`
- **Finances & Invoicing** — `/finances`, `/invoices`, `/costs`, `/unitcost`, `/payments`
- **Helpdesk & Ticketing** — `/helpdesk`, `/ticketsmith`, `/bugspray`, `/mantis`
- **Time & Resources** — `/timeplanning`, `/timecard`, `/timesheet`, `/timetrack`, `/resource_m`
- **Monitoring, Gantt & Risks** — `/monitoringandcontrol`, `/igantt`, `/risks`
- **Files & System Admin** — `/files`, `/system`, `/backup`, `/dataimport`
- **Third-party integrations** — `/tracIntegration`, `/gallery2`

Contributors prioritizing v2.4.0 should focus on PHP 8 warnings and Material
restyling in the above modules.

---

## Reporting & community

- **This fork's issues:** <https://github.com/davmont/DotProject/issues>
- Upstream support forums: <http://forums.dotproject.net/index.php>
- Upstream bug tracker: <http://bugs.dotproject.net/>
- Upstream SourceForge: <http://sourceforge.net/projects/dotproject>

---

## License

As of upstream version 2.0, dotProject is released under the **GPL**.
Versions 1.0.2 and earlier were released under BSD. See [COPYING](COPYING) and
[LICENSE](LICENSE).

Bundled third-party libraries (Smarty, xajax, JpGraph, phpGACL, overLIB, PHPXMLRPC,
ADOdb, etc.) ship under their original licences. Several of these are end-of-life and
slated for replacement — see audit findings L1–L5.
