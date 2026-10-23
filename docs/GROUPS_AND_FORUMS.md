# Grupos y foros · 0.7.0

## Experiencia

Comunidad organiza Actividad, Grupos y Foros. Inicio ofrece accesos directos a Mis grupos y Foros. El directorio de grupos incluye Mis grupos/Explorar, búsqueda, portada original, membresía y tamaño. El detalle conserva el espacio de origen al entrar a foro/discusión y regresar; el botón Volver permanece en el encabezado fijo. El botón físico de Android también regresa dentro de esta jerarquía. Cambiar de sección conserva las listas recientes en memoria de la sesión.

Los grupos muestran sus foros vinculados, actividad y miembros permitidos. Las publicaciones del grupo se pueden abrir y comentar. La incorporación a grupos públicos y las solicitudes a privados usan los handlers de BuddyBoss: no se concede membresía localmente ni se ofrecen roles administrativos. El estado se vuelve a consultar después de enviar la solicitud.

Los foros conservan categorías/subforos, búsqueda, paginación, discusiones destacadas, autor y estado de solo lectura. Se pueden publicar discusiones y respuestas de texto, con confirmación del servidor. La moderación nativa puede dejar un envío pendiente; la interfaz lo comunica en vez de presentarlo como publicado. No se implementan todavía edición/borrado de temas y respuestas, adjuntos, editor enriquecido, suscripciones, administración de grupos ni publicación nueva en la actividad del grupo. No se añaden contenidos de demostración al sitio real.

La bienvenida conserva una sola marca en el encabezado; desaparece Tu Academia. El bloque repetido de marca se sustituye por Un espacio para avanzar / Aprende · Pregunta · Conecta, sin saldos, testimonios o estadísticas inventados.

## Datos, permisos y rendimiento

Se revisaron el índice REST y los handlers instalados de BuddyBoss Platform 3.4.3 para grupos, membresías, foros, temas, respuestas y actividad. Las rutas nativas son `/groups`, `/forums`, `/topics` y **`/reply` en singular**. El bridge añade rutas propias fijas bajo `/auren/v1`, no un proxy arbitrario.

- `/spaces` agrupa la primera página de grupos propios y foros raíz en una sola petición. Se prepara después del arranque sin bloquear Inicio; Comunidad reutiliza esa consulta.
- `/groups/{id}` agrupa metadatos, hasta 20 foros vinculados y primera página de actividad. Los miembros y las páginas adicionales se solicitan al necesitarlos.
- `/forums/{id}` agrupa información, subforos y temas. `/topics/{id}` agrupa conversación y respuestas; no se hace una consulta HTTP por tarjeta.
- Las listas de directorio se reutilizan durante 30 segundos; la presentación inicial puede usar memoria de hasta 120 segundos mientras vuelve a consultar. Memoria por instancia/cuenta, sin almacenamiento en disco ni caché pública. Una escritura o rechazo de autorización invalida las lecturas. Una respuesta anterior a una escritura no puede rellenarlas de nuevo.
- Los detalles de grupos, foros y temas se consultan otra vez al abrirlos. BuddyBoss mantiene sus hooks originales; además se exige permiso de contenido antes de devolver foros/replies, se excluyen contraseñas y se rechaza abrir contenido protegido con contraseña desde este adaptador.
- Los grupos privados sin membresía solo entregan información que permite su endpoint nativo: no actividad, foros o miembros. Los ocultos respetan el permiso del endpoint; el cliente no puede solicitar otra identidad ni forzar `show_hidden`.
- Los envíos aceptan únicamente texto/título y una clave de intención; usuario, foro/grupo, rol, estado y moderación se derivan del servidor. Bloqueo por usuario, una clave por intención y ninguna repetición automática de POST evitan duplicar un envío reconocido. Una respuesta de transporte incierta exige actualizar antes de repetirla.

Esto reduce peticiones y esperas repetidas; no elimina la inicialización de PHP medida anteriormente ni promete cargas instantáneas en el hosting actual. No se cambió el hosting ni se instaló caché/Redis.

## Validación

25 comprobaciones PHP nuevas cubren privacidad, aislamiento, jerarquía, búsquedas, publicación, claves repetidas, foros cerrados, moderación y solicitudes de acceso. Utilizan posts reales de WordPress local y **doubles explícitos de BuddyBoss**, sin correo ni notificaciones a personas externas. Dos pruebas de cliente nuevas verifican reutilización de directorios y que un resultado tardío no restaure datos después de una escritura.

Tres recorridos nuevos de navegador cubren bienvenida sin marca repetida, grupos/jerarquía/regreso fijo y publicación/respuesta/solicitud privada. Se revisaron capturas a 390 px y ausencia de desbordamiento. Los recorridos anteriores de academia, autenticación, gamificación, avisos, perfil y registro se conservan. `test:spaces` restablece únicamente datos de sus fixtures. `local:up` prepara también las categorías y temas locales para que las pruebas no dependan de un sitio comercial.

Evidencias: `spaces-deployment.json` y `spaces-live.json`. La verificación remota se realiza con una cuenta real y lecturas originales comparadas; no publica preguntas, envía solicitudes ni cambia matrículas/progreso de alumnos. Con la cuenta real se leyeron siete grupos propios y ocho foros raíz; se contrastaron metadatos/membresía con BuddyBoss y se abrieron grupo, miembros, foro, tema y respuestas existentes. La preparación de directorios tardó 2088 ms y las otras aperturas observadas entre 1736 y 2455 ms: son muestras puntuales, no una prueba de carga. La aceptación visual en iPhone/Android físicos sigue pendiente. Las exportaciones Hermes son JavaScript, no APK/IPA firmados.

## Continuidad

Pendiente: prueba de escritura en un foro QA del sitio sin notificar a alumnos reales; envío de adjuntos/editor enriquecido; publicar una actividad nueva dentro del grupo; edición/borrado de temas/respuestas; suscripciones; más de 20 foros directamente vinculados a un solo grupo; conservar posición y búsqueda completa al salir de Comunidad y volver; advertencia de borrador de respuesta ante cambios de sección. El regreso dentro de grupo/foro/tema ya mantiene la jerarquía, pero no se presenta como historial profundo del navegador web.

Revertir solo archivos propios del bridge desde `.local/deploy-backups/spaces-*` o el paquete anterior. No restaurar tablas de BuddyBoss/foros ni borrar publicaciones/membresías legítimas de usuarios.
