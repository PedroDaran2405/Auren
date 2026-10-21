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

## Instalación web y actualizaciones 0.2.1

`tests/browser/install.spec.ts` verifica manifest/identidad de inicio, metadatos Apple, PNG de 180/192/512, rechazo de rutas de icono no permitidas, versión pública sin datos de cuenta, ausencia de service workers/Cache Storage, continuidad ante desconexión y aviso/recarga de un nuevo build. Son 5 recorridos de navegador en total; no equivale a instalar en dispositivos reales. La simulación del release actualizado se limita a esa prueba local.

## Preparación Expo tras crear la cuenta

Se instalaron `expo-updates` y `expo-dev-client` con las restricciones del SDK 58 incluidas en `bundledNativeModules.json`. Pasaron TypeScript, evaluación de `expo config --type prebuild` sin generar proyectos nativos y exportaciones Hermes Android/iOS. Ver `expo-native-preparation.json`. No hubo login remoto de Expo, vinculación del UUID, build remoto ni publicación de updates: siguen pendientes enlace completo y token seguro. No se modificó WordPress en este paso.

## Aceptación 0.3.0: acciones conectadas

Resultados locales: 7 pruebas del cliente, TypeScript, 84 comprobaciones PHP y 8 recorridos de navegador aprobados. Los tres recorridos nuevos cubren CRUD social/reacción/comentario con comprobación del REST original de fixture y recarga; perfil/lectura/búsqueda/miembros/logout; y bloqueo/desbloqueo persistido. La fixture devuelve objetos anidados en xProfile para reproducir la serialización de BuddyBoss real. La fixture de actualización de avisos valida el entero que exige BuddyBoss instalado. Los handlers de producción nunca cargan fixtures. Restablecer el estado de fixture con `npm run test:wordpress` antes de repetir la suite completa: los recorridos modifican progreso y avisos locales.

Los controles PHP comprueban actor fijo, privacidad, edición sin ampliar audiencia, propiedad del post y aviso, claves duplicadas/incompatibles, respuesta pendiente sin duplicación, lock, estado deseado de reacción/bloqueo, campos de perfil permitidos y rechazo de roles/tipos de miembro/campos ajenos. En las pruebas locales se restablece únicamente el estado de fixture; no se envía correo.

Pruebas reales separadas: `connected-live-read.json` verifica perfil (23 campos), comunidad/miembros/categorías reales y curso PRO rechazado. `connected-live-native-write.json` documenta bearer sin cookies, actividad propia Solo yo, app→REST original, cambio original→app, conservación de privacidad, reacción propia idempotente, eliminación comprobada y revocación. `connected-live-profile-sync.json` documenta escritura del campo propio, comprobación en xProfile original, restauración desde la web y relectura en app, sin cambiar progreso. No se publican textos ni datos personales en las evidencias.

No se envían publicaciones públicas, comentarios/reportes/bloqueos hacia personas reales ni mensajes para demostrar funcionalidad. Esas mutaciones tienen aceptación completa local; la prueba real social se limita a actividad privada propia sin menciones. La evidencia de protocolo bearer no equivale a ejecutar una app firmada en teléfono. Las capturas de la cuenta real quedan ignoradas en artifacts; no se publican en Git.

`connected-live-notification-sync.json` comprueba marcado de lectura desde la UI, estado original, historial y restauración. `connected-live-ui.json` documenta pantallas internas en 390×844 sin desbordamiento/errores JS observados, sin enviar posts. `connected-live-learning.json` comprueba búsqueda y siguiente lección real sin completar contenido. Los ajustes finales evitan un fetch duplicado de avisos y hacen visible una segunda publicación al crearla desde Mi actividad; este último caso forma parte del recorrido social local.

`connected-live-final.json` confirma valores de perfil y nombre restaurados, progreso intacto y cierre de sesión: siete rutas privadas 401, ninguna pestaña privada y recarga fuera de sesión. `connected-live-release.json` contrasta PHP y recursos públicos con el árbol empaquetado y el build anunciado; la portada original responde 200 sin cargar el bundle de la app.


## Aceptación 0.4.0

`npm run local:gamipress` instala el plugin gratuito oficial 8.0.6 en Docker; `npm run test:gamification` prepara objetos locales explícitos y ejecuta 15 contratos contra el plugin real. Comprueba lectura nativa, progreso, cambios de requisitos/nombre y premios/descuentos nativos, privacidad, ausencia de otorgamientos en GET y prohibición de escribir puntos desde móvil. El instalador no toca el sitio remoto ni distribuye plugins comerciales.

El recorrido `gamification.spec.ts` comprueba saldo/barra/tooltip, detalle de recompensa, perfil/logros, marca, Avisos y anchos 320/390/768. Usa la copia original del logo para aislar esa petición pública del proxy cloud. Debe ejecutarse después de preparar GamiPress; en instalaciones sin él se omite explícitamente. La batería completa tiene 9 recorridos. Los 8 anteriores conservan aceptación de progreso/social/perfil/avisos y aislamiento.

Antes de repetir la batería completa, `npm run test:wordpress` restaura el progreso y avisos locales y `npm run test:gamification` restablece sus saldos. No repetir pruebas de escritura sobre alumnos reales. La aceptación remota 0.4.0 se limita a lecturas y sesiones propias; incluye PKCE/bearer revocable y no altera progreso ni puntos. Para la limitación de CA del navegador y el relay temporal de renderizado, ver GAMIFICATION.
