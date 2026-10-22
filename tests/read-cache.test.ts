import {test} from 'node:test';
import assert from 'node:assert/strict';
import {AurenApi,ApiError} from '../packages/api-client/src/index';
const json=(data:unknown,status=200)=>new Response(JSON.stringify(data),{status,headers:{'content-type':'application/json'}});
test('overlapping reads share one network request; a fresh screen uses its session memory',async()=>{
 let calls=0,finish!:(r:Response)=>void;
 const api=new AurenApi({baseUrl:'https://example.com/',fetcher:()=>{calls++;return new Promise(resolve=>finish=resolve);}});
 const first=api.profile(),second=api.profile();assert.equal(calls,1);finish(json({fields:[]}));await Promise.all([first,second]);
 await api.profile();assert.equal(calls,1);assert.deepEqual(api.peek('/profile'),{fields:[]});api.dispose();assert.equal(api.peek('/profile'),undefined);
});
test('memory is private to each account/client and never replaces course/lesson permission checks',async()=>{
 let calls=0;const fetcher=async()=>{calls++;return json({fields:[],id:calls});};
 const first=new AurenApi({baseUrl:'https://example.com/',fetcher}),other=new AurenApi({baseUrl:'https://example.com/',fetcher});
 await first.profile();assert.equal(other.peek('/profile'),undefined);await other.profile();assert.equal(calls,2);
 await first.course(1);await first.course(1);await first.lesson(1,2);await first.lesson(1,2);assert.equal(calls,6);
});
test('explicit refresh bypasses memory and expired entries request current server data',async()=>{
 const realNow=Date.now;let now=realNow(),calls=0;Date.now=()=>now;
 try{
  const api=new AurenApi({baseUrl:'https://example.com/',fetcher:async()=>json({fields:[],revision:++calls})});
  await api.profile();await api.profile();assert.equal(calls,1);await api.profile(true);assert.equal(calls,2);
  now+=31000;await api.profile();assert.equal(calls,3);now+=121000;assert.equal(api.peek('/profile'),undefined);
 }finally{Date.now=realNow;}
});
test('a write invalidates reads immediately, including a failed write, and late older reads cannot repopulate memory',async()=>{
 let finish!:(r:Response)=>void,calls=0;
 const api=new AurenApi({baseUrl:'https://example.com/',fetcher:async(_,init)=>{
  calls++;if(init?.method==='PATCH')return json({code:'profile_validation'},400);
  if(calls===1)return new Promise(resolve=>finish=resolve);return json({fields:[]});
 }});
 const old=api.profile();await assert.rejects(api.saveProfile([]));finish(json({fields:[{private:'old'}]}));await assert.rejects(old,(e:ApiError)=>e.code==='request_superseded');
 assert.equal(api.peek('/profile'),undefined);await api.profile();assert.equal(calls,3);
});
test('server rejection clears memory even when another view was warmed successfully',async()=>{
 const api=new AurenApi({baseUrl:'https://example.com/',fetcher:async(url)=>String(url).endsWith('/me')?json({code:'session_expired'},401):json({fields:[]})});
 await api.profile();await assert.rejects(api.me());assert.equal(api.peek('/profile'),undefined);
});
test('one startup response warms only actor-scoped reads; courses still fetch authorization',async()=>{
 let calls=0;const profile={fields:[]},data={identity:{id:2},catalog:{items:[],next_page:null},profile,notifications:{items:[],next_page:null,unread_count:0},community:{items:[],next_page:null}};
 const api=new AurenApi({baseUrl:'https://example.com/',fetcher:async()=>{calls++;return json(data);}});
 await api.startup();await api.profile();await api.notifications();await api.community();assert.equal(calls,1);await api.course(3);assert.equal(calls,2);
});
test('startup fallback is only for an older bridge, never for rejected authorization',async()=>{
 let calls=0;const denied=new AurenApi({baseUrl:'https://example.com/',fetcher:async()=>{calls++;return json({code:'login_required'},401);}});
 await assert.rejects(denied.startup());assert.equal(calls,1);
 const old=new AurenApi({baseUrl:'https://example.com/',fetcher:async(url)=>{const path=new URL(String(url)).pathname;return path==='/startup'?json({code:'rest_no_route'},404):path==='/me'?json({id:3}):json({items:[],next_page:null});}});
 assert.equal((await old.startup()).identity.id,3);
});
