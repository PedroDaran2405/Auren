import React,{useEffect,useState} from 'react';
import { Text,View } from 'react-native';
import type { AurenApi,Member } from '@auren/api-client';
import { Avatar,Button,u } from './ui';
import { Feedback,Sheet,f } from './Forms';
export default function BlockedMembers({api,onClose}:{api:AurenApi;onClose:()=>void}){
 const [items,setItems]=useState<Member[]>([]),[next,setNext]=useState<number|null>(null),[busy,setBusy]=useState(false),[loading,setLoading]=useState(true),[error,setError]=useState('');
 function load(page=1){setLoading(true);void api.blocked(page).then(data=>{setItems(old=>page===1?data.items:[...old,...data.items]);setNext(data.next_page);}).catch(e=>setError(e.message)).finally(()=>setLoading(false));}
 useEffect(()=>{load();},[api]);
 return <Sheet title="Personas bloqueadas" onClose={onClose} busy={busy}><Text style={u.body}>Los bloqueos son los mismos que en la comunidad web. Desbloquear no restaura automáticamente conexiones anteriores.</Text><Feedback error={error} busy={loading||busy}/>{items.map(x=><View key={x.id} style={f.panel}><View style={[u.row,{gap:12}]}><Avatar url={x.avatar_url} name={x.name}/><Text style={u.author}>{x.name}</Text></View><Button secondary compact disabled={busy} onPress={()=>{setBusy(true);setError('');void api.blockMember(x.id,false).then(()=>setItems(old=>old.filter(y=>y.id!==x.id))).catch(e=>setError(e.message)).finally(()=>setBusy(false));}}>Desbloquear</Button></View>)}{!items.length&&!loading&&!error&&<Text style={u.body}>No tienes personas bloqueadas.</Text>}{next&&<Button secondary disabled={loading||busy} onPress={()=>load(next)}>Cargar más</Button>}</Sheet>;
}
