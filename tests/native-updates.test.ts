import { test } from 'node:test';
import assert from 'node:assert/strict';
import { UpdateActions,type UpdateDriver } from '../apps/mobile/src/updateActions';
function fixture(){const calls:string[]=[];const driver:UpdateDriver={check:async()=>{calls.push('check');return {isAvailable:true,isRollBackToEmbedded:false};},download:async()=>{calls.push('download');},reload:async()=>{calls.push('reload');}};return {calls,driver};}
test('finding and downloading a native update never restarts a lesson',async()=>{
 const {calls,driver}=fixture(),actions=new UpdateActions(driver);
 await actions.check();assert.deepEqual(calls,['check']);await actions.download();assert.deepEqual(calls,['check','download']);
 await actions.apply();assert.deepEqual(calls,['check','download','reload']);
});
test('a second tap cannot overlap a native update operation',async()=>{
 const {calls,driver}=fixture();let resolve!:()=>void;
 driver.download=async()=>{calls.push('download');await new Promise<void>(r=>{resolve=r;});};
 const states:boolean[]=[],actions=new UpdateActions(driver,b=>states.push(b)),first=actions.download();
 assert.equal(await actions.download(),null);assert.equal(await actions.check(),null);assert.equal(await actions.apply(),null);
 resolve();await first;assert.deepEqual(calls,['download']);assert.deepEqual(states,[true,false]);
});
test('an offline update keeps the current app and permits a later retry',async()=>{
 const {calls,driver}=fixture();driver.download=async()=>{throw new Error('offline');};const actions=new UpdateActions(driver);
 await assert.rejects(actions.download(),/offline/);assert.deepEqual(calls,[]);assert.equal((await actions.check())?.value.isAvailable,true);
});
