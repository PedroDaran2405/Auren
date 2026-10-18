# Distribución y costos

Existe un proyecto nativo para Android/iOS y una exportación web. Exportar JavaScript/Hermes no genera APK, AAB o IPA ni prueba ejecución nativa. `eas.json` prepara perfiles preview APK y production AAB, pero no se ha contratado EAS ni enviado un build.

Android: `com.aurenfx.academy` es un identificador propuesto, no un registro adquirido. Para alumnos se necesita un APK release firmado, custodia de keystore, verificación de desarrollador aplicable al mercado y canal de actualización. Un AAB no se entrega como instalación directa.

iOS México: una IPA alojada en la web no es distribución general. La beta nativa requiere cuenta Apple, firma, build macOS y TestFlight, con condiciones y vencimiento actuales. La web móvil ofrece una vía inicial; una PWA instalada todavía requiere manifiesto, iconos propios y pruebas de Safari en iPhone.

No se ha publicado en tiendas, aceptado acuerdos ni creado servicios de pago. Las reglas de compras digitales se revisarán antes de añadir compras en nativo.

| Recurso | Uso / costo |
|---|---|
| WordPress, hosting, BuddyBoss, LearnDash, Woo | Infraestructura existente; licencias y hosting siguen siendo del propietario |
| Expo y librerías | Desarrollo local sin contratar servicios; licencias de dependencias en el lockfile |
| EAS Build/Update | Opcional; cuotas y precio deberán verificarse antes de contratar |
| Cuenta Apple / Google | Pendientes; verificar tarifas, impuestos y requisitos vigentes con el propietario |
| Firma y pruebas físicas | Custodia de claves, dispositivos y macOS necesarios para el hito nativo |
| Push / observabilidad | Sin proveedor activado ni telemetría comercial en la app |
| Mantenimiento | Actualizaciones de WP/LD/BuddyBoss pueden requerir validar adaptador; el gate impide asumir compatibilidad |

Fuentes de distribución a revisar antes del build de distribución: developer.android.com/distribute/marketing-tools/alternative-distribution, developer.android.com/developer-verification, developer.apple.com/help/app-store-connect/test-a-beta-version/testflight-overview/, support.apple.com/en-us/118110. No se fijan tarifas o reglas futuras por memoria.
