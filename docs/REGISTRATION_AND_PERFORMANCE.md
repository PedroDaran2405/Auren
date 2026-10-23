# Registro y diagnóstico de rendimiento · 0.6.0

## Una cuenta compartida

La pantalla Crear una cuenta aparece en web y en el código nativo. Obtiene los campos de `/buddyboss/v1/signup/form` y registra mediante el handler original `/buddyboss/v1/signup`; la app no mantiene una segunda base de usuarios. El sitio auditado permite registro (`users_can_register=1`) y tiene `default_role=subscriber`. Los campos reales incluyen correo, contraseña, nombre, apellido, alias, procedencia y acuerdo legal. Se conserva la configuración de BuddyBoss y el proceso de activación que entrega la plataforma.

El bridge expone únicamente GET/POST `/auren/v1/registration` para ese propósito. Los campos se permiten según el formulario nativo, se exigen todos los requeridos, se validan opciones y se rechazan asignaciones de rol/identidad o tipos inesperados. Contraseña mínima de 12 caracteres, HTTPS, desafío firmado de 10 minutos ligado a la dirección observada, trampa de formulario y límite de cuatro intentos cada diez minutos por dirección observada. Un bloqueo breve hace atómico el contador; una marca de un solo uso evita repetir solicitudes reconocidas. Se restaura `$_POST` después del handler y se devuelve solamente la confirmación, sin identificadores, claves de activación ni hashes. Las marcas no contienen correos o contraseñas, no se autocargan y se limpian diariamente; desactivar el plugin retira su tarea programada.

El límite usa `REMOTE_ADDR`, sin confiar en cabeceras de reenvío aportadas por el cliente. En redes compartidas/proxies puede agrupar usuarios; debe comprobarse con el proveedor antes de escalar el registro. Estos controles no equivalen a una attestation de dispositivo ni a un sistema antifraude terminado. El flujo web original y sus endpoints existentes permanecen bajo la configuración de WordPress/BuddyBoss.

En web, los campos públicos se preparan dentro del documento inicial sin almacenamiento compartido, para evitar otra espera al pulsar Crear una cuenta. Contraseñas y valores introducidos solo permanecen en el estado temporal del formulario; no se guardan en cachés locales ni se incorporan a logs propios. El registro no inicia sesión automáticamente ni concede acceso a cursos de pago. Para entrar se usa después el mismo login de WordPress y el protocolo existente de la app.

## CAPTCHA y encabezado de iPhone

El casillero de la captura pertenece al login web de BuddyBoss que utiliza la app. El código instalado admite v2 checkbox, v2 invisible y v3; cada modalidad requiere una configuración/claves compatibles. No se cambiaron las claves ni se desactivó el CAPTCHA del login. Nunca se añade el bypass privado a la app.

Si BuddyBoss exige CAPTCHA en el registro, el servidor impide la creación por este adaptador y la interfaz conduce al formulario protegido original. También usa ese formulario si el administrador añade tipos de campo que la interfaz aún no representa. La inspección inicial del HTML público no mostraba un casillero, pero la comprobación del bridge en el sitio real confirmó `native_form_only=true`: BuddyBoss sí tiene habilitado CAPTCHA para `bb_register`. Por eso, actualmente Crear una cuenta conduce al registro original protegido; el formulario integrado está implementado y probado localmente, pero no se presenta como un flujo remoto sin CAPTCHA. Se conserva esa configuración, sin fingir que los controles de la app habilitan protección invisible en el login.

Se reservó espacio superior para el reloj/área segura del iPhone en el login, con un mínimo aun cuando Safari no informe el inset. No se duplica el padding de esa zona. Falta confirmarlo visualmente en el dispositivo del propietario.

## Medir antes de cambiar infraestructura

`Server-Timing` informa del tiempo total de PHP en las rutas propias (`auren_php`) y del tiempo de cada fragmento en `/startup`. No contiene consultas SQL, rutas internas, datos personales ni credenciales. Permite separar parte de la generación de WordPress de la duración completa vista por el cliente; la diferencia también puede incluir red, CDN y espera antes de ejecutar PHP.

No se encontraron plugins activos de caché en la selección auditada ni los drop-ins `object-cache.php`/`advanced-cache.php`; eso no prueba que el proveedor/CDN no tenga caché propia. No se instaló Redis sin comprobar que exista su servicio. Próximos ajustes deben basarse en los tiempos: soporte de caché de objetos persistente en el hosting, coste de inicialización de plugins/opciones y capacidad de workers/PHP. Las respuestas privadas no deben convertirse en una caché pública compartida.

## Validación y límites

18 comprobaciones nuevas verifican registro, rol, contraseña WordPress, campos/opciones, CAPTCHA configurado, repetición, tipos, límites y ausencia de claves internas en respuestas. Los usuarios WordPress son reales en el entorno local; BuddyBoss signup/activación es una fixture explícita y los correos están desactivados. No se creó una persona real ni se enviaron correos de prueba en Auren. La entrega efectiva del correo y la activación con un buzón real siguen pendientes de una prueba del propietario.

La auditoría sí revisó el handler instalado y los campos públicos reales. La verificación remota cubre configuración, bundle, rechazo por la protección CAPTCHA sin creación y mediciones de lectura. Ver `registration-deployment.json` y `registration-live.json`. Las exportaciones Hermes no son un APK/IPA firmado; sigue pendiente el bloqueo de subida a Expo descrito en DEVICE_TESTING.

## Medición remota del 23 de octubre de 2026

Con una sesión real de alumno, sin modificar contenido ni progreso: `/startup` tardó 2639 ms en total y 2513 ms dentro de PHP. Sus fragmentos sumaron aproximadamente 770 ms: identidad 45,7; cursos 10,7; perfil 59,4; avisos 161; comunidad 492,8. El resto del tiempo de PHP incluye inicialización de WordPress/plugins y procesamiento anterior a esos fragmentos; esta medición no permite atribuirlo a un plugin específico. `/me` tardó 1939 ms, de los cuales 1807,6 fueron PHP. La diferencia entre ambos relojes no es una medición pura de latencia de red.

La mayor oportunidad observada está en el servidor: perfilar la inicialización, comprobar workers y preguntar al proveedor por un servicio de caché de objetos persistente. No se recomienda cachear públicamente identidad, permisos o progreso. La agrupación de cinco lecturas y el caché temporal privado ya evitan repetir parte de ese coste al navegar. Son muestras puntuales, no una prueba de carga ni un percentil representativo.
