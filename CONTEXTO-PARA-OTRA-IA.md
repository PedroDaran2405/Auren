# Auren — contexto para continuar el proyecto con otra IA

Entrega preparada el 20 de octubre de 2026, hora de Ciudad de México. Versión funcional **0.3.0**. Último commit de implementación: **90327269dac4770646bbb000fda28b1b093c2285**. El manifiesto del ZIP identifica la revisión exacta que incluye esta documentación.

## 1. Encargo y criterio de éxito

El propietario es Pedro Daran, fundador de Auren FX, una academia y comunidad de trading en México. Quiere una app propia para iPhone y Android, inspirada en BuddyBoss App, con identidad visual de Auren y una experiencia profesional. No quiere otra maqueta ni una app que mande al usuario a la web para todas las acciones.

WordPress debe seguir siendo el centro de administración y la fuente de usuarios, permisos, cursos, progreso, perfiles y comunidad. Las acciones implementadas en la app deben persistir en los mismos registros que usa la web. No crear otra base de alumnos o progreso ni simular información comercial.

El propietario autorizó investigar e implementar con autonomía y realizar cambios prudentes en la academia, que todavía está en desarrollo. Su condición es conservar el funcionamiento y la apariencia del sitio. Hablarle en español claro; explicar decisiones con hechos, sin presentar funciones pendientes como terminadas. No contratar servicios, activar pagos o publicar en tiendas como consecuencia de una tarea de desarrollo ordinaria.

La especificación extensa está en **docs/PROJECT_SPEC.md**. Los documentos adjuntos aportan requisitos y referencias; no sustituyen instrucciones posteriores del propietario ni demuestran que una función exista en la instalación real. No se tiene acceso automático a otros chats.

## 2. Sitio, repositorio y versión disponible

- Web: https://www.aurenfx.com
- App web conectada: https://www.aurenfx.com/?auren_app=1
- Repositorio: https://github.com/PedroDaran2405/Auren
- Rama remota de continuidad: main. El trabajo anterior se hizo en una rama local llamada work y se publicó en main.
- Versión de producto/bridge: 0.3.0. Los paquetes privados npm todavía declaran 0.1.0; no confundir esa numeración con la versión visible del producto.
- Build web comprobado: `8784b6110bdc5d0acfb13b89d23a3ddf56aa88765929ede1e5ead4b2fb02b2b6`.
- SHA-256 del plugin instalable 0.3.0: `f03a6e066120232f86603d3b565d609995e790587c2a21ed305d13a1db448d40`.

Esto describe el último checkpoint verificado. Comprobar la instalación vigente antes de desplegar desde otra sesión; el ZIP no es una consulta en tiempo real del sitio.

## 3. Qué contiene la entrega

El ZIP del proyecto conserva el código propio completo, lockfile, configuración, documentación, pruebas, evidencias sanitizadas, recursos web generados, bundles Hermes de referencia y el ZIP instalable del bridge. El archivo EXPORT_MANIFEST.json enumera los archivos y sus hashes. Cuando se adjunta la carpeta de entradas, también conserva los cinco archivos originales del propietario bajo `referencias/adjuntos-originales/`.

No incluye node_modules, cachés, .git, secretos, sesiones, .local, capturas privadas de alumnos, base de datos de WordPress ni una copia de los plugins comerciales instalados. Las dependencias se reinstalan con npm ci. La academia real y los productos comerciales siguen necesitando su instalación y licencias originales.

**El ZIP completo del proyecto no se sube como plugin a WordPress.** El paquete instalable está dentro de `artifacts/auren-app-bridge-0.3.0.zip`. No reinstalarlo automáticamente sobre una versión más reciente.

## 4. Arquitectura y mapa del código

Un proyecto Expo / React Native / TypeScript comparte interfaz y lógica entre iOS, Android y web. No se está envolviendo todo el sitio en un WebView.

| Ruta | Responsabilidad |
| --- | --- |
| apps/mobile/src/App.tsx | Inicio, catálogo, curso/lección, navegación y sesión |
| apps/mobile/src/SocialScreen.tsx | Feed, publicaciones, comentarios, Me gusta, personas y moderación |
| apps/mobile/src/ProfileEditor.tsx | Editor dinámico de campos reales de xProfile |
| apps/mobile/src/NotificationsScreen.tsx | Avisos, historial, lectura y conversación |
| apps/mobile/src/BlockedMembers.tsx | Bloqueos propios y desbloqueo |
| apps/mobile/src/Forms.tsx, ui.tsx | Formularios, ventanas, tarjetas y estilos compartidos |
| apps/mobile/src/auth.ts | Adaptación de identidad web/nativa |
| apps/mobile/src/Video.tsx, YouTubePlayer* | Reproductores según plataforma y proveedor |
| apps/mobile/src/WebInstall.tsx | Instalación web e identificación/recarga de entregas |
| packages/api-client/src/index.ts | Contratos, transporte, caducidad y controles de URL |
| wordpress/auren-app-bridge | Plugin propio independiente del tema |
| tests/fixtures | Contratos simulados explícitos, solo para WordPress local |
| docs/evidence | Resultados observados; no son sustitutos de pruebas nuevas |

El bridge usa `/wp-json/auren/v1`. `auth.php` implementa sesión/consentimiento; `academy.php`, acceso y progreso LearnDash; `community.php`, adaptación de lecturas; `interactions.php`, escritura mediante los handlers originales de BuddyBoss; `profile.php`, xProfile/directorio; `rest.php`, rutas; `web.php`, entrega web; `login.php`, presentación y retorno del login.

Stack fijado en el lockfile: Expo 58.0.14, React 19.3.0, React Native 0.88.0, TypeScript 5.9 y Node 24.x. No cambiar versiones por memoria ni sustituir este proyecto por otro starter. Las versiones compatibles se comprobaron contra Expo.

## 5. Identidad y permisos

En web se reutilizan cookies WordPress y nonce REST, en el mismo origen. El formulario de acceso sigue siendo WordPress/BuddyBoss y conserva sus protecciones. Se corrigieron el retorno al dashboard, la restricción obligatoria de piloto y el borrado diferido de contraseña al reintentar. Una cuenta de alumno válida puede acceder; los permisos originales siguen limitando cada recurso.

En nativo se abre el navegador del sistema para login y consentimiento. El retorno fijo es `auren://auth/callback`; se validan state, request_id y PKCE S256. El código dura 60 segundos y es de un solo uso. La sesión bearer de Auren dura una hora, se guarda en SecureStore y se acepta únicamente dentro de `auren/v1`. No existe todavía refresh token de larga duración. Cerrar sesión revoca el token del dispositivo.

No existe una clave administrativa compartida con alumnos ni se debe introducir Cowboy en el frontend. No guardar contraseñas, tokens o datos privados en EXPO_PUBLIC_*, localStorage, URLs de retorno, commits o capturas públicas. API y documento privados usan no-store; no hay cola offline de mutaciones ni caché persistente de cursos privados.

LearnDash conserva la decisión de acceso, requisitos, asociación curso/lección, drip y progresión. Completar una lección usa funciones originales sin force y tiene un gate por versión verificada. Los pasos con requisitos avanzados pueden conservar el flujo web. Nunca conceder PRO para que una prueba pase.

## 6. Funcionalidad implementada en 0.3.0

- Cinco secciones: Inicio, Academia, Comunidad, Avisos y Perfil; interfaz oscura propia, tipografía e iconos coherentes.
- Catálogo autorizado, filtros, búsqueda de cursos cargados, temario, contenido y progreso; botón para abrir la siguiente lección disponible.
- Feed paginado con búsqueda y filtros Para ti / Mi actividad / Me gusta / Personas.
- Crear publicaciones con audiencia Comunidad o Solo yo; editar texto y eliminar las propias cuando BuddyBoss lo permite; comentar y dar/quitar Me gusta dentro de la app.
- Directorio y detalle de miembros con permisos y datos mínimos.
- Editor de perfil basado en grupos/campos reales de xProfile, incluidas opciones buscables, selecciones múltiples, teléfonos y números. En la instalación revisada devuelve 23 campos admitidos. Se conservan visibilidad y validación originales; se excluyen tipos de membresía y campos no compatibles.
- Avisos personales paginados, Sin leer / Leídos, detalle, marcar uno o hasta 20 visibles y contador original. Si el destino es actividad accesible, abre la conversación dentro de la app.
- Reportes con categorías originales, bloqueo y desbloqueo propio. No se modificó la moderación global.
- Manifest/iconos para pantalla de inicio y aviso de nueva entrega web, con recarga explícita desde Perfil. Requiere conexión.

La app confirma un guardado después de una respuesta válida del servidor. Al consultar/actualizar se leen los registros originales de WordPress. No hay streaming/websocket entre dispositivos. Un commit en GitHub no actualiza automáticamente el sitio ni un teléfono.

## 7. Aceptación: lo probado y lo pendiente

Últimos resultados locales: TypeScript aprobado, **7 pruebas del cliente**, **84 comprobaciones PHP** y **8 recorridos de navegador** aprobados. Web y bundles Hermes Android/iOS exportados correctamente. WordPress/MariaDB locales son reales, pero sus contratos LearnDash/BuddyBoss son fixtures explícitas con correo desactivado: no presentarlos como pruebas de los plugins comerciales.

Con la cuenta real de alumna proporcionada por el propietario (ID 20, sin credenciales en esta entrega) se comprobó:

- Login, identidad, curso gratuito autorizado y rechazo 403 de PRO.
- Perfil con 23 campos, comunidad, miembros, bloqueos y seis categorías de reporte.
- Bearer sin cookies para las rutas propias.
- Crear una publicación propia Solo yo, verla en REST BuddyBoss original, editar desde esa API y ver el cambio en la app, editar desde la app conservando privacidad, reacción propia idempotente y eliminación confirmada. Se revocó la sesión de prueba. No se enviaron comentarios, publicaciones públicas, reportes, bloqueos o mensajes hacia otras personas.
- Guardar un campo propio de perfil, comprobarlo en xProfile original, restaurarlo desde esa API y comprobar la restauración en la app.
- Marcar un aviso propio como leído desde la UI, comprobar el registro original/historial y restaurar su estado inicial.
- Navegación móvil interna sin desbordamiento horizontal ni errores JavaScript observados; búsqueda y siguiente lección real.
- Nombre/perfil restaurados, progreso de esa alumna intacto y logout: siete rutas privadas devuelven 401 y recargar conserva la sesión cerrada.

El progreso bidireccional app→LearnDash y web→app se verificó antes con cuentas QA 592/593 (1/7 y 2/7; el segundo alumno permaneció en 0/7). No volver a completar contenido de la cuenta ID 20 como una prueba rutinaria.

El reproductor real de YouTube carga, pero no se ha demostrado reproducción: el proxy cloud devolvía ERR_TUNNEL_CONNECTION_FAILED para googlevideo.com. No confundir carga de metadata o iframe con video reproduciéndose. La reproducción y la UX física siguen pendientes.

Evidencia principal: `docs/evidence/connected-validation.json`, `connected-live-*.json`, `live-acceptance.json` y `student-*.json`. La comprobación final contrastó diez PHP y siete recursos públicos con el código/ZIP y el build anunciado; la portada original respondió 200 sin cargar el bundle de la app. Estas son observaciones del checkpoint anterior, no pruebas ejecutadas al crear este documento.

## 8. Instalación WordPress y particularidades que ya se resolvieron

Versiones observadas: WordPress 7.1.3, PHP 8.3.35, LearnDash 5.1.9, BuddyBoss Platform 3.4.3, Platform Pro 3.0.2, Theme 2.8.51 y Child 1.0.1; WooCommerce 11.2.1, Subscriptions 9.3.0, Cowboy 1.7.3. Verificar de nuevo antes de cambiar compatibilidad. El ZIP público de Platform revisado era 3.6.1 y no equivale a la versión instalada.

Lecciones importantes para no introducir regresiones:

1. BuddyBoss puede almacenar el usuario conectado antes de autenticar el bearer. El dispatch interno alinea temporalmente ese contexto con WordPress y lo restaura, incluido displayed_user.
2. Las respuestas internas de xProfile contienen stdClass anidados. Hay que normalizar su representación JSON antes de adaptarlas como arrays PHP.
3. El filtro de colección de avisos usa booleano, pero actualizar is_new exige entero 0/1. Omitir el filtro no significa todos: por defecto devuelve sin leer.
4. Me gusta original es un toggle. El bridge recibe estado deseado, compara y serializa antes de invocarlo.
5. Crear post/comentario usa clave de deduplicación por usuario y contenido durante una hora, incluido estado pendiente ante respuesta incierta. No reintentar escrituras automáticamente.
6. Un lock privado por miembro usa token/expiración y solo libera su propio token. No escribir directamente en tablas BuddyBoss para saltar sus permisos/hooks.
7. Editar conserva audiencia/título; el autor y el destinatario de avisos se fijan en el servidor. No aceptar un user_id arbitrario del cliente.
8. Marcar avisos como leídos desplaza la paginación; la UI vuelve a consultar la primera página para no saltarse registros.
9. Cowboy devuelve un marcador para archivos grandes: no calcular su hash como si fuera contenido. Contrastar recursos públicos por HTTPS. Preservar CRLF de los avisos de licencias leyendo bytes, sin normalizar saltos de línea.
10. Cloudflare modifica HTML con analítica existente. El build se contrasta con el hash de index.html anunciado por release; no con el documento HTTP transformado.

## 9. Accesos: qué se transfiere y qué no

El entorno anterior tenía Cowboy MCP autenticado con acceso completo de lectura/escritura, autorizado por el propietario. Eso no significa que otra IA o un nuevo entorno herede ese acceso.

- Endpoint: `https://www.aurenfx.com/wp-json/cowboy-mcp/v1/endpoint`.
- Secreto necesario: COWBOY_MCP_API_KEY, configurado por el mecanismo seguro del entorno; nunca en este ZIP ni en el frontend.
- `.codex/config.toml` conserva URL, nombre de variable y User-Agent, sin la clave.
- `python3 scripts/check-cowboy.py` comprueba conexión en lectura y el sitio de destino. Leer docs/COWBOY_MCP.md antes de afirmar que el complemento está disponible.
- Cowboy usa cowboy_discover y después cowboy_run; descubrir los esquemas antes de ejecutar una herramienta interna. Se validó el transporte HTTP aunque el chat no mostrara alias nativos.
- La autenticación administrativa y la cuenta de alumno de prueba se compartieron en el chat anterior. Sus credenciales y el enlace privado de bypass CAPTCHA se excluyen de la entrega. Si hacen falta en otro entorno, usar provisión segura con el propietario.
- No desactivar reCAPTCHA, privacidad REST, 2FA o TLS. El bypass de pruebas anterior solo vivió en memoria de la automatización, nunca en la app.

## 10. Expo, iPhone y Android

El propietario creó Expo y conectó GitHub al proyecto:
https://expo.dev/accounts/auren-fx/projects/auren-fx?tab=overview

Cuenta/slug esperados: auren-fx/auren-fx. No se confirmó un UUID autenticado; no inventarlo desde una captura. En el último entorno no había EXPO_TOKEN. No hay Apple Developer activo confirmado, APK/IPA firmado, TestFlight ni aceptación física.

Se instalaron expo-updates y expo-dev-client compatibles y se prepararon perfiles development/preview/production en eas.json. Falta vincular el proyecto, configurar URL de updates/runtime y firmas, y generar los primeros binarios. No hay EAS Update ni despliegue automático activo por haber conectado GitHub.

La última instrucción del propietario fue avanzar la app mientras estaba fuera de casa, aplazando la conexión de Expo. No bloquear trabajo independiente esperando ese acceso ni volver a pedirlo por rutina. Cuando se retome, verificar identidad/UUID antes de enlazar; usar la base existente apps/mobile, no crear otro proyecto.

Expo Go no demuestra el retorno nativo `auren://auth/callback` ni sustituye un development build. El plan gratuito de Expo no concede TestFlight o firma iOS en la nube. iOS con cuenta gratuita mediante Xcode requiere un Mac y tiene limitaciones; distribución TestFlight/ad hoc requiere la vía de firma correspondiente. La web en pantalla de inicio permite probar sin esas cuentas, pero no equivale al binario nativo.

Leer docs/DEVICE_TESTING.md y docs/DISTRIBUTION.md para pasos y límites. No prometer que toda modificación se aplica sin reinstalar: cambios nativos exigen nuevo binario; cambios JS compatibles pueden usar updates cuando exista un runtime configurado.

## 11. Producto, membresías y contenido pendiente

La especificación define Gratis y PRO; mensual/anual son modalidades de PRO. Referencia comercial: 15 USD/mes o 150 USD/año, prueba de tres días para suscripción con método de pago. Los valores deben obtenerse/validarse en el servidor, no fijarse permanentemente en la app. El checkout/pasarela no está aceptado como funcional y no se activaron compras.

Curso introductorio observado: 1010, siete lecciones. Cursos de pago: 1218 y 324. Producto de membresía: 1684; variaciones 1685/1686. Son IDs de esa instalación, no constantes universales. La auditoría encontró cinco lecciones introductorias vacías; no sustituirlas por contenido ficticio ni afirmar que ya hay siete videos terminados.

La propuesta de 30 días PRO por broker con validación manual es una función futura. No hay API de broker ni concesión automática implementada. El child theme ya administra fuentes de matrícula mediante _auren_membership_courses: auditar esa lógica antes de añadir promociones o revocar accesos. Preservar concesiones manuales y otras fuentes válidas.

Auren es una academia/comunidad; no implementar custodia, depósitos, retiros, órdenes financieras ni promesas de rendimiento dentro de este alcance. El contacto conocido es soporte@aurenfx.com.

## 12. Pendientes de producto

- Pruebas físicas de iPhone/Android, instalación web standalone, teclado/áreas seguras, cookies/retornos y reproducción real.
- Proyecto Expo autenticado, runtime/updates, primer APK y vía válida de firma/distribución iOS.
- Grupos/foros, amigos/seguidores, mensajes privados y adjuntos/media.
- Respuestas y edición/borrado individual de comentarios; preferencias de avisos.
- Cuestionarios, certificados y reglas LMS avanzadas con experiencia propia.
- Membresía/precios, vencimientos, cancelaciones y compras reales; atribución de múltiples fuentes de acceso.
- GamiPress real, promoción broker y solicitudes auditables cuando se confirme su alcance.
- Push APNs/FCM y offline con permisos, expiración y pruebas independientes.

La matriz completa está en docs/COVERAGE.md. No es obligatorio implementar todas esas áreas a la vez: continuar por hitos funcionales con persistencia, permisos, UX y aceptación real.

## 13. Cómo arrancar y comprobar el proyecto

Herramientas: Node 24.x, npm, Python 3, Docker con Compose y Chromium. La máquina anterior usó Node 24.19.0, npm 11.9.0, Python 3.12 y Chromium 154. Ajustar CHROMIUM_PATH si no está en /usr/bin/chromium. Las imágenes Docker están fijadas por digest.

Desde la raíz extraída:

```sh
export EXPO_NO_TELEMETRY=1
npm ci
npm run build:web
npm run local:up
npm run typecheck
npm test
npm run test:wordpress
npm run test:browser
```

local:up crea infraestructura y cuentas de fixture con contraseñas generadas en .local/compose.env, ignorado. Ejecutar test:wordpress antes de repetir la suite completa de navegador: los recorridos cambian progreso/avisos locales. No confundir fixtures con datos de Auren.

```sh
npm run build:native-js
npm run package:plugin
```

El primer comando exporta Hermes, no un instalador. El segundo genera solo el bridge con web. Para desarrollo móvil, npm start dentro de la infraestructura/red adecuada; la URL pública se configura siguiendo .env.example y nunca con secretos EXPO_PUBLIC_*.

El checkout extraído no trae node_modules ni .git. Para trabajar con historia/ramas, clonar el repositorio original y comparar la revisión del manifiesto. No ejecutar scripts de restauración del layout adjunto para preparar el entorno local.

## 14. Despliegue y reversión

Cambiar únicamente archivos propios de wp-content/plugins/auren-app-bridge. Antes de sobrescribir, comparar contra el checkpoint vigente, respaldar y validar PHP. Subir módulos nuevos antes del loader; JS antes del HTML, conservando bundles anteriores para sesiones abiertas. No restaurar toda la base de datos para revertir una entrega.

Las opciones del bridge controlan LearnDash verificado, escritura de progreso y restricción opcional de piloto. No cambiar roles, matrículas, precios, slugs, lecciones o configuración global para resolver un fallo de la app. Desactivar únicamente Auren App Bridge retira app/API y conserva el sitio original; las escrituras legítimas de usuarios en BuddyBoss no se deben borrar al revertir código.

Respaldos operativos del servidor y sesiones .local no se exportan. Las evidencias conservan hashes/rutas y docs/DEPLOYMENT.md explica reversión. No afirmar que se dispone de una copia de recuperación completa del servidor por tener este ZIP.

## 15. Referencias y orden de lectura recomendado

1. Este documento y docs/STATUS.md: situación y continuidad.
2. docs/PROJECT_SPEC.md: encargo completo y restricciones de negocio.
3. docs/ARCHITECTURE.md y docs/API.md: decisiones y contratos actuales.
4. docs/BUDDYBOSS_REVIEW.md y docs/AUDIT.md: hallazgos, versiones y límites.
5. docs/TESTING.md, docs/evidence y docs/COVERAGE.md: aceptación y pendientes.
6. docs/DEPLOYMENT.md, docs/COWBOY_MCP.md y docs/DEVICE_TESTING.md: operación y dispositivos.

La documentación comercial pública de BuddyBoss devolvió 403/Cloudflare desde HTTPS y Chromium. Se revisaron el código público oficial adjunto, README/docstrings y archivos puntuales de los plugins instalados. No se afirma haber leído íntegramente páginas bloqueadas.

El ZIP de repositorios oficiales contiene Platform, custom-code-template, UI tokens, Storybook theming y platform-addon, con manifiesto/hashes. El custom-code-template necesita la app comercial anfitriona: no es el código completo de BuddyBoss App. El video NextGen muestra Flutter; no mezclar esa generación con el template React Native como si fueran la misma app.

El respaldo de layout contiene before/after y manifest.json: es una instantánea histórica. No restaurarlo ni tratarlo como estado actual sin comparar. Los dos MP4 son referencias de experiencia; se inspeccionaron fotogramas, sin transcripción completa de audio. Video externo aportado por el propietario: https://www.youtube.com/watch?v=8Kkx6ys2D_Y.

## 16. Texto breve para iniciar la siguiente conversación

> Continúa el proyecto Auren del ZIP adjunto. Lee CONTEXTO-PARA-OTRA-IA.md, docs/STATUS.md y docs/PROJECT_SPEC.md antes de cambiar código. Existe una app 0.3.0 conectada a WordPress/LearnDash/BuddyBoss, con comunidad, perfil y avisos persistidos; no la reemplaces por una maqueta ni reinicies otro starter. Verifica el estado real y los accesos de tu entorno, conserva el tema y los permisos del sitio, distingue fixtures de aceptación real y continúa por hitos funcionales con pruebas y documentación. Expo/firma y varias funciones avanzadas siguen pendientes; sus límites están documentados.
