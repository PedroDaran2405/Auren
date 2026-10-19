# Cambios

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
