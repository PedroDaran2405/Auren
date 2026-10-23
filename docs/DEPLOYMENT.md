# Despliegue y reversión del piloto

El propietario autorizó desarrollo y cambios prudentes en su academia, conservando apariencia y funcionamiento. El propietario aclaró posteriormente que Auren está en desarrollo y aún no está en producción. Se conserva el cuidado sobre sus datos y el sitio existente. Se prueba primero el código en WordPress aislado.

## Cambios del piloto

- Añadir `wp-content/plugins/auren-app-bridge/` con el código y exportación web de este repositorio.
- Activar únicamente ese plugin; crea la tabla `{prefijo}auren_app_codes` para solicitudes/códigos efímeros.
- Opciones propias `auren_app_verified_learndash_version`, `auren_app_pilot_users`, `auren_app_progress_writes`.
- Crear alumnos de QA sin privilegios administrativos, con email reservado de prueba; no usar cuentas de clientes para modificar progreso.
- Sesiones de la app en el mecanismo estándar de WordPress; escrituras de avance únicamente para los alumnos de QA mediante LearnDash.

No se cambian tema, CSS del sitio, slugs, lecciones, precios, pedidos, suscripciones ni matrículas ajenas. Las comprobaciones de apariencia registran portada/curso antes y después. `plugin-package.json` identifica el ZIP exacto; STATUS registra si se instaló y activó.

## Deshacer

1. Desactivar **Auren App Bridge** en Plugins. La app/API dejan de estar disponibles; la web original continúa con su tema y plugins originales.
2. No restaurar toda la base de datos. No borrar usuarios, pedidos o avances de alumnos existentes.
3. Si se retira definitivamente, eliminar únicamente el directorio de este plugin tras comprobar que no hay cambios posteriores que conservar. Las opciones/tabla propias pueden conservarse para análisis; su retirada posterior debe limitarse a los nombres indicados.
4. Las cuentas QA 592 y 593 están registradas en `docs/evidence/pilot-accounts.json`. Su eliminación o reinicio debe limitarse a esas cuentas verificadas y no es necesario para desactivar la app.
5. Las sesiones de dispositivo duran una hora; cerrar sesión las revoca. El administrador puede cerrar sesiones de los usuarios de QA desde WordPress.

Para actualizar, regenerar el ZIP solo desde el árbol del plugin. No incluir `.local`, archivos de sesiones, fixtures ni plugins comerciales. Verificar la versión real de LearnDash después de cada actualización: el gate cierra el adaptador cuando cambia.

## Registro de instalación

Se instaló y activó 0.1.0; después se actualizaron únicamente archivos del bridge a 0.1.1 (contraseñas de contenido), 0.1.2 (compatibilidad con REST privado de BuddyBoss) y 0.1.3 (avisos de licencias empaquetados). El ZIP 0.1.0 original se conserva en artifacts como punto de retorno; no hay migraciones de datos entre estas versiones. El estado final activo se registra en `live-deployment.json`. Ajustes: LearnDash verificado 5.1.9, piloto 592/593, escritura de progreso activada para el piloto.

Durante la instalación inicial, WordPress ejecutó automáticamente una actualización de traducción es_ES de WooCommerce Subscriptions. No se solicitó actualizar su código ni se cambiaron sus ajustes. Se registra este efecto incidental para no afirmar que la instalación solo escribió archivos propios.

Comprobación visual: portada y página pública de trading devolvieron 200; conservaron título y 60/63 hojas de estilo respectivamente. Las capturas antes/después de 1280×900 son idénticas píxel a píxel; se guardan hashes y capturas en `visual-comparison.json`. La portada ya mostraba solo su título, navegación y pie en la observación previa; no se modificó su contenido. Esto no sustituye pruebas de todas las páginas ni de dispositivos reales.

## Actualización 0.2.0: alumnos y experiencia BuddyBoss

La lista del piloto pasa a ser opcional mediante `auren_app_pilot_restricted` (por defecto false). Las opciones anteriores se conservan. La presentación de login se activa solo para retornos de la app y se incluye en `assets/login.css`; el login ordinario conserva el tema. `includes/login.php` registra el retorno después del filtro final de BuddyBoss y conserva el ingreso por correo, recuperación y errores.

Se añaden lecturas de comunidad y avisos a través del REST original de BuddyBoss como el usuario actual. No se crean tablas sociales ni se cambian roles o matrículas. El formulario usa los mecanismos de autenticación existentes; el bypass privado se utilizó exclusivamente en la automatización temporal.

Archivos nuevos primero, loader después y rutas al final, con preflight sintáctico de Cowboy. Para recursos web: nuevo JS primero y HTML al final; fuentes sin cambios, bundles anteriores conservados. Respaldos de archivos propios en `.local/deploy-backups/`; hashes y orden en las evidencias. Cowboy tiene un error al listar directorios recursivamente (`SplFileInfo::isDot`), por lo que se verificaron rutas concretas con lectura de archivos; no se modificó Cowboy.

Reversión puntual: restablecer archivos del bridge desde el respaldo o el paquete 0.1.3 conocido, o desactivar únicamente Auren App Bridge. No restaurar la base de datos entera. Para volver temporalmente al acceso acotado, marcar la restricción de piloto en Ajustes; la lista previa 592/593 se conserva.

## Actualización 0.2.1: pruebas desde pantalla de inicio

El bridge sirve manifest, iconos fijos y metadatos de versión públicos mediante parámetros propios. Los PNG se conservan como recursos base64 de solo lectura para permitir despliegue con la herramienta textual de Cowboy; el servidor devuelve exclusivamente las tres dimensiones permitidas y no escribe archivos al servirlos. No se instala un worker con scope sobre la academia. Se publican iconos/JS antes del loader y web.php; el HTML se reemplaza al final. Ver `device-preview-deployment.json` y DEVICE_TESTING. El aviso web comprueba cambios, pero nunca ejecuta un despliegue: publicar código en GitHub no actualiza automáticamente el sitio o un teléfono.

Cowboy devuelve un texto de marcador al leer archivos grandes, no su contenido. Para recursos estáticos públicos se contrastan y respaldan los bytes mediante HTTPS con el hash esperado; nunca se acepta el hash de ese marcador como si fuera el archivo original.

Cloudflare añade su script de analítica existente a respuestas HTML, por lo que un hash HTTP del documento transformado difiere del archivo local. La identidad de build se contrasta con el hash del archivo servido por los metadatos de release y los JS/licencias se verifican byte a byte por HTTPS. No se cambió la configuración de Cloudflare.

## Actualización 0.3.0: acciones dentro de la app

Se añaden `interactions.php` y `profile.php` antes de cambiar el loader, seguidos de comunidad/rutas y nuevo bundle web. HTML se publica al final; bundles anteriores se conservan. Los archivos existentes se contrastan con el checkpoint y se respaldan bajo `.local/deploy-backups`. Cowboy ejecuta preflight PHP antes de escribir. Ver `connected-experience-deployment.json`.

El consentimiento nativo ahora describe aprendizaje, perfil, avisos y comunidad. No cambia el TTL ni el namespace del bearer (`connected-auth-consent-deployment.json`). Una corrección posterior normaliza stdClass anidados del REST interno, necesaria para leer el perfil de la instalación real (`connected-serialization-deployment.json`). La actualización de avisos se adapta al esquema entero original (`connected-notification-deployment.json`). El último ajuste de UI y búsqueda se registra en `connected-ui-final-deployment.json`. No se modifica el tema, contenido de LearnDash, roles, matrículas ni ajustes globales de BuddyBoss.

Las acciones del alumno sí escriben los registros originales de BuddyBoss, como solicita el proyecto. La aceptación real crea y elimina únicamente una actividad propia privada, restaura el campo de perfil probado y el estado del aviso probado. Los bloqueos/reportes hacia otros usuarios se comprueban únicamente con fixtures locales. El paquete generado debe coincidir con los hashes finales del bridge, no solo con el despliegue inicial anterior a la corrección.

Reversión 0.3: restaurar los archivos propios del respaldo o desactivar únicamente el bridge. No restaurar tablas BuddyBoss: hacerlo borraría cambios legítimos de usuarios. No hay migración destructiva de datos sociales en esta entrega.

Los avisos de terceros contienen algunos segmentos CRLF originales. Para comprobar/desplegar sus bytes se lee con `read_bytes().decode('utf-8')`: `read_text()` normaliza esos segmentos a LF y produce un hash distinto del ZIP. `connected-licenses-final-deployment.json` registra la entrega exacta, y `connected-live-release.json` verifica paquete, recursos y PHP finales por separado.


## Entrega visual y GamiPress 0.4.0

Los módulos nuevos y recursos del encabezado se instalaron antes del loader; después se actualizó el contrato de identidad y los selectores propios. La nueva web se publicó con JavaScript primero e índice al final, preservando bundles anteriores. El HTML servido por Cloudflare incorpora su beacon existente: el hash del archivo fuente se comprueba mediante MCP; la entrada pública se comprueba por versión, bundle nuevo y no-store, sin exigir que ese HTML inyectado coincida byte a byte. JavaScript, fuentes y licencias sí coinciden con los recursos empaquetados. Ver `gamification-backend-deployment.json`, `gamification-ui-deployment.json` y `plugin-package.json`.

Para revertir únicamente la interfaz, restaurar el índice/bundle anterior desde `.local/deploy-backups`. Para retirar también el indicador, revertir los archivos propios del bridge o desactivarlo. Los objetos de muestra son nativos: se administran por GamiPress y no se borran al desactivar el bridge. No restaurar tablas de puntos o progreso para revertir código.

## Grupos y foros 0.7.0

Módulo propio spaces.php antes del loader y registro de rutas después; JavaScript con nombre nuevo y HTML al final. Guardas contra diferencias en archivos remotos propios, preflight de Cowboy y lectura/hash posteriores. Sin cambios de ajustes BuddyBoss ni instalaciones de plugins de terceros. Ver `spaces-deployment.json`. Los nuevos endpoints de escritura actúan exclusivamente cuando el alumno envía su acción y conservan autoría, membresía y moderación nativas.
