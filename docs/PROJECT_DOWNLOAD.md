# Descarga del proyecto completo

Los enlaces `/workspace/...` del chat apuntaban al disco del entorno, no a una descarga accesible desde el navegador del propietario.

La entrega completa está disponible temporalmente en:

https://www.aurenfx.com/wp-content/plugins/auren-handoff-download/download.php

Requiere iniciar sesión en WordPress con una cuenta que tenga `manage_options` (administración). Los alumnos reciben 403; los visitantes sin sesión se dirigen al login original, sin incluir ningún bypass de CAPTCHA en el enlace público. Si BuddyBoss devuelve al panel después del login, pulsar otra vez el enlace de descarga.

El archivo es el mismo ZIP del checkpoint 879ba4e: 54 368 093 bytes, SHA-256 `e75dd9e0ed23ed76bbf25ce0fa9dfad19964a5d29241b0db1171c88d330b9571`. Contiene código, documentación, pruebas, recursos generados, plugin y cinco adjuntos originales. No contiene credenciales ni sesiones. La entrega caduca el 19 de noviembre de 2026 a las 15:46 de Ciudad de México.

El repositorio de GitHub es público. El ZIP con respaldos/adjuntos no se publicó allí. El código y contexto que ya pertenecen al repositorio también se pueden descargar mediante https://github.com/PedroDaran2405/Auren/archive/refs/heads/main.zip; ese archivo automático no incluye los cinco adjuntos originales ni los builds ignorados en Git.

## Implementación y retirada

Se añadió únicamente `wp-content/plugins/auren-handoff-download/` como carpeta auxiliar independiente. No es un plugin activado y no añade hooks al sitio. Su script autónomo carga WordPress al solicitar esa URL, verifica sesión/capacidad/caducidad y después valida tamaño/hash de la entrega antes de transmitirla. No se modificaron el tema, la app ni datos de alumnos.

`scripts/private-handoff-download.php` conserva el código auditable del lector. Los 18 fragmentos y `manifest.php` permanecen solo en el servidor; no se guardan en Git. Cada fragmento lleva una cabecera PHP que devuelve 404 y finaliza antes de sus datos, con `__halt_compiler`; el lector los procesa como archivos de datos, nunca mediante include. Las respuestas de descarga usan `private, no-store`.

Para retirar esta entrega, eliminar únicamente esa carpeta auxiliar y sus 20 archivos, después de comprobar que todavía corresponden al manifiesto registrado. No desactivar Auren App Bridge ni borrar carpetas del proyecto principal. La caducidad bloquea el acceso, pero no elimina los archivos automáticamente.

Evidencia de despliegue y comprobación: `docs/evidence/private-project-download-*.json`. El acceso de prueba usa sesiones temporales sin persistir contraseñas/cookies y se cierra al terminar.

## Lectura en un chat sin ZIP

https://www.aurenfx.com/wp-content/plugins/auren-handoff-download/opus.php descarga `AUREN-PARA-OPUS.txt`: un archivo UTF-8 de lectura con contexto, 66 archivos de implementación/configuración/documentación/pruebas y sus hashes. Pesa aproximadamente 405 KB. Se adjunta directamente como texto, sin MCP o GitHub; también se pueden copiar sus secciones por ruta.

El paquete de lectura excluye package-lock.json, dependencias instaladas, bundles, videos y adjuntos históricos para reducir contexto. No reemplaza el ZIP completo como fuente para reconstruir/instalar: el lockfile permanece allí. Los recursos binarios imprescindibles están en base64. El lector no recibe credenciales ni capacidad de modificar el servidor; debe entregar parches con rutas.

Usa la misma autorización/caducidad de la descarga completa. Los dos archivos remotos adicionales son `opus.php` y `opus-content.php`; retirar también esos archivos al eliminar la carpeta auxiliar. `scripts/export-opus.py` permite regenerar el paquete, y `scripts/private-opus-download.php` conserva el lector auditable.
