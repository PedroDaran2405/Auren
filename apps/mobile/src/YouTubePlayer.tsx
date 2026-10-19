import { useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import { WebView } from 'react-native-webview';
import { Play } from './icons';
export default function YouTubePlayer({id}:{id:string}){
 const [playing,setPlaying]=useState(false);const [failed,setFailed]=useState(false);
 if(!/^[A-Za-z0-9_-]{11}$/.test(id))return null;
 const origin=process.env.EXPO_PUBLIC_WORDPRESS_URL??'https://www.aurenfx.com';
 return <View style={{aspectRatio:16/9,borderRadius:18,overflow:'hidden',backgroundColor:'#0b172a',minHeight:180}}>{failed?<Text style={{color:'#f3f6ff',padding:22}}>No pudimos cargar el video. Puedes abrir la lección en la academia.</Text>:playing?<WebView source={{uri:`https://www.youtube-nocookie.com/embed/${id}?rel=0&playsinline=1`,headers:{Referer:origin}}} originWhitelist={['https://www.youtube-nocookie.com']} onShouldStartLoadWithRequest={r=>r.url.startsWith('https://www.youtube-nocookie.com/embed/')||r.url==='about:blank'} allowsFullscreenVideo allowsInlineMediaPlayback mediaPlaybackRequiresUserAction sharedCookiesEnabled={false} thirdPartyCookiesEnabled={false} onError={()=>setFailed(true)} style={{flex:1,backgroundColor:'#0b172a'}}/>:<Pressable accessibilityRole="button" onPress={()=>setPlaying(true)} style={{flex:1,alignItems:'center',justifyContent:'center',gap:12}}><Play size={36} color="#b9bcff" fill="#b9bcff" accessible={false}/><Text style={{color:'#f3f6ff',fontFamily:'PlusJakartaSans_600SemiBold'}}>Reproducir lección</Text><Text style={{color:'#a5b4ce',fontSize:12}}>El reproductor conecta con YouTube.</Text></Pressable>}</View>;
}
