# Deployment security notes

dotProject serves every page through `index.php` (plus `fileviewer.php` for
downloads and `install/` for the installer). Everything else in the tree is
either included by those entry points or not meant for the web at all.

## Web server rules

### Apache

The repository ships `.htaccess` files that enforce this. They need
`AllowOverride All` (or at least `AuthConfig Limit Options FileInfo`) for the
dotProject directory; without it Apache ignores them silently.

| Path | Rule |
|---|---|
| `modules/`, `lib/`, `classes/`, `functions/`, `includes/`, `style/`, `locales/`, `db/`, `misc/`, `xmlrpc/` | `.php`, `.phtml`, `.phar` and `.inc` refused; images, CSS and JS still served |
| `vendor/`, `tests/`, `docs/`, `docker/`, `files/` | Everything refused |
| Root | `queuescanner.php`, developer scripts, `composer.*`, `package*.json` and `*.md` refused |

Uploaded files are served by `fileviewer.php?file_id=...` and each user's PDF
report by `fileviewer.php?report=pdf`, both after the login and permission
checks.

### nginx

nginx does not read `.htaccess`. Put the equivalent rules in the `server`
block, before the PHP `location`:

```nginx
location ~ ^/(modules|lib|classes|functions|includes|style|locales|db|misc|xmlrpc)/.*\.(php|phtml|phar|inc)$ { deny all; }
location ~ ^/(vendor|tests|docs|docker|files)/ { deny all; }
location ~ ^/(queuescanner|benchmark_\w+|test_\w+|fix_locales|install_timeplanning|gen_locale\w*|find_missing_labels)\.php$ { deny all; }
location ~ ^/(composer\.(json|lock)|package(-lock)?\.json)$ { deny all; }
location ~ \.md$ { deny all; }
```

Prefix the paths if dotProject is installed in a subdirectory.

### Checking a deployment

Each of these must answer 403 (or 404), never 200:

```sh
for p in modules/tasks/tasks.class.php vendor/autoload.php includes/config.php \
         tests/run_tests.php files/ queuescanner.php composer.json; do
  printf '%-32s %s\n' "$p" "$(curl -s -o /dev/null -w '%{http_code}' "https://your-host/$p")"
done
```

## Event queue (`queuescanner.php`)

`queuescanner.php` sends the queued events, such as task reminder mails. It
runs on the command line only: over HTTP it answers 404 (and the web server
rules above refuse it before PHP runs). Run it from cron as the web server
user, with the site's public URL so that the links in the mails work:

```cron
# /etc/cron.d/dotproject
*/5 * * * * www-data php /var/www/dotproject/queuescanner.php --base-url=https://pm.example.com
```

- `--base-url` (or the `DP_BASE_URL` environment variable) is required; the
  script exits with status 2 without it.
- Overlapping runs are skipped through a lock on
  `files/temp/queuescanner.lock`; status 1 means that file could not be
  opened (wrong user or permissions).
- Output goes to cron's mail, or append `>> /var/log/dotproject-queue.log 2>&1`.

Sites that fetched `queuescanner.php` over HTTP (`wget`/`curl` in a cron job)
must switch to the command above.
