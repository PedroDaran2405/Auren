#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p .local
find wordpress/auren-app-bridge -type d -exec chmod 755 {} +
find wordpress/auren-app-bridge -type f -exec chmod 644 {} +
chmod 644 tests/fixtures/learndash.php
if [[ ! -f .local/compose.env ]]; then
  python - <<'PY'
import secrets,pathlib,os
p=pathlib.Path('.local/compose.env')
db=secrets.token_hex(24)
values={'MARIADB_PASSWORD':db,'WORDPRESS_DB_PASSWORD':db,'MARIADB_ROOT_PASSWORD':secrets.token_hex(24),'AUREN_TEST_PASSWORD':secrets.token_hex(24)}
fd=os.open(p,os.O_WRONLY|os.O_CREAT|os.O_EXCL,0o600)
with os.fdopen(fd,'w') as f:f.write('\n'.join(k+'='+v for k,v in values.items())+'\n')
PY
fi
docker compose -f compose.test.yml up -d --wait --wait-timeout 120
docker compose -f compose.test.yml exec -T wp php /opt/auren-tests/setup.php > .local/test-state.json
python - <<'PY'
import json
j=json.load(open('.local/test-state.json'));print('WordPress local instalado; usuarios y cursos de prueba:',j)
PY
