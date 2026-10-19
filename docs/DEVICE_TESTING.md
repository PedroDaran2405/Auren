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

Se necesitan cuenta/proyecto de Expo bajo propiedad de Auren y, para iPhone distribuido mediante TestFlight o ad hoc, membresía activa de Apple Developer. Android de prueba puede instalar un APK firmado sin publicar en Google Play. No introducir contraseñas Apple/Expo ni claves privadas en Git o en el chat; vincular cuentas por sus flujos oficiales. El propietario confirmó en este hito que todavía no tiene esas cuentas. No hay sesión ni token de Expo disponibles en este entorno; la instalación web permite empezar mientras se preparan.

Canales reservados en `apps/mobile/eas.json`: `preview` para pruebas internas y `production` para distribución. **Solo definir canales no activa EAS Update.** Todavía faltan proyecto real, `expo-updates`, URL de actualizaciones y política de runtime, así como firmas/builds. No se ha iniciado una compilación remota ni se ha distribuido una app a dispositivos.

Pasos técnicos cuando la cuenta esté vinculada (desde `apps/mobile`, no guardar IDs inventados):

```sh
npx eas-cli@latest login
npx eas-cli@latest init
npx expo install expo-updates
npx eas-cli@latest update:configure
```

Revisar el diff generado, conservar la URL de WordPress, comprobar las dependencias con Expo y definir `runtimeVersion: { "policy": "fingerprint" }` en la configuración. Conservar el `projectId` público real en el repositorio; los tokens de publicación van en secretos. Configurar EAS environments `preview`/`production` si se usan variables; las variables de `build.<perfil>.env` no se aplican automáticamente a `eas update`.

- **Android:** `eas build --platform android --profile preview` produce APK interno; compartir su enlace privado/QR e instalar aceptando esa fuente en el dispositivo de prueba.
- **iPhone con pocos dispositivos:** `eas device:create`, registrar el iPhone y `eas build --platform ios --profile preview`. Requiere perfil ad hoc que incluya su UDID. Añadir otro dispositivo puede exigir nuevo build o nueva firma.
- **iPhone con TestFlight:** crear el registro en App Store Connect, `eas build --platform ios --profile production` y enviar esa compilación a TestFlight. No ejecutar `eas submit` sin haber preparado y revisado los datos de distribución. Testers externos pueden requerir revisión beta. Las compilaciones TestFlight caducan a los 90 días.

## Actualizaciones nativas sin reinstalar todo

Una vez que el primer binario incluya EAS Update, publicar cambios compatibles con:

```sh
npx eas-cli@latest update --channel preview --environment preview --message "Descripción verificable del cambio"
```

Ese comando publica una entrega; no hacerlo por cada guardado sin pruebas. No hay publicación automática desde GitHub configurada. El circuito previsto es **cambio → checks → actualización preview → prueba en ambos dispositivos → publicación production cuando corresponda**.

| Cambio | Versión web instalada | Binario con EAS Update |
| --- | --- | --- |
| Cursos, publicaciones, avisos, permisos o progreso en WordPress | Al consultar/actualizar contenido | Al consultar/actualizar contenido |
| Diseño y lógica JavaScript compatibles | Desplegar web y recargar/aceptar aviso | Publicar actualización en su canal/runtime; normalmente se descarga al abrir y aplica en una apertura posterior |
| Plugin/bridge PHP | Desplegar el bridge; conservar contrato compatible | Igual; los binarios existentes siguen usando ese servidor |
| SDK, librerías/configuración nativa, permisos, esquema, push nativo | Evaluar versión web por separado | Nuevo binario firmado e instalación/actualización por su canal de distribución |

Un update remoto no sustituye la firma inicial, no evita las reglas de Apple/Google ni puede añadir componentes nativos ausentes del binario. El control de actualizaciones con aviso dentro del binario se implementará y probará cuando exista el runtime real; el aviso actual solo corresponde a la versión web.

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
