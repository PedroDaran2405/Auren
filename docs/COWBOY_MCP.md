# Conexión Cowboy MCP

Servidor: `www-aurenfx-com`. Endpoint HTTPS:
`https://www.aurenfx.com/wp-json/cowboy-mcp/v1/endpoint`.

El propietario pidió registrar ese endpoint con Codex. Se intentó el comando exacto:

```sh
codex mcp add www-aurenfx-com --url https://www.aurenfx.com/wp-json/cowboy-mcp/v1/endpoint
```

En este entorno cloud falló porque `/run/codex-environment/codex-home/config.toml`
está montado como solo lectura. No se cambió ese montaje ni la configuración global.

Se preparó `.codex/config.toml` sin secretos. Se validó TOML y Codex reconoció la
entrada al pasar su URL explícitamente con `-c`. `codex mcp get` sin ese argumento
no cargó la entrada en esta sesión: el archivo preparado no demuestra registro
global ni activación de herramientas en el chat actual.

También se intentó `codex mcp login` con la configuración explícita y falló la
conexión durante el descubrimiento de OAuth. No se completó autenticación ni una
llamada a herramientas Cowboy. Las pruebas HTTP directas previas devolvieron 403
del proxy antes de llegar al sitio; no es evidencia de una contraseña rechazada
por Auren ni permite concluir qué autenticación admite Cowboy.

Para usarlo en una instalación local de Codex con configuración escribible, ejecutar
el comando anterior. Si el servidor ofrece OAuth, completar su autorización con
`codex mcp login www-aurenfx-com`. Si requiere token, configurarlo mediante el
almacenamiento seguro del cliente, nunca en este repositorio. Reiniciar o reconectar
la sesión del cliente si hace falta para cargar sus herramientas. El registro en
Codex CLI no demuestra que un chat cloud de Work reciba la misma configuración.

La aceptación exige una llamada real de solo lectura que identifique Auren y sus
versiones de plugins; esa prueba sigue pendiente mediante Cowboy. La integración
funcional anterior se verificó con las interfaces web/REST autorizadas de WordPress.

## Vía de clave API confirmada en la interfaz de Cowboy

La captura proporcionada por el propietario ofrece explícitamente una clave API
para entornos sin navegador y muestra `--bearer-token-env-var COWBOY_MCP_API_KEY`.
Se añadió esa referencia a la configuración del proyecto, sin ningún valor secreto.
Se preparó en el borrador cloud el requisito secreto `COWBOY_MCP_API_KEY`, limitado
a `www.aurenfx.com`. Todavía falta que el propietario introduzca la clave de Cowboy
mediante la configuración segura y active el cambio de entorno.

Para la primera prueba basta una clave de **Solo lectura**, con un nombre reconocible
como `Codex Auren - prueba`. No pegarla en el chat, commits, comandos registrados ni
archivos de configuración. Una vez disponible, probar primero la identificación del
sitio y el inventario de plugins mediante MCP. La clave no demuestra por sí sola
que se haya resuelto la restricción de red o que se hayan cargado herramientas en
esta conversación: esas comprobaciones siguen pendientes.

Guardar/publicar aquí se refiere a activar una versión de la configuración del
entorno cloud; no publica la app ni modifica el sitio WordPress. No hace falta
publicar el entorno por cada mensaje o cada edición de código.
