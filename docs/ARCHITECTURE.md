# Decisión 001: app propia, backend WordPress

Se usa un único proyecto Expo/React Native/TypeScript para Android, iOS y web. El registry npm y `bundledNativeModules.json` de Expo determinaron la combinación: Expo 58.0.14, React 19.3.0, React Native 0.88.0 y las versiones compatibles de video, SecureStore y WebBrowser. El lockfile fija las versiones realmente instaladas. Node 24.19.0 en el entorno.

Se descartó intentar compilar la extensión BuddyBoss como app; le falta el anfitrión comercial. Flutter es viable, pero no comparte las referencias TypeScript ni la preferencia del encargo. No hay nuevo backend de usuarios ni microservicios.

`apps/mobile` contiene interfaz y adaptadores de plataforma. `packages/api-client` contiene contratos, transporte, cancelación y errores. `wordpress/auren-app-bridge` añade un namespace propio sin editar el tema ni reimplementar suscripciones. WordPress y LearnDash son la fuente de identidad, acceso y progreso.

## Identidad

Web se sirve en el mismo origen mediante `?auren_app=1`. Reutiliza login WordPress/BuddyBoss, cookies de WordPress y nonce REST. No hay token web en localStorage ni service worker que guarde respuestas privadas. El documento dinámico se entrega `private, no-store` y `noindex`; solo los recursos estáticos son públicos.

Nativo abre el navegador del sistema para el login existente y consentimiento. Un código aleatorio dura 60 segundos, se vincula a PKCE S256 y se consume mediante DELETE SQL atómico. El callback es fijo, `auren://auth/callback`; el cliente verifica state y request_id. Nunca hay contraseña ni bearer en el enlace de retorno. No es un servidor OAuth general ni utiliza criptografía propia: aleatoriedad, SHA-256, comparación constante y sesiones son primitivas estándar/PHP/WordPress.

El bearer es una sesión **marcada para Auren** administrada por `WP_Session_Tokens`, de una hora. Se acepta solo dentro de `auren/v1`. Se almacena en SecureStore nativo y se revoca en logout; WordPress conserva la capacidad de destruir sesiones. La renovación inicial requiere repetir autorización en el navegador (que puede tener SSO); no se ha implementado un refresh token de larga duración. La cookie de navegador y la sesión de dispositivo son independientes. El cierre global y las protecciones concretas de cuentas deben probarse en la instalación real.

BuddyBoss tiene activado el bloqueo REST para anónimos. Se usa su filtro oficial `bb_exclude_endpoints_from_restriction` únicamente para `/auren/v1/auth/start` y `/auren/v1/auth/exchange`; no se altera la configuración de privacidad global. El intercambio todavía exige código, caducidad y PKCE. Se comprobó este filtro en el código de BuddyBoss 3.4.3 instalado.

No hay autenticación con una clave administrativa compartida, una application password de todos los alumnos ni captura de contraseñas en la app. No se deshabilitan 2FA, verificación o CAPTCHA.

## Permisos y aprendizaje

El plugin exige versión de LearnDash verificada y admite alumnos con una cuenta válida. La restricción a IDs de piloto es opcional y está desactivada por defecto. Los endpoints personales nunca aceptan un user_id objetivo. Curso publicado, permiso de LearnDash, requisitos, asociación curso/lección y disponibilidad se comprueban antes de devolver texto o video. Las lecciones lineales respetan sus anteriores; los requisitos avanzados conservan el flujo web. Los resultados privados no se almacenan de forma persistente en el cliente.

La escritura está desactivada por defecto. Cuando se habilita para el piloto, solo completa lecciones simples pasando por las funciones de LearnDash, sin `force`. Videos con progresión, tareas, temas y cuestionarios se abren en la academia. Una petición fallida no se reenvía automáticamente. No existe cola offline que prolongue permisos.

## Video y web

MP4/HLS autorizados utilizan expo-video. Los bloques YouTube se reconocen en servidor; el cliente recibe un ID validado y muestra el reproductor oficial después de una acción del usuario. Nativo usa un WebView limitado al reproductor de YouTube; el resto de la academia se representa con componentes React Native. No se extraen ni descargan videos embebidos. El contenido de texto se sanea; no se ejecutan shortcodes arbitrarios desde REST.

La salida web comparte pantallas, tipos y lógica. Requiere adaptadores de autenticación y reproductor. La web tiene manifest, iconos y detección de entrega nueva; instalación física y funcionamiento en teléfonos siguen pendientes. No cachea respuestas privadas para uso offline.

## Comunidad y login en 0.2.0

La app añade lecturas paginadas de comunidad y avisos. El bridge ejecuta solicitudes internas al REST de BuddyBoss como el usuario autenticado, con contexto view y sin permitir elegir destinatario o privacidad desde el cliente. La visibilidad de actividad, grupos, bloqueos y detalle permanece en BuddyBoss. Las notificaciones se fijan al usuario actual y además se comprueba el destinatario al reducir la respuesta. Se devuelve texto plano y URLs seguras, sin ejecutar HTML social en la app.

En 0.2 la participación abría su destino web. La versión 0.3 añade escritura propia, descrita abajo. El contador representa avisos internos de BuddyBoss, no push APNs/FCM.

El formulario de acceso sigue siendo wp-login.php con sus hooks y protecciones. El diseño propio solo se aplica a URLs de retorno verificadas de la app o su marca de contexto; no cambia la presentación ordinaria del sitio. El retorno se registra en login_init después del filtro PHP_INT_MAX de BuddyBoss. Se evita el borrado diferido del campo contraseña al reintentar en este contexto. Los destinos externos se rechazan.

## Interacciones compartidas en 0.3.0

`interactions.php` centraliza el dispatch de rutas BuddyBoss fijas. Clona y alinea temporalmente el usuario almacenado por BuddyBoss con la identidad ya autenticada por WordPress; restaura tanto loggedin_user como displayed_user después del dispatch. Normaliza los objetos anidados a la representación JSON que entrega REST: xProfile devuelve stdClass en campos internos y no puede asumirse que todos son arrays PHP.

Publicar, comentar, editar, borrar, reaccionar, marcar avisos, reportar y bloquear llaman a los handlers originales. No hay tablas paralelas de perfiles/actividad ni sincronización mediante scraping. `profile.php` reduce el esquema real a tipos editables admitidos y valida antes de guardar; no expone roles, email, tipo de miembro o cambios de visibilidad. El directorio conserva permisos y minimiza datos personales.

Las mutaciones de una cuenta se serializan con un lock privado de WordPress con token y expiración. Crear post/comentario conserva una clave de deduplicación durante una hora, incluyendo un estado pendiente para una respuesta incierta. Likes reciben estado deseado; el bridge solo invoca el toggle si hace falta. La respuesta visible llega después del guardado y una nueva lectura del registro original. No hay promesa de transacciones globales ni streaming entre dispositivos.

La UI separa comunidad, editor de perfil, avisos y bloqueos en componentes propios, con estados de carga/error, filtros, búsqueda, paginación y ventanas adaptadas al teclado. Al cambiar de pantalla o refrescar se consulta WordPress. La búsqueda de cursos abarca el catálogo ya cargado; comunidad/miembros consultan al servidor. No se usan datos de fixture en la aplicación publicada.
