# Investigación pública de BuddyBoss App · 23 de octubre de 2026

Petición: localizar fotografías/videos públicos sin depender de adjuntos del propietario y compararlos con Auren. **Comparación visual pendiente: no se consiguió observar capturas ni reproducir una demo pública en esta sesión.** No atribuir recomendaciones visuales a videos que no se han visto.

## Fuentes consultadas y resultados

| Fuente pública | Resultado observado |
| --- | --- |
| https://www.buddyboss.com/app/ | Redirige a buddyboss.com/app/; HTTP 403, Attention Required / Cloudflare |
| https://www.buddyboss.com/mobile-app/ | HTTP 403 de Cloudflare; no se verifica que sea una página de producto válida |
| https://www.buddyboss.com/resources/ | HTTP 403 de Cloudflare |
| https://www.youtube.com/@BuddyBoss | El proxy rechaza la conexión; no se inspeccionó el canal |
| Búsqueda pública de YouTube `buddyboss app demo` | El proxy rechaza la conexión; no hubo resultados leídos |
| https://github.com/buddyboss | HTTP 200; organización pública con repositorios de Platform y personalización de App |
| https://github.com/buddyboss/buddyboss-app-custom-code-template | HTTP 200; README actual revisado, sin galería de capturas |
| https://github.com/buddyboss/bb-app-ui-tokens | HTTP 200; README sin documentación visual suficiente |
| https://github.com/buddyboss/storybook-bb-app-theming | HTTP 200; repositorio de tooling de theming, no una demo pública completa de BuddyBoss App |

El README público actual del template requiere React Native 0.85.1+ y New Architecture, y distingue métodos síncronos breves de operaciones de red/disco asíncronas. Esto confirma características de **ese template**, no la tecnología de todas las generaciones de App ni el aspecto de su demo. No se copia código nativo ni se propone actualizar el SDK de Auren solo por esa referencia.

Se comprobó también el reproductor público sin cookies del enlace YouTube que ya figuraba en la conversación: el HTML respondió 200, pero su estado era ERROR / 153 (identidad/referer de embed ausente), sin imágenes de demo vistas ni reproducción. Una respuesta HTML 200 no equivale a haber visto un video.

WordPress.org redirigió la URL ensayada de BuddyBoss Platform a resultados de búsqueda: no se acepta como ficha oficial del producto ni como fuente de capturas. La búsqueda de repositorios GitHub `buddyboss app demo` no devolvió resultados. No se eludieron los bloqueos.

## Acceso pendiente

Se guardó un borrador que conserva los destinos anteriores, incluidos IONOS, y añade `www.google.com`, `www.bing.com`, `play.google.com`, `apps.apple.com`, `play-lh.googleusercontent.com` e `is1-ssl.mzstatic.com`. El servicio confirmó `status=saved` y `requires_publish=true`. Guardar el borrador no habilita la red del proceso ni evita Cloudflare. Las fichas/capturas de las tiendas aún no fueron consultadas; no se inventan IDs de aplicaciones.

Continuar después de aplicar la configuración: localizar fichas oficiales y videos públicos, comprobar autor/producto/generación, ver las imágenes/fotogramas realmente accesibles, registrar fuente y marca temporal, y comparar Inicio, Cursos/Lección, Grupos/Foros y Perfil con Auren 0.7.0. Priorizar navegación, legibilidad y carga sin introducir funciones ni datos ficticios. Mantener las imágenes de concepto del propietario como referencia visual principal. No hay cambios de interfaz ni despliegues en esta etapa de investigación.
