# Entorno cloud reproducible

La máquina comprobada usa Node 24.19.0, npm 11.9.0, Python 3.12.14, Chromium 154 y Docker Compose 2.40.3. Las imágenes de WordPress/MariaDB están fijadas por digest en compose.test.yml. El checkout de la tarea está aislado; no requiere otro worktree.

Instalación desde `/workspace/Auren`:

```sh
export EXPO_NO_TELEMETRY=1
export NPM_CONFIG_CACHE=/workspace/.npm-cache
npm ci --no-audit --loglevel error
npm run build:web
```

Inicio y comprobación después de reabrir una tarea:

```sh
cd /workspace/Auren
export EXPO_NO_TELEMETRY=1
npm run local:up
npm run typecheck
npm test
npm run test:wordpress
npm run test:browser
```

Los servicios deben arrancarse de nuevo; no se presupone que procesos o sesiones sobrevivan a la publicación del entorno. `local:up` es repetible. Las contraseñas locales permanecen en `.local/compose.env` (ignorado, permisos 600); no imprimir ese archivo. `test:wordpress` reinicia solo el progreso de las fixtures locales, antes del recorrido de navegador.

Para refrescar la auditoría pública: `npm run audit:site`. Usa el proxy de red del entorno con verificación TLS. No consulta datos personales ni lleva credenciales. Para Android/iOS: `npm run build:native-js` comprueba los bundles; las herramientas de firma, SDK y dispositivos requieren un hito aparte.

Las pruebas locales no requieren acceso administrativo a producción. Las pruebas autorizadas en Auren usan sesiones efímeras y cuentas QA documentadas, jamás credenciales en código. Si cambia la CA del proxy y Chromium no la reconoce, registrar la CA suministrada por la plataforma en su almacén NSS con permiso de escritura; nunca desactivar TLS. No guardar cookies, contraseñas ni el enlace privado de CAPTCHA en el snapshot o repositorio.

El borrador de entorno guarda instalación y arranque; guardarlo no lo ejecuta ni publica. Una nueva tarea restaurada deberá volver a superar los checks antes de afirmar que la restauración funcionó.
