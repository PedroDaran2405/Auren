import React,{createContext,useContext,useEffect,useMemo,useRef,useState} from 'react';
import { Alert,AppState,Text,View } from 'react-native';
import * as Updates from 'expo-updates';
import { expo } from '../app.json';
import { Button,colors as c,u } from './ui';
import { UpdateActions } from './updateActions';

type State={enabled:boolean;available:boolean;pending:boolean;working:boolean;error:string;notice:string;check:(manual?:boolean)=>void;download:()=>void;apply:()=>void};
const Context=createContext<State|null>(null);
export function AppUpdatesProvider({children}:{children:React.ReactNode}){
 const native=Updates.useUpdates(),enabled=Updates.isEnabled&&!__DEV__;
 const [working,setWorking]=useState(false),[error,setError]=useState(''),[notice,setNotice]=useState('');
 const alive=useRef(true),lastCheck=useRef(Date.now());
 const actions=useMemo(()=>new UpdateActions({check:Updates.checkForUpdateAsync,download:Updates.fetchUpdateAsync,reload:Updates.reloadAsync},busy=>{if(alive.current)setWorking(busy);}),[]);
 useEffect(()=>{alive.current=true;return()=>{alive.current=false;};},[]);
 function check(manual=true){
  if(!enabled||working||native.isChecking||native.isDownloading||native.isStartupProcedureRunning)return;
  if(!manual&&Date.now()-lastCheck.current<5*60*1000)return;
  lastCheck.current=Date.now();if(manual){setError('');setNotice('');}
  void actions.check().then(result=>{if(result&&manual&&alive.current)setNotice(result.value.isAvailable||result.value.isRollBackToEmbedded?'Hay una actualización disponible.':'Ya tienes la versión más reciente para tu instalación.');})
   .catch(()=>{if(manual&&alive.current)setError('No pudimos buscar actualizaciones. Revisa tu conexión e intenta de nuevo.');});
 }
 function download(){
  if(!enabled||!native.isUpdateAvailable||working||native.isChecking||native.isDownloading)return;
  setError('');setNotice('');void actions.download().catch(()=>{if(alive.current)setError('No pudimos descargar la actualización. Puedes seguir usando tu versión actual.');});
 }
 function apply(){
  if(!enabled||!native.isUpdatePending||working)return;
  Alert.alert('Actualizar Auren','La app se reiniciará. Termina tu actividad actual antes de continuar.',[
   {text:'Más tarde',style:'cancel'},
   {text:'Actualizar ahora',onPress:()=>{setError('');void actions.apply().catch(()=>{if(alive.current)setError('No pudimos reiniciar Auren. Cierra y vuelve a abrir la app para aplicar la actualización.');});}},
  ]);
 }
 useEffect(()=>{
  if(!enabled)return;
  const subscription=AppState.addEventListener('change',state=>{if(state==='active')check(false);});
  return()=>subscription.remove();
 },[enabled,working,native.isChecking,native.isDownloading,native.isStartupProcedureRunning]);
 const state={enabled,available:native.isUpdateAvailable,pending:native.isUpdatePending,working:working||native.isChecking||native.isDownloading||native.isRestarting,error,notice,check,download,apply};
 return <Context.Provider value={state}>{children}</Context.Provider>;
}
function UpdateButton({state,busy}:{state:State;busy:boolean}){
 return <Button secondary compact disabled={busy||state.working} onPress={state.pending?state.apply:state.available?state.download:()=>state.check()}>{state.working?'Preparando actualización…':state.pending?'Aplicar actualización':state.available?'Descargar actualización':'Buscar actualizaciones'}</Button>;
}
export function ReleaseBanner({busy}:{busy:boolean}){
 const state=useContext(Context);if(!state?.enabled||(!state.available&&!state.pending))return null;
 return <View accessibilityRole="alert" style={{padding:15,gap:10,backgroundColor:c.raised}}><Text style={u.body}>{state.pending?'La actualización está lista. Aplícala cuando termines tu actividad.':'Hay una nueva versión de Auren disponible para tu instalación.'}</Text><UpdateButton state={state} busy={busy}/>{!!state.error&&<Text accessibilityRole="alert" style={u.caption}>{state.error}</Text>}</View>;
}
export function ReleaseInfo({busy}:{busy:boolean}){
 const state=useContext(Context),channel=Updates.channel==='preview'?' · Pruebas':Updates.channel==='development'?' · Desarrollo':'';
 return <View style={{gap:12}}><Text style={u.caption}>Auren {expo.version}{channel}{Updates.updateId?` · ${Updates.updateId.slice(0,7)}`:''}</Text>{state?.enabled?<><UpdateButton state={state} busy={busy}/>{!!state.error&&<Text accessibilityRole="alert" style={u.body}>{state.error}</Text>}{!!state.notice&&<Text accessibilityLiveRegion="polite" style={u.caption}>{state.notice}</Text>}</>:<Text style={u.caption}>Modo de desarrollo</Text>}</View>;
}
