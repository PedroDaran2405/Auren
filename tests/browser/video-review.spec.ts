import {test,expect} from '@playwright/test';
import {readFileSync} from 'node:fs';
const password=readFileSync('.local/compose.env','utf8').match(/^AUREN_TEST_PASSWORD=(.+)$/m)![1]!;
test('startup batches reads, warms screens, reuses them and refreshes explicitly without losing profile edits',async({page})=>{
 let startupReads=0,profileReads=0,noticeReads=0,feedReads=0;
 await page.route('**/wp-json/auren/v1/startup',async route=>{startupReads++;await new Promise(r=>setTimeout(r,500));await route.continue();});
 await page.route('**/wp-json/auren/v1/profile',async route=>{if(route.request().method()==='GET')profileReads++;await route.continue();});
 await page.route('**/wp-json/auren/v1/notifications?*',async route=>{noticeReads++;await route.continue();});
 await page.route('**/wp-json/auren/v1/community?*',async route=>{feedReads++;await route.continue();});
 await page.goto('/?auren_app=1');await page.getByRole('button',{name:/Entrar a mi academia/}).click();
 await expect(page.locator('.auren-login-brand img')).toHaveAttribute('alt','Auren FX');
 await page.locator('#user_login').fill('alumno-a');await page.locator('#user_pass').fill(password);await page.locator('#wp-submit').click();
 await expect(page.getByRole('progressbar',{name:'Cargando tu academia'})).toBeVisible();
 await expect(page.getByRole('button',{name:/Entrar a mi academia/})).toHaveCount(0);
 await expect(page.getByRole('tab',{name:'Cursos',exact:true})).toBeVisible();expect(startupReads).toBe(1);
 expect(profileReads).toBe(0);expect(noticeReads).toBe(0);expect(feedReads).toBe(0);
 // Wait for warm requests to settle before entering the screens.
 await page.waitForTimeout(350);
 await page.getByRole('tab',{name:'Avisos',exact:true}).click();await expect(page.getByRole('heading',{name:'Tus avisos'})).toBeVisible();
 await page.getByRole('tab',{name:'Perfil',exact:true}).click();await page.getByRole('button',{name:'Editar mi perfil',exact:true}).click();
 await expect(page.getByRole('textbox',{name:'Nombre',exact:true})).toBeVisible();await page.getByRole('textbox',{name:'Nombre',exact:true}).fill('Cambios sin guardar');
 await page.getByRole('button',{name:'Cerrar ventana',exact:true}).click();await expect(page.getByRole('heading',{name:'¿Descartar tus cambios?'})).toBeVisible();
 await page.getByRole('button',{name:'Seguir editando',exact:true}).click();await expect(page.getByRole('textbox',{name:'Nombre',exact:true})).toHaveValue('Cambios sin guardar');
 await page.getByRole('button',{name:'Cerrar ventana',exact:true}).click();await page.getByRole('button',{name:'Descartar cambios',exact:true}).click();
 await page.getByRole('tab',{name:'Comunidad',exact:true}).click();await expect(page.getByRole('heading',{name:'Comunidad',exact:true})).toBeVisible();
 expect(profileReads).toBe(0);expect(noticeReads).toBe(0);expect(feedReads).toBe(0);
 await page.getByRole('button',{name:'Actualizar comunidad',exact:true}).click();await expect.poll(()=>feedReads).toBe(1);
 await page.getByRole('tab',{name:'Avisos',exact:true}).click();await page.getByRole('button',{name:'Actualizar avisos',exact:true}).click();await expect.poll(()=>noticeReads).toBe(1);
 expect(await page.evaluate(()=>({html:getComputedStyle(document.documentElement).backgroundColor,body:getComputedStyle(document.body).backgroundColor,overflow:document.documentElement.scrollWidth>innerWidth}))).toEqual({html:'rgb(0, 16, 30)',body:'rgb(0, 16, 30)',overflow:false});
 await page.screenshot({path:'artifacts/video-review-mobile.png',fullPage:true});
});

test('a background refresh cannot overwrite text already being edited',async({page})=>{
 await page.clock.install();let profileReads=0;
 await page.route('**/wp-json/auren/v1/profile',async route=>{
  if(route.request().method()==='GET'&&++profileReads>0)await new Promise(r=>setTimeout(r,600));
  await route.continue();
 });
 await page.goto('/?auren_app=1');await page.getByRole('button',{name:/Entrar a mi academia/}).click();await page.locator('#user_login').fill('alumno-a');await page.locator('#user_pass').fill(password);await page.locator('#wp-submit').click();
 await expect(page.getByRole('tab',{name:'Perfil',exact:true})).toBeVisible();expect(profileReads).toBe(0);await page.waitForTimeout(100);
 await page.clock.fastForward(31000);
 await page.getByRole('tab',{name:'Perfil',exact:true}).click();await page.getByRole('button',{name:'Editar mi perfil',exact:true}).click();
 const name=page.getByRole('textbox',{name:'Nombre',exact:true});await name.fill('Texto que estoy escribiendo');await expect.poll(()=>profileReads).toBe(1);
 await page.waitForTimeout(800);await expect(name).toHaveValue('Texto que estoy escribiendo');
});
