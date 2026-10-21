# Auren

App propia para la academia de Auren: Expo / React Native / TypeScript y un plugin independiente de WordPress. WordPress mantiene usuarios, cursos, permisos y progreso. Consulta [el estado real](docs/STATUS.md) antes de asumir que un hito está terminado.

Para continuar con otra IA: [contexto y guía de entrega](CONTEXTO-PARA-OTRA-IA.md). `python3 scripts/export-project.py --output artifacts/Auren-proyecto-completo-0.3.0.zip` exporta código, documentación, pruebas, web generada y el plugin empaquetado, sin claves ni dependencias instaladas. El argumento opcional `--attachments-root /ruta/a/adjuntos` incorpora los cinco archivos originales de referencia. Antes de exportar, conservar el build web y el paquete del mismo checkpoint o regenerarlos con los comandos documentados.

[Descarga privada del proyecto completo](docs/PROJECT_DOWNLOAD.md): enlace de navegador para el propietario, con acceso de administrador y sin publicar los adjuntos en GitHub.

## Desarrollo

Node 24.x, Python 3, Chromium y Docker con Compose. Ver [configuración cloud](docs/ENVIRONMENT.md). El checkout de cada tarea cloud ya está aislado; usa este checkout, sin crear worktrees salvo petición explícita.

```sh
npm ci
export EXPO_NO_TELEMETRY=1
npm run build:web
npm run local:up
npm test
npm run typecheck
npm run test:wordpress
npm run test:browser
```

`local:up` genera contraseñas locales aleatorias en `.local/compose.env`, crea WordPress/MariaDB aislados y dos alumnos de pruebas. Son **fixtures** de los contratos LearnDash/BuddyBoss, no una copia de la academia. Antes de repetir la suite completa de navegador, `test:wordpress` restablece su estado local. La interfaz local indica expresamente que es contenido de pruebas.

Para probar GamiPress real en el entorno aislado: `npm run local:gamipress`, `npm run test:gamification` y después `npm run test:browser`. Ver [GAMIFICATION](docs/GAMIFICATION.md).

Para desarrollo móvil: `npm start`. Para empaquetar JavaScript nativo: `npm run build:native-js`. Eso no genera un APK/IPA. Usa `.env.example` como referencia; ninguna contraseña o clave administrativa debe aparecer en variables `EXPO_PUBLIC_*`.

## Integración WordPress

El plugin está en `wordpress/auren-app-bridge`. `build:web` incorpora una exportación web generada dentro del plugin, excluida de Git. La ruta de entrada es `?auren_app=1`; no reemplaza páginas ni menús del sitio. El indicador de gamificación se añade al encabezado BuddyBoss mediante recursos propios, sin editar sus plantillas.

App conectada a Auren: https://www.aurenfx.com/?auren_app=1 (alumnos registrados y administradores). Para generar el ZIP reproducible después de `build:web`: `npm run package:plugin`.

En Ajustes → Auren App se configura la versión de LearnDash verificada, la escritura de progreso y una restricción de piloto opcional. Por defecto los alumnos registrados pueden entrar; cada curso y publicación conserva sus permisos. El login de la app reutiliza WordPress, con presentación propia y retorno corregido para BuddyBoss. La escritura de progreso se habilita por separado.

La interfaz 0.4.0 incluye Inicio, Cursos, Comunidad, Avisos y Perfil. El logo original y el diseño azul/cian siguen los conceptos del propietario. [Gamificación compartida y configuración de niveles](docs/GAMIFICATION.md): Auren Credits, niveles y logros nativos de GamiPress, también en el encabezado web. El feed y los avisos se consultan desde BuddyBoss con el usuario real. Publicaciones, comentarios y Me gusta ya se gestionan dentro de la app y se guardan en BuddyBoss. Perfil ampliado, avisos leídos, búsqueda de miembros, reportes y bloqueos usan los mismos registros de WordPress.

[Auditoría](docs/AUDIT.md) · [Revisión BuddyBoss](docs/BUDDYBOSS_REVIEW.md) · [Arquitectura](docs/ARCHITECTURE.md) · [API](docs/API.md) · [Cobertura](docs/COVERAGE.md) · [Pruebas](docs/TESTING.md) · [Despliegue y reversión](docs/DEPLOYMENT.md) · [Distribución y costos](docs/DISTRIBUTION.md).

Para instalar la versión web de prueba en iPhone/Android y conocer cómo se reciben cambios: [DEVICE_TESTING](docs/DEVICE_TESTING.md). La instalación web no requiere cuentas de Expo/Apple; APK/TestFlight y EAS Update siguen pendientes de vincular cuentas y generar binarios.

La revisión de BuddyBoss y las diferencias entre comprobaciones locales, reales y pendientes están en [BUDDYBOSS_REVIEW](docs/BUDDYBOSS_REVIEW.md). No se declara equivalencia total ni entrega nativa por implementar recorridos web.
