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
