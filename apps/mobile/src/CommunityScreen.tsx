import React,{useEffect} from 'react';
import {Pressable,Text,View} from 'react-native';
import SocialScreen from './SocialScreen';
import SpacesScreen from './SpacesScreen';
import {MessageCircle,Users} from './icons';
import {colors as c,font} from './ui';
type Props=React.ComponentProps<typeof SocialScreen>&{section?:'activity'|'groups'|'forums';onSection:(s:'activity'|'groups'|'forums')=>void;onBackChange:(back:(()=>void)|null)=>void};
export default function CommunityScreen({section='activity',onSection,onBackChange,...props}:Props){
 const {api,user,initialPost}=props;
 useEffect(()=>{if(user.capabilities.groups||user.capabilities.forums)void api.spaces().catch(()=>{});},[api,user.id]);
 const selected=initialPost?'activity':section;
 const options=[{id:'activity',label:'Actividad',icon:MessageCircle},...(user.capabilities.groups?[{id:'groups',label:'Grupos',icon:Users}]:[]),...(user.capabilities.forums?[{id:'forums',label:'Foros',icon:MessageCircle}]:[])] as const;
 return <View style={{gap:22}}><View accessibilityRole="tablist" style={{flexDirection:'row',padding:4,borderRadius:16,backgroundColor:c.surface,borderWidth:1,borderColor:c.line}}>{options.map(({id,label,icon:Icon})=><Pressable key={id} accessibilityRole="tab" accessibilityLabel={label} accessibilityState={{selected:selected===id}} onPress={()=>onSection(id as 'activity'|'groups'|'forums')} style={{flex:1,minHeight:48,alignItems:'center',justifyContent:'center',gap:5,flexDirection:'row',borderRadius:12,backgroundColor:selected===id?'#06345a':'transparent'}}><Icon size={17} color={selected===id?c.mint:c.muted}/><Text style={{fontFamily:font.bold,fontSize:12,color:selected===id?c.text:c.muted}}>{label}</Text></Pressable>)}</View>{selected==='activity'?<SocialScreen {...props}/>:<SpacesScreen onBackChange={onBackChange} key={selected} api={api} kind={selected} onNavigate={props.onNavigate}/>}</View>;
}
