"""Create a UTF-8 reading pack for an AI chat that cannot accept ZIP archives."""
from pathlib import Path
import base64, hashlib, subprocess, json, re, os
root=Path(__file__).resolve().parent.parent
tracked=subprocess.check_output(['git','ls-files'],cwd=root,text=True).splitlines()
first=['CONTEXTO-PARA-OTRA-IA.md','docs/STATUS.md','docs/ARCHITECTURE.md','docs/API.md','docs/COVERAGE.md','docs/TESTING.md','docs/ENVIRONMENT.md','docs/DEVICE_TESTING.md']
paths=first+[p for p in tracked if p not in first and (p.startswith(('apps/mobile/src/','packages/','wordpress/auren-app-bridge/','tests/','scripts/')) or p in ['README.md','package.json','compose.test.yml','playwright.config.ts','.env.example','.gitignore','.npmrc','.codex/config.toml','apps/mobile/app.json','apps/mobile/eas.json','apps/mobile/package.json','apps/mobile/tsconfig.json','apps/mobile/index.ts','apps/mobile/assets/icon.png'])]
# Delivery utilities are unrelated to the application's implementation.
paths=[p for p in paths if p not in ['scripts/private-handoff-download.php','scripts/export-opus.py']]
revision=subprocess.check_output(['git','rev-parse','HEAD'],cwd=root,text=True).strip()
out=['# Auren: contexto y código para Opus\n', 'Copia de lectura de la app 0.3.0. No necesitas MCP ni GitHub para analizar este archivo. No contiene claves ni acceso al servidor.\n', 'Lee primero CONTEXTO-PARA-OTRA-IA.md y docs/STATUS.md. Respeta los permisos originales, conserva el sitio y entrega cambios con rutas y diff. No declares pruebas ejecutadas por leer evidencias previas.\n', 'Este archivo incluye implementación, configuración, scripts y pruebas. No incluye dependencias instaladas, bundles generados, videos, adjuntos originales ni el lockfile grande. El ZIP completo conserva esos insumos y package-lock.json: úsalo para instalar/reconstruir sin inventar versiones. Los binarios imprescindibles aparecen codificados en base64 con su ruta.\n',f'Revisión base: {revision}\n','## Índice\n']
for p in paths:out.append('- '+p+'\n')
records=[]
for p in paths:
 data=(root/p).read_bytes();sha=hashlib.sha256(data).hexdigest();records.append({'path':p,'bytes':len(data),'sha256':sha})
 try:text=data.decode('utf-8');language=Path(p).suffix.lstrip('.') or 'text';encoding='UTF-8 exacto'
 except UnicodeDecodeError:text=base64.b64encode(data).decode();language='base64';encoding='base64: decodificar para reconstruir el archivo binario'
 longest=max([len(x) for x in re.findall(r'`+',text)]+[3]);fence='`'*(longest+1)
 out.extend([f'\n## ARCHIVO: {p}\n',f'Bytes: {len(data)}; SHA-256: {sha}; codificación: {encoding}.\n',fence+language+'\n',text,'\n'+fence+'\n'])
lock=root/'package-lock.json';out.append('\n## Lockfile complementario\npackage-lock.json está incluido en el ZIP completo. SHA-256: '+hashlib.sha256(lock.read_bytes()).hexdigest()+'. No reemplazarlo por uno inventado.\n')
body='\n'.join(out).encode();key=os.getenv('COWBOY_MCP_API_KEY','').encode()
assert not (key and key in body)
assert not re.search(rb'mcp_[0-9a-fA-F]{40,}|bypass_captcha\s*=',body)
folder=root/'artifacts';folder.mkdir(exist_ok=True);target=folder/'AUREN-PARA-OPUS.txt';target.write_bytes(body)
manifest={'reading_pack':target.name,'app_version':'0.3.0','base_revision':revision,'files':records,'bytes':len(body),'sha256':hashlib.sha256(body).hexdigest(),'package_lock_in_complete_zip':True}
(folder/'AUREN-PARA-OPUS.manifest.json').write_text(json.dumps(manifest,indent=2)+'\n')
print(json.dumps({'file':str(target),'source_files':len(records),'bytes':len(body),'sha256':manifest['sha256']}))
