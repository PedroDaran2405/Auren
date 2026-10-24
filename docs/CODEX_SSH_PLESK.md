# Codex y Plesk mediante SSH

Se leyó íntegramente la guía pública aportada por el propietario:
https://seorankgenius.co.uk/blog/setup-codex-ssh-on-plesk/

Se contrastó con la documentación oficial accesible:
https://developers.openai.com/codex/remote-connections#connect-to-an-ssh-host
https://developers.openai.com/codex/cli/reference/

La página de la guía y ambas referencias respondieron HTTP 200. El entorno declara acceso web general; que una página concreta abra no garantiza que cualquier servicio elimine sus propias restricciones. No se ha configurado una conexión SSH ni instalado Codex en el VPS.

## Qué propone y qué confirma OpenAI

La guía utiliza la aplicación de escritorio en el ordenador del propietario, autenticación SSH por clave y Codex CLI instalado bajo el usuario del sitio en el VPS. OpenAI documenta que la app descubre aliases concretos en `~/.ssh/config`, establece SSH y arranca el servidor remoto de Codex mediante el shell de login del usuario. El CLI debe estar en ese PATH y autenticado. El proyecto remoto opera sobre archivos y comandos del host remoto.

El flujo es válido para conectar un proyecto remoto en la aplicación de escritorio. No equivale a habilitar acceso SSH de esta conversación cloud por permitir dominios HTTP, ni garantiza que su historial y entorno se transfieran automáticamente. La documentación describe requisitos específicos para handoff y proyectos correspondientes; no se presume una migración de este chat cloud.

## Orden adaptado a Auren

1. Determinar si el propietario usa navegador, escritorio Windows o escritorio Mac; comprobar disponibilidad de Conexiones/SSH en la aplicación actual.
2. Identificar el usuario de sistema de la suscripción Auren en Plesk y el alcance de esa suscripción. Es diferente del administrador WordPress y del usuario root de las capturas.
3. Revisar/habilitar el shell SSH adecuado para ese usuario. La guía eligió `/bin/bash` sin chroot porque su instalación enjaulada no disponía de Node/npm; no es una regla universal de Codex ni una garantía de aislamiento entre dominios de una misma suscripción.
4. Generar una clave dedicada en el ordenador que ejecutará la aplicación, antes de importar su clave pública al usuario correcto de Plesk. Mantener la privada en el cliente y comprobar la huella del servidor antes de aceptar la primera conexión.
5. Probar SSH manualmente desde ese ordenador. Crear un alias concreto en su configuración SSH para que la app lo descubra.
6. Comprobar Node/npm como el usuario remoto, instalar y autenticar Codex CLI para ese usuario, y verificar su disponibilidad en el shell usado por SSH. La extensión Plesk SSH Keys Manager facilita importar la pública; lo esencial para SSH es la configuración de claves autorizadas, no instalar una extensión concreta a ciegas.
7. En la aplicación de escritorio: Settings → Connections → SSH; conectar el host y seleccionar la carpeta de proyecto. Usar una carpeta de desarrollo del repositorio para construir/probar; los archivos publicados de WordPress requieren su procedimiento de despliegue y reversión.

Corrección de la guía: propone `codex status`. No aparece como comando independiente en la referencia oficial consultada; para comprobar autenticación se documenta `codex login status`. `codex --version` comprueba la instalación. No ejecutar automáticamente todo el artículo como un script.

## Permisos y objetivo Redis

El usuario de sistema del sitio puede permitir trabajo con archivos de la suscripción y ejecutar herramientas disponibles para él. Instalar redis-server con apt y administrar servicios systemd requiere permisos administrativos del VPS. Por tanto, conectar únicamente el usuario del sitio como en la guía **no basta para que ese usuario instale Redis**.

El propietario ya tiene una terminal root en Plesk y recibió instrucciones de instalación, pero todavía no hay evidencia de que las haya ejecutado. WordPress/PHP cliente está diagnosticado en HOSTING.md. Mantener separados: shell de desarrollo, acciones administrativas de servidor y autorización REST de WordPress. La clave SSH privada y las contraseñas no se deben pegar en el chat ni incorporarse al repositorio.

Pendiente: respuesta sobre interfaz/OS del propietario, usuario y destino SSH, prueba manual de conectividad y huella, disponibilidad/autenticación del CLI remoto y proyecto elegido. No se han creado usuarios, cambiado shells, instalado Node/Codex, concedido sudo ni abierto puertos desde esta revisión.
