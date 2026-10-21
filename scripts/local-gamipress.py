"""Install the exact free GamiPress release for isolated local integration tests."""
import hashlib
import io
from pathlib import Path
import subprocess
import tempfile
import urllib.request
import zipfile

root=Path(__file__).resolve().parent.parent
url='https://downloads.wordpress.org/plugin/gamipress.8.0.6.zip'
data=urllib.request.urlopen(urllib.request.Request(url,headers={'User-Agent':'AurenDevelopment/0.4'}),timeout=45).read()
# WordPress.org release, never commercial code or site backups.
archive=zipfile.ZipFile(io.BytesIO(data))
if any(not n.startswith('gamipress/') or '..' in Path(n).parts for n in archive.namelist()):
    raise SystemExit('Unexpected archive paths')
with tempfile.TemporaryDirectory(prefix='auren-gamipress-') as temporary:
    archive.extractall(temporary)
    for item in Path(temporary).rglob("*"):
        item.chmod(0o755 if item.is_dir() else 0o644)
    subprocess.run(['docker','compose','-f','compose.test.yml','cp',str(Path(temporary)/'gamipress'),'wp:/var/www/html/wp-content/plugins/gamipress'],cwd=root,check=True)
    php='require "/var/www/html/wp-load.php"; if(wp_get_environment_type()!=="local"){exit(1);} require_once ABSPATH."wp-admin/includes/plugin.php"; $r=activate_plugin("gamipress/gamipress.php");if(is_wp_error($r)){exit(1);} do_action("admin_init");'
    subprocess.run(['docker','compose','-f','compose.test.yml','exec','-T','wp','php','-r',php],cwd=root,check=True)
print('Local GamiPress 8.0.6 installed; archive SHA-256:',hashlib.sha256(data).hexdigest())
