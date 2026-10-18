import { useState } from 'react';
import { Pressable, Text, View } from 'react-native';
export default function YouTubePlayer({id}:{id:string}){
 const [playing,setPlaying]=useState(false);
 if(!/^[A-Za-z0-9_-]{11}$/.test(id))return null;
 return <View style={{aspectRatio:16/9,borderRadius:18,overflow:'hidden',backgroundColor:'#0b172a',minHeight:180}}>{playing?<iframe title="Video de la lección en YouTube" src={`https://www.youtube-nocookie.com/embed/${id}?rel=0&playsinline=1`} style={{width:'100%',height:'100%',border:0}} referrerPolicy="strict-origin-when-cross-origin" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowFullScreen/>:<Pressable accessibilityRole="button" onPress={()=>setPlaying(true)} style={{flex:1,alignItems:'center',justifyContent:'center',gap:12}}><Text style={{color:'#80e6ff',fontSize:40}}>▶</Text><Text style={{color:'#f3f6ff',fontFamily:'PlusJakartaSans_600SemiBold'}}>Reproducir lección</Text><Text style={{color:'#a5b4ce',fontSize:12}}>El reproductor conecta con YouTube.</Text></Pressable>}</View>;
}
