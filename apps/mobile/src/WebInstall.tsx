import React, { useEffect, useState } from 'react';
import { Platform, Pressable, Text, View } from 'react-native';
import { bootstrap } from './auth';
import { Button, colors, u } from './ui';

/** No service worker or private offline cache: each document load reads the deployed release. */
export function WebReleaseBanner({busy}:{busy:boolean}) {
 const [available,setAvailable]=useState(false);
 useEffect(()=>{
  if(Platform.OS!=='web')return;
  const b=bootstrap();if(!b?.release?.build_id||!b.release_url)return;
  const url=new URL(b.release_url,globalThis.location.origin);
  if(url.origin!==globalThis.location.origin)return;
  let stopped=false,last=0,controller:AbortController|null=null;
  async function check(){
   if(stopped||document.visibilityState==='hidden'||controller||Date.now()-last<15000)return;
   last=Date.now();controller=new AbortController();const active=controller;
   const timeout=setTimeout(()=>active.abort(),8000);
   try{
    const r=await fetch(url.href,{credentials:'omit',cache:'no-store',redirect:'error',signal:active.signal});
    if(!r.ok||!r.headers.get('content-type')?.includes('application/json'))return;
    const release=await r.json();
    if(!stopped&&typeof release.build_id==='string'&&/^[a-f0-9]{64}$/.test(release.build_id))setAvailable(release.build_id!==b?.release?.build_id||release.version!==b?.release?.version);
   }catch{/* Offline checks must never interrupt a lesson or session. */}
   finally{clearTimeout(timeout);if(controller===active)controller=null;}
  }
  const onVisible=()=>{void check();};
  const interval=setInterval(onVisible,60000);onVisible();
  document.addEventListener('visibilitychange',onVisible);globalThis.addEventListener('pageshow',onVisible);globalThis.addEventListener('focus',onVisible);
  return()=>{stopped=true;clearInterval(interval);controller?.abort();document.removeEventListener('visibilitychange',onVisible);globalThis.removeEventListener('pageshow',onVisible);globalThis.removeEventListener('focus',onVisible);};
 },[]);
 if(!available)return null;
 return <View accessibilityRole="alert" style={{padding:16,gap:10,backgroundColor:colors.raised}}><Text style={u.body}>Hay una nueva versión de Auren. Actualiza cuando termines lo que estás haciendo.</Text><Button compact disabled={busy} onPress={()=>globalThis.location.reload()}>Cargar nueva versión</Button></View>;
}

export function WebInstallHelp(){
 const [open,setOpen]=useState(false);
 if(Platform.OS!=='web')return null;
 return <View style={{gap:12}}><Pressable accessibilityRole="button" accessibilityState={{expanded:open}} onPress={()=>setOpen(!open)}><Text style={u.link}>Instalar Auren en mi celular</Text></Pressable>{open&&<View style={{gap:10}}><Text style={u.body}>iPhone: abre esta página en Safari, toca Compartir y elige Agregar a inicio. Si aparece «Abrir como app web», actívalo.</Text><Text style={u.body}>Android: abre esta página en Chrome, toca el menú ⋮ y elige Agregar a la pantalla principal o Instalar aplicación.</Text><Text style={u.caption}>Usa el icono Auren para entrar. Necesitas conexión; tus cursos y tu progreso se guardan en la academia.</Text></View>}</View>;
}

export function WebReleaseInfo({busy}:{busy:boolean}){
 if(Platform.OS!=='web')return null;
 const release=bootstrap()?.release;
 return <View style={{gap:12}}><Text style={u.caption}>Auren web{release?` · ${release.version} · ${release.build_id.slice(0,7)}`:''}</Text><Button secondary compact disabled={busy} onPress={()=>globalThis.location.reload()}>Actualizar app</Button><WebInstallHelp/></View>;
}
