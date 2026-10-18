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
