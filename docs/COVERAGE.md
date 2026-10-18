# Matriz de cobertura

Esta tabla describe implementación, no equivale a aceptación en producción. Ver STATUS y TESTING para evidencia actual.

| Función | Estado / endpoint | Dependencia, prueba y limitación | Fase |
|---|---|---|---|
| Identidad y logout | Implementados /me, /logout, auth/* | WordPress local y Auren real; sesión nativa probada por protocolo, no en dispositivo | 1 |
| Curso y lecciones | Implementados /courses | Adaptador LearnDash con revisión del código real; solo autorizados | 1 |
| Progreso | Implementado /complete, gate por versión | App→WordPress y web→app comprobados en Auren; reglas avanzadas van a web | 1 |
| Video | expo-video + YouTube oficial | YouTube real detectado; CDN bloqueada por túnel del proxy, reproducción sin verificar | 1 |
| Inicio y perfil | Datos de cuenta y cursos | No se inventan matrícula ni plan | 1 |
| Registro y recuperación | Enlaces al flujo WordPress | Requieren la configuración existente, sin perfiles paralelos | 1 |
| Membresías y precios | Sin UI de compra | WooCommerce real; checkout y vencimientos por auditar | 2 |
| Feed, comentarios y reacciones | Inventario buddyboss/v1; pendiente | Privacidad, paginación, moderación, reporte y bloqueo antes de escritura | 2 |
| Grupos / foros | Pendiente | BuddyBoss; permisos privados y pruebas de alumnos | 2 |
| Amigos / seguidores | Pendiente | Confirmar componentes activos y privacidad | 2 |
| Búsqueda | Pendiente | Evitar resultados privados o cursos no autorizados | 2 |
| Documentos / fotos | Pendiente | Permisos de archivos, URLs y borrado | 2 |
| Mensajes privados | Pendiente | Acceso cruzado, adjuntos, retención; no tiempo real prometido | 3 |
| Notificaciones internas / push | Pendiente | Distinguir API BuddyBoss de APNs/FCM; sin envíos reales | 3 |
| Cuestionarios / certificados | Flujo web, interfaz propia pendiente | LearnDash, temporizadores y reglas | 2 |
| GamiPress | Inventariado, sin saldo local | API/autorización real antes de mostrar puntos | 3 |
| Promoción broker | No implementada | Modelo de fuentes, vencimiento y revisión manual antes de conceder acceso | 3 |
| Sesiones en vivo | Sin oferta confirmada | No anunciar ni implementar hasta inventario | Posterior |
| Videos offline | No implementados | Derechos del proveedor, expiración y almacenamiento | Posterior |
| Android APK / iOS TestFlight | Configuración inicial, sin firma | Cuenta, claves y compilación nativa pendientes | 2 |
| PWA | Web compartida inicial | Instalación y service worker privado pendientes | 2 |
