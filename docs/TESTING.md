# Pruebas

## Reproducibles localmente

- `npm test`: transporte, origen HTTPS, nonce/cookie, caducidad, no reintentar POST, respuestas tardías después de logout y URLs de video.
- `npm run typecheck`: TypeScript de la app y el cliente.
- `npm run local:up`: WordPress y MariaDB reales; fixtures separadas de LearnDash; repetible sin sustituir usuarios existentes.
- `npm run test:wordpress`: permisos, aislamiento, pertenencia de lección, prerrequisitos, drip, temporizador, veto de LMS, repetición, caché, token ordinario frente a token de app, expiración/revocación, gate por versión y piloto. 49 comprobaciones, incluidas acceso de alumnos, retorno con filtro final de BuddyBoss, destinatarios de avisos, saneado y privacidad delegada.
- `npm run test:browser`: Chromium, viewport 390×844. Login real de WordPress local, curso, lección, progreso, recarga, logout, segundo alumno. Consentimiento y PKCE, intercambio único y bearer limitado al namespace. 4 recorridos: progreso/cambio de cuenta, protocolo nativo, error de contraseña con reintento/correo/comunidad/avisos/logout, recuperación y cookie eliminada.
- `npm run build:web`: exportación Metro y empaquetado del plugin.
- `npm run build:native-js`: paquetes Hermes Android/iOS; no equivale a build de tiendas ni a prueba en simulador.

Las fixtures viven en `tests/fixtures` y solo se cargan con `WP_ENVIRONMENT_TYPE=local` y `AUREN_TEST_FIXTURES`. No se incluyen en el ZIP del plugin. No se reemplazó una prueba real de LearnDash por una fixture declarada como producción.

Capturas de comprobación en `artifacts/` (no Git). Traces de navegador desactivadas para no registrar credenciales. Los scripts nunca imprimen contraseñas generadas.

## Aceptación en Auren

Se conserva evidencia de operaciones reales en `docs/evidence/live-acceptance.json` y se reflejarán resultados y pendientes en STATUS. Deben distinguirse inicio de sesión administrativo, alumno real, guardado de progreso, reproducción de video y compilación nativa. No son pruebas intercambiables.

La aceptación real confirmó app→LearnDash (1/7), recarga, repetición y web→app (2/7); el segundo alumno permanece en 0/7. Las rutas propias y las originales de LearnDash rechazan PRO. El protocolo nativo pasó contra producción tras corregir el bloqueo REST de BuddyBoss. Ver `live-native-auth.json`.

Video: se cargó el reproductor y los metadatos reales, pero `currentTime` no avanzó. Las solicitudes a dos hosts de `googlevideo.com` fallaron con `ERR_TUNNEL_CONNECTION_FAILED` en el proxy cloud. `live-video.json` conserva hosts/errores sin URLs firmadas ni credenciales. Se guardaron los dominios necesarios en el borrador de red; su guardado no prueba aplicación en runtime. Reproducción en teléfono real pendiente.

## Aceptación 0.2.0 con alumna real

Se reprodujeron dos fallos antes de cambiar código: retorno al panel de BuddyBoss y `pilot_only` en /me. Después se comprobó login por correo, contraseña incorrecta rechazada, retorno a app, curso/lección, comunidad y detalle, avisos, perfil, paginación y acceso PRO/ajeno rechazado. Las evidencias `student-*.json` contienen estados/contadores y el ID de prueba, sin email ni contraseña. La prueba nativa de protocolo usa un contexto sin cookies para comprobar el bearer; no equivale a ejecución de APK/IPA.

Las fixtures locales añaden respuestas simuladas del REST de BuddyBoss, explícitas y excluidas del ZIP. No se presenta esa simulación como una prueba de privacidad de BuddyBoss real; el acceso real se verificó por separado con la alumna. No se enviaron publicaciones, reacciones, mensajes o correos de recuperación reales durante las pruebas.

La revisión final conserva `student-navigation-final.json` (pestañas y retornos), `student-logout-final.json` (rutas privadas 401 tras salir) y `student-reentry-final.json` (reingreso y progreso). El nuevo intento de video con la alumna volvió a cargar el reproductor, pero la red cloud rechazó los hosts de medios de Google con `ERR_TUNNEL_CONNECTION_FAILED`; `student-video-final.json` registra ese resultado. No se declara reproducción comprobada.
