# Estado y continuidad

Actualizado el 18 de octubre de 2026 (México). Repositorio inicialmente vacío; trabajo en rama local `work`, checkpoints conservados y enviados a `main` en GitHub. La aplicación completa sigue en desarrollo.

## Funciona con Auren real

Piloto: **https://www.aurenfx.com/?auren_app=1**. Iniciar sesión con una cuenta administradora o un alumno habilitado en Ajustes → Auren App. El piloto está limitado a administradores y los usuarios QA 592/593; no está abierto a todos los alumnos.

- Login de WordPress con cuentas de prueba reales y lectura de identidad.
- Curso 1010 y sus siete lecciones consultados desde LearnDash 5.1.9.
- Alumno QA A completó 1015 desde la interfaz; LearnDash confirmó 1/7, persistente al recargar y sin duplicación al repetir.
- Ese alumno completó 1017 desde el formulario original de la web; la app mostró 2/7. Se probó sincronización en ambos sentidos.
- QA B conserva 0/7. Se rechazan PRO, cambio de usuario objetivo y lectura del progreso ajeno. Las rutas originales de LearnDash también rechazan PRO.
- Autorización nativa probada por protocolo contra producción: consentimiento, PKCE incorrecto rechazado, código de un solo uso, bearer limitado al namespace y revocación. Esto no es una prueba de APK/IPA.
- Cierre de sesión probado desde la interfaz: desaparecen los cursos y la consulta privada devuelve 401.
- Se corrigió un bloqueo real de BuddyBoss privado con una excepción exacta para los dos endpoints públicos de autorización. No se desactivó la privacidad general.

Bridge 0.1.3 activo. La portada y el curso público conservaron títulos, estilos y capturas idénticas de 1280×900 antes/después. El progreso del propietario se mantuvo en 1/7. Esta comparación cubre esas dos vistas, no una regresión exhaustiva de toda la web.

Evidencias: `docs/evidence/live-acceptance.json`, `live-web-to-app.json`, `live-permissions-*.json`, `live-alternate-routes.json`, `live-native-auth.json`, `live-logout.json`, `live-deployment.json` y `visual-comparison.json`. Captura del recorrido real: `docs/evidence/screenshots/lesson-completed.png`.

## Código y validación

Expo 58 / React Native 0.88 / TypeScript; app compartida web/Android/iOS, API cliente, plugin independiente, autenticación web/nativa, cursos, lecciones, texto, adaptadores de video y progreso. WordPress sigue siendo la única fuente de usuarios, permisos y aprendizaje.

Pasaron: instalación por lockfile, TypeScript, 6 pruebas de cliente, 34 verificaciones PHP, 2 recorridos de navegador, exportación web y bundles Hermes Android/iOS. El entorno PHP local usa WordPress real y fixtures de LearnDash explícitas; las evidencias de producción se conservan por separado. `local-checks.json` registra comandos y límites.

`npm run package:plugin` genera el ZIP reproducible con avisos de dependencias y fuentes. `plugin-package.json` contiene versión y SHA-256. La instalación/arranque quedaron guardados en el borrador del entorno cloud; publicarlo corresponde al producto, y una tarea nueva todavía debe verificar la restauración.

## Límites actuales

- Video: el reproductor oficial identifica el YouTube publicado, pero el proxy del entorno devuelve `ERR_TUNNEL_CONNECTION_FAILED` al conectar con los servidores de video de Google; no se ha confirmado reproducción efectiva. Ver `live-video.json`. No se presenta como una prueba superada.
- El curso actual contiene un video antiguo de GM HUB, una lección de texto muy breve y cinco vacías. No se reescribió contenido para simular siete videos nuevos.
- No hay APK/IPA firmado, ejecución nativa en dispositivo, PWA instalada ni publicación en tiendas. Se compilaron bundles JavaScript/Hermes.
- Perfil básico y academia implementados. Comunidad, membresía detallada, notificaciones, promociones y descargas quedan para los siguientes hitos; ver COVERAGE.
- No se probaron pagos, vencimiento de una suscripción real, combinación de múltiples fuentes de acceso, 2FA ni verificación pendiente. No se modificaron pedidos ni matrículas para simular esos casos.
- No se contrataron servicios ni aceptaron acuerdos. La autorización inicial se utilizó mediante WordPress web/REST. Después se verificó acceso autenticado mediante Cowboy MCP por HTTP con una clave del entorno seguro; ver COWBOY_MCP y la evidencia `cowboy-connection.json`. No hay acceso a otros chats.

## Cowboy MCP verificado

`python3 scripts/check-cowboy.py` repite la consulta de solo lectura a Auren con `COWBOY_MCP_API_KEY`. Confirmó Auren FX, WordPress 7.1.3, PHP 8.3.35, BuddyBoss Child y bridge activo. La conexión funcionó al identificar explícitamente el cliente HTTP; Cloudflare rechazaba la firma genérica de Python con código 1010. No se cambiaron reglas de seguridad ni se guardaron valores secretos en el repositorio.

## Continuar sin repetir la auditoría

1. Leer AUDIT, ARCHITECTURE, API y DEPLOYMENT; no restaurar el ZIP `before` sobre producción.
2. Ejecutar el arranque de ENVIRONMENT y sus pruebas. Los servicios y sesiones no sobreviven necesariamente a otra tarea.
3. Confirmar video en un teléfono real y revisar con el propietario el contenido editorial pendiente, sin publicarlo automáticamente.
4. Generar un development build Android y validar login/callback, SecureStore, navegación, video y retorno desde navegador. Preparar firma y distribución con credenciales seguras; iOS requiere el hito Apple/macOS/TestFlight.
5. Completar instalación PWA y comprobar Safari/iPhone y Chrome/Android. No cachear cuenta, cursos o progreso en un service worker genérico.
6. Ampliar membresía leyendo precios/estado desde servidor, resolver fuentes de acceso antes de promociones y habilitar comunidad con pruebas de privacidad/moderación.

Las cuentas QA figuran en `pilot-accounts.json`; sus contraseñas aleatorias no están guardadas. Para reanudar una prueba administrativa autorizada, verificar primero ID, nombre y roles antes de restablecer exclusivamente una cuenta QA. No tocar progreso del propietario ni de clientes. Nunca guardar credenciales, cookies, enlaces privados de CAPTCHA o fuentes comerciales inspeccionadas en el repositorio.
