# Contrato Auren v1

Base: `{wordpress}/wp-json/auren/v1`. Namespace nuevo del plugin de este repositorio, no parte de BuddyBoss comercial. Todos los resultados, incluidos errores, llevan `Cache-Control: private, no-store`.

| Método y ruta | Autorización | Resultado |
|---|---|---|
| POST /auth/start | Pública, límite de frecuencia, HTTPS | Recibe challenge S256 y state; devuelve request_id y URL de consentimiento |
| POST /auth/exchange | Código de 60 s + verifier PKCE | access_token, expires_at, token_type; código de un solo uso |
| GET /me | Cookie + nonce o bearer de app | Identidad mínima y capacidades verificadas |
| GET /courses?page=N | Sesión y piloto | Cursos autorizados de esa página, next_page; no contenido PRO no autorizado |
| GET /courses/{course} | Sesión + acceso | Resumen, progreso y lista de lecciones disponibles/bloqueadas |
| GET /courses/{course}/lessons/{lesson} | Sesión + acceso + requisitos | Texto, video opcional, estado y completion_mode app/web |
| POST /courses/{course}/lessons/{lesson}/complete | Sesión + permiso vigente + escritura habilitada | Lección y progreso recalculados por LearnDash |
| POST /logout | Sesión | Revoca el bearer o la sesión web actual |

La identidad objetivo siempre es `get_current_user_id`. Parámetros `user_id` y `user` se rechazan. No hay endpoints de compra, de cambio de rol ni de concesión PRO.

`Progress = { completed, total, percent }`. `Course.access = { allowed:true, source:"learndash", label }`: esa etiqueta no prueba una modalidad de pago o fuente de matrícula concreta. `Lesson.video` puede ser null, direct (HTTPS), youtube (ID) o web (requiere flujo existente). `completion_mode` es decisión del servidor, nunca permiso concedido por el cliente.

Errores principales: login_required/session_expired 401, course_forbidden/pilot_only/prerequisites/lesson_locked/lesson_scheduled 403, not_found 404, web_required/completion_rejected 409, rate_limited 429, lms_unavailable/lms_not_verified 503. El cliente evita mostrar errores crudos de infraestructura.

El inventario de rutas observado en producción está en `evidence/public-api-inventory.json`. No deben tratarse como contratos universales de otras instalaciones.

## Ampliaciones 0.2.0

`GET /me` añade `profile_url`, `community_url`, `joined`, `unread_notifications` y capacidad `notifications`, sin email ni metadatos de cuenta privados.

`GET /community?page=N` devuelve `{items,next_page}`: ID, autor/avatar, texto saneado, fecha, tipo, imagen, enlace original, grupo y contadores reales. La petición interna usa `/buddyboss/v1/activity` con contexto view, 10 elementos y permisos originales. `GET /community/{id}` añade comentarios y respuestas de lectura (máximo 50 y cuatro niveles); el detalle sigue el permiso de lectura original. No hay parámetros para ampliar privacidad ni actuar como otro usuario.

`GET /notifications?page=N` devuelve `{items,next_page,unread_count}` con hasta 20 avisos sin leer del destinatario autenticado. Se fuerza user_id en servidor y se excluye cualquier resultado de otro destinatario. El cliente no puede elegir user_id; se devuelve 400. Estas consultas no marcan avisos como leídos ni envían push.

Todos estos endpoints mantienen la autenticación y los encabezados privados. `POST /logout` requiere sesión autenticada y transporte seguro, incluso si una restricción opcional del piloto impide consultar cursos; el alumno siempre puede cerrar su propia sesión.

## Ampliaciones 0.3.0: acciones conectadas

Todas las rutas siguen exigiendo cookie+nonce o bearer de app; el actor se fija en el servidor. BuddyBoss conserva permisos, privacidad, bloqueo y validación de contenido. Los errores internos se traducen a mensajes públicos.

| Método/ruta | Datos y efecto |
| --- | --- |
| GET /community?page=N&filter=all/mine/liked&search=texto | Feed original con búsqueda y alcance de cuenta; sin elegir otro autor para mine/liked |
| POST /community | content, privacy loggedin/onlyme, request_key UUID, title si el sitio lo exige; crea actividad propia |
| PATCH /community/{id} | content; solo autor con permiso nativo, conserva audiencia/título |
| DELETE /community/{id} | Solo autor con permiso nativo; elimina el registro original y su conversación |
| POST /community/{id}/comments | content y request_key; comentario en publicación visible y comentable |
| PUT /community/{id}/like | liked booleano; estado deseado idempotente sobre el toggle original |
| GET /profile | Campos/grupos reales de xProfile de la cuenta autenticada; valor escalar o lista, tipo, opciones y obligatoriedad |
| PATCH /profile | fields [{id,value}], máximo 50; valida IDs/tipos/opciones y handler nativo; no permite cambiar roles o tipo de membresía ni visibilidad |
| GET /members?page=N&search=texto | Directorio con permiso original; nombre/avatar/alta, sin email o datos administrativos |
| GET /members/{id} | Perfil mínimo y únicamente campos que permite la privacidad original |
| GET /notifications?page=N&filter=unread/read | Sin leer por defecto; read fija is_new=false explícitamente |
| PUT /notifications/{id}/read | Verifica destinatario y cambia estado, devuelve contador recalculado |
| PUT /notifications/read | ids hasta 20; verifica todos los destinatarios antes de empezar; errores posteriores pueden exigir refrescar la lista |
| GET /reports | Categorías reales del formulario de moderación original |
| POST /community/{id}/report | category y note; destino fijo actividad, permisos nativos |
| GET /blocked?page=N | Lista de bloqueos del miembro autenticado |
| PUT /members/{id}/block | blocked booleano; deriva registro de moderación desde la lista propia para bloquear/desbloquear |

Activity añade author.id, title, liked, privacy y permissions {like,comment,edit,delete}. Notification añade target de actividad cuando se puede resolver; abrirlo vuelve a comprobar visibilidad. /me anuncia capacidades profile, members, report, block y post_title detectadas en el servidor.

Publicaciones/comentarios llevan deduplicación por usuario y clave durante una hora, incluyendo estado pendiente para no repetir una escritura cuyo resultado es incierto. Un lock de opción propia serializa mutaciones por miembro; su liberación comprueba el token de la solicitud. No se reintentan mutaciones automáticamente. Los datos sociales permanecen en las tablas nativas, no en una base paralela de la app.
