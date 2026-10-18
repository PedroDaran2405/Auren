// Public, read-only discovery. Never accepts or stores credentials.
import { mkdir, writeFile } from 'node:fs/promises';
const base = new URL(process.argv[2] ?? 'https://www.aurenfx.com');
if (base.protocol !== 'https:' || base.username || base.password || base.search || base.hash) {
  throw new Error('Use an HTTPS site origin without credentials or query parameters.');
}
const response = await fetch(new URL('/wp-json/', base), { signal: AbortSignal.timeout(30000), redirect: 'error' });
if (!response.ok) throw new Error(`REST discovery returned HTTP ${response.status}`);
const index = await response.json();
if (!Array.isArray(index.namespaces) || !index.routes) throw new Error('Unexpected WordPress REST index.');
const result = {
  checked_at: new Date().toISOString(), site: base.origin, namespaces: index.namespaces,
  routes: Object.fromEntries(Object.entries(index.routes)
    .filter(([route]) => /^\/(auren|ldlms|buddypress|buddyboss|wc)\//.test(route))
    .map(([route, value]) => [route, { methods: value.methods }])),
  scope: 'Public route discovery only; does not prove authentication, enrollment or progress writes.',
};
await mkdir('artifacts', { recursive: true });
await writeFile('artifacts/public-site-audit.json', JSON.stringify(result, null, 2) + '\n');
console.log(`REST available: ${result.namespaces.length} namespaces; ${Object.keys(result.routes).length} relevant routes. Evidence: artifacts/public-site-audit.json`);
