import brandAsset from './brandAsset';
import React, { useEffect, useState } from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import Svg, { Defs, LinearGradient, Polygon, Stop } from 'react-native-svg';
import type { GamiAchievement, GamiProgress, GamiRank, Gamification, Identity } from '@auren/api-client';
import { Award, Check, ChevronRight, LockKeyhole, Trophy } from './icons';
import { Sheet } from './Forms';
import { colors as c, font, u } from './ui';

export function Brand({url,large=false}:{url?:string|null;large?:boolean}) {
 const [failed,setFailed]=useState(false);
 useEffect(()=>setFailed(false),[url]);
 return <Image source={!url||failed?brandAsset:{uri:url}} onError={()=>setFailed(true)} accessibilityRole="image" accessibilityLabel="Auren FX" resizeMode="contain" style={{width:large?224:132,height:large?38:26}}/>;
}
const format=(n:number)=>new Intl.NumberFormat('es-MX',{maximumFractionDigits:2}).format(n);
function Meter({percent,label}:{percent:number|null;label:string}) {
 if(percent===null)return null;
 return <View accessibilityRole="progressbar" accessibilityLabel={label} accessibilityValue={{min:0,max:100,now:percent}} aria-valuemin={0} aria-valuemax={100} aria-valuenow={percent} style={g.track}><View style={[g.fill,{width:`${Math.max(0,Math.min(100,percent))}%`}]}/></View>;
}
export function CreditBadge({data,onPress}:{data?:Gamification;onPress:()=>void}) {
 const [tip,setTip]=useState(false),credit=data?.credits;
 if(!credit)return null;
 return <Pressable accessibilityRole="button" accessibilityLabel={`${format(credit.balance)} Auren Credits`} accessibilityHint="Ver tus créditos y nivel" onPress={onPress} onHoverIn={()=>setTip(true)} onHoverOut={()=>setTip(false)} onFocus={()=>setTip(true)} onBlur={()=>setTip(false)} style={g.creditBadge}>
  {!!credit.image_url&&<Image source={{uri:credit.image_url}} style={g.coin} accessibilityLabel=""/>}<Text numberOfLines={1} style={g.balance}>{format(credit.balance)}</Text>
  {tip&&<View pointerEvents="none" style={g.tooltip}><Text style={g.tooltipText}>Auren Credits</Text></View>}
 </Pressable>;
}
function LevelMedal({rank}:{rank:GamiRank}) {
 if(rank.current.image_url)return <Image source={{uri:rank.current.image_url}} style={{width:64,height:72}} resizeMode="contain" accessibilityLabel=""/>;
 return <View style={g.medal}><Svg width={64} height={72} viewBox="0 0 64 72" accessible={false}><Defs><LinearGradient id="medal" x1="0" y1="0" x2="1" y2="1"><Stop offset="0" stopColor="#49efff"/><Stop offset=".45" stopColor="#1970ff"/><Stop offset="1" stopColor="#06366e"/></LinearGradient></Defs><Polygon points="32,2 61,19 61,53 32,70 3,53 3,19" fill="url(#medal)"/><Polygon points="32,9 55,22 55,50 32,63 9,50 9,22" fill="#032445" stroke="#258fff" strokeWidth="2"/></Svg><Text style={g.levelNumber}>{rank.current.number||'★'}</Text></View>;
}
function RankCaption({rank}:{rank:GamiRank}) {
 const required=rank.progress.requirements.filter(r=>!r.optional);
 if(!rank.next)return <Text style={u.caption}>Has alcanzado el nivel más alto disponible.</Text>;
 if(required.length===1&&required[0]?.percent!==null){const r=required[0]!;return <Text style={u.caption}>{format(r.current)} / {format(r.total)} {r.unit}</Text>;}
 return <Text style={u.caption}>{rank.next.name} · {required.filter(r=>r.earned||r.percent===100).length} de {required.length} requisitos</Text>;
}
export function RankCard({rank,onPress,compact=false}:{rank?:GamiRank|null;onPress?:()=>void;compact?:boolean}) {
 if(!rank)return null;
 return <Pressable accessibilityRole={onPress?'button':undefined} accessibilityLabel={onPress?'Ver mi nivel':undefined} onPress={onPress} disabled={!onPress} style={[g.card,compact&&{padding:0,borderWidth:0,backgroundColor:'transparent'}]}>
  {!compact&&<Text style={u.sectionTitle}>Tu progreso</Text>}
  <View style={[u.row,{gap:16}]}><LevelMedal rank={rank}/><View style={{flex:1,gap:8}}><View style={u.between}><Text style={[u.author,{flex:1,fontSize:15}]}>{rank.current.number?`Nivel ${rank.current.number} · `:''}{rank.current.name}</Text>{onPress&&<ChevronRight color={c.muted} size={19}/>}</View><RankCaption rank={rank}/><Meter percent={rank.progress.percent} label={`Progreso hacia ${rank.next?.name??rank.current.name}`}/>{!!rank.next&&<Text style={[u.caption,{color:c.muted}]}>Siguiente: {rank.next.name}</Text>}</View></View>
 </Pressable>;
}
export function NextAchievement({data,onPress}:{data?:Gamification;onPress:(item:GamiAchievement)=>void}) {
 const item=data?.achievements.find(a=>!a.earned&&a.progress.requirements.length>0);
 if(!item)return null;
 return <Pressable accessibilityRole="button" accessibilityLabel={`Ver próximo logro ${item.name}`} onPress={()=>onPress(item)} style={[g.card,u.row,{gap:14}]}>
  {item.image_url?<Image source={{uri:item.image_url}} style={g.achievementImage} resizeMode="contain" accessibilityLabel=""/>:<Award size={43} color={c.mint}/>}
  <View style={{flex:1,gap:7}}><Text style={u.author}>Tu próximo logro</Text><Text style={u.body}>{item.name}</Text><Meter percent={item.progress.percent} label={`Avance de ${item.name}`}/></View><ChevronRight size={19} color={c.muted}/>
 </Pressable>;
}
export function Achievements({data,onPress}:{data?:Gamification;onPress:(item:GamiAchievement)=>void}) {
 if(!data?.achievements.length)return null;
 return <View style={u.stack}><View style={u.between}><Text style={u.sectionTitle}>Tus logros</Text><Text style={u.caption}>{data.achievement_earned} de {data.achievement_total}</Text></View><View style={g.achievementGrid}>{data.achievements.map(item=><Pressable key={item.id} accessibilityRole="button" accessibilityLabel={`Ver logro ${item.name}${item.earned?', completado':''}`} onPress={()=>onPress(item)} style={[g.achievementTile,item.earned&&{borderColor:'#007f9d'}]}>
  {item.image_url?<Image source={{uri:item.image_url}} resizeMode="contain" style={[g.tileImage,!item.earned&&{opacity:.62}]} accessibilityLabel=""/>:<Award size={50} color={item.earned?c.mint:c.muted}/>}
  <Text numberOfLines={2} style={[u.author,{textAlign:'center'}]}>{item.name}</Text><Text style={[u.caption,{color:item.earned?c.mint:c.muted}]}>{item.earned?'Completado':'Por completar'}</Text>{item.earned?<Check size={16} color={c.mint}/>:<LockKeyhole size={14} color={c.muted}/>}
 </Pressable>)}</View></View>;
}
function Requirements({progress}:{progress:GamiProgress}) {
 if(!progress.requirements.length)return null;
 return <View style={u.stack}>{progress.requirements.map(r=><View key={r.id} style={g.requirement}><View style={[u.row,{gap:10}]}>{r.earned?<Check color={c.mint} size={19}/>:<View style={g.requirementDot}/>}<Text style={[u.body,{flex:1,color:c.text}]}>{r.title}{r.optional?' (opcional)':''}</Text></View><Text style={u.caption}>{r.earned?'Requisito completado':r.percent===null?'Avance según las condiciones de esta actividad':`${format(r.current)} / ${format(r.total)} ${r.unit}`}</Text><Meter percent={r.percent} label={`Avance: ${r.title}`}/></View>)}</View>;
}
export function GamificationSheet({user,achievement,onClose}:{user:Identity;achievement:GamiAchievement|null;onClose:()=>void}) {
 const data=user.gamification,rank=data?.rank,credit=data?.credits;
 return <Sheet title={achievement?'Tu logro':'Auren Credits'} onClose={onClose}>
  {achievement?<><View style={g.detailHero}>{achievement.image_url?<Image source={{uri:achievement.image_url}} resizeMode="contain" style={{width:100,height:100}} accessibilityLabel=""/>:<Award size={72} color={c.mint}/>}<Text style={[u.title,{textAlign:'center'}]}>{achievement.name}</Text><Text style={[u.tag,achievement.earned&&{color:c.mint}]}>{achievement.earned?'Completado':'Por completar'}</Text></View>{!!achievement.description&&<Text style={u.body}>{achievement.description}</Text>}<Requirements progress={achievement.progress}/>{!!achievement.reward&&achievement.reward>0&&<View style={[g.card,u.row,{gap:12}]}><Trophy size={26} color={c.mint}/><View style={{flex:1,gap:3}}><Text style={u.author}>Recompensa al completar</Text><Text style={u.body}>+{format(achievement.reward)} Auren Credits</Text></View>{!!credit?.image_url&&<Image source={{uri:credit.image_url}} style={g.coin} accessibilityLabel=""/>}</View>}</>
  :<>{credit&&<View style={g.detailHero}>{credit.image_url&&<Image source={{uri:credit.image_url}} resizeMode="contain" style={{width:74,height:74}} accessibilityLabel=""/>}<Text style={[u.title,{fontSize:40,lineHeight:52}]}>{format(credit.balance)}</Text><Text style={u.body}>Auren Credits</Text></View>}{rank&&<><RankCard rank={rank} compact/>{rank.next&&<><Text style={u.sectionTitle}>Camino a {rank.next.name}</Text><Requirements progress={rank.progress}/></>}</>}<Text style={u.caption}>Tu saldo y tus niveles se comparten con tu cuenta de Auren. Las actividades y sus recompensas dependen de las reglas de la academia.</Text></>}
 </Sheet>;
}
const g=StyleSheet.create({
 card:{padding:16,gap:16,borderRadius:16,borderWidth:1,borderColor:c.line,backgroundColor:c.surface},
 creditBadge:{flexDirection:'row',alignItems:'center',gap:5,minHeight:40,paddingHorizontal:8,borderRadius:22,borderWidth:1,borderColor:c.line,backgroundColor:'#001528',zIndex:5},coin:{width:28,height:28},balance:{fontFamily:font.bold,fontSize:13,color:c.text,maxWidth:88},tooltip:{position:'absolute',top:46,right:0,backgroundColor:'#123654',borderRadius:8,paddingHorizontal:12,paddingVertical:7,minWidth:118,zIndex:20},tooltipText:{fontFamily:font.medium,fontSize:11,color:c.text,textAlign:'center'},
 track:{height:7,borderRadius:8,backgroundColor:'#123552',overflow:'hidden'},fill:{height:'100%',borderRadius:8,backgroundColor:c.mint},medal:{width:64,height:72,alignItems:'center',justifyContent:'center'},levelNumber:{position:'absolute',fontFamily:font.heavy,fontSize:29,color:c.text},achievementImage:{width:48,height:58},achievementGrid:{flexDirection:'row',flexWrap:'wrap',gap:10},achievementTile:{flexGrow:1,flexBasis:'44%',maxWidth:'49%',alignItems:'center',gap:8,padding:14,borderRadius:14,borderWidth:1,borderColor:c.line,backgroundColor:c.surface},tileImage:{height:76,width:76},detailHero:{alignItems:'center',gap:16,paddingVertical:15},requirement:{gap:10,paddingBottom:16,borderBottomWidth:1,borderBottomColor:c.line},requirementDot:{width:18,height:18,borderRadius:9,borderWidth:1,borderColor:c.muted},
});
