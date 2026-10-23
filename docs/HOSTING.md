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

El propietario pospuso la optimización del hosting/WordPress mientras avanzan grupos y foros. No se han instalado servicios, cambiado workers/PHP ni activado caché para esta investigación.

Expo es independiente del VPS: genera/distribuye binarios de prueba y permite actualizaciones compatibles de JavaScript mediante EAS Update. La PWA servida por WordPress se actualiza con el despliegue web. Último estado registrado: proyecto Expo vinculado; exportaciones Hermes verificadas; subida de fuentes para APK bloqueada por la política de red del almacenamiento de EAS. No hay APK/IPA firmado ni TestFlight confirmado. Una cuenta Apple gratuita no permite TestFlight. Ver DEVICE_TESTING.md.
