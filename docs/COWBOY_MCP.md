# Conexión Cowboy MCP

**Conexión autenticada verificada.** La prueba ejecutó inicialización MCP, listado de herramientas, descubrimiento y `wp_site_info` contra Auren. WordPress respondió con nombre **Auren FX**, URL `https://www.aurenfx.com`, WordPress **7.1.3**, PHP **8.3.35** y tema **BuddyBoss Child 1.0.1**. El bridge de Auren figura activo. No se ejecutaron herramientas de escritura.

Evidencia sin credenciales: `docs/evidence/cowboy-connection.json`.

## Repetir la comprobación

Con el secreto `COWBOY_MCP_API_KEY` disponible en el entorno seguro:

```sh
cd /workspace/Auren
python3 scripts/check-cowboy.py
```

El script solo llama a `initialize`, `notifications/initialized`, `tools/list`, `cowboy_discover` y `cowboy_run` con la herramienta fija `wp_site_info`, confirmada como lectura. Comprueba que el sitio de destino sea Auren. No acepta tokens como argumentos ni imprime el valor, cabeceras de autenticación, sesiones o respuesta administrativa completa. No modifica WordPress ni incluye una interfaz para ejecutar herramientas arbitrarias.

Endpoint: `https://www.aurenfx.com/wp-json/cowboy-mcp/v1/endpoint`.
Transporte comprobado: HTTP MCP, protocolo negociado `2025-06-18`, servidor Cowboy `1.7.3`. Los dos puntos de entrada son `cowboy_discover` y `cowboy_run`; cada herramienta interna debe descubrirse antes de utilizarse. La comprobación HTTP demuestra acceso MCP autenticado; no implica que aparezcan alias nativos Cowboy entre las herramientas del chat.

## Identificación del cliente y seguridad

Las primeras solicitudes con el cliente genérico de Python recibían un 403 de Cloudflare, código **1010** (`browser_signature_banned`), antes de validar el acceso. Se identificó el cliente explícitamente como `AurenCodexMCP/0.1 (+https://www.aurenfx.com)`. Con ese encabezado y la clave del entorno, las llamadas MCP funcionaron. No se cambió Cloudflare, no se suplantó un navegador ni se desactivó TLS o CAPTCHA.

`.codex/config.toml` contiene URL, referencia al nombre de la variable y User-Agent; no el secreto. La clave se proporciona desde la configuración segura del entorno, limitada a `www.aurenfx.com`. No pegarla en el chat, commits, comandos registrados ni archivos. Si se reemplaza o revoca una clave, actualizar el secreto y repetir la prueba.

## Diferencia respecto a la configuración global

El comando solicitado por el propietario fue:

```sh
codex mcp add www-aurenfx-com --url https://www.aurenfx.com/wp-json/cowboy-mcp/v1/endpoint --bearer-token-env-var COWBOY_MCP_API_KEY
```

Ese registro global falló porque `/run/codex-environment/codex-home/config.toml` está montado como solo lectura. No se alteró el montaje. Se validó la configuración preparada mediante argumentos explícitos `-c`; el subcomando `codex mcp get` sin ellos no cargó la entrada en esta sesión. El script reproducible permite verificar y usar el transporte autorizado sin afirmar que se haya modificado el cliente que aloja el chat.

Guardar/publicar en la configuración cloud activa una versión del entorno de trabajo; no publica la app ni modifica WordPress. No hace falta por cada mensaje o edición de código. El secreto ya estaba disponible en la ejecución que produjo la evidencia de aceptación.
