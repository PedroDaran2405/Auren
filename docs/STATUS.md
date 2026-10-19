# Estado y continuidad

Actualizado el 18 de octubre de 2026 (México). Versión **0.2.0**. Trabajo en rama local `work`, conservado en `main` de GitHub. El propietario aclaró que Auren todavía está en desarrollo; se sigue cuidando su contenido y funcionamiento.

## Acceso de alumnos corregido

App: **https://www.aurenfx.com/?auren_app=1**. Cualquier alumno con una cuenta válida puede entrar; el acceso ya no exige estar en la lista del piloto. Los permisos de LearnDash y BuddyBoss siguen decidiendo qué contenido puede ver. Ajustes → Auren App conserva una restricción opcional para emergencias o pruebas; está desactivada por defecto.

Se reprodujeron y corrigieron tres problemas con la cuenta de alumna proporcionada por el propietario (ID 20; credenciales fuera del repositorio):

1. `403 pilot_only` pese a una autenticación válida: el piloto era obligatorio. Ahora es una opción explícita.
2. BuddyBoss enviaba al panel web en lugar de volver a la app. Su filtro usa `PHP_INT_MAX`; el retorno de la app se registra después de inicializar los plugins, solo para las URLs propias de retorno.
3. WordPress podía borrar una contraseña recién escrita al reintentar después de un error mediante su autofocus diferido. Se desactiva ese comportamiento únicamente en el login de la app.

Se verificó login por correo, rechazo de contraseña incorrecta, conservación del destino tras el error y regreso a la app. El formulario tiene presentación propia, campos en español, recuperación y enlace de regreso; conserva la autenticación y las comprobaciones del sitio. El enlace privado para las pruebas de CAPTCHA solo se usó en la sesión temporal del navegador.

## Interfaz y datos reales

- Cinco secciones: Inicio, Academia, Comunidad, Avisos y Perfil; iconos, navegación y estado seleccionado coherentes.
- Inicio con curso para continuar, avance real y novedades de BuddyBoss.
- Academia con filtros, temario, lección, estados de contenido pendiente y siguiente lección. No se modificaron los cinco contenidos vacíos del curso introductorio.
- Comunidad con feed paginado, autores, avatares, imágenes y detalle de publicación/conversación. El bridge delega lectura y permisos en el REST original de BuddyBoss, sin consultar directamente sus tablas ni ampliar privacidad.
- Avisos propios paginados y contador real. El destinatario se fija al usuario autenticado; no se permite elegir otro ID.
- Perfil con avatar, fecha de alta y acceso a su perfil original. No se inventa un plan PRO, saldo de puntos o estado de suscripción.

Con la alumna se abrieron curso/lección, diez publicaciones y veinte avisos por página, detalle de publicación y perfil, sin errores JavaScript observados. Las páginas 1/2 no repiten IDs. Curso gratuito 200; PRO 403; avisos ajenos 400 en el bridge y 403 en BuddyBoss. Todas las respuestas propias comprobadas usan caché privada sin almacenamiento.

La participación (publicar, comentar o reaccionar) se abre todavía en la comunidad web. No se enviaron publicaciones ni mensajes durante las pruebas. Los contadores mostrados proceden del servidor.

## Sesiones y pruebas

- La cuenta ID 20 pasó consentimiento/PKCE y acceso bearer sin cookies a identidad, comunidad y avisos; bearer fuera del namespace rechazado y revocación confirmada. Es una prueba de protocolo, no de ejecución en un teléfono.
- Pasaron TypeScript, 6 pruebas de cliente, 49 comprobaciones PHP y 4 recorridos de navegador local. Las fixtures son explícitas y se mantienen fuera del paquete.
- Exportaciones web y Hermes Android/iOS correctas. Sigue sin existir APK/IPA firmado, prueba física o PWA instalada.
- Bundle web aproximadamente 606 KB: importaciones de iconos individuales evitan incluir todo el catálogo de Lucide. Avisos de licencias se incorporan al paquete.
- La sincronización app→LearnDash y web→app fue verificada en el hito anterior con QA 592/593. En este hito no se completaron lecciones ni se cambió manualmente el progreso de la alumna ID 20.

Evidencia actual: `docs/evidence/student-login-before.json`, `student-login-after.json`, `student-ui-acceptance.json`, `student-permissions-final.json`, `student-native-session.json` y `student-logout-final.json`. Capturas del navegador en `artifacts/student-*-final.png`; credenciales y cookies no se serializan.

## Despliegue y reversión

Se usó Cowboy MCP con la clave segura de acceso completo autorizada. Archivos propios comparados con el checkpoint antes de escribir, respaldo en `.local/deploy-backups`, comprobación sintáctica de PHP y módulos nuevos antes del loader. El bundle se publicó antes del HTML que lo referencia. No se cambió el tema ni se editaron matrículas, precios, pedidos o suscripciones. El login ordinario conserva el tema y el banner original.

Consultar `DEPLOYMENT.md`, `login-backend-deployment.json`, `student-ui-deployment.json` y `plugin-package.json`. El ZIP reproducible solo contiene este plugin, sus recursos propios y licencias; nunca fixtures o claves.

## Próximos hitos

1. Probar Android/iPhone físicos, generar development builds y preparar firma/distribución; no contratar servicios ni aceptar acuerdos automáticamente.
2. Instalar y verificar PWA en dispositivos, sin cachear información de cuenta/cursos de forma indiscriminada.
3. Añadir participación dentro de la app con permisos, moderación, reporte y bloqueo antes de habilitar escritura social.
4. Integrar membresía/precios desde servidor y probar caducidad/cancelación y múltiples fuentes de acceso. No conceder promociones antes de resolver el modelo de permisos.
5. Cuestionarios, certificados, grupos, mensajes, GamiPress y push permanecen en COVERAGE. No declarar entrega nativa, offline o notificaciones push por haber compilado JavaScript.

Para continuar: leer AUDIT, ARCHITECTURE, API y ENVIRONMENT; usar el checkout existente. `python3 scripts/check-cowboy.py` comprueba Cowboy en lectura con el secreto del entorno. No guardar contraseñas, cookies, claves o el enlace privado de CAPTCHA en código o documentación.
