# Developer checks. Each target is also a CI job (.github/workflows/ci.yml).
#
#   make check         lint + phpstan + raw-input (no sandbox needed)
#   make audit-smoke   start docker/, reinstall the database, run the security smoke test

PHPSTAN_IMAGE := ghcr.io/phpstan/phpstan:2
DOCKER_RUN := docker run --rm --user $(shell id -u):$(shell id -g) --security-opt label=disable -v $(CURDIR):/app -w /app
export DP_UID := $(shell id -u)
export DP_GID := $(shell id -g)

.PHONY: check lint phpstan phpstan-baseline raw-input audit-smoke

check: lint phpstan raw-input

# PHP 8.3 syntax check of first-party code (vendored copies excluded, as in phpstan.neon.dist).
lint:
	git ls-files -z '*.php' \
	  | grep -zvE '^(lib|vendor)/|^modules/(holiday/PEAR|mantis/(core|no\.core)|tracIntegration/xrlib|unitcost/patch_203)/' \
	  | $(DOCKER_RUN) -i --entrypoint sh php:8.3-cli -c 'xargs -0 -n 50 -P 4 sh -c "for f; do php -d error_reporting=E_ALL\&~E_DEPRECATED -l \"\$$f\" >/dev/null || { php -l \"\$$f\"; exit 255; }; done" _'
	@echo "OK: no syntax errors."

phpstan:
	$(DOCKER_RUN) $(PHPSTAN_IMAGE) analyse -c phpstan.neon.dist --memory-limit=3G --no-progress

# Only after fixing existing findings: records the remaining ones as accepted.
phpstan-baseline:
	$(DOCKER_RUN) $(PHPSTAN_IMAGE) analyse -c phpstan.neon.dist --memory-limit=3G --no-progress --generate-baseline phpstan-baseline.neon

raw-input:
	scripts/check-raw-input.sh

audit-smoke:
	docker/up.sh
	@for i in $$(seq 30); do curl -s -o /dev/null http://127.0.0.1:8089/index.php && break; sleep 1; done
	docker/reset-db.sh
	docker/smoke.sh
