# Encabezado estable · 0.4.1

El video de 13,8 segundos mostró el defecto de la integración 0.4.0: el JavaScript del pie creaba un widget oculto, consultaba REST y lo mostraba. Su ancho pasaba de cero al ancho del nivel/monedas, desplazando el menú.

La corrección utiliza `wp_before_load_template` y `wp_after_load_template` de WordPress, limitados a `template-parts/header-aside.php` y `header-mobile.php` del tema activo/padre. Intercepta solamente esos fragmentos, inserta el componente antes de los controles existentes y entrega el HTML completo al navegador. No hay copia de templates, cambios en BuddyBoss/GamiPress, ni buffer de toda la página. Si una actualización del tema cambia los puntos de inserción, se conserva su HTML y se debe revisar la integración; no se improvisa una inserción tardía en JavaScript.

El mismo adaptador nativo lee saldo, imagen, rango y requisitos actuales. El encabezado omite consultar el catálogo de logros. Los valores ya existen aunque JavaScript esté desactivado. El CSS crítico se incluye en `<head>`; el contenedor, nivel, monedas e imágenes tienen dimensiones explícitas y los títulos largos se acotan. JavaScript actualiza exclusivamente los componentes existentes al volver a la ventana y cada minuto visible; no solicita nuevamente los valores al cargar una página recién renderizada. Un fallo de red conserva los últimos valores confirmados. Un 401/403 borra los valores privados y conserva el espacio hasta navegar/autenticarse.

Se desactiva la caché de página para esas vistas autenticadas y se envían cabeceras privadas mediante `nocache_headers`; no se persisten saldos en almacenamiento del navegador ni se comparte un HTML autenticado entre alumnos. Las opciones del administrador, cambios de requisitos y saldo siguen procediendo de GamiPress.

## Verificación

- `npm run test:gamification`: 15 comprobaciones con GamiPress 8.0.6 real en WordPress local.
- `npm run test:header`: 12 comprobaciones PHP de datos iniciales, aislamiento, integración de ambos templates, anidamiento y degradación segura; seis recorridos de Chromium.
- Navegador: 1920, 1280, 1024, 390 y 320 px; respuesta retenida, saldo grande, título largo, imagen fallida y sesión caducada. Las coordenadas del control vecino permanecen iguales y el observador no registra desplazamientos. Caso adicional con JavaScript desactivado.
- Sitio real: login del alumno mediante el formulario original y sus campos ocultos, sin desactivar CAPTCHA. HTML autenticado con ambos widgets y CSS crítico antes de ejecutar JS; saldo y porcentaje coinciden con REST nativo. No se completaron lecciones ni se asignaron puntos.
- Revisión visual del HTML/CSS auténticos de Auren con JS desactivado a 1920, 1280 y 390 px: widgets visibles y sin desbordamiento horizontal. Relay temporal GET en loopback, HTTPS upstream verificado; sin cambiar la confianza TLS del navegador. Capturas/HTML privados excluidos de Git. Esta revisión no equivale a un nuevo ensayo físico en los teléfonos del propietario.

## Despliegue y reversión

Cinco archivos propios del bridge, versión 0.4.1, comparados con su versión anterior y comprobados de nuevo por SHA-256. Respaldo local ignorado y rutas en `docs/evidence/header-stability-deployment.json`. Para revertir, restaurar esos cinco archivos del respaldo; no restaurar la base de datos ni los ajustes de GamiPress. El módulo de datos conserva compatibilidad con las llamadas existentes de la app.
