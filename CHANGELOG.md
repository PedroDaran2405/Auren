# Cambios

## 0.4.0 — 2026-10-21

- Diseño azul profundo, azul eléctrico y cian basado en los conceptos; logo original de Auren FX, tarjetas de curso con continuación, niveles/logros reales y perfil con portada de WordPress.
- Una sola moneda: Auren Credits, imagen nativa, número y tooltip; detalle accesible al tocar. Se conserva Avisos en la navegación.
- Adaptador privado de GamiPress, compartido por app y encabezado BuddyBoss. Sin asignaciones de puntos desde el cliente ni cambios al plugin/tema.
- Tipo nativo de muestra Niveles Auren: Inicio y Explorador, requisito editable de saldo de 200 Credits; se preservan los siete rangos anteriores.
- Actualización de identidad al volver/cada minuto en primer plano y después de acciones; requisitos/logros y privacidad originales respetados.
- Pruebas con GamiPress real, aislamiento de cuentas, cambios de reglas/saldo, accesibilidad y recorridos completos. Ver GAMIFICATION y las evidencias 0.4.0.

## 0.3.0

- Comunidad dentro de la app: crear/editar/eliminar publicaciones propias, comentarios, Me gusta, filtros y búsqueda; privacidad y permisos originales.
- Perfil dinámico xProfile organizado por secciones, selección múltiple, opciones buscables, teléfono y validación previa.
- Directorio y perfil de miembros dentro de la app, respetando privacidad.
- Avisos sin leer/leídos, detalle interno, recibos de lectura individuales/visibles y navegación a conversación.
- Reporte, bloqueo y desbloqueo conectados a la moderación original; sin activar opciones globales.
- Acceso directo a la próxima lección disponible, búsqueda del catálogo cargado y mejoras de navegación/modal/teclado.
- Escrituras serializadas, claves de deduplicación, controles de actor/recipiente y pruebas de persistencia.

## 0.2.1 — 2026-10-19

- Manifest e iconos de instalación en pantalla de inicio de iPhone/Android; metadatos standalone e instrucciones dentro de la app.
- Detección de nueva entrega web al volver/consultar cada minuto y recarga explícita, sin interrumpir videos o progreso.
- Versión/build visible y botón Actualizar app en Perfil; metadatos públicos sin sesión ni datos privados.
- Sin service worker ni caché offline privada. Circuito APK/TestFlight/EAS Update documentado; cuentas y binarios todavía pendientes.

## 0.2.0 — 2026-10-18

- Acceso para alumnos registrados; piloto opcional y permisos de LearnDash/BuddyBoss preservados.
- Login propio sobre WordPress, retorno corregido tras BuddyBoss, errores/reintentos y recuperación con contexto de app.
- Inicio, Academia, Comunidad, Avisos y Perfil con interfaz renovada e iconos SVG.
- Feed paginado, detalle/comentarios de lectura y avisos personales reales desde BuddyBoss. Participación social todavía mediante la web.
- Pruebas reales con la cuenta de alumna proporcionada; caché privada, aislamiento y revocación.
- 49 comprobaciones PHP y 4 recorridos de navegador; bundle web reducido mediante imports individuales de iconos.

## 0.1.3 — 2026-10-18, piloto restringido

- Paquete reproducible con avisos de dependencias y licencia OFL de las fuentes.
- Evidencias de integración real, documentación de continuidad y configuración cloud reutilizable.

## 0.1.2 — 2026-10-18

- Compatibilidad con REST privado de BuddyBoss mediante excepción exacta para los dos pasos públicos de autorización.
- Protocolo de consentimiento/PKCE, intercambio único, alcance de bearer y revocación verificados contra Auren.
- Regresión local de esa política de privacidad, conservando el bloqueo de contenido.

## 0.1.1 — 2026-10-18

- Cursos y lecciones protegidos con contraseña de WordPress también se bloquean en la API.
- Auditoría pública ejecutable y script de empaquetado del plugin.

## 0.1.0 — 2026-10-18

- Auditoría completa de adjuntos y contraste autenticado con WordPress, BuddyBoss y LearnDash reales.
- App Expo propia en español: academia, curso/lección, perfil, estados de conexión y progreso.
- Bridge separado del tema, autenticación web con cookies/nonce y protocolo nativo.
- Permisos por usuario, piloto limitado, caché privada, cierre y revocación de sesión.
- Guardado app→LearnDash, persistencia, repetición idempotente y web→app comprobados con alumnos QA reales.
- Adaptadores de video directo y YouTube; reproducción real pendiente por conectividad del entorno, sin prometer offline.
- Entorno local reproducible y pruebas de cliente, WordPress y navegador.
