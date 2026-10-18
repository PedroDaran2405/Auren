import { cp, mkdir, readFile, writeFile, rm } from 'node:fs/promises';
const target = new URL('../wordpress/auren-app-bridge/web/',import.meta.url);
await rm(target,{recursive:true,force:true});
await mkdir(target,{recursive:true});
await cp(new URL('../apps/mobile/dist/',import.meta.url),target,{recursive:true});
const index=new URL('index.html',target);
let html=await readFile(index,'utf8');
html=html.replace('<html lang="en">','<html lang="es">').replace('</head>','<meta name="robots" content="noindex,nofollow"></head>');
await writeFile(index,html);
// Apache inside the local container runs under its own UID; generated assets are public files.
const { execFileSync } = await import('node:child_process');
execFileSync('chmod',['-R','a+rX',target.pathname]);
console.log('Export web empaquetado en el plugin. No se ha desplegado.');
