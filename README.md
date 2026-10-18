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

Piloto real: https://www.aurenfx.com/?auren_app=1 (administradores y cuentas QA habilitadas). El acceso público general sigue cerrado. Para generar el ZIP reproducible después de `build:web`: `npm run package:plugin`.

En Ajustes → Auren App se configura la versión de LearnDash verificada, IDs de alumnos del piloto y escritura de progreso. Por defecto el acceso de alumnos y la escritura están cerrados. La app usa el login existente y conserva sus protecciones.

[Auditoría](docs/AUDIT.md) · [Arquitectura](docs/ARCHITECTURE.md) · [API](docs/API.md) · [Cobertura](docs/COVERAGE.md) · [Pruebas](docs/TESTING.md) · [Despliegue y reversión](docs/DEPLOYMENT.md) · [Distribución y costos](docs/DISTRIBUTION.md).
