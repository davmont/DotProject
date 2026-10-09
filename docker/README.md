# Security-audit sandbox

Ephemeral PHP 8.3 + MariaDB stack that serves the repository read-only on http://127.0.0.1:8089.

    cd docker
    docker compose up -d --build     # first time
    ./reset-db.sh                    # load db/dotproject.sql + db/init_permissions.sql, create admin/passwd and worker/worker
    docker compose logs -f web       # PHP errors go to stderr
    docker compose down -v           # tear down

Notes
- `config.php` here overrides `includes/config.php` inside the container (DB host `db`). The repo tree is mounted read-only; only `docker/files` is writable (uploads, rate-limiter state).
- `security_opt: label:disable` is needed on SELinux hosts; `APACHE_RUN_USER=#1000` makes Apache run as the host uid.
- The installer's permission step crashes on PHP 8 (`gacl_api.class.php:849`), so `reset-db.sh` loads `init_permissions.sql` instead.
