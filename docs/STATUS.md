# Estado y continuidad

Versión **0.6.0**, en **https://www.aurenfx.com/?auren_app=1**. La entrega añade el diseño inspirado en los conceptos, marca original y gamificación nativa compartida con el encabezado web. Ver [GAMIFICATION](GAMIFICATION.md) para edición de niveles, auditoría y límites de contenido.

## Registro y diagnóstico · 0.6.0

Registro dentro de la app con campos y creación originales de BuddyBoss, cuenta compartida WordPress, validaciones y límites de intentos. Preparación del formulario público en web y reserva de área segura superior en el login. El CAPTCHA del login se conserva; el registro delega al formulario original si su protección lo exige. Medición Server-Timing propia para diagnosticar la demora antes de cambios de infraestructura. Ver [REGISTRATION_AND_PERFORMANCE](REGISTRATION_AND_PERFORMANCE.md). Correo/activación reales y aspecto final en iPhone pendientes de confirmación física.

## Revisión de video y navegación · 0.5.0

Arranque sin bienvenida transitoria y una sola petición autenticada para preparar identidad, catálogo, perfil, avisos y comunidad; cada fragmento conserva los permisos de su ruta nativa. Perfil/avisos/feed preparados y reutilizados en memoria privada de sesión, con invalidación de escrituras y refresco explícito. Logo original en login, protección de cambios del perfil y ajustes de viewport/fondo para iOS. Ver [VIDEO_REVIEW_2026-10-22](VIDEO_REVIEW_2026-10-22.md) para pruebas, límites y pendientes físicos. El token nativo sigue durando una hora; este cambio no modifica la política de sesiones.

## Icono de instalación · bridge 0.4.2

Toro original de la biblioteca (adjunto 56) para iconos de iPhone/Android, favicon y vista de compartir. Imagen original conservada, recursos derivados reproducibles y metadatos públicos sin información de alumnos. Icono nativo actualizado para la próxima compilación. Ver [DEVICE_TESTING](DEVICE_TESTING.md); aceptación física del icono pendiente.

## Corrección del encabezado web · bridge 0.4.1

El video del propietario mostraba el desplazamiento causado por insertar el widget después de consultar REST. Se retiró esa inserción: los templates originales de BuddyBoss reciben saldo, nivel y progreso en el servidor mediante los hooks de carga de templates de WordPress. CSS crítico en `<head>`, dimensiones fijas de widgets e imágenes y texto acotado evitan que sus refrescos muevan el menú. No se copian ni editan templates del tema; GamiPress conserva sus tipos, reglas y saldos.

Ver [HEADER_STABILITY](HEADER_STABILITY.md), pruebas reproducibles `npm run test:header` y evidencias `header-stability-*.json`. La app nativa conserva su versión independiente 0.4.0.

## Preparación de dispositivos · pendiente de subida

Expo autenticado y proyecto real vinculado. Canal/branch preview, variable pública WordPress y keystore Android remoto creados. Configuración de EAS Update y control de actualizaciones dentro de la app implementados; TypeScript, 10 tests y exportaciones Hermes de ambas plataformas pasaron. La subida a almacenamiento de EAS recibió 403 del proxy: `storage.googleapis.com` necesita permiso de red cloud. No hay APK, compilación remota creada ni update publicado. Ver [DEVICE_TESTING](DEVICE_TESTING.md) y `expo-native-preparation.json`. No se generaron claves iOS ni se publicaron tiendas.

## Entrega 0.4.0

- Auren Credits existentes, con su imagen y saldo reales. Sin segunda moneda ni XP inventado. Tooltip y detalle de saldo/nivel.
- Niveles Auren nativos: Inicio y Explorador (saldo de 200 Credits). Editables en GamiPress; siete rangos anteriores preservados. Selector de tipos en Ajustes → Auren App.
- Inicio, Cursos, clase, Comunidad, Avisos y Perfil con sistema visual azul/cian, navegación inferior y datos/acciones existentes. Logo del sitio, portada real del perfil y logros nativos.
- 15 comprobaciones nuevas contra GamiPress 8.0.6 real, además de las 84 PHP anteriores, 7 pruebas de cliente y 9 recorridos de navegador. Exportaciones web y Hermes iOS/Android; no son binarios firmados.
- En Auren: alumno admitido, curso autorizado 200 y PRO 403; identidad y endpoint de gamificación coinciden; bearer nativo devuelve el mismo estado, rechaza otro usuario y queda revocado al salir. Sin completar lecciones ni asignar puntos a la alumna.
- Apariencia revisada con el bundle actual y lecturas/recursos reales mediante un relay temporal GET de loopback que verifica HTTPS. No se amplió el almacén de confianza del navegador. Capturas privadas fuera de Git; aceptación física pendiente.

Las siguientes secciones conservan la aceptación anterior 0.3.0. Los grupos nativos, offline, adjuntos, certificados y push siguen pendientes. Varias lecciones están vacías; no se simulan materiales o recompensas de curso.

## Entrega conectada

- Comunidad: crear publicaciones con audiencia Comunidad/Solo yo, editar y borrar las propias con permiso, comentar y dar/quitar Me gusta. Feed paginado, búsqueda y filtros Para ti / Mi actividad / Me gusta / Personas.
- Perfil: editor dentro de la app a partir de los grupos y campos reales de xProfile. Auren devuelve 23 campos admitidos, con opciones, selección múltiple, teléfonos y números; no un formulario de demostración. Se conserva la visibilidad y se excluyen tipos de miembro y campos no compatibles.
- Miembros: directorio y perfil dentro de la app, con permisos originales y datos mínimos. No se exponen emails ni metadatos administrativos.
- Avisos: detalle interno, Sin leer / Leídos, marcar uno o los visibles, contador recalculado y apertura de conversación cuando el destino corresponde a actividad accesible.
- Moderación: formulario con las seis categorías reales y bloqueo/desbloqueo propio. No se cambian ajustes globales ni se simula que la app sea administradora.
- Aprendizaje: búsqueda del catálogo cargado y acceso directo a la siguiente lección disponible. Se conservan las reglas de acceso y progreso de LearnDash.
- UX: ventanas adaptadas al teclado, estados de guardado/error, confirmación de eliminación, controles independientes de autor/reacción/opciones y navegación interna.

Los cambios se confirman después del guardado del servidor. No hay una segunda base de perfiles, publicaciones o progreso en el teléfono. Al consultar/actualizar la pantalla se leen los registros actuales de WordPress; no se promete actualización instantánea mediante websocket entre dispositivos.

## Comprobaciones reales en Auren

Con la cuenta de alumna proporcionada por el propietario (ID 20, credenciales fuera del repositorio):

- Identidad, perfil, comunidad, miembros, bloqueos y categorías responden 200. Curso introductorio 200; curso PRO sin autorización 403. Progreso inicial 0/7.
- Una sesión bearer sin cookies obtuvo identidad/perfil/miembros/bloqueos. Creó una actividad propia «Solo yo»; el REST original de BuddyBoss devolvió el mismo registro. Una edición desde ese REST apareció en la app. Editar desde la app conservó la privacidad. Me gusta propio e intento repetido conservaron el estado. El registro privado se eliminó y la sesión de prueba se revocó.
- Marcar un aviso propio como leído dentro de la UI cambió su estado en el REST original y apareció en el historial. Se restauró su estado inicial desde ese REST y la app reflejó la restauración.
- En viewport móvil se abrieron perfil, compositor privado, personas y avisos sin salir de la app, sin errores JavaScript observados ni desbordamiento. La búsqueda filtró el curso real y continuar abrió su siguiente lección disponible sin cambiar progreso.
- Un campo de perfil guardado por la app apareció en xProfile original. Su valor inicial se restauró desde el REST original y la app reflejó la restauración. El progreso quedó intacto.

Evidencias: `connected-live-read.json`, `connected-live-native-write.json`, `connected-live-profile-sync.json`, `connected-live-notification-sync.json`, `connected-live-ui.json`, `connected-live-learning.json` y `connected-live-final.json`. No contienen contraseñas, tokens, contenido del perfil ni textos privados. No se enviaron publicaciones públicas, comentarios, reportes, bloqueos o mensajes hacia otras personas. La prueba de actividad privada no llevaba menciones; se revisó que el helper instalado de avisos a seguidores omite la audiencia Solo yo.

La aceptación detectó y corrigió una diferencia real frente a las fixtures: xProfile entrega objetos PHP anidados al hacer dispatch interno. Se normaliza su representación JSON antes de adaptarlos; el caso se añadió a la fixture local. El perfil real vuelve a responder 200. La lectura de avisos acepta un filtro booleano, pero su escritura exige un entero 0/1; también se ajustó y se incorporó a la fixture.

La sincronización de progreso app→LearnDash y web→app fue probada previamente con QA 592/593 (1/7 y 2/7; el segundo alumno permaneció 0/7). En esta entrega no se completaron lecciones ni se cambió manualmente el progreso de la alumna ID 20. Login por correo de alumno, retorno desde BuddyBoss, error/reintento, recuperación y logout conservan sus pruebas previas y locales.

## Validación y límites

TypeScript, **7 pruebas de cliente**, **84 comprobaciones PHP** y **8 recorridos de navegador** pasaron localmente. Los recorridos sociales completos usan WordPress/MariaDB reales con fixtures explícitas de BuddyBoss/LearnDash y correo desactivado. Prueban persistencia por las rutas originales simuladas, recarga, comentario, perfil, lectura, bloqueo/desbloqueo y rechazo entre cuentas. Eso no sustituye las comprobaciones con los plugins comerciales instalados, registradas por separado arriba.

Exportaciones web y Hermes Android/iOS correctas. Bundle web de aproximadamente 661 KB y licencias incluidas en el ZIP. No hay APK/IPA firmado, prueba física o publicación en tiendas. El reproductor YouTube carga, pero la red cloud bloquea `googlevideo.com` con `ERR_TUNNEL_CONNECTION_FAILED`; reproducción física sigue pendiente.

La comprobación final confirmó nombre/perfil restaurados y progreso intacto. Tras salir, las siete rutas privadas comprobadas devolvieron 401 y recargar mantuvo la sesión cerrada.

La documentación comercial pública de BuddyBoss devolvió 403/Cloudflare tanto por HTTPS como por Chromium. Se revisaron el código oficial adjunto, docstrings REST y los handlers exactos instalados: Platform 3.4.3, Pro 3.0.2 y Theme 2.8.51. [BUDDYBOSS_REVIEW](BUDDYBOSS_REVIEW.md) conserva fuentes, decisiones y funciones pendientes; no se afirma haber leído íntegramente páginas bloqueadas.

## Despliegue y reversión

Cowboy MCP con secreto seguro del entorno; archivos propios contrastados antes de escribir, respaldo en `.local/deploy-backups`, preflight PHP, módulos nuevos antes del loader y HTML después del JS. Se conservan bundles anteriores. No se modificaron tema, contenido de LearnDash, roles, matrículas, pedidos, precios ni ajustes globales.

Ver `connected-experience-deployment.json`, `connected-auth-consent-deployment.json`, `connected-serialization-deployment.json`, `connected-notification-deployment.json`, `connected-ui-final-deployment.json` y `plugin-package.json`. El consentimiento describe las acciones disponibles sin ampliar TTL o namespace del bearer. Desactivar únicamente Auren App Bridge retira la app/API y conserva el sitio original. No restaurar tablas sociales para revertir código: borraría cambios legítimos.

## Próximos hitos

1. Probar iPhone/Android físicos. La web tiene manifest/iconos y aviso de entrega nueva, sin caché privada offline. Instalar desde Safari/Chrome sigue requiriendo comprobación física.
2. Retomar Expo cuando el propietario esté disponible. Ya conectó GitHub a `auren-fx/auren-fx`; `EXPO_TOKEN` sigue ausente. Falta comprobar UUID autenticado, vincular runtime/proyecto y preparar builds/firma. No se contrató ni publicó nada automáticamente. [DEVICE_TESTING](DEVICE_TESTING.md) conserva instrucciones.
3. Grupos/foros, adjuntos, respuestas/edición individual de comentarios y preferencias avanzadas, con permisos y pruebas propias.
4. Cuestionarios/certificados, membresía/precios, vencimientos y compras desde sus fuentes reales; no conceder PRO ni inventar saldos.
5. Push APNs/FCM y offline con aceptación independiente. Un bundle JavaScript no demuestra esas capacidades.

Para continuar: leer AUDIT, BUDDYBOSS_REVIEW, ARCHITECTURE, API y ENVIRONMENT. `python3 scripts/check-cowboy.py` verifica acceso en lectura. No guardar credenciales, cookies, claves ni el enlace privado de CAPTCHA en el repositorio.

## Entrega y descarga para otra IA

`CONTEXTO-PARA-OTRA-IA.md` resume objetivos, implementación, aceptación, accesos, restricciones y siguientes hitos. El exportador conserva código, pruebas, documentación, recursos generados y, opcionalmente, los cinco adjuntos originales. El ZIP completo del checkpoint 879ba4e se verificó por hashes, sin claves ni sesiones.

Los enlaces de archivo del entorno no resultaron descargables para el propietario. Se preparó una [descarga protegida en Auren](PROJECT_DOWNLOAD.md), en una carpeta auxiliar independiente que no se activa como plugin ni modifica app/tema. Aceptación real: administrador 200, alumno 403, visitante enviado al login; el ZIP descargado coincide con sus 54 368 093 bytes y SHA-256 original. Ambas sesiones temporales se cerraron. Como GitHub es público, no se publicaron allí los adjuntos y el respaldo completos.

Validación remota 0.6.0: `native_form_only=true` por CAPTCHA de BuddyBoss; Crear una cuenta abre por ahora su formulario protegido. El formulario integrado tiene pruebas locales; la entrega/activación real del correo queda pendiente. Medición con alumno: startup 2639 ms (PHP 2513 ms), identidad 1939 ms (PHP 1808 ms). No se modificaron usuarios, progreso ni claves CAPTCHA. Ver `REGISTRATION_AND_PERFORMANCE.md`.
