# VPS de Auren · inventario y siguiente comprobación

El propietario aportó el 23 de octubre de 2026 una captura del panel de IONOS y confirmó sistema/administración. No se presenta como una ficha comercial consultada ni como una inspección SSH realizada.

| Dato | Configuración informada |
| --- | --- |
| Tipo | Máquina virtual IONOS, VPS Linux XXL |
| Tamaño del panel | VPS 12-24-720 |
| CPU | 12 vCore |
| RAM | 24 GB |
| Disco | 720 GB NVMe SSD |
| Sistema operativo | Ubuntu 24.04 |
| Administración | Plesk; el propietario tiene terminal SSH en el panel |

Los recursos contratados no demuestran memoria libre, carga, capacidad PHP ni disponibilidad de Redis. Antes de aumentar plan o instalar un plugin de caché, revisar uso y servicio. El acceso Cowboy a WordPress no equivale a acceso SSH/root al VPS.

Lecturas iniciales que puede ejecutar el propietario en la terminal del servidor:

```sh
free -h
uptime
command -v redis-server
command -v redis-cli
systemctl is-active redis-server
```

Estos comandos no instalan ni reinician servicios. Una respuesta `inactive`, `unknown` o ausencia de un binario es un dato de diagnóstico. Después corresponde confirmar la versión/handler PHP seleccionados **para el dominio en Plesk**, comprobar su extensión Redis y conectividad local del servicio. El PHP de `php` en SSH puede ser distinto del que sirve WordPress.

Si Redis no está instalado, se requerirá un usuario administrativo del VPS para configurarlo. No abrir el puerto Redis a Internet ni instalar/activar el plugin WordPress antes de validar el servicio y respaldar únicamente la configuración afectada. El tamaño inicial de su caché se decidirá con la memoria disponible y las mediciones, no a partir de los 24 GB contratados solamente.

El propietario pospuso inicialmente la optimización para avanzar grupos/foros y después aportó capturas de diagnóstico; no se ha instalado Redis ni se han modificado servicios del VPS a partir de este diagnóstico. No se han instalado servicios, cambiado workers/PHP ni activado caché para esta investigación.

Expo es independiente del VPS: genera/distribuye binarios de prueba y permite actualizaciones compatibles de JavaScript mediante EAS Update. La PWA servida por WordPress se actualiza con el despliegue web. Último estado registrado: proyecto Expo vinculado; exportaciones Hermes verificadas; subida de fuentes para APK bloqueada por la política de red del almacenamiento de EAS. No hay APK/IPA firmado ni TestFlight confirmado. Una cuenta Apple gratuita no permite TestFlight. Ver DEVICE_TESTING.md.

## Capturas de terminal y Plesk recibidas · 23 de octubre

Observación aportada por el propietario, no comandos ejecutados desde Codex:

- Ejecución final: 23 GiB totales, 2,4 GiB usados, 19 GiB de buffers/caché y **21 GiB disponibles**. Swap 0. La caché de archivos de Linux no es memoria perdida; no se recomienda vaciarla.
- Carga 0,20 / 0,16 / 0,11 con 12 vCore contratados, uptime 51 días. No indica saturación en esa muestra; no descarta picos ni demuestra ausencia de esperas en PHP/DB.
- `command -v redis-server` y `redis-cli` sin salida; `systemctl is-active redis-server` final informa inactive. Sugiere servicio ausente/inactivo, pero aún falta confirmar paquetes y extensión PHP. Los caracteres de pegado y tilde de la primera ejecución no forman parte del diagnóstico final.
- Plesk para Auren: PHP **8.3.36**, FPM servido por Apache; memory_limit 512M; OPcache habilitado. `opcache_get_status` está deshabilitada como función; eso no significa que OPcache esté apagado.
- FPM: pm=ondemand, pm.max_children=10, pm.max_requests=500. Los diez children limitan concurrencia PHP, no son prueba de la causa de una petición lenta. Ondemand puede introducir arranque de workers tras inactividad: hipótesis que requiere comparación frío/caliente y memoria de procesos. Los valores start_servers/min_spare/max_spare no se utilizan en ondemand.

Siguiente lectura propuesta, sin instalar ni reiniciar:

```sh
apt-cache policy redis-server redis-tools
/opt/plesk/php/8.3/bin/php --ri redis
```

La segunda orden usa el PHP de Plesk, no `/usr/bin/php`. Su CLI orienta el diagnóstico; la extensión efectiva del handler FPM del dominio también debe verificarse al configurar el servicio. Si faltan paquetes o extensión, no activar todavía el object-cache de WordPress. No subir memory_limit/children ni cambiar a dynamic sin medir.

## Paquetes y cliente confirmados por nuevas capturas

- `apt-cache policy`: **redis-server y redis-tools no instalados** (`Installed: (none)`). Candidato en los índices mostrados: `5:7.0.15-1ubuntu0.24.04.5`, desde Ubuntu noble-updates/noble-security. La versión disponible puede cambiar al refrescar índices.
- `/opt/plesk/php/8.3/bin/php --ri redis`: extensión **PhpRedis 6.3.0 habilitada**, serializadores php/json y compresión zstd. Ese 6.3.0 corresponde al cliente PHP, no a un servidor Redis instalado. No hace falta instalar otra extensión para esa CLI de Plesk.
- Aún no hay evidencia de servidor instalado, PONG ni conexión PHP al servicio. Tampoco se activó caché de objetos en WordPress.

Siguiente paso entregado al propietario para su terminal root: actualizar índices e instalar únicamente redis-server/redis-tools, habilitar/iniciar el servicio, y comprobar PONG, bind/protected-mode y conexión desde el PHP de Plesk. Conservar loopback como interfaz de escucha; no publicar 6379. Antes de conectar WordPress: respaldar la configuración nueva, fijar un límite inicial de memoria para el uso de caché y su política de expulsión, verificar el handler PHP efectivo y preparar reversión de su drop-in. Esta secuencia no equivale a una instalación ya ejecutada.
