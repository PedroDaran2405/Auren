import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
const password=readFileSync('.local/compose.env','utf8').match(/^AUREN_TEST_PASSWORD=(.+)$/m)![1]!;
test('real GamiPress balance, level, reward details and original branding remain connected across screens',async({page})=>{
 await page.route('**/ChatGPT-Image-9-jul-2026-10_53_16-p.m-e1783659259666.png',route=>route.fulfill({body:readFileSync('apps/mobile/assets/auren-logo.png'),contentType:'image/png'}));
 const errors:string[]=[];page.on('pageerror',e=>errors.push(e.message));
 await page.goto('/?auren_app=1');await page.getByRole('button',{name:/Entrar a mi academia/}).click();
 await page.locator('#user_login').fill('alumno-a');await page.locator('#user_pass').fill(password);await page.locator('#wp-submit').click();
 await expect(page.getByRole('tab',{name:'Cursos',exact:true})).toBeVisible();
 const data=await page.evaluate(async()=>{const b=globalThis.__AUREN_BOOTSTRAP__!;return (await fetch(b.api_url+'gamification',{headers:{'X-WP-Nonce':b.nonce!}})).json();});
 test.skip(!data.available,'Run npm run local:gamipress and npm run test:gamification for real GamiPress acceptance.');
 const credit=page.getByRole('button',{name:'100 Auren Credits',exact:true});await expect(credit).toBeVisible();
 await expect(page.getByRole('img',{name:'Auren FX',exact:true}).first()).toBeVisible();
 await expect(page.getByRole('progressbar',{name:'Progreso hacia Explorador · fixture',exact:true})).toHaveAttribute('aria-valuenow','50');
 await credit.hover();await expect(page.getByText('Auren Credits',{exact:true})).toBeVisible();await credit.click();
 await expect(page.getByRole('heading',{name:'Auren Credits',exact:true})).toBeVisible();await expect(page.getByText('Camino a Explorador · fixture',{exact:true})).toBeVisible();
 await page.getByRole('button',{name:'Cerrar ventana',exact:true}).click();
 await page.getByRole('button',{name:'Ver próximo logro Primer paso · fixture',exact:true}).click();await expect(page.getByText('+20 Auren Credits',{exact:true})).toBeVisible();
 await page.getByRole('button',{name:'Cerrar ventana',exact:true}).click();
 await page.getByRole('tab',{name:'Perfil',exact:true}).click();await expect(page.getByText('Tus logros',{exact:true})).toBeVisible();
 await expect(page.getByRole('button',{name:'Ver logro Primer paso · fixture',exact:true})).toBeVisible();
 for(const width of [320,390,768]){await page.setViewportSize({width,height:844});expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth)).toBe(true);await expect(credit).toBeVisible();}
 await page.setViewportSize({width:390,height:844});await page.screenshot({path:'artifacts/profile-gamification-mobile.png',fullPage:true});
 await page.getByRole('tab',{name:'Inicio',exact:true}).click();await page.screenshot({path:'artifacts/home-gamification-mobile.png',fullPage:true});
 await expect(page.getByRole('tab',{name:'Mensajes',exact:true})).toHaveCount(0);await expect(page.getByRole('tab',{name:'Avisos',exact:true})).toBeVisible();
 await page.getByRole('tab',{name:'Perfil',exact:true}).click();await page.getByRole('button',{name:'Cerrar sesión',exact:true}).click();
 await expect(credit).toHaveCount(0);expect((await page.request.get('/wp-json/auren/v1/gamification')).status()).toBe(401);expect(errors).toEqual([]);
});
