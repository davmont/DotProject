# Developer checks. Each target is also a CI job (.github/workflows/ci.yml).
#
#   make check         lint + phpstan + raw-input + inline-js + js-test (no sandbox needed)
#   make audit-smoke   start docker/, reinstall the database, run the security smoke test

PHPSTAN_IMAGE := ghcr.io/phpstan/phpstan:2
DOCKER_RUN := docker run --rm --user $(shell id -u):$(shell id -g) --security-opt label=disable -v $(CURDIR):/app -w /app
export DP_UID := $(shell id -u)
export DP_GID := $(shell id -g)

.PHONY: check lint phpstan phpstan-baseline raw-input inline-js js-test audit-smoke

check: lint phpstan raw-input inline-js js-test

# PHP 8.3 syntax check of first-party code (vendored copies excluded, as in phpstan.neon.dist).
lint:
	git ls-files -z '*.php' \
	  | grep -zvE '^(lib|vendor)/|^modules/(holiday/PEAR|mantis/(core|no\.core)|unitcost/patch_203)/' \
	  | $(DOCKER_RUN) -i --entrypoint sh php:8.3-cli -c 'xargs -0 -n 50 -P 4 sh -c "for f; do php -d error_reporting=E_ALL\&~E_DEPRECATED -l \"\$$f\" >/dev/null || { php -l \"\$$f\"; exit 255; }; done" _'
	@echo "OK: no syntax errors."

# includes/config.php is not tracked; PHPStan follows require statements into it.
phpstan:
	test -e includes/config.php || cp includes/config-dist.php includes/config.php
	$(DOCKER_RUN) $(PHPSTAN_IMAGE) analyse -c phpstan.neon.dist --memory-limit=3G --no-progress

# Only after fixing existing findings: records the remaining ones as accepted.
phpstan-baseline:
	test -e includes/config.php || cp includes/config-dist.php includes/config.php
	$(DOCKER_RUN) $(PHPSTAN_IMAGE) analyse -c phpstan.neon.dist --memory-limit=3G --no-progress --generate-baseline phpstan-baseline.neon

raw-input:
	scripts/check-raw-input.sh

# No new inline JavaScript (CSP); see docs/csp.md.
inline-js:
	scripts/check-inline-js.sh

# Jest tests for js/ (js/tests/*.test.js), in the node image so no local Node is needed.
js-test:
	$(DOCKER_RUN) -e HOME=/tmp node:22 sh -c 'npm ci --no-audit --no-fund --silent && npx jest --roots js/tests'

audit-smoke:
	docker/up.sh
	@for i in $$(seq 30); do curl -s -o /dev/null http://127.0.0.1:8089/index.php && break; sleep 1; done
	docker/reset-db.sh
	docker/smoke.sh
