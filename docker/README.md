# Security-audit sandbox

Ephemeral PHP 8.3 + MariaDB stack that serves the repository read-only on http://127.0.0.1:8089.

    cd docker
    ./up.sh                          # start (creates the includes/config.php mount point if missing)
    ./reset-db.sh                    # run the real installer (admin/passwd), then create worker/worker through the user form
    docker compose logs -f web       # PHP errors go to stderr
    ./smoke.sh                       # security smoke checks: login, CSRF, reset flow, role check
    docker compose down -v           # tear down

Notes
- `config.php` here overrides `includes/config.php` inside the container (DB host `db`). `includes/config.php` is not tracked by git; `up.sh` creates an empty one as the mount point. The repo tree is mounted read-only; only `docker/files` is writable (uploads, rate-limiter state).
- `security_opt: label:disable` is needed on SELinux hosts; `APACHE_RUN_USER=#1000` makes Apache run as the host uid.
- Browser checks (forms sent with `form.submit()`, all five themes) were run with Playwright and headless Chrome; `smoke.sh` covers the server-side behaviour with curl.
- `config.php` is bind-mounted as a single file: after editing it, run `docker compose up -d --force-recreate web`, otherwise the container keeps serving the old copy.
- Optional MantisBT for the Mantis module: `docker compose -f compose.yml -f compose.mantis.yml up -d mantis && ./mantis-setup.sh` installs MantisBT 2.28 on http://127.0.0.1:8090 (administrator / root, database `bugtracker` in the same MariaDB), creates a test project with issues, prints an API token and sets dotProject's Mantis address (`http://mantis`, HTTP allowed for the sandbox only). Install the Mantis module first, then enter the token on its "Mantis API token" page. `reset-db.sh` resets dotProject only; run the script again afterwards.
