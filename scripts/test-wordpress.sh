#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
docker compose -f compose.test.yml exec -T wp php /opt/auren-tests/integration.php
