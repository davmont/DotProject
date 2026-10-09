#!/bin/bash
# Start the sandbox. includes/config.php is not tracked by git, but docker needs it to exist
# as a mount point for docker/config.php inside the read-only repository mount.
set -e
cd "$(dirname "$0")"
[ -e ../includes/config.php ] || touch ../includes/config.php
docker compose up -d --build "$@"
