import { test } from 'node:test';
import assert from 'node:assert/strict';
import { AurenApi, ApiError, safeHttpsUrl, validateBaseUrl } from '../packages/api-client/src/index';
function json(data:unknown,status=200){return new Response(JSON.stringify(data),{status,headers:{'content-type':'application/json'}});}
test('credentials stay on the configured HTTPS origin and requests do not cache',async()=>{
 let request:RequestInit|undefined;let target='';
 const api=new AurenApi({baseUrl:'https://example.com/wp-json/auren/v1',token:'private-token',fetcher:async(url,init)=>{target=String(url);request=init;return json({id:7});}});
 await api.me();assert.equal(target,'https://example.com/wp-json/auren/v1/me');assert.equal(request?.credentials,'omit');assert.equal(request?.cache,'no-store');assert.equal(request?.redirect,'error');
 await assert.rejects(api.request('//attacker.example'),/Ruta/);
 assert.throws(()=>validateBaseUrl('http://example.com'));
 assert.throws(()=>validateBaseUrl('https://user:pass@example.com'));
});
test('web uses a REST nonce and same-origin cookies',async()=>{
 const api=new AurenApi({baseUrl:'https://example.com/wp-json/auren/v1',nonce:'test-nonce',fetcher:async(_,init)=>{assert.equal(init?.credentials,'same-origin');assert.equal((init?.headers as Record<string,string>)['X-WP-Nonce'],'test-nonce');assert.equal((init?.headers as Record<string,string>).Authorization,undefined);return json({id:7});}});await api.me();
});
test('expiry clears state, while a denied course does not log out the user',async()=>{
 let expirations=0;
 const api=new AurenApi({baseUrl:'https://example.com/',onExpired:()=>expirations++,fetcher:async()=>json({code:'course_forbidden'},403)});
 await assert.rejects(api.course(2),ApiError);assert.equal(expirations,0);
 const expired=new AurenApi({baseUrl:'https://example.com/',onExpired:()=>expirations++,fetcher:async()=>json({code:'session_expired'},401)});
 await assert.rejects(expired.me(),ApiError);assert.equal(expirations,1);
});
test('logout disposal prevents a delayed private response from resurfacing',async()=>{
 let finish:((response:Response)=>void)|undefined;
 const api=new AurenApi({baseUrl:'https://example.com/',fetcher:()=>new Promise(resolve=>{finish=resolve;})});
 const request=api.me();api.dispose();finish!(json({id:99,name:'private'}));
 await assert.rejects(request,(e:ApiError)=>e.code==='session_closed');await assert.rejects(api.me());
});
test('failed writes are never retried automatically and upstream HTML is not shown',async()=>{
 let calls=0;const api=new AurenApi({baseUrl:'https://example.com/',fetcher:async()=>{calls++;return new Response('<script>private</script>',{status:502});}});
 await assert.rejects(api.complete(1,2),(e:ApiError)=>!e.message.includes('private'));assert.equal(calls,1);
});
test('only safe HTTPS video URLs are accepted',()=>{
 for(const s of ['javascript:alert(1)','data:text/html,x','http://cdn.example/a.mp4','https://user:pw@example.com/v.mp4'])assert.equal(safeHttpsUrl(s),null);
 assert.equal(safeHttpsUrl('https://cdn.example/a.mp4'),'https://cdn.example/a.mp4');
});
