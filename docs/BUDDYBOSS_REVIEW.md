# BuddyBoss y decisiones para Auren 0.3

Esta revisión amplía la auditoría inicial, no implica que todas las funciones comerciales estén implementadas.

## Fuentes y límites

Se intentaron las páginas oficiales de App, Platform, Platform Pro, Theme y documentación tanto por HTTPS como desde Chromium. BuddyBoss devolvió **403 / Attention Required (Cloudflare)** desde este entorno. No se eludió ese bloqueo ni se presentan esas páginas como leídas íntegramente. La referencia disponible es el código oficial adjunto, sus README/docstrings REST y archivos puntuales de la instalación real, leídos con Cowboy. Versiones confirmadas: Platform 3.4.3, Platform Pro 3.0.2 y Theme 2.8.51; no se presupone equivalencia con la rama 3.6.1 adjunta.

Los archivos comerciales permanecen fuera de Git. Se conserva únicamente un manifiesto de rutas/hashes de la revisión. Los vídeos adjuntos y el template oficial de personalización siguen siendo referencias de experiencia; no contienen el producto comercial completo. El template React Native y la demo NextGen basada en Flutter no se mezclan como si fueran la misma generación.

| Producto | Aporte relevante | Decisión en Auren |
| --- | --- | --- |
| Platform | Identidad, perfiles ampliados, actividad, comentarios, favoritos, miembros y avisos | Reutilizar sus REST y permisos como el alumno autenticado; escribir en sus registros originales |
| Platform Pro | El loader instalado declara reacciones, encuestas e integraciones; su disponibilidad depende de módulos/configuración | Detectar funciones/rutas reales; conservar restricciones, no anunciar módulos por el hecho de estar instalado Pro |
| Theme | Presentación y recorridos de la web, integración visual con aprendizaje y comunidad | Conservar tema/child theme; diseñar interfaz propia móvil sin copiar PHP/CSS comerciales ni romper las páginas existentes |
| App | Recorridos móviles, navegación persistente, interacción dentro de la app y distribución/actualización de binarios | Completar acciones dentro de Auren y mantener API compartida; firma, push, offline y distribución tienen aceptación independiente |

## Recorridos priorizados

| Recorrido | Antes | Implementación 0.3 |
| --- | --- | --- |
| Participar en comunidad | Lectura y enlace web | Crear, editar y eliminar publicaciones propias; comentarios; Me gusta; filtros y búsqueda con permisos de BuddyBoss |
| Gestionar perfil | Enlace al perfil web | Campos reales de xProfile, secciones, búsqueda de opciones, selección múltiple, contacto; guardar sin cambiar visibilidad ni roles |
| Gestionar avisos | Abrir enlace web | Detalle propio, marcar uno/visibles como leídos, historial de leídos, contador del servidor y conversación interna cuando el origen es actividad |
| Conocer miembros | Sin directorio propio | Directorio y perfil mínimo dentro de la app; datos adicionales solo cuando los permite la privacidad original |
| Moderación | No expuesta | Reportes originales y bloqueo/desbloqueo propio; no se activa moderación global ni se aceptan IDs de otro actor |
| Continuar aprendizaje | Abrir curso y elegir lección | Acceso directo a la siguiente lección disponible, búsqueda en el catálogo cargado y conservación de permisos/progreso |

## Hallazgos concretos que afectaron código

- `activity/{id}/favorite` es un **toggle**. El bridge recibe un estado deseado y serializa cambios del mismo miembro para evitar que un doble toque invierta la intención.
- La respuesta REST contiene `can_favorite`, `can_comment`, `can_edit`, `can_delete`, `favorited`, `post_title` y audiencia. Se usan capacidades del servidor, no el aspecto del botón como autorización.
- El componente de títulos puede exigir `post_title`. La app consulta su configuración y lo pide cuando corresponde; editar texto conserva el título/audiencia existentes.
- xProfile depende de `bp_loggedin_user_id()`, que puede estar almacenado antes de autenticar un bearer. El contexto interno se alinea temporalmente con el usuario autenticado de WordPress y se restaura después, incluido el displayed user que el feed modifica.
- El perfil real contiene grupos, teléfonos, país, números, checkboxes y multiselect. No se sustituyó por un formulario ficticio de nombre/biografía. Se excluyen tipos de membresía y campos no compatibles, y se valida el conjunto antes de enviarlo al handler original.
- Actualizar una notificación exige `is_new` entero (0/1), mientras que su filtro de colección es booleano. La prueba en Auren detectó el rechazo de false al actualizar; se corrigió el adaptador y se hizo estricta la fixture.
- Las notificaciones usan `is_new=true` por defecto; omitirlo **no significa todos**. La UI separa Sin leer / Leídos y envía el booleano explícito.
- Una notificación de comentario puede apuntar al ID del comentario, no al post raíz. Se resuelve el destino y se vuelve a comprobar el acceso al abrirlo.
- Los reportes devuelven una lista de descriptores de formulario. Las categorías se obtienen del descriptor `report_category`; no se inventan razones ni destinatarios.
- El listado de moderación fija el usuario desde BuddyBoss. El desbloqueo deriva el ID de moderación de ese listado propio; el cliente no elige un ID de reporte ajeno.

## Semántica de sincronización

Se muestra «guardado» después de una respuesta válida del servidor. No se mantienen perfiles, likes, avisos o posts paralelos en almacenamiento del teléfono. Una recarga obtiene los registros de WordPress. Al volver a una pantalla se consulta su fuente; no hay una promesa de streaming/websocket entre dispositivos.

Las mutaciones no se reintentan automáticamente. Publicaciones/comentarios incorporan clave de reintento por usuario y contenido; un bloqueo breve evita cambios concurrentes incompatibles. Los locks son opciones privadas del bridge, no acceso SQL a las tablas de BuddyBoss. Confirmar borrar explica que también elimina la conversación de la web.

## Pendientes que no se ocultan

Grupos/foros completos, mensajería privada, adjuntos/media, respuestas y edición individual de comentarios, preferencias avanzadas de notificaciones, push, offline, cuestionarios/certificados nativos y compras siguen en etapas posteriores. Los enlaces de autenticación/recuperación y actividades avanzadas de LearnDash mantienen el flujo original cuando es necesario. Expo continúa pendiente de autorización y firma según la vía de iOS elegida.

La evidencia distingue fixture local, API real y recorrido físico. No se publican mensajes, reportes o reacciones de prueba hacia personas reales para demostrar una pantalla; los recorridos sociales completos se prueban en WordPress local con correo desactivado. La aceptación real utiliza una publicación propia «Solo yo», sin menciones, y una reacción propia; se verifica contra el REST original y se elimina el registro al terminar. El helper instalado de avisos a seguidores omite esa audiencia. No se envían comentarios, reportes, bloqueos ni mensajes hacia otras personas en Auren.


## Diseño y gamificación 0.4.0

Se aplican los conceptos visuales entregados por el propietario a las acciones ya conectadas. Se conserva Avisos y no se incluye una pestaña de mensajes. El adaptador GamiPress consulta el plugin nativo; la moneda, niveles y logros coinciden con el encabezado web. [GAMIFICATION](GAMIFICATION.md) registra objetos/configuración, fuentes, pruebas y contenido que falta antes de implementar los bloques restantes del concepto. Las cantidades, cursos y recompensas de las imágenes no se usan como datos de alumno.
