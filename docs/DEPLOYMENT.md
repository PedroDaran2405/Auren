# Despliegue y reversión del piloto

El propietario autorizó desarrollo y cambios prudentes en su academia, conservando apariencia y funcionamiento. La instalación es producción, no staging. Se prueba primero el código en WordPress aislado.

## Cambios previstos

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
4. Las cuentas de QA y sus IDs se registrarán en la evidencia. Su eliminación o reinicio debe limitarse a esas cuentas verificadas y no es necesario para desactivar la app.
5. Las sesiones de dispositivo duran una hora; cerrar sesión las revoca. El administrador puede cerrar sesiones de los usuarios de QA desde WordPress.

Para actualizar, regenerar el ZIP solo desde el árbol del plugin. No incluir `.local`, archivos de sesiones, fixtures ni plugins comerciales. Verificar la versión real de LearnDash después de cada actualización: el gate cierra el adaptador cuando cambia.
