"""Package only the bridge and its generated web assets; no test fixtures or secrets."""
import hashlib
import json
from pathlib import Path
import re
import zipfile

root = Path(__file__).resolve().parent.parent
plugin = root / 'wordpress/auren-app-bridge'
if not (plugin / 'web/index.html').is_file():
    raise SystemExit('Run npm run build:web before packaging.')
version = re.search(r'\* Version: ([0-9.]+)', (plugin / 'auren-app-bridge.php').read_text())[1]
target = root / f'artifacts/auren-app-bridge-{version}.zip'
target.parent.mkdir(exist_ok=True)
with zipfile.ZipFile(target, 'w', compression=zipfile.ZIP_DEFLATED) as archive:
    for source in sorted(plugin.rglob('*')):
        if source.is_symlink():
            raise SystemExit('Symlinks are not allowed in the plugin package.')
        if not source.is_file():
            continue
        relative = source.relative_to(plugin)
        if relative.parts[0] not in ('includes', 'web', 'auren-app-bridge.php') or any(p.startswith('.') for p in relative.parts):
            raise SystemExit(f'Unexpected plugin file: {relative}')
        info = zipfile.ZipInfo('auren-app-bridge/' + relative.as_posix())
        info.create_system = 3
        info.external_attr = 0o100644 << 16
        info.compress_type = zipfile.ZIP_DEFLATED
        archive.writestr(info, source.read_bytes())
evidence = {
    'version': version, 'sha256': hashlib.sha256(target.read_bytes()).hexdigest(),
    'bytes': target.stat().st_size,
    'contains': 'Auren bridge and generated Expo web; no fixtures, credentials or third-party server plugins',
}
(root / 'docs/evidence/plugin-package.json').write_text(json.dumps(evidence, indent=2) + '\n')
print(json.dumps({'package': str(target.relative_to(root)), **evidence}, indent=2))
