import { useState } from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { useVideoPlayer, VideoView } from 'expo-video';
import { useEventListener } from 'expo';
import { safeHttpsUrl } from '@auren/api-client';
export default function Video({url}:{url:string}) {
  const [failed,setFailed] = useState(false);
  const source = safeHttpsUrl(url);
  const player = useVideoPlayer(source, p=>{ p.loop=false; });
  useEventListener(player,'statusChange',event=>setFailed(event.status === 'error'));
  if (!source || failed) return <Text accessibilityRole="alert" style={s.error}>El video no se pudo cargar. Puedes abrir la lección en la academia e intentar de nuevo.</Text>;
  return <View style={s.frame}><VideoView player={player} nativeControls style={s.video} accessibilityLabel="Video de la lección" /></View>;
}
const s=StyleSheet.create({frame:{borderRadius:18,overflow:'hidden',backgroundColor:'#030711'},video:{width:'100%',aspectRatio:16/9},error:{color:'#f5c19a',lineHeight:24}});
