import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
const fixture=()=>readFileSync('artifacts/header-fixture.html','utf8');
const script=()=>readFileSync('wordpress/auren-app-bridge/assets/gamification.js','utf8');
for(const width of [1920,1280,1024,390,320]){
 test(`server-rendered header stays in place through delayed refresh and session expiry at ${width}px`,async({page})=>{
  await page.setViewportSize({width,height:900});
  await page.route('**/header-fixture',route=>route.fulfill({contentType:'text/html',body:fixture()}));
  const errors:string[]=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto('/header-fixture');
  const host=width>900?'#header-aside':'.bb-mobile-header';
  const widget=page.locator(`${host} [data-auren-gami]`);
  const control=page.locator(width>900?'#menu-control':'#mobile-menu');
  await expect(widget.locator('.auren-gami__balance')).toHaveText('100');
  const before=await control.boundingBox();
  await page.evaluate(()=>{(window as any).headerShifts=[];new PerformanceObserver(list=>{for(const e of list.getEntries())(window as any).headerShifts.push((e as any).value);}).observe({type:'layout-shift',buffered:false});});
  let complete:(()=>void)|undefined;
  let status=200;
  await page.route('**/gamification',async route=>{
   await new Promise<void>(resolve=>{complete=resolve;});
   await route.fulfill({status,contentType:'application/json',body:JSON.stringify({available:true,credits:{balance:123456789,image_url:'https://example.invalid/delayed-coin.png'},rank:{current:{name:'Un nivel con un nombre muy largo que se administra en WordPress',number:100,image_url:null},next:{name:'Siguiente nivel'},progress:{percent:76,requirements:[]}}})});
  });
  await page.route('https://example.invalid/**',route=>route.abort());
  await page.addScriptTag({content:script()});
  await expect(control).toHaveJSProperty('id',width>900?'menu-control':'mobile-menu');
  expect(await control.boundingBox()).toEqual(before);
  // Starting JS does not add a widget or fetch initial server values again.
  expect(complete).toBeUndefined();
  await page.evaluate(()=>window.dispatchEvent(new Event('focus')));
  await expect.poll(()=>Boolean(complete)).toBe(true);
  expect(await control.boundingBox()).toEqual(before);complete!();complete=undefined;
  await expect(widget.locator('.auren-gami__balance')).toHaveText('123,456,789');
  expect(await control.boundingBox()).toEqual(before);
  await expect(widget.locator('.auren-gami__track')).toHaveAttribute('aria-valuenow','76');
  await page.evaluate(()=>window.dispatchEvent(new Event('focus')));await expect.poll(()=>Boolean(complete)).toBe(true);
  status=401;complete!();complete=undefined;
  await expect(widget).toHaveCSS('visibility','hidden');
  expect(await control.boundingBox()).toEqual(before);
  expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
  await page.waitForTimeout(100);
  expect(await page.evaluate(()=>(window as any).headerShifts)).toEqual([]);expect(errors).toEqual([]);
 });
}
test('server values and progress remain visible with JavaScript disabled',async({browser})=>{
 const context=await browser.newContext({javaScriptEnabled:false,viewport:{width:1440,height:900}});const page=await context.newPage();
 await page.route('**/header-fixture',route=>route.fulfill({contentType:'text/html',body:fixture()}));await page.goto('http://localhost:8080/header-fixture');
 await expect(page.locator('#header-aside .auren-gami__balance')).toHaveText('100');await expect(page.locator('#header-aside [role="progressbar"]')).toHaveAttribute('aria-valuenow','50');await context.close();
});
