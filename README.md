# Auren

App propia para la academia de Auren: Expo / React Native / TypeScript y un plugin independiente de WordPress. WordPress mantiene usuarios, cursos, permisos y progreso. Consulta [el estado real](docs/STATUS.md) antes de asumir que un hito está terminado.

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

`local:up` genera contraseñas locales aleatorias en `.local/compose.env`, crea WordPress/MariaDB aislados y dos alumnos de pruebas. Son **fixtures** de contratos de LearnDash, no una copia de la academia. `test:wordpress` reinicia únicamente el progreso de esas fixtures. La interfaz local indica expresamente que es contenido de pruebas.

Para desarrollo móvil: `npm start`. Para empaquetar JavaScript nativo: `npm run build:native-js`. Eso no genera un APK/IPA. Usa `.env.example` como referencia; ninguna contraseña o clave administrativa debe aparecer en variables `EXPO_PUBLIC_*`.

## Integración WordPress

El plugin está en `wordpress/auren-app-bridge`. `build:web` incorpora una exportación web generada dentro del plugin, excluida de Git. La ruta de entrada es `?auren_app=1`; no reemplaza páginas ni menús del sitio.

App conectada a Auren: https://www.aurenfx.com/?auren_app=1 (alumnos registrados y administradores). Para generar el ZIP reproducible después de `build:web`: `npm run package:plugin`.

En Ajustes → Auren App se configura la versión de LearnDash verificada, la escritura de progreso y una restricción de piloto opcional. Por defecto los alumnos registrados pueden entrar; cada curso y publicación conserva sus permisos. El login de la app reutiliza WordPress, con presentación propia y retorno corregido para BuddyBoss. La escritura de progreso se habilita por separado.

La interfaz incluye Inicio, Academia, Comunidad, Avisos y Perfil. El feed y los avisos se consultan desde BuddyBoss con el usuario real. Publicaciones, comentarios y reacciones se realizan todavía en la web de la comunidad.

[Auditoría](docs/AUDIT.md) · [Arquitectura](docs/ARCHITECTURE.md) · [API](docs/API.md) · [Cobertura](docs/COVERAGE.md) · [Pruebas](docs/TESTING.md) · [Despliegue y reversión](docs/DEPLOYMENT.md) · [Distribución y costos](docs/DISTRIBUTION.md).
