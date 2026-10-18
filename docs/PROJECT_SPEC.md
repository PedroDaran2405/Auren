# Proyecto Auren App — encargo completo para Codex

Quiero que desarrolles la aplicación propia de Auren, una academia y comunidad de trading que ya funciona sobre WordPress, BuddyBoss y LearnDash. Trabaja como responsable técnico y de producto: investiga el código disponible, toma decisiones razonadas, implementa, ejecuta pruebas y conserva avances verificables. No te limites a proponer una arquitectura o dibujar pantallas.

Este documento es un encargo de desarrollo por etapas. El objetivo final es una experiencia móvil propia, con funciones equivalentes a las de BuddyBoss App que sean relevantes para Auren. No estoy afirmando que tengamos el código fuente de la aplicación comercial ni exigiendo una copia literal de su marca, sus activos o sus servicios privados.

## 1. Contexto del propietario y restricciones

- Soy Pedro Daran, fundador de Auren FX, en México. La interfaz del alumno y las explicaciones para mí deben estar en español claro, preferiblemente español de México.
- Tengo presupuesto limitado. Busco aprovechar lo que ya pago: WordPress, BuddyBoss, LearnDash, WooCommerce Subscriptions y el hosting existente. Justifica cualquier costo nuevo antes de contratarlo.
- Necesito una aplicación real que podamos probar con alumnos. Una interfaz con datos ficticios no demuestra integración.
- Prefiero avances por hitos que funcionen, con persistencia y documentación para continuar en otra sesión sin perder trabajo.
- Quiero poder revertir cambios concretos sin borrar otros avances ni afectar alumnos, pedidos o suscripciones.
- No necesito introducir inteligencia artificial, un chatbot ni una API de OpenAI en la aplicación. Este proyecto no requiere esas funciones para ser útil.

## 2. Producto y modelo comercial de Auren

Auren capta personas desde redes sociales, contenido y artículos. El usuario típico es un principiante que no conoce el trading. La ruta prevista es: conocer Auren, registrarse, tomar el curso gratuito, comprender las opciones y continuar con una membresía.

Hay dos niveles educativos, Gratis y PRO. Mensual y anual son modalidades de pago del mismo nivel PRO; el acceso por broker es una promoción temporal y no un tercer nivel educativo.

### Nivel Gratis

- Registro y acceso al curso introductorio sin tarjeta, suscripción ni depósito.
- La intención editorial es un curso de siete videos, cada uno de menos de cinco minutos, con conceptos básicos y explicación de las distintas rutas de aprendizaje.
- El contenido actual debe recuperarse de LearnDash. No supongas que ese nuevo guion ya fue grabado ni reemplaces las lecciones existentes para hacer coincidir una maqueta.
- Posibles rutas educativas: operar con capital propio, conocer las evaluaciones de fondeo y conocer el copy trading de estrategias de Auren.

Como referencia editorial para una futura revisión del curso gratuito, se propusieron siete temas: qué es trading y cómo se producen ganancias y pérdidas; qué es un broker y una cuenta demo; comprar, vender y cerrar una operación; riesgo, apalancamiento y costos; capital propio y evaluaciones de fondeo; funcionamiento y riesgos del copy de Auren; primera práctica y siguiente paso educativo. Esa propuesta no es una instrucción para reescribir o publicar contenido del curso durante el desarrollo de la app.

### Auren PRO

- Precio elegido: 15 USD mensuales o 150 USD anuales.
- La modalidad anual ahorra 30 USD frente a doce mensualidades al precio actual.
- En el sitio se conserva una prueba de tres días para nuevos suscriptores. Requiere método de pago; después se cobra la modalidad contratada y se renueva hasta cancelar.
- Esto es distinto de tomar el curso Gratis, que no requiere tarjeta, y de la promoción por broker, que no autoriza cobros al terminar.
- PRO incluye los cursos de pago disponibles de la academia. Hay seguimiento del aprendizaje y comunidad. No anuncies mentorías, sesiones en vivo, cursos futuros ni soporte ilimitado si el propietario no ha confirmado su existencia.
- Los precios, impuestos, duración de prueba, disponibilidad comercial y estado de suscripción deben llegar del servidor. No conviertas los precios de este contexto en valores permanentes dentro de las pantallas.
- La pasarela de suscripciones seguía pendiente en la última revisión. El sitio muestra el precio pero dirige al curso gratuito mientras el checkout no está disponible. No actives pagos ni simules que se puede comprar si el servidor indica lo contrario.

### Promoción de acceso por broker

- La oferta planteada y presentada en el layout es consultar 30 días de PRO, por una sola vez, con cuenta elegible vinculada a Auren y depósito mínimo de 100 USD en VT Markets, sujetos a verificación manual.
- No hay una API ni una confirmación automática del broker que podamos utilizar actualmente. No inventes webhooks, integraciones, acceso a saldos ni validaciones automáticas.
- En la versión del sitio revisada solo existe información y un enlace de consulta. No se implementó un sistema completo de solicitudes y concesión automática del beneficio.
- Diseña una futura solicitud con estados pendiente, requiere información, aprobada, rechazada y expirada, visible solo a las personas autorizadas.
- El administrador comprueba la atribución de la cuenta y el depósito por los medios disponibles. Una captura por sí sola no confirma la atribución a Auren.
- Una aprobación debe registrar actor, fecha, fundamento, inicio y vencimiento. No debe crear una suscripción con renovación ni requerir volver a depositar para conservar días ya concedidos.
- Al terminar la promoción, el alumno vuelve a Gratis salvo que tenga otra fuente válida de acceso. Conserva su avance.
- Esta función requiere primero resolver correctamente los permisos en el servidor. No habilites un botón que se conceda acceso a sí mismo.
- El contacto público conocido es soporte@aurenfx.com. No inventes un WhatsApp, un enlace de referido ni una relación de patrocinio.
- Actualmente se usa VT Markets en esta propuesta. Puede existir contenido antiguo que mencione Vantage o EC Markets. Registra la inconsistencia para revisión y mantén el broker configurable; no cambies artículos o contratos de producción como efecto lateral del desarrollo móvil.

### Posicionamiento financiero

La aplicación es una academia y comunidad. El depósito se realiza en la cuenta del alumno con el broker; no es ingreso ni pago a Auren. El copy trading y sus posibles comisiones se explican por separado. No implementes custodia de dinero, órdenes de trading, depósitos, retiros ni una terminal financiera dentro del alcance inicial.

No prometas que el alumno ganará dinero mientras aprende ni que la aplicación, el copy o una estrategia garantizan rendimientos. Presenta el aprendizaje, la práctica y los riesgos de manera comprensible. No inventes testimonios, resultados, alumnos activos, certificaciones ni porcentajes de rentabilidad.

## 3. Plataforma existente y datos observados

Sitio: https://www.aurenfx.com

Infraestructura conocida: VPS IONOS con Plesk y Cloudflare. Usa WordPress, BuddyBoss Theme con child theme activo, BuddyBoss Platform y Platform Pro, LearnDash, GamiPress, Elementor, WooCommerce y WooCommerce Subscriptions. Verifica versiones, configuración y capacidad actuales antes de decidir compatibilidad o rendimiento. Que Elementor esté instalado no implica que controle el layout de curso.

El centro de administración debe seguir siendo WordPress. No crees otra base de usuarios, cursos o progreso como fuente principal. Una base adicional de sesiones, dispositivos o notificaciones solo se justifica para funciones que realmente la requieran.

Identificadores observados, que deben comprobarse y convertirse en configuración, no en suposiciones universales:

| Recurso | ID observado | Nota |
| --- | ---: | --- |
| Curso introductorio | 1010 | Bienvenida & Activación, gratuito |
| Curso de trading | 1218 | Curso 1: Trading desde Cero, de pago |
| Curso de criptomonedas | 324 | Entendiendo el Mundo del Bitcoin y las Criptomonedas, de pago |
| Producto de membresía | 1684 | Membresía Auren, suscripción variable |
| Variación mensual | 1685 | 15 USD por mes |
| Variación anual | 1686 | 150 USD por año |

URL pública del curso de trading:
https://www.aurenfx.com/courses/curso-1-trading-desde-cero/

URL actual del curso introductorio, cuyo slug antiguo no coincide con el título:
https://www.aurenfx.com/courses/construye-tu-imperio-digital-el-poder-de-la-marca-personal/

No cambies esos slugs ni URLs como parte de este encargo. No infieras que un curso está completo solo porque tiene una página, un título o una descripción publicados. Maneja cursos vacíos, lecciones todavía no disponibles y videos faltantes sin crear contenido ficticio.

## 4. Integración de membresía ya implementada

En el child theme existen personalizaciones bajo estas rutas relativas a wp-content:

- themes/buddyboss-theme-child/functions.php
- themes/buddyboss-theme-child/inc/auren-membership.php
- themes/buddyboss-theme-child/assets/css/auren-membership.css
- themes/buddyboss-theme-child/assets/js/auren-membership.js
- themes/buddyboss-theme-child/learndash/ld30/template-single-course-sidebar.php
- themes/buddyboss-theme-child/learndash/ld30/template-course-item.php

El contexto siguiente describe la última revisión, no sustituye leer el código actual:

- La integración identifica los cursos de pago de LearnDash en modo Closed, excluyendo el curso gratuito 1010.
- Las suscripciones active y pending-cancel conceden acceso mientras corresponda. Los estados pending, on-hold, cancelled y expired no conceden acceso por esa suscripción.
- Hay una lista de cursos concedidos por esta integración, mediante el meta de usuario _auren_membership_courses.
- Al retirar acceso debe revocar únicamente las concesiones de las que esa integración es responsable. Debe preservar inscripciones manuales o anteriores.
- El curso gratuito se concede a usuarios registrados según la configuración existente.
- Existe una comprobación de disponibilidad de pasarela de suscripciones y un bloqueo de compra hasta que haya una compatible.
- Las personas con acceso al curso ven los controles de aprendizaje en lugar de la oferta de compra; un suscriptor mensual puede ver la opción de cambio anual.
- Se contemplan avisos para recuperar pagos fallidos. No sustituyas esos controles ni alteres suscripciones de clientes durante pruebas.

Si creamos acceso promocional, incorpora esa fuente al cálculo central de permisos antes de modificar matrículas. No basta con añadir una inscripción: la sincronización existente podría revocarla si desconoce el beneficio. Modela las fuentes de acceso y sus propietarios para evitar que una cancelación quite derechos válidos concedidos por otra vía.

Quiero una única decisión de acceso coherente para web y app, calculada en el servidor, que pueda explicar: gratuito, suscripción vigente, periodo restante tras cancelación, concesión manual o beneficio promocional válido. No supongas cómo resolver administradores y grupos: verifica el comportamiento de LearnDash y respétalo.

## 5. Archivos y repositorios de referencia

Puedo proporcionar BuddyBoss-repositorios-oficiales.zip. Contiene cinco ZIP originales, un LEEME y un manifiesto con enlaces, ramas y hashes:

1. https://github.com/buddyboss/buddyboss-platform — rama release.
2. https://github.com/buddyboss/buddyboss-app-custom-code-template — rama master.
3. https://github.com/buddyboss/bb-app-ui-tokens — rama master.
4. https://github.com/buddyboss/storybook-bb-app-theming — rama main.
5. https://github.com/buddyboss/buddyboss-platform-addon — rama master.

Estos repositorios son fuentes y referencias. El segundo es una extensión que necesita una aplicación anfitriona; no contiene el núcleo completo de BuddyBoss App. Las carpetas android/ e ios/ de una biblioteca no prueban que sea una app independiente. Audita manifests, entradas, dependencias, licencias y configuración de compilación antes de afirmar que algo se puede reutilizar.

El archivo Auren-respaldo-layout-2026-10-18.zip conserva una instantánea de personalizaciones del sitio. Sus carpetas before/ y after/ son estados anterior y posterior del layout. Para entender el estado aplicado en aquella revisión, empieza por after/ y manifest.json. No ejecutes la restauración ni uses before/ como si fuera el código actual. Compara cualquier instantánea con el sitio o repositorio vigente antes de aplicarla.

Si los adjuntos no están presentes, busca los repositorios públicos y avanza con lo disponible. No asumas que tienes acceso a Library, a las herramientas de otro chat, al servidor o a credenciales por tener sus nombres en este documento. Pide solo el acceso específico que realmente bloquee la integración.

Documentación que debes volver a consultar en su versión vigente:

- https://buddyboss.com/resources/api/
- https://buddyboss.com/resources/app-codex/dependencies.html
- https://buddyboss.com/docs/what-can-be-achieved-with-developer-tools-buddyboss-app-custom-development-access/
- https://developers.learndash.com/learndash-rest-api-listing/
- https://developer.wordpress.org/rest-api/
- Documentación oficial de WooCommerce, WooCommerce Subscriptions, GamiPress y del stack móvil elegido.

Distingue el API REST de la plataforma web de los hooks internos que solo existen dentro de la app comercial. No confundas conocer el nombre de un hook con tener su implementación. Conserva avisos y obligaciones de licencia de cualquier componente reutilizado. Un ZIP público no debe tratarse como una licencia para copiar todos los activos o servicios asociados a la marca.

## 6. Arquitectura y primera decisión técnica

Prefiero TypeScript y React Native para Android e iOS. Evalúa Expo y sus herramientas si facilitan compilación, pruebas y mantenimiento, verificando soporte real para video, autenticación, enlaces y notificaciones. No fijes versiones por memoria: elige una combinación compatible, registra versiones y conserva el lockfile.

La aplicación debe tener identidad visual propia de Auren, aunque podamos aprender de BuddyBoss. Reutiliza WordPress como backend. Si faltan operaciones, construye un plugin de integración pequeño y documentado, separado del tema cuando sea viable. No copies toda la lógica de negocio en un backend nuevo.

Considera una estructura como apps/mobile, apps/web, packages/api-client, packages/domain y wordpress/auren-app-bridge, pero simplifícala si el alcance no justifica un monorepo. Evita sobreingeniería, microservicios y sistemas de tiempo real externos sin necesidad demostrada.

También quiero una vía de instalación sin tiendas. Android debe poder generar un APK firmado de distribución cuando tengamos las credenciales de firma apropiadas. Para iPhone en México, contempla una PWA como vía inicial accesible y TestFlight para beta nativa. La PWA no reemplaza silenciosamente el objetivo de aplicación nativa. Explica cuánto código compartir y qué adaptadores requiere cada plataforma antes de mantener dos interfaces completas.

Elige una estrategia, escribe una decisión breve con alternativas y empieza a implementar. No conviertas esa decisión en una excusa para detenerte esperando preferencias rutinarias.

## 7. Hito inicial obligatorio: una integración vertical real

Construye primero un recorrido pequeño que atraviese todas las capas:

1. Abrir la aplicación en un dispositivo o entorno de prueba disponible.
2. Iniciar sesión con una cuenta de prueba de Auren mediante un mecanismo apropiado.
3. Consultar identidad y permisos desde el servidor.
4. Mostrar el curso introductorio real y sus lecciones existentes.
5. Abrir una lección y reproducir un video real si hay uno accesible y autorizado.
6. Consultar y actualizar el progreso conforme a las reglas de LearnDash.
7. Verificar el resultado en la web y en una segunda carga de la app.
8. Cerrar sesión, limpiar datos privados locales y comprobar que no siguen accesibles.
9. Comprobar que una cuenta Gratis no puede obtener por el API el contenido protegido de un curso PRO.

Si no hay video publicado o falta acceso a staging, deja claro qué parte no pudo comprobarse. Implementa el reproductor y las pruebas con una fixture identificada como tal, pero no declares demostrada la integración de video real. Un GET exitoso del curso tampoco demuestra sincronización bidireccional del progreso.

## 8. Funciones de la primera versión utilizable

### Inicio y navegación

- Inicio con continuar aprendiendo, progreso y siguiente paso útil.
- Navegación inferior consistente, por ejemplo Inicio, Academia, Comunidad y Perfil.
- Onboarding breve, explicación Gratis/PRO y acceso a información de Auren.
- Enlaces profundos hacia curso, lección y publicación cuando la funcionalidad exista.
- Pantallas útiles para carga, vacío, error, sesión caducada y pérdida de conexión.

### Academia

- Catálogo real, con distinción entre curso disponible, gratuito, incluido en PRO y todavía sin contenido utilizable.
- Detalle de curso, temario, instructor y avance.
- Lista de lecciones y contenido con jerarquía de LearnDash.
- Reproductor móvil con controles accesibles, pantalla completa y adaptación al proveedor real del video.
- Continuación de aprendizaje y marcado de progreso respetando requisitos, temporizadores y prerrequisitos configurados.
- Materiales descargables solo si existen y el alumno tiene permiso.
- Cuestionarios y certificados reales según el inventario y la compatibilidad comprobada. Declara explícitamente qué parte queda para la siguiente fase.

### Identidad y perfil

- Registro, verificación de cuenta y recuperación de contraseña compatibles con WordPress/BuddyBoss; reutiliza flujos existentes seguros si el API no los expone adecuadamente.
- Perfil básico y avatar usando campos y permisos actuales, sin crear perfiles paralelos.
- Consulta de membresía, periodo, fecha de vencimiento y acciones realmente disponibles.
- Cierre de sesión y controles de privacidad que correspondan a la distribución elegida.

### Comunidad

- Feed paginado, detalle de publicación, comentarios y reacciones usando lo que el API permita realmente.
- Crear publicaciones y comentarios solo con los permisos y validaciones correctos.
- Avatares, nombres y enlaces a perfiles conforme a la privacidad configurada.
- Reportar contenido y bloquear usuarios al habilitar contenido generado por usuarios; documenta quién atiende esos reportes.
- No prometas mensajes en tiempo real si solo has implementado consultas periódicas.

### Artículos y descubrimiento

- Auren también quiere publicar información educativa y de la propia marca para que las personas la encuentren en Google y en búsquedas con IA.
- La web pública sigue siendo el destino principal de esos artículos. Si añadimos una sección de artículos en la app, consume el contenido original de WordPress y conserva el enlace público de referencia.
- Mantén públicos los artículos y las páginas informativas destinadas a búsqueda, y protegidos los cursos, perfiles y contenidos privados que así estén configurados.
- No crees copias indexables del mismo blog en varios dominios como efecto lateral de la PWA. Define canonical e indexación según cada ruta y su función.
- No prometas posicionamiento, citas de IA ni tráfico por incluir el blog en la app. No publiques artículos automáticamente como parte de este proyecto.

### Membresía

- Comparación clara entre Gratis y PRO y entre las modalidades de pago del mismo PRO.
- Precios y condiciones desde el servidor, con total del periodo explícito.
- Estado comercial pendiente cuando el checkout no esté listo.
- Separación entre acceso del alumno y canal por el que pagó.
- Los flujos de compra dentro de apps distribuidas por tiendas se diseñarán tras verificar las reglas vigentes de cada plataforma y mercado. No uses enlaces ocultos, depósitos con broker ni otro mecanismo para eludirlas.
- No almacenes tarjetas ni construyas una pasarela propia.

## 9. Alcance posterior, con una matriz de cobertura

Mantén un inventario por función: objetivo, estado, endpoint utilizado, dependencia, prueba, limitación y fase prevista. Incluye al menos grupos, foros, mensajes privados, amigos/seguidores, búsqueda, documentos, fotos, notificaciones, cuestionarios avanzados, certificados, GamiPress, sesiones en vivo si se ofrecen, y contenido offline.

Recrea progresivamente las funciones que sean relevantes para Auren. No abandones el objetivo amplio al terminar una pantalla, pero tampoco intentes implementar todo a la vez sin validar la base.

Las insignias, puntos y rangos deben venir de GamiPress o del sistema existente. No inventes un saldo de puntos local. El chat requiere revisión de privacidad, borrado, adjuntos, moderación y entrega antes de declararlo funcional.

El modo offline de videos queda condicionado a derechos y capacidades del proveedor de video, almacenamiento, expiración de permisos y comportamiento real del sistema operativo. Cachear HTML no equivale a ofrecer cursos completos sin conexión. No prometas protección anticopia perfecta ni descargas de videos embebidos que no permitan esa función.

## 10. Autenticación, sesiones y permisos

Audita primero cómo podemos autenticar una app propia contra la instalación existente. No incrustes claves administrativas de WordPress, secretos de WooCommerce, contraseñas del propietario ni una application password compartida para todos los alumnos.

Prefiere mecanismos estándar y bibliotecas mantenidas. Si falta un mecanismo adecuado, justifica el diseño del bridge de autenticación, la duración de sesiones, revocación y renovación. No implementes tu propia criptografía. Comprueba compatibilidad con verificación de email, 2FA y otras protecciones habilitadas; no las desactives para que el login funcione.

En móvil guarda credenciales de sesión mediante el almacenamiento seguro de la plataforma. No conserves contraseñas en texto plano. En web evalúa sesión con cookies HttpOnly/Secure y protección CSRF según la topología elegida; no copies automáticamente un diseño de tokens nativos al navegador.

No se puede cambiar el usuario objetivo modificando un ID del cliente. Las operaciones de progreso y datos personales deben vincularse al usuario autenticado y comprobar permisos en servidor. No permitas que la interfaz se conceda PRO, apruebe un depósito o cambie el estado de una suscripción.

Incorpora pruebas de acceso cruzado entre alumnos, caducidad, revocación, peticiones repetidas y contenido privado. Las respuestas de cursos y archivos protegidos no deben filtrarse mediante caché pública, URLs directas sin control o un precargado de contenido que el alumno aún no puede leer.

## 11. Bridge de WordPress y contratos de API

Antes de programar endpoints, inventaría las rutas reales de WordPress, BuddyBoss y LearnDash, sus esquemas, paginación, autenticación y permisos. No asumas que todos los entornos tienen las mismas versiones o rutas.

Si necesitamos un namespace propio, auren/v1 es una propuesta, no un endpoint existente. Documenta cada operación y reutiliza funciones de los plugins para aplicar las reglas de negocio. Evita escribir directamente estructuras internas de progreso o cobro que deban manejar sus APIs.

Expón solo la información necesaria: usuario actual, catálogo permitido, progreso, permisos y disponibilidad comercial. No entregues todos los metadatos privados de usuarios o productos para ahorrar trabajo de modelado. Los endpoints administrativos requieren capacidades específicas y trazabilidad.

Separa entitlements de matrículas y de solicitudes de promoción. Define precedencia de fuentes válidas y vencimientos. Considera caché, invalidación, cambio de estado de suscripción y concesiones manuales. No prolongues acceso automáticamente por una caché vencida o por cambiar el reloj del teléfono.

Las notificaciones o tareas programadas necesitan una estrategia real de ejecución en el hosting; no supongas que WP-Cron tendrá puntualidad exacta. La autorización al solicitar contenido debe comprobar vencimiento aunque un proceso de limpieza todavía no se ejecute.

## 12. Diseño y experiencia de Auren

El sitio usa una identidad oscura, azul marino y azul eléctrico, con acentos cian. Como referencia inicial: fondo cercano a #000918, superficies azul oscuro, acción principal cercana a #3D5CFF y acento cercano a #80E6FF. Extrae los colores y activos oficiales cuando estén disponibles y verifica contraste; estos valores no autorizan cambiar el branding existente.

Prefiero Plus Jakarta Sans o Montserrat. La app debe sentirse moderna y cuidada: jerarquía clara, iconos coherentes, textos concretos y botones cómodos para el pulgar. Evita tarjetas repetitivas, exceso de degradados, colores de demostración y pantallas que parezcan un panel administrativo trasladado al teléfono.

La experiencia principal debe ser de aprendizaje y comunidad. Utiliza componentes móviles apropiados. Un WebView puede ser una solución delimitada para un flujo heredado difícil, pero identifica su uso y justifícalo; no presentes una web completa dentro de un contenedor como si ya fuera una implementación nativa equivalente.

Contempla áreas seguras, teclado, navegación atrás, estados de foco, lector de pantalla, tamaño dinámico de texto y pantallas pequeñas. Evita instrucciones técnicas o mensajes internos en el flujo del alumno. El contenido educativo debe aparecer con buena tipografía, espaciado y controles de video útiles.

## 13. Rendimiento, conectividad y privacidad

- Prueba el inicio y el catálogo en redes móviles lentas y dispositivos de capacidad modesta.
- Pagina feed, comentarios y directorios. Optimiza imágenes y evita descargar videos completos al abrir una pantalla.
- Usa cancelación de peticiones, reintentos limitados y estados claros. No generes tormentas de consultas al servidor.
- Separa cachés por usuario; elimina datos privados al cerrar sesión. No registres tokens, capturas de depósitos o información personal en herramientas de error.
- La caché del service worker debe tener una política explícita para contenido privado. No caches endpoints de cuenta y pagos indiscriminadamente.
- Mide antes de atribuir problemas al VPS. No cambies memoria, cachés, Cloudflare o plugins del sitio sin entender la causa y el alcance autorizado.
- Añade solo telemetría necesaria y documenta datos y proveedores. No actives marketing o seguimiento externo por defecto como efecto lateral.

## 14. Notificaciones

Diseña notificaciones de respuestas, novedades de aprendizaje y recordatorios voluntarios. Diferencia las notificaciones internas que ya existen en BuddyBoss de un servicio push para dispositivos.

Elige una solución compatible con Android, iOS y, si se desarrolla, web. Explica costos, límites y credenciales necesarias. Implementa registro y retirada de dispositivos, preferencias, idempotencia, prevención de duplicados y destino del enlace. Pide permiso después de explicar el valor y de una acción del usuario.

No prometas el mismo funcionamiento en segundo plano para PWA y nativo. Si falta un proveedor o credencial, conserva un adaptador comprobable y declara que no se ha demostrado entrega real. No envíes notificaciones a alumnos reales durante pruebas.

## 15. Distribución sin tiendas y publicación futura

Android: preparar compilaciones de prueba y un APK firmado de release cuando se disponga de un método de firma apropiado. Explicar instalación desde la web, requisitos actuales del sistema y verificación de desarrollador aplicables en los países de los alumnos. Conservar la identidad del paquete y una estrategia segura de actualización y custodia de claves. Un AAB no debe presentarse como un APK que un alumno instala directamente.

iPhone: no prometer distribución general en México simplemente alojando un IPA. Verificar reglas actuales por región. Para una beta nativa, contemplar TestFlight y sus requisitos de cuenta, revisión y vencimiento. No presentar TestFlight como distribución permanente de producción.

PWA: HTTPS, manifiesto, iconos, modo standalone, rutas, pantallas de instalación apropiadas por plataforma y política de actualización del service worker. Verificar capacidades de Safari/iOS y Chrome/Android con dispositivos reales. Mostrar instrucciones adecuadas cuando no exista un aviso automático de instalación.

Publicación futura: mantener preparados identificadores y configuración para tiendas, privacidad, permisos y fichas. No publicar, contratar servicios ni aceptar acuerdos por mí sin autorización específica. No uses distribución empresarial como sustituto de distribución pública a alumnos.

Fuentes a verificar antes de tomar decisiones de distribución:
https://developer.android.com/distribute/marketing-tools/alternative-distribution
https://developer.android.com/developer-verification
https://developer.apple.com/help/app-store-connect/test-a-beta-version/testflight-overview/
https://support.apple.com/en-us/118110
https://webkit.org/blog/13878/web-push-for-web-apps-on-ios-and-ipados/

## 16. Entornos, trabajo autorizado y reversión

Puedes inspeccionar los archivos aportados, crear código en el repositorio de la app, ejecutar herramientas de desarrollo y preparar pruebas locales. Trabaja con autonomía en decisiones rutinarias y reversibles. No te detengas para preguntar colores ya definidos o elecciones técnicas que puedas resolver con evidencia.

Antes de tocar una instalación WordPress identifica si es local, staging o producción y el alcance del acceso. La existencia de un conector no constituye por sí misma permiso para modificar producción. Prepara el plugin, cambios y pruebas fuera de producción. Solicita autorización cuando falte para desplegar en el sitio real o realizar una acción con costos o consecuencias externas.

Mantén Git con cambios pequeños, diffs legibles y puntos de recuperación. No hagas commits de secretos ni reescribas la historia del usuario. Si hay trabajo previo sin confirmar, protégelo y no lo mezcles con una restauración.

Cada modificación al backend debe documentar estado anterior, archivos/configuración afectados, verificación y reversión concreta. No uses restaurar toda la base de datos como mecanismo normal de deshacer una interfaz: podría borrar altas, pedidos y avances nuevos.

## 17. Pruebas y criterios de aceptación

Ejecuta las comprobaciones apropiadas para las capas que implementes: tipos, lint, pruebas de dominio, contratos de API, integración y recorridos de interfaz. Prioriza los riesgos reales sobre pruebas que solo repiten la implementación.

Cobertura mínima:

- Login correcto, incorrecto, verificación pendiente, sesión caducada, renovación y logout.
- Un alumno no puede acceder a datos, progreso, mensajes o documentos privados de otro.
- Gratis puede abrir el curso introductorio y no puede leer el contenido PRO por rutas alternativas.
- PRO vigente accede; una cancelación pendiente conserva solo lo que corresponda al periodo vigente.
- Caducidad, suspensión, fin de promoción y múltiples fuentes válidas de acceso funcionan sin borrar inscripciones manuales.
- Los precios y la modalidad seleccionada coinciden con el servidor y muestran el cobro total del periodo.
- Una pasarela no disponible no genera compras falsas ni botones que fallen sin explicación.
- El progreso persiste al cerrar y abrir la app y coincide con LearnDash; peticiones duplicadas no corrompen el estado.
- Videos inexistentes, formatos no soportados y enlaces vencidos tienen un comportamiento comprensible.
- Conectividad interrumpida, caché privada, cierre de sesión y cambio de usuario no filtran contenido.
- Navegación, áreas seguras, teclado, contraste y tamaños de texto se revisan en Android e iOS, señalando qué dispositivos o simuladores se usaron realmente.
- La PWA se prueba como instalada, no solamente en una pestaña de escritorio, si se incluye en el hito.

Nunca declares una compilación o publicación exitosa si no se ejecutó. Registra el comando, resultado, entorno y limitaciones. Las fixtures deben estar aisladas y etiquetadas. Si faltan credenciales o un equipo Mac, distingue código comprobado, compilación pendiente y prueba física pendiente.

## 18. Entregables y continuidad

Conserva, al menos, un README de puesta en marcha, .env.example sin secretos, documentación de arquitectura, contratos de API, matriz de funciones, pruebas y distribución. Añade CHANGELOG y un documento de estado con tareas completadas, bloqueadas y próximas, actualizado al cerrar cada hito.

El documento de continuidad debe permitir que otra sesión continúe sin repetir la investigación: decisiones, rutas relevantes, comandos ejecutados, errores abiertos, acceso necesario, estado de ramas y último punto de recuperación. Si ya existe un AGENTS.md, léelo y respétalo. No reemplaces instrucciones del repositorio para hacerte más fácil el trabajo.

Incluye un inventario de costos: infraestructura actual aprovechada, servicios nuevos opcionales, firma/compilación, cuentas de distribución y mantenimiento. No inventes tarifas ni prometas que una app propia siempre será más barata que la licencia comercial.

En cada actualización dime qué funciona y cómo puedo comprobarlo, qué falta y qué decisión estás tomando. No me entregues solo una lista de tareas futuras. Cuando haya un bloqueo, explica el requisito concreto y continúa con el trabajo independiente que sí sea posible.

## 19. Orden de ejecución solicitado

1. Lee el repositorio y los adjuntos. Identifica su estado y las restricciones de entorno.
2. Audita los cinco ZIP y separa código reutilizable, documentación, ejemplos y piezas que dependen de BuddyBoss App comercial.
3. Inventaría las integraciones y permisos existentes; verifica lo que esté accesible sin mutar producción.
4. Define arquitectura y alcance del primer hito con una decisión breve, rutas y criterios de aceptación.
5. Implementa inmediatamente el recorrido vertical real: sesión, identidad, curso gratuito, lección, progreso y logout.
6. Ejecuta pruebas, corrige problemas y prepara una forma reproducible de probarlo.
7. Amplía catálogo, estados Gratis/PRO, comunidad, perfil y distribución según los hitos comprobados.
8. Continúa con las funciones posteriores manteniendo la matriz de cobertura y los checkpoints. No declares que toda la app está terminada porque la primera demo ya abre.

Empieza ahora con la inspección y el primer hito ejecutable. Usa los recursos disponibles, no supongas que recuerdas conversaciones anteriores y no me devuelvas únicamente una propuesta.
