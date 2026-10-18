# Auditoría de Auren · 18 octubre 2026

## Evidencia y acceso

Se leyó completa `PROJECT_SPEC.md`. Se extrajeron ambos ZIP comprobando rutas y ausencia de enlaces simbólicos peligrosos. Coinciden los cinco SHA-256 del manifiesto BuddyBoss y los seis hashes before/after del layout. Se inspeccionaron fotogramas de los dos MP4; no se transcribió su audio. La URL adicional de YouTube queda como referencia, no como fuente de APIs.

El repositorio estaba vacío (rama `work`, sin commits). No existían AGENTS.md ni trabajo previo. No hay herramientas del complemento Cowboy ni búsqueda de otros chats expuestas en esta sesión. El sitio sí anuncia `/cowboy-mcp/v1/endpoint`, pero rechaza la sesión de WordPress sin su autorización MCP separada. Se obtuvo acceso administrativo por el formulario web existente, conservando sus campos ocultos; no se desactivó reCAPTCHA. No se guardaron credenciales ni el enlace privado en el repositorio.

La red inicialmente bloqueaba Auren y documentación. Se añadieron dominios al borrador de entorno; luego se confirmó HTTPS por curl y Chromium. La verificación TLS se mantuvo activa; Chromium usa la confianza del certificado del proxy del entorno.

## ZIP BuddyBoss

| Repositorio | Lo que contiene | Licencia observada | Decisión |
|---|---|---|---|
| buddyboss-platform / release | Plugin WordPress, REST, componentes sociales; versión 3.6.1 | GPL-2.0-or-later | Referencia de API; no sustituir la versión instalada |
| buddyboss-app-custom-code-template / master | `applyCustomCode`, TurboModule, podspec, biblioteca Gradle; requiere anfitrión RN 0.85.1+ y React 19.2.3 | MIT declarada en package.json | No es una app independiente |
| bb-app-ui-tokens / master | Tokens y transformaciones Style Dictionary; 0.0.3 | ISC declarada | Referencia; identidad y tokens propios |
| storybook-bb-app-theming / main | Add-on Storybook 6, React 17, dependencia relativa `../addon-development-kit` | MIT, archivo LICENSE | Herramienta de temas, no runtime móvil; no incorporada |
| buddyboss-platform-addon / master | Plugin de ejemplo 1.0.0 | GPL-3.0-or-later | Referencia de extensión, no backend móvil completo |

Los hashes y commits de archivo están en `evidence/buddyboss-manifest.json`. El video NextGen muestra “Built on Flutter”; no se debe asumir que esos videos y el template React Native describen la misma generación comercial. No se copiaron activos, marca ni servicios privados de BuddyBoss.

## Producción verificada

| Componente | Versión real |
|---|---|
| PHP | 8.3.35 (cabecera HTTP) |
| BuddyBoss Platform | 3.4.3 |
| BuddyBoss Platform Pro | 3.0.2 |
| LearnDash LMS | 5.1.9 |
| BuddyBoss Child | 1.0.1, activo; padre buddyboss-theme |
| WooCommerce | 11.2.1 |
| WooCommerce Subscriptions | 9.3.0 |
| GamiPress / Pro | 8.0.6 / 1.1.0 |
| Elementor / Pro | 4.3.5 / 4.2.0 |
| Cowboy MCP | 1.7.3 |

Consulta autenticada de plugins y cursos, inventario público REST guardado. Los plugins comerciales no se copiaron al repositorio. Se leyeron archivos puntuales de LearnDash mediante el editor administrativo, sin guardarlos en producción, para comprobar firmas y reglas. El archivo actual `inc/auren-membership.php` tiene SHA-256 `c53a53888eb965514ae670b911cfcf99d7ce2716e6137608ded0086fffd21883`, idéntico al `after/` del respaldo.

## Cursos y contenido

- 1010: Bienvenida & Activación, publicado, modalidad **open** (el documento decía gratuito; no es modalidad `free`), progresión libre, siete lecciones; slug conservado.
- 1218: Curso 1: Trading desde Cero, publicado, `closed`, progresión libre.
- 324: Entendiendo el Mundo del Bitcoin y las Criptomonedas, publicado, `closed`, progresión libre.
- 1015: Bienvenida a Auren FX. Bloque Gutenberg YouTube real; el oEmbed conserva el título “BIENVENIDOS A GM HUB”. Requiere revisión editorial, no reemplazo automático.
- 1017: Cómo funciona Auren. Texto breve “Hola! Comencemos!”, sin video.
- 1019, 1021, 1187, 1023, 1025: contenido vacío al auditar. No equivalen a siete videos grabados.

No se modificaron títulos, contenidos, slugs ni precios para completar una demo.

## Hallazgos de integración

- REST anuncia `ldlms/v1`, `ldlms/v2`, `learndash/v1`, `buddyboss/v1`, WooCommerce y application passwords. No se presupone JWT ni OAuth de alumnos.
- Las rutas `ldlms/v2/users/{id}/course-progress...` del índice actual son GET. No se inventó un POST equivalente.
- La función correcta para disponibilidad temporal es `ld_lesson_access_from`, no un nombre supuesto `learndash_lesson_available_from`.
- `learndash_can_complete_step(user, step, course, false)` controla requisitos; `learndash_process_mark_complete(user, step, false, course, false)` conserva validaciones, hooks y escritura interna. Se inspeccionó la implementación instalada.
- El respaldo de membresía posee concesiones mediante `_auren_membership_courses`. La app no añade ni revoca matrículas por su cuenta. La atribución exacta de múltiples fuentes, vencimientos y promociones exige una siguiente revisión central de permisos; no se implementó una promoción paralela.
- No se han demostrado pagos; no se muestran precios inventados ni acciones de compra en esta primera versión.

El estado final de pruebas y despliegue se registra en `STATUS.md`.

## Hallazgo de aceptación: REST privado de BuddyBoss

El inicio anónimo de autorización devolvía `401 bb_rest_authorization_required`, aunque la ruta propia fuese pública. El código **instalado** de BuddyBoss 3.4.3 confirma el filtro `bb_exclude_endpoints_from_restriction` con comparación exacta. Bridge 0.1.2 añade solo los dos endpoints de código/PKCE. No se deshabilitó la privacidad REST del sitio. Se añadió una regresión local antes de actualizar el piloto.
