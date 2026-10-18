import { readFile, readdir, access, realpath } from 'node:fs/promises';
import path from 'node:path';

// Include license files from the installed runtime dependency graph, including fonts.
// Build-tool dependencies reached from Expo may be included as a conservative superset.
export async function runtimeNotices(root) {
  const visited = new Set();
  const sections = [];
  async function locate(name, directory) {
    for (let current = directory; ; current = path.dirname(current)) {
      const candidate = path.join(current, 'node_modules', name, 'package.json');
      try { await access(candidate); return await realpath(candidate); } catch {}
      if (path.dirname(current) === current) return null;
    }
  }
  async function visit(file) {
    if (visited.has(file)) return;
    visited.add(file);
    const directory = path.dirname(file);
    const pkg = JSON.parse(await readFile(file, 'utf8'));
    if (!pkg.name?.startsWith('@auren/')) {
      const names = (await readdir(directory)).filter(name => /^(license|licence|copying|notice|ofl)([._-]|$)/i.test(name)).sort();
      const texts = [];
      for (const name of names) {
        try { texts.push(`${name}\n${await readFile(path.join(directory, name), 'utf8')}`); }
        catch (error) { if (error.code !== 'EISDIR') throw error; }
      }
      sections.push(`${pkg.name}@${pkg.version}\nDeclared license: ${JSON.stringify(pkg.license ?? 'See upstream package')}\n${texts.join('\n\n')}`);
    }
    for (const name of Object.keys({ ...pkg.dependencies, ...pkg.optionalDependencies }).sort()) {
      const dependency = await locate(name, directory);
      if (dependency) await visit(dependency);
      else if (!pkg.optionalDependencies?.[name]) throw new Error(`Missing installed runtime dependency ${name}`);
    }
  }
  await visit(path.join(root, 'apps/mobile/package.json'));
  if (!sections.some(text => text.includes('SIL OPEN FONT LICENSE'))) throw new Error('Font license was not included.');
  return `Auren: third-party component notices\nThis file covers the installed runtime dependency graph; inclusion does not imply every component is loaded on every platform.\n\n${sections.sort().join('\n\n' + '='.repeat(72) + '\n\n')}\n`;
}
