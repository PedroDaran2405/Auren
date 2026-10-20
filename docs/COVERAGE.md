# Matriz de cobertura

Esta tabla describe implementación, no equivale a aceptación en producción. Ver STATUS y TESTING para evidencia actual.

| Función | Estado / endpoint | Dependencia, prueba y limitación | Fase |
|---|---|---|---|
| Identidad y logout | Implementados /me, /logout, auth/* | WordPress local y Auren real; sesión nativa probada por protocolo, no en dispositivo | 1 |
| Curso y lecciones | Implementados /courses | Adaptador LearnDash con revisión del código real; solo autorizados | 1 |
| Progreso | Implementado /complete, gate por versión | App→WordPress y web→app comprobados en Auren; reglas avanzadas van a web | 1 |
| Video | expo-video + YouTube oficial | YouTube real detectado; CDN bloqueada por túnel del proxy, reproducción sin verificar | 1 |
| Inicio y perfil | Avatar, alta, cursos y editor xProfile | Campos reales, privacidad conservada; sin inventar matrícula ni plan | 1 |
| Registro y recuperación | Enlaces al flujo WordPress | Requieren la configuración existente, sin perfiles paralelos | 1 |
| Membresías y precios | Sin UI de compra | WooCommerce real; checkout y vencimientos por auditar | 2 |
| Feed, comentarios y reacciones | Feed y escritura de posts/comentarios/Me gusta implementados | REST BuddyBoss original, autor/permisos, privacidad, deduplicación; reporte/bloqueo/desbloqueo; edición individual de comentarios pendiente | 2 |
| Grupos / foros | Pendiente | BuddyBoss; permisos privados y pruebas de alumnos | 2 |
| Amigos / seguidores | Pendiente | Confirmar componentes activos y privacidad | 2 |
| Búsqueda | Feed y miembros desde servidor; cursos cargados en cliente | Permisos originales; no hay búsqueda global de documentos/grupos | 2 |
| Documentos / fotos | Pendiente | Permisos de archivos, URLs y borrado | 2 |
| Mensajes privados | Pendiente | Acceso cruzado, adjuntos, retención; no tiempo real prometido | 3 |
| Notificaciones internas / push | Avisos internos y estado leído implementados; push pendiente | Destinatario fijo, historial y conversación interna; sin APNs/FCM | 3 |
| Cuestionarios / certificados | Flujo web, interfaz propia pendiente | LearnDash, temporizadores y reglas | 2 |
| GamiPress | Inventariado, sin saldo local | API/autorización real antes de mostrar puntos | 3 |
| Promoción broker | No implementada | Modelo de fuentes, vencimiento y revisión manual antes de conceder acceso | 3 |
| Sesiones en vivo | Sin oferta confirmada | No anunciar ni implementar hasta inventario | Posterior |
| Videos offline | No implementados | Derechos del proveedor, expiración y almacenamiento | Posterior |
| Android APK / iOS TestFlight | Configuración inicial, sin firma | Cuenta, claves y compilación nativa pendientes | 2 |
| PWA | Manifest e iconos instalables, recarga de versiones | Instalación física pendiente; deliberadamente sin caché offline privada | 2 |

Recorridos internos 0.3: perfil, miembros, comunidad, detalle de avisos, reportes y bloqueos. Los grupos/foros, mensajes, medios, push y LMS avanzado mantienen aceptación independiente.
