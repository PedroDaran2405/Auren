import { useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import { Play } from './icons';
import { colors as c } from './ui';
export default function YouTubePlayer({id}:{id:string}){
 const [playing,setPlaying]=useState(false);
 if(!/^[A-Za-z0-9_-]{11}$/.test(id))return null;
 return <View style={{aspectRatio:16/9,borderRadius:18,overflow:'hidden',backgroundColor:c.surface,minHeight:180}}>{playing?<iframe title="Video de la lección en YouTube" src={`https://www.youtube-nocookie.com/embed/${id}?rel=0&playsinline=1&autoplay=1`} style={{width:'100%',height:'100%',border:0}} referrerPolicy="strict-origin-when-cross-origin" allow="autoplay; accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowFullScreen/>:<Pressable accessibilityRole="button" accessibilityLabel="Reproducir lección" onPress={()=>setPlaying(true)} style={{flex:1,alignItems:'center',justifyContent:'center',gap:12}}><View style={{width:52,height:52,borderRadius:26,backgroundColor:c.primary,alignItems:'center',justifyContent:'center'}}><Play size={24} fill={c.text} color={c.text} accessible={false}/></View><Text style={{color:'#f3f6ff',fontFamily:'PlusJakartaSans_600SemiBold'}}>Reproducir lección</Text><Text style={{color:'#a5b4ce',fontSize:12}}>Video de tu academia en YouTube</Text></Pressable>}</View>;
}
