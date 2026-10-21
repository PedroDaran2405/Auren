# Pruebas en iPhone y Android y distribución de cambios

## Disponible ahora: versión web en pantalla de inicio

URL estable: https://www.aurenfx.com/?auren_app=1. Abrir en una pestaña normal, no en modo privado/incógnito. Instalar desde esa URL, sin añadir el enlace privado de CAPTCHA. Las credenciales de WordPress se introducen exclusivamente en su formulario de acceso.

- **iPhone:** abrir en Safari → Compartir (puede estar dentro del menú Más) → Agregar a inicio → activar «Abrir como app web» si aparece → Agregar.
- **Android:** abrir en Chrome → menú ⋮ → Agregar a la pantalla principal / Instalar aplicación → confirmar. Los nombres dependen de la versión del navegador. Si solo ofrece acceso directo, sigue sirviendo para abrir la versión web; no afirmar que se instaló en modo independiente hasta probarlo en el dispositivo.
- Entrar desde el icono Auren con una cuenta de alumno válida. La app web y Safari pueden mantener sesiones separadas; si pide acceso otra vez, autenticarse dentro de la app instalada.

Esta entrega incluye manifest con identidad estable, iconos PNG 192/512, icono Apple 180, modo standalone y metadatos iOS. Se sirve desde el bridge; no cambia el tema ni instala un service worker sobre el sitio. Requiere conexión. No es todavía una IPA ni un APK y no comprueba UX de componentes nativos, push u offline.

## Cómo recibe los cambios la versión web

1. Codex cambia el repositorio, valida y despliega únicamente el bridge/recursos propios. Un commit en GitHub por sí solo no actualiza el sitio.
2. Se publica el bundle nuevo antes del HTML que lo referencia y se conservan bundles anteriores para sesiones abiertas.
3. La app consulta metadatos públicos de versión al abrir/volver a primer plano y cada 60 segundos mientras está visible (mínimo 15 segundos entre consultas). Si hay otro build o versión, ofrece **Cargar nueva versión**. Nunca interrumpe automáticamente una lección/video ni una escritura de progreso.
4. **Perfil → Actualizar app** recarga el documento completo. El botón de actualizar contenido consulta WordPress, pero por sí solo no carga un nuevo bundle.
5. El documento, API privada y metadatos de versión se entregan sin almacenamiento. Los recursos con hash pueden reutilizarse; no hay caché offline propia ni persistencia de cursos/cuentas en Cache Storage.

Reabrir un icono puede recuperar la ventana anterior sin recargarla: por eso existe el aviso de versión y el botón explícito. La firma abreviada en Perfil identifica el bundle durante reportes de errores. No se muestran credenciales ni estado administrativo en los metadatos públicos.

## Próxima entrega nativa

El repositorio usa Expo/React Native con identificadores `com.aurenfx.academy` y esquema `auren://auth/callback`. El login real usa ese retorno; **Expo Go no sustituye la prueba de la app nativa**. El protocolo de autorización ya pasó en un contexto sin cookies, pero falta validación física del retorno al binario.

El acceso seguro `EXPO_TOKEN` ya fue verificado con EAS CLI 25.3.0 y con la API autenticada de Expo. Proyecto bajo propiedad de Auren: `@auren-fx/auren-fx`, UUID público `b13fe800-5cec-4287-b75a-6565639979ec`. `app.json` ya fija esa identidad, URL de EAS Update y política `fingerprint`; `eas.json` fija Node 24.19.0, versión local y canales/environments separados. La variable pública `EXPO_PUBLIC_WORDPRESS_URL` también está creada en el environment remoto `preview` para publicar updates con la misma URL del build.

El canal y branch `preview` fueron creados y Expo generó el primer keystore Android remoto. **La compilación todavía no se creó:** la subida de fuentes a `storage.googleapis.com` recibió 403 porque el proxy cloud no permite ese destino. El token sí funciona. No regenerar el keystore al reintentar y no comprar un plan para intentar resolver un bloqueo de red. Queda pendiente añadir ese host mediante el flujo de configuración del entorno y su revisión, volver a ejecutar el build y comprobar el APK final. No hay APK/IPA disponible ni update remoto publicado.

TypeScript y 10 pruebas de cliente/actualizaciones pasaron; exportaciones Hermes Android/iOS correctas. Expo Doctor pasó 18/20 comprobaciones: la consulta de esquema y metadatos de React Native Directory quedó sin completar por red. No presentar ese resultado como 20/20 ni como una prueba de instalación física. Las compilaciones iOS siguen necesitando una vía de firma válida; el propietario no tiene membresía Apple Developer de pago.

Reintento técnico desde `apps/mobile` cuando se permita subir los archivos:

```sh
EXPO_NO_TELEMETRY=1 npm exec --yes --package=eas-cli@25.3.0 -- eas build --platform android --profile preview --no-wait
```

Se conserva el mismo proyecto, paquete y keystore. Después del primer binario exitoso, publicar únicamente cambios compatibles en el canal `preview` y verificar descarga/aplicación en un teléfono. GitHub conectado a Expo no publica cada commit automáticamente.

- **Android:** `eas build --platform android --profile preview` produce APK interno; compartir su enlace privado/QR e instalar aceptando esa fuente en el dispositivo de prueba.
- **iPhone con pocos dispositivos:** `eas device:create`, registrar el iPhone y `eas build --platform ios --profile preview`. Requiere perfil ad hoc que incluya su UDID. Añadir otro dispositivo puede exigir nuevo build o nueva firma.
- **iPhone con TestFlight:** crear el registro en App Store Connect, `eas build --platform ios --profile production` y enviar esa compilación a TestFlight. No ejecutar `eas submit` sin haber preparado y revisado los datos de distribución. Testers externos pueden requerir revisión beta. Las compilaciones TestFlight caducan a los 90 días.

## Autorizar Codex en Expo

1. Enlace del proyecto recibido: https://expo.dev/accounts/auren-fx/projects/auren-fx?tab=overview. Identidad y UUID ya verificados con la API autenticada; no hace falta crear otro proyecto ni token para repetir la vinculación.
2. Crear el token desde la cuenta **personal**, no los ajustes del proyecto/organización. La documentación oficial enlaza https://expo.dev/settings/access-tokens. Ruta directa para el usuario mostrado en la captura: https://expo.dev/accounts/pedrodaran2405/settings/access-tokens. Iniciar sesión si hace falta y crear un token identificable, por ejemplo `Codex Auren`.
3. Guardarlo en la configuración segura de Codex como **`EXPO_TOKEN`** (clave) y el token en **Valor**. No usar una variable `EXPO_PUBLIC_*`, pegarlo en el chat, escribirlo en `.env.example` o subirlo a Git.
4. Comprobar la identidad y el proyecto con EAS antes de modificar la vinculación. Usar el repositorio existente; omitir el paso genérico de crear otro starter. No es necesario instalar paquetes de skills para conectar el proyecto.
5. Vincular el UUID confirmado, configurar updates/runtime, comprobar la configuración nativa y generar primero el APK con la cuota gratuita disponible. Mantener las compilaciones iOS condicionadas a una vía de firma válida.

El QR de **Download Expo Go** instala el cliente Expo Go; no instala Auren ni acredita que el proyecto esté vinculado con este repositorio. El login actual usa un esquema propio, por lo que la aceptación completa debe hacerse con un binario Auren/development build. El plan gratuito de Expo permite comenzar sujeto a cuotas; no concede TestFlight ni firma iOS gratuita en la nube.

## Actualizaciones nativas sin reinstalar todo

Una vez que el primer binario incluya EAS Update, publicar cambios compatibles con:

```sh
npx eas-cli@25.3.0 update --channel preview --environment preview --message "Descripción verificable del cambio"
```

Ese comando publica una entrega; no hacerlo por cada guardado sin pruebas. No hay publicación automática desde GitHub configurada. El circuito previsto es **cambio → checks → actualización preview → prueba en ambos dispositivos → publicación production cuando corresponda**.

| Cambio | Versión web instalada | Binario con EAS Update |
| --- | --- | --- |
| Cursos, publicaciones, avisos, permisos o progreso en WordPress | Al consultar/actualizar contenido | Al consultar/actualizar contenido |
| Diseño y lógica JavaScript compatibles | Desplegar web y recargar/aceptar aviso | Publicar actualización en su canal/runtime; normalmente se descarga al abrir y aplica en una apertura posterior |
| Plugin/bridge PHP | Desplegar el bridge; conservar contrato compatible | Igual; los binarios existentes siguen usando ese servidor |
| SDK, librerías/configuración nativa, permisos, esquema, push nativo | Evaluar versión web por separado | Nuevo binario firmado e instalación/actualización por su canal de distribución |

Un update remoto no sustituye la firma inicial, no evita las reglas de Apple/Google ni puede añadir componentes nativos ausentes del binario. El código nativo ya incorpora aviso de disponibilidad, descarga y aplicación con confirmación explícita, así como búsqueda manual en Perfil. Consultar o descargar nunca reinicia la lección; una operación en curso impide duplicar acciones y los errores permiten seguir usando la versión instalada. Las tres pruebas nuevas comprueban esas garantías con un driver controlado. Queda pendiente probarlo con EAS Update real y un APK instalado. La variante web conserva su aviso de versión independiente.

## Primera sesión de UX en ambos teléfonos

Registrar modelo, versión de sistema, navegador/modo de instalación y versión/build visible. Comprobar login de alumno (incluido error y recuperación sin enviar correos innecesarios), teclado, áreas seguras, regreso después de cambiar de app, curso/lección/video, comunidad/avisos, permisos PRO, progreso con cuentas QA y logout/reingreso. No alterar el progreso de la cuenta proporcionada sin que esa prueba se haya acordado. Reportar pantalla, acción, resultado esperado/real y captura sin contraseñas.

Pendiente físico: instalación real standalone, cookies y retorno desde login, recarga tras despliegue, reproducción YouTube y márgenes de iPhone/Android. Las pruebas automatizadas de manifest/aviso no equivalen a esa validación.

## Referencias oficiales revisadas

- [Instalar sitio como app en iPhone](https://support.apple.com/guide/iphone/turn-a-website-into-an-app-iph42ab2f3a7/ios)
- [Distribución interna EAS](https://docs.expo.dev/build/internal-distribution/)
- [EAS Update](https://docs.expo.dev/eas-update/introduction/)
- [Runtime versions](https://docs.expo.dev/eas-update/runtime-versions/)
- [Updates con EAS Build](https://docs.expo.dev/build/updates/)
- [TestFlight](https://developer.apple.com/testflight/)

Conectar GitHub a Expo no autentica este entorno en EAS. La [documentación de acceso programático](https://docs.expo.dev/accounts/programmatic-access/) confirma el uso de `EXPO_TOKEN`. La [documentación de compilación desde GitHub](https://docs.expo.dev/build/building-from-github/) exige preparar EAS y un primer build exitoso por plataforma; la conexión del repositorio por sí sola no es una app lista para instalar. Si se usa ese flujo después, la base del proyecto en este monorepo es `apps/mobile`.

## Icono del toro · bridge 0.4.2

Se usa la imagen original de la biblioteca, adjunto 56 (`FAVICON-AUREN-1.png`), sin redibujar el símbolo. Fuente conservada en `apps/mobile/assets/auren-bull-original.png`; `bash scripts/prepare-app-icons.sh` genera con ImageMagick los PNG de instalación 180/192/512 y el icono nativo 1024, con fondo opaco azul oscuro. Se actualizan `apple-touch-icon`, favicon, manifest e imagen pública Open Graph/Twitter de la app. El manifest declara `purpose: any`; no promete recorte seguro maskable. La identidad y URL de inicio de la app permanecen iguales.

En iPhone, abrir la URL de Auren en Safari, recargar y elegir Compartir → Agregar a inicio (puede estar en Ver más/Editar acciones). Si ya se añadió antes, quitar únicamente ese acceso directo y añadirlo de nuevo: iOS puede conservar su imagen anterior. La vista de compartir también puede conservar una miniatura en caché; el cambio de URL del icono incluye versión para evitar reutilizar el recurso anterior. Falta confirmación visual en el iPhone del propietario. El nuevo icono nativo se incluirá al generar el siguiente APK/IPA; no cambia un binario ya instalado.

Evidencias: `app-icon-deployment.json` y `app-icon-live.json`. El sitio conserva su biblioteca, logo horizontal y ajustes generales; el despliegue afecta únicamente al bridge de la app.
