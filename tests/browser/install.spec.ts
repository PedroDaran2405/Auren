import { test, expect } from '@playwright/test';

test('installation metadata is public, private pages stay uncached, release notice survives offline and reloads the document',async({page})=>{
 await page.clock.install();
 await page.goto('/?auren_app=1');
 await expect(page.getByRole('button',{name:/Entrar a mi academia/})).toBeVisible();
 await expect(page.locator('link[rel=manifest]')).toHaveAttribute('href',/auren_app_manifest=1/);
 await expect(page.locator('meta[name=apple-mobile-web-app-capable]')).toHaveAttribute('content','yes');
 await expect(page.locator('link[rel=apple-touch-icon]')).toHaveAttribute('href',/auren_app_icon=180.*v=0\.7\.0/);
 await expect(page.locator('meta[property="og:image"]')).toHaveAttribute('content',/auren_app_icon=512.*v=0\.7\.0/);
 await expect(page.locator('link[rel=icon]')).toHaveAttribute('href',/auren_app_icon=192/);
 const metadata=await page.request.get('/?auren_app_manifest=1');expect(metadata.status()).toBe(200);expect(metadata.headers()['content-type']).toContain('application/manifest+json');
 const manifest=await metadata.json();expect(manifest.display).toBe('standalone');expect(manifest.start_url).toContain('auren_app=1');expect(manifest.id).toBe(manifest.start_url);
 for(const size of [180,192,512]){
  const r=await page.request.get(`/?auren_app_icon=${size}`);expect(r.status()).toBe(200);expect(r.headers()['content-type']).toBe('image/png');
  const bytes=await r.body();expect(bytes.subarray(0,8).toString('hex')).toBe('89504e470d0a1a0a');expect(bytes.readUInt32BE(16)).toBe(size);expect(bytes.readUInt32BE(20)).toBe(size);
 }
 expect((await page.request.get('/?auren_app_icon=../wp-config.php')).status()).toBe(404);
 const r=await page.request.get('/?auren_app_release=1');expect(r.status()).toBe(200);expect(r.headers()['cache-control']).toContain('no-store');
 const release=await r.json();expect(Object.keys(release).sort()).toEqual(['build_id','version']);expect(release.build_id).toMatch(/^[a-f0-9]{64}$/);expect(release.version).toBe('0.7.0');
 await page.getByRole('button',{name:'Instalar Auren en mi celular'}).click();await expect(page.getByText(/iPhone: abre esta página en Safari/)).toBeVisible();
 expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
 expect(await page.evaluate(async()=>({registrations:(await navigator.serviceWorker.getRegistrations()).length,caches:(await caches.keys()).length}))).toEqual({registrations:0,caches:0});
 // Install checks never persist credentials. Offline version checks must not break the welcome screen.
 await page.route('**/?auren_app_release=1',route=>route.abort('internetdisconnected'));
 const failed=page.waitForEvent('requestfailed',r=>r.url().includes('auren_app_release=1'));
 await page.clock.fastForward(61000);await failed;await expect(page.getByRole('button',{name:/Entrar a mi academia/})).toBeVisible();await expect(page.getByRole('button',{name:'Cargar nueva versión'})).toHaveCount(0);
 await page.unroute('**/?auren_app_release=1');
 await page.route('**/?auren_app_release=1',route=>route.fulfill({json:{...release,build_id:'f'.repeat(64)}}));
 await page.clock.fastForward(61000);await expect(page.getByRole('button',{name:'Cargar nueva versión'})).toBeVisible();
 await page.getByRole('button',{name:'Cargar nueva versión'}).click();await expect(page.getByRole('button',{name:/Entrar a mi academia/})).toBeVisible();
 expect(await page.evaluate(()=>window.__AUREN_BOOTSTRAP__?.release?.build_id)).toBe(release.build_id);
});
