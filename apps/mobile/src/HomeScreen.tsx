import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import type { Activity, Course, GamiAchievement, Identity } from '@auren/api-client';
import { Bell, BookOpen, ChevronRight, MessageCircle, ShieldCheck, UserRound, Users } from './icons';
import { ActivityCard, CourseCard, colors as c, font, u } from './ui';
import { NextAchievement, RankCard } from './Gamification';
type Tab='Inicio'|'Cursos'|'Comunidad'|'Avisos'|'Perfil';
export default function HomeScreen({user,course,posts,communityError,busy,onTab,onSpaces,onCourse,onResume,onPost,onLevel,onAchievement}:{user:Identity;course?:Course;posts:Activity[];communityError:string;busy:boolean;onTab:(tab:Tab)=>void;onSpaces:(section:'groups'|'forums')=>void;onCourse:(id:number)=>void;onResume:(id:number)=>void;onPost:(id:number)=>void;onLevel:()=>void;onAchievement:(item:GamiAchievement)=>void}) {
 const shortcuts=[{title:'Mis cursos',action:()=>onTab('Cursos'),icon:BookOpen},...(user.capabilities.groups?[{title:'Mis grupos',action:()=>onSpaces('groups'),icon:Users}]:[]),...(user.capabilities.forums?[{title:'Foros',action:()=>onSpaces('forums'),icon:MessageCircle}]:[]),{title:'Mis avisos',action:()=>onTab('Avisos'),icon:Bell}];
 return <View style={[u.stack,{gap:16}]}>
  <View style={{gap:3}}><Text accessibilityRole="header" style={s.greeting}>Hola, {user.name.split(' ')[0]}.</Text><Text style={u.body}>Sigamos donde te quedaste.</Text></View>
  <View style={u.between}><Text style={u.sectionTitle}>Continúa aprendiendo</Text><Pressable accessibilityRole="button" accessibilityLabel="Ver mis cursos" onPress={()=>onTab('Cursos')} hitSlop={10}><ChevronRight size={23} color={c.text}/></Pressable></View>
  {course?<CourseCard course={course} hero onPress={()=>onCourse(course.id)} onContinue={()=>onResume(course.id)}/>:!busy&&<View style={s.panel}><BookOpen size={30} color={c.mint}/><Text style={u.sectionTitle}>Tu ruta está por comenzar</Text><Text style={u.body}>Los cursos disponibles para tu cuenta aparecerán aquí.</Text></View>}
  <RankCard rank={user.gamification?.rank} onPress={onLevel}/>
  <NextAchievement data={user.gamification} onPress={onAchievement}/>
  <View style={{gap:12}}><Text style={u.sectionTitle}>Accesos rápidos</Text><View style={[u.row,{gap:8,alignItems:'stretch'}]}>{shortcuts.map(({title,action,icon:Icon})=><Pressable key={title} accessibilityRole="button" accessibilityLabel={title} onPress={action} style={({pressed})=>[s.shortcut,pressed&&{backgroundColor:c.raised}]}><Icon size={26} color={c.mint} strokeWidth={1.7}/><Text numberOfLines={1} style={s.shortcutLabel}>{title}</Text></Pressable>)}</View></View>
  {user.capabilities.community&&<><View style={u.between}><Text style={u.sectionTitle}>En la comunidad</Text><Pressable accessibilityRole="button" onPress={()=>onTab('Comunidad')} hitSlop={8}><Text style={u.link}>Ver todo →</Text></Pressable></View>{posts.slice(0,2).map(item=><ActivityCard key={item.id} item={item} onPress={()=>onPost(item.id)}/>)}{!posts.length&&!busy&&<View style={s.panel}><Text style={u.body}>{communityError||'Las novedades de tu comunidad aparecerán aquí.'}</Text></View>}</>}
  <View style={[u.row,{gap:10,paddingVertical:8}]}><ShieldCheck size={19} color={c.muted}/><Text style={[u.caption,{flex:1}]}>Tu progreso se guarda en Auren y te acompaña en la web y en la app.</Text></View>
 </View>;
}
const s=StyleSheet.create({greeting:{fontFamily:font.heavy,fontSize:27,lineHeight:37,color:c.text,letterSpacing:-.8},panel:{padding:20,gap:12,borderRadius:16,borderWidth:1,borderColor:c.line,backgroundColor:c.surface},shortcut:{flex:1,minHeight:86,paddingHorizontal:3,paddingVertical:13,gap:10,alignItems:'center',justifyContent:'center',borderWidth:1,borderColor:c.line,borderRadius:13,backgroundColor:c.surface},shortcutLabel:{fontFamily:font.medium,fontSize:10,color:c.text}});
