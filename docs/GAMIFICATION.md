# Gamificación y diseño 0.4.0

La app y el encabezado web consultan los mismos objetos de GamiPress. No existe una segunda moneda, tabla de puntos, regla de recompensas o escritor de rangos en el cliente. GamiPress y sus integraciones conservan el otorgamiento de puntos/logros/niveles.

## Configuración real auditada

GamiPress **8.0.6**, Pro **1.1.0**. Tipo de puntos existente `credits`, ID **610**, imagen original **1578** (`2025/07/AUREN-CREDITS-V4.png`). La interfaz lo identifica como **Auren Credits**, sin renombrar ni duplicar el tipo de WordPress. El encabezado muestra imagen y número; hover/foco abre el tooltip «Auren Credits» y tocar el saldo de la app abre su detalle.

Había siete rangos publicados del tipo `rangos` (613–619), sin requisitos publicados de ascenso. Se conservaron. Los logros configurados son 620 y 854, con pasos 1415 y 1607 y recompensas de 100 y 20 Credits. Otros dos logros publicados carecen de pasos y no se anuncian como recompensas disponibles. Se respeta el ajuste de ocultación de GamiPress.

El propietario solicitó un nivel de muestra. Se creó mediante las habilidades nativas de GamiPress y se publicó con WordPress:

| Objeto | ID | Regla |
| --- | --- | --- |
| Tipo Niveles Auren (`niveles-auren`) | 1691 | Tipo nativo independiente, editable |
| Inicio | 1692 | Primer rango publicado; GamiPress lo devuelve como nivel inicial |
| Explorador | 1693 | Segundo rango, requisito nativo 1694 |
| Requisito de Explorador | 1694 | Saldo `credits` mayor o igual a 200 |

No se otorgaron manualmente puntos, logros o rangos a alumnos para esta entrega. El umbral es **saldo**, no XP ni puntos de por vida. Al alcanzar requisitos, el motor original de GamiPress decide el ascenso; leer la app no lo concede. El rango inicial sigue la semántica nativa del rango de menor orden.

## Cómo administrarlo

1. En WordPress abre **GamiPress → Niveles Auren**: [lista de niveles](https://www.aurenfx.com/wp-admin/edit.php?post_type=niveles-auren).
2. Edita Inicio/Explorador, sus nombres, imágenes, orden y requisitos con el editor normal de GamiPress. Puedes añadir más niveles; el orden determina su número en la app.
3. Las reglas de obtención de Credits y los pasos/recompensas de los logros se siguen editando en GamiPress.
4. **Ajustes → Auren App → Gamificación compartida** permite elegir el tipo original de puntos y el tipo de rangos mostrado. Automático prefiere `credits` y `niveles-auren`. Para usar los siete rangos anteriores, selecciona `rangos` y configura sus requisitos en GamiPress.
5. Actualiza o vuelve a la app. El encabezado y la identidad se vuelven a consultar al recuperar foco y cada 60 segundos en primer plano; las acciones sociales y completar una lección también actualizan identidad. No hay sincronización mediante websocket.

Un requisito sin progreso cuantificable se muestra sin porcentaje inventado. Un nivel sin siguiente rango muestra el estado final. Los requisitos opcionales no aumentan el denominador de la barra. El progreso agregado es el promedio de condiciones obligatorias medibles, no una nueva moneda. Los saldos ajenos no pueden consultarse pasando un ID de usuario.

## Interfaz y marca

Las seis pantallas de concepto orientan la paleta azul profundo/azul eléctrico/cian, tarjetas con borde, navegación inferior y jerarquía. Inicio muestra el curso autorizado para continuar, nivel, siguiente logro y accesos existentes. Cursos, lección, comunidad, avisos y perfil conservan sus acciones sobre WordPress. La pestaña es **Avisos**; no se agregó Mensajes.

El logo es el archivo original que usa el sitio: `2026/07/ChatGPT-Image-9-jul-2026-10_53_16-p.m-e1783659259666.png`, SHA-256 de la copia nativa registrado en la evidencia. Se incluye sin modificar en `apps/mobile/assets/auren-logo.png`; web reutiliza el archivo original, y `/me` toma el logo configurado en BuddyBoss. Las portadas de cursos y perfil y las imágenes de logros/moneda proceden de WordPress. No se generaron fotografías de cursos o mentores ficticios.

El sitio conserva sus plantillas: el módulo propio `gamification-web.php` encola CSS/JS solo para miembros y monta el indicador en los contenedores existentes del encabezado BuddyBoss. El rango completo se muestra en escritorio con espacio disponible; el encabezado móvil compacto conserva el saldo. No se editó el tema padre, el hijo ni el código de GamiPress. Desactivar Auren App Bridge retira el indicador; los objetos nativos de GamiPress permanecen editables.

## Contenido y funciones pendientes

- El curso real introductorio es **Bienvenida & Activación**. Las imágenes «Trading desde cero» y sus cantidades eran conceptos; no se crearon cursos, progreso ni recompensas con esos datos.
- La primera lección contiene un YouTube existente. Varias lecciones del curso siguen sin contenido. La app indica «Contenido por publicar» en lugar de simular un reproductor.
- No se añadieron PDF, materiales, recompensas de lección/curso, duración de videos o certificados sin una fuente configurada. Hace falta publicar/configurar esos elementos antes de exponerlos.
- Grupos/foros nativos, adjuntos, descargas offline, certificados y push conservan su fase pendiente. No tienen botones de demostración que finjan implementarlos.
- Falta la aceptación física iPhone/Android y la firma/distribución Expo; exportar JavaScript/Hermes no equivale a una app instalada ni publicada en tiendas.

## Pruebas y acceso

Se ejecutan las pruebas habituales más `npm run test:gamification` con **GamiPress gratuito real** en Docker. Instalación reproducible: `npm run local:up`, `npm run local:gamipress`, `npm run test:gamification`. LearnDash/BuddyBoss locales siguen usando fixtures explícitas. Los objetos y saldos de estas fixtures nunca se envían a Auren.

La aceptación en Auren verifica la cuenta de alumno, moneda/imagen originales, niveles/requisitos, logros, igualdad entre cookie+nonce y bearer nativo, rechazo de otro usuario y revocación del bearer. Se conserva el progreso inicial. Las capturas con datos de alumno están en `artifacts/` ignorado, no en GitHub público.

Chromium no confiaba en la CA actual del proxy cloud. La revisión automática rechazó ampliar persistentemente su almacén; no se hizo ese cambio. Las lecturas reales usan HTTPS con validación de certificados. La revisión visual usa el bundle actual y datos/recursos reales mediante un servidor temporal limitado a loopback y peticiones GET verificadas a Auren; los endpoints se reescriben únicamente para esa prueba local. No se desactiva TLS ni se cambia autenticación o CORS del sitio. La prueba física/directa en Safari/Chrome del propietario sigue siendo necesaria.
