"""Export a self-contained Auren handoff without credentials, caches or private QA files."""

import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import subprocess
import zipfile


ROOT = Path(__file__).resolve().parent.parent
INPUT_NAMES = (
    'Prompt-Auren-App-Codex.md',
    'Auren-respaldo-layout-2026-10-18.zip',
    'BuddyBoss-repositorios-oficiales.zip',
    'BuddyBoss App NextGen_ Enable, Brand, and Build.mp4',
    'Build Better App Pages with BuddyBoss App Blocks.mp4',
)
FORBIDDEN_PARTS = {
    '.git', '.local', '.expo', 'node_modules', '__pycache__',
    'test-results', 'playwright-report', 'coverage',
}


def digest(path):
    hasher = hashlib.sha256()
    with path.open('rb') as stream:
        for block in iter(lambda: stream.read(1024 * 1024), b''):
            hasher.update(block)
    return hasher.hexdigest()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, required=True)
    parser.add_argument('--attachments-root', type=Path)
    args = parser.parse_args()
    output = args.output.resolve()
    version = json.loads((ROOT / 'apps/mobile/app.json').read_text())['expo']['version']
    revision = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip()
    tracked = subprocess.check_output(['git', 'ls-files', '-z'], cwd=ROOT).decode().split('\0')
    files = {}

    def add(path, relative):
        if path.is_symlink() or not path.is_file():
            raise SystemExit(f'Expected a regular file: {relative}')
        if any(part in FORBIDDEN_PARTS for part in relative.parts):
            raise SystemExit(f'Private/generated dependency path rejected: {relative}')
        if relative.name.startswith('.env') and relative.name != '.env.example':
            raise SystemExit('Only .env.example may be exported.')
        if path.suffix.lower() in {'.p12', '.pfx', '.jks', '.keystore', '.key', '.log', '.pyc'}:
            raise SystemExit(f'Private credential/runtime file rejected: {relative}')
        if path.resolve() == output:
            raise SystemExit('The export cannot contain itself.')
        files[relative.as_posix()] = path

    for name in tracked:
        if name:
            add(ROOT / name, Path(name))

    web = ROOT / 'wordpress/auren-app-bridge/web'
    if not (web / 'index.html').is_file():
        raise SystemExit('Run npm run build:web before exporting.')
    for source in sorted(web.rglob('*')):
        if source.is_file() or source.is_symlink():
            add(source, source.relative_to(ROOT))

    plugin_zip = ROOT / f'artifacts/auren-app-bridge-{version}.zip'
    package_evidence = json.loads((ROOT / 'docs/evidence/plugin-package.json').read_text())
    if not plugin_zip.is_file() or digest(plugin_zip) != package_evidence['sha256']:
        raise SystemExit('Run npm run package:plugin; the package must match its recorded hash.')
    with zipfile.ZipFile(plugin_zip) as package:
        plugin = ROOT / 'wordpress/auren-app-bridge'
        for source in sorted(plugin.rglob('*')):
            if source.is_file():
                name = 'auren-app-bridge/' + source.relative_to(plugin).as_posix()
                if package.read(name) != source.read_bytes():
                    raise SystemExit('The plugin package differs from the current source.')
    add(plugin_zip, plugin_zip.relative_to(ROOT))

    native = ROOT / 'apps/mobile/dist-native'
    for source in sorted(native.rglob('*')):
        if source.is_file() or source.is_symlink():
            add(source, source.relative_to(ROOT))

    inputs = []
    if args.attachments_root:
        for name in INPUT_NAMES:
            matches = list(args.attachments_root.rglob(name))
            if len(matches) != 1:
                raise SystemExit(f'Expected exactly one original input: {name}')
            relative = Path('referencias/adjuntos-originales') / name
            add(matches[0], relative)
            inputs.append(relative.as_posix())

    # Check source/configuration, including compiled text, without printing secrets.
    key = os.environ.get('COWBOY_MCP_API_KEY', '').encode()
    credential_pattern = re.compile(rb'mcp_[0-9a-fA-F]{40,}|bypass_captcha\s*=')
    text_types = {'.md', '.txt', '.py', '.ts', '.tsx', '.js', '.json', '.php', '.toml', '.yml', '.sh', '.html', '.css', '.example'}
    for relative, source in files.items():
        if source.suffix in text_types or source.name in {'.npmrc', '.gitignore'}:
            data = source.read_bytes()
            if (key and key in data) or credential_pattern.search(data):
                raise SystemExit(f'Credential pattern detected; refusing export: {relative}')

    manifest = {
        'project': 'Auren',
        'version': version,
        'git_revision': revision,
        'implementation_checkpoint': '90327269dac4770646bbb000fda28b1b093c2285',
        'working_tree_changes': subprocess.check_output(
            ['git', 'status', '--porcelain', '--untracked-files=no'], cwd=ROOT, text=True
        ).splitlines(),
        'start_here': 'CONTEXTO-PARA-OTRA-IA.md',
        'original_inputs': inputs,
        'signed_native_app_included': False,
        'live_wordpress_database_included': False,
        'excluded': ['credentials', 'private QA sessions/screenshots', '.local', '.git',
                     'node_modules/caches', 'installed commercial WordPress plugins'],
        'files': [
            {'path': name, 'bytes': source.stat().st_size, 'sha256': digest(source)}
            for name, source in sorted(files.items())
        ],
    }
    output.parent.mkdir(parents=True, exist_ok=True)
    temporary = output.with_suffix('.zip.part')
    try:
        with zipfile.ZipFile(temporary, 'w', compression=zipfile.ZIP_DEFLATED) as archive:
            for name, source in sorted(files.items()):
                info = zipfile.ZipInfo('Auren/' + name)
                info.create_system = 3
                executable = bool(source.stat().st_mode & 0o111)
                info.external_attr = (0o100755 if executable else 0o100644) << 16
                info.compress_type = zipfile.ZIP_STORED if source.suffix.lower() in {'.zip', '.mp4'} else zipfile.ZIP_DEFLATED
                archive.writestr(info, source.read_bytes())
            info = zipfile.ZipInfo('Auren/EXPORT_MANIFEST.json')
            info.create_system = 3
            info.external_attr = 0o100644 << 16
            info.compress_type = zipfile.ZIP_DEFLATED
            archive.writestr(info, json.dumps(manifest, ensure_ascii=False, indent=2) + '\n')

        with zipfile.ZipFile(temporary) as archive:
            if archive.testzip():
                raise SystemExit('ZIP integrity verification failed.')
            for item in manifest['files']:
                data = archive.read('Auren/' + item['path'])
                if hashlib.sha256(data).hexdigest() != item['sha256']:
                    raise SystemExit('Exported file hash mismatch.')
        temporary.replace(output)
    finally:
        if temporary.exists():
            temporary.unlink()

    result = {
        'archive': str(output), 'version': version, 'git_revision': revision,
        'files': len(files) + 1, 'original_inputs': len(inputs),
        'bytes': output.stat().st_size, 'sha256': digest(output), 'verified': True,
    }
    output.with_suffix('.zip.sha256').write_text(result['sha256'] + '  ' + output.name + '\n')
    output.with_suffix('.manifest.json').write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + '\n')
    print(json.dumps(result, ensure_ascii=False, indent=2))


if __name__ == '__main__':
    main()
