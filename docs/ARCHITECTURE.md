# Decisión 001: app propia, backend WordPress

Se usa un único proyecto Expo/React Native/TypeScript para Android, iOS y web. El registry npm y `bundledNativeModules.json` de Expo determinaron la combinación: Expo 58.0.14, React 19.3.0, React Native 0.88.0 y las versiones compatibles de video, SecureStore y WebBrowser. El lockfile fija las versiones realmente instaladas. Node 24.19.0 en el entorno.

Se descartó intentar compilar la extensión BuddyBoss como app; le falta el anfitrión comercial. Flutter es viable, pero no comparte las referencias TypeScript ni la preferencia del encargo. No hay nuevo backend de usuarios ni microservicios.

`apps/mobile` contiene interfaz y adaptadores de plataforma. `packages/api-client` contiene contratos, transporte, cancelación y errores. `wordpress/auren-app-bridge` añade un namespace propio sin editar el tema ni reimplementar suscripciones. WordPress y LearnDash son la fuente de identidad, acceso y progreso.

## Identidad

Web se sirve en el mismo origen mediante `?auren_app=1`. Reutiliza login WordPress/BuddyBoss, cookies de WordPress y nonce REST. No hay token web en localStorage ni service worker que guarde respuestas privadas. El documento dinámico se entrega `private, no-store` y `noindex`; solo los recursos estáticos son públicos.

Nativo abre el navegador del sistema para el login existente y consentimiento. Un código aleatorio dura 60 segundos, se vincula a PKCE S256 y se consume mediante DELETE SQL atómico. El callback es fijo, `auren://auth/callback`; el cliente verifica state y request_id. Nunca hay contraseña ni bearer en el enlace de retorno. No es un servidor OAuth general ni utiliza criptografía propia: aleatoriedad, SHA-256, comparación constante y sesiones son primitivas estándar/PHP/WordPress.

El bearer es una sesión **marcada para Auren** administrada por `WP_Session_Tokens`, de una hora. Se acepta solo dentro de `auren/v1`. Se almacena en SecureStore nativo y se revoca en logout; WordPress conserva la capacidad de destruir sesiones. La renovación inicial requiere repetir autorización en el navegador (que puede tener SSO); no se ha implementado un refresh token de larga duración. La cookie de navegador y la sesión de dispositivo son independientes. El cierre global y las protecciones concretas de cuentas deben probarse en la instalación real.

No hay autenticación con una clave administrativa compartida, una application password de todos los alumnos ni captura de contraseñas en la app. No se deshabilitan 2FA, verificación o CAPTCHA.

## Permisos y aprendizaje

El plugin exige versión de LearnDash verificada y restringe el piloto a IDs configurados y administradores. Los endpoints personales nunca aceptan un user_id objetivo. Curso publicado, permiso de LearnDash, requisitos, asociación curso/lección y disponibilidad se comprueban antes de devolver texto o video. Las lecciones lineales respetan sus anteriores; los requisitos avanzados conservan el flujo web. Los resultados privados no se almacenan de forma persistente en el cliente.

La escritura está desactivada por defecto. Cuando se habilita para el piloto, solo completa lecciones simples pasando por las funciones de LearnDash, sin `force`. Videos con progresión, tareas, temas y cuestionarios se abren en la academia. Una petición fallida no se reenvía automáticamente. No existe cola offline que prolongue permisos.

## Video y web

MP4/HLS autorizados utilizan expo-video. Los bloques YouTube se reconocen en servidor; el cliente recibe un ID validado y muestra el reproductor oficial después de una acción del usuario. Nativo usa un WebView limitado al reproductor de YouTube; el resto de la academia se representa con componentes React Native. No se extraen ni descargan videos embebidos. El contenido de texto se sanea; no se ejecutan shortcodes arbitrarios desde REST.

La salida web comparte pantallas, tipos y lógica. Requiere adaptadores de autenticación y reproductor. Es una web móvil inicial, no una PWA instalada ya certificada; manifiesto, iconos finales, política de actualización y pruebas físicas son otro hito.
