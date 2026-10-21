#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p artifacts
docker compose -f compose.test.yml exec -T wp php /opt/auren-tests/header.php
docker compose -f compose.test.yml cp wp:/tmp/auren-header-fixture.html artifacts/header-fixture.html
npm exec playwright test tests/browser/header.spec.ts
