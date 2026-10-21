import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, AppState, BackHandler, Linking, Platform, Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaProvider, SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { useFonts } from 'expo-font';
import { PlusJakartaSans_400Regular } from '@expo-google-fonts/plus-jakarta-sans/400Regular';
import { PlusJakartaSans_600SemiBold } from '@expo-google-fonts/plus-jakarta-sans/600SemiBold';
import { PlusJakartaSans_700Bold } from '@expo-google-fonts/plus-jakarta-sans/700Bold';
import { PlusJakartaSans_800ExtraBold } from '@expo-google-fonts/plus-jakarta-sans/800ExtraBold';
import { Bell, BookOpen, Home, ShieldCheck, UserRound, Users } from './icons';
import { AurenApi, type Activity, type Course, type GamiAchievement, type Identity, type Lesson, type Session } from '@auren/api-client';
import { apiBase, bootstrap, clearSession, restoreSession, signIn } from './auth';
import { Avatar, Button, colors as c, font, u } from './ui';
import { WebInstallHelp } from './WebInstall';
import { AppUpdatesProvider, ReleaseBanner } from './AppUpdates';
import SocialScreen from './SocialScreen';
import NotificationsScreen from './NotificationsScreen';
import ProfileEditor from './ProfileEditor';
import BlockedMembers from './BlockedMembers';
import { Brand, CreditBadge, GamificationSheet } from './Gamification';
import HomeScreen from './HomeScreen';
import ProfileScreen from './ProfileScreen';
import { CatalogScreen, CourseScreen, LessonScreen } from './AcademyScreens';

type Tab = 'Inicio'|'Cursos'|'Comunidad'|'Avisos'|'Perfil';
const tabs=[{name:'Inicio',icon:Home},{name:'Cursos',icon:BookOpen},{name:'Comunidad',icon:Users},{name:'Avisos',icon:Bell},{name:'Perfil',icon:UserRound}] as const;

export default function App() {
 useFonts({PlusJakartaSans_400Regular,PlusJakartaSans_600SemiBold,PlusJakartaSans_700Bold,PlusJakartaSans_800ExtraBold});
 const [user,setUser]=useState<Identity|null>(null);
 const [courses,setCourses]=useState<Course[]>([]),[coursesPage,setCoursesPage]=useState<number|null>(null);
 const [posts,setPosts]=useState<Activity[]>([]);
 const [communityError,setCommunityError]=useState('');
 const [tab,setTab]=useState<Tab>('Inicio'),[course,setCourse]=useState<Course|null>(null),[lesson,setLesson]=useState<Lesson|null>(null),[post,setPost]=useState<Activity|null>(null);
 const [blockedOpen,setBlockedOpen]=useState(false);
 const [gamificationOpen,setGamificationOpen]=useState(false),[achievement,setAchievement]=useState<GamiAchievement|null>(null);
 const [editProfile,setEditProfile]=useState(false),[courseSearch,setCourseSearch]=useState('');
 const [filter,setFilter]=useState<'all'|'started'|'done'>('all');
 const [busy,setBusy]=useState(true),[error,setError]=useState(''),[notice,setNotice]=useState('');
 const apiRef=useRef<AurenApi|null>(null),generation=useRef(0),requestId=useRef(0),identityId=useRef(0),mounted=useRef(true),leaving=useRef(false);
 const scroll=useRef<React.ComponentRef<typeof ScrollView>>(null),b=bootstrap();
 const top=()=>scroll.current?.scrollTo({y:0,animated:false});
 const clear=useCallback(()=>{
  generation.current++;requestId.current++;apiRef.current?.dispose();apiRef.current=null;
  setUser(null);setCourses([]);setPosts([]);setCourse(null);setLesson(null);setPost(null);setCoursesPage(null);setCommunityError('');setTab('Inicio');setFilter('all');setEditProfile(false);setBlockedOpen(false);setGamificationOpen(false);setAchievement(null);setCourseSearch('');setBusy(false);
  void clearSession().catch(()=>setError('No pudimos borrar la sesión del dispositivo. Intenta cerrar sesión de nuevo.'));
 },[]);
 const expire=useCallback(()=>{clear();setNotice('');setError('Tu sesión terminó. Vuelve a entrar para continuar.');},[clear]);
 const connect=useCallback(async(session:Session|null)=>{
  const base=apiBase();if(!base){setBusy(false);return;}
  apiRef.current?.dispose();const epoch=++generation.current;
  const api=new AurenApi({baseUrl:base,nonce:bootstrap()?.nonce,token:session?.access_token,allowLocal:bootstrap()?.environment==='local',onExpired:expire});
  apiRef.current=api;setBusy(true);setError('');
  try {
   const identity=await api.me();if(epoch!==generation.current||!mounted.current)return;setUser(identity);
   const [catalog,feed]=await Promise.allSettled([api.courses(),identity.capabilities.community?api.community():Promise.resolve(null)]);
   if(epoch!==generation.current||!mounted.current)return;
   if(catalog.status==='fulfilled'){setCourses(catalog.value.items);setCoursesPage(catalog.value.next_page);}else setError(catalog.reason instanceof Error?catalog.reason.message:'No pudimos consultar tus cursos.');
   if(feed.status==='fulfilled'&&feed.value){setPosts(feed.value.items);setCommunityError('');}
   else if(feed.status==='rejected')setCommunityError('No pudimos consultar la comunidad. Actualiza para intentar de nuevo.');
  }catch(e){if(epoch===generation.current&&mounted.current)setError(e instanceof Error?e.message:'No pudimos conectar con la academia.');}
  finally{if(epoch===generation.current&&mounted.current)setBusy(false);}
 },[expire]);
 useEffect(()=>{
  mounted.current=true;
  void restoreSession().then(session=>{
   if(!mounted.current)return;
   if((Platform.OS!=='web'&&!session)||(Platform.OS==='web'&&!bootstrap()?.nonce)){setBusy(false);return;}
   void connect(session);
  }).catch(()=>{setBusy(false);setError('No pudimos recuperar tu sesión. Vuelve a entrar.');});
  return()=>{mounted.current=false;apiRef.current?.dispose();};
 },[connect]);
 const refreshIdentity=useCallback(()=>{const api=apiRef.current,id=++identityId.current;if(api&&!leaving.current)void api.me().then(identity=>{if(id===identityId.current&&api===apiRef.current&&mounted.current)setUser(current=>current?.id===identity.id?identity:current);}).catch(()=>{});},[]);
 useEffect(()=>{
  const check=()=>{if(Platform.OS!=='web'||document.visibilityState!=='hidden')refreshIdentity();};
  const subscription=AppState.addEventListener('change',state=>{if(state==='active')check();});
  const interval=setInterval(check,60000);
  if(Platform.OS==='web'){globalThis.addEventListener('pageshow',check);globalThis.addEventListener('focus',check);document.addEventListener('visibilitychange',check);}
  return()=>{clearInterval(interval);subscription.remove();if(Platform.OS==='web'){globalThis.removeEventListener('pageshow',check);globalThis.removeEventListener('focus',check);document.removeEventListener('visibilitychange',check);}};
 },[refreshIdentity]);
 function back(){requestId.current++;setBusy(false);setError('');setNotice('');if(lesson)setLesson(null);else if(post)setPost(null);else setCourse(null);top();}
 useEffect(()=>{const subscription=BackHandler.addEventListener('hardwareBackPress',()=>{if(lesson||course){back();return true;}return false;});return()=>subscription.remove();},[lesson,course]);
 async function action(run:(api:AurenApi,current:()=>boolean)=>Promise<void>){
  const api=apiRef.current;if(!api)return;const epoch=generation.current,id=++requestId.current;setBusy(true);setError('');setNotice('');
  try{await run(api,()=>mounted.current&&epoch===generation.current&&id===requestId.current&&api===apiRef.current&&!leaving.current);}
  catch(e){if(epoch===generation.current&&id===requestId.current)setError(e instanceof Error?e.message:'No pudimos completar la solicitud.');}
  finally{if(epoch===generation.current&&id===requestId.current)setBusy(false);}
 }
 function openCourse(id:number){void action(async(api,current)=>{const data=await api.course(id);if(current()){setCourse(data);setTab('Cursos');setLesson(null);setPost(null);top();}});}
 function openLesson(id:number){if(course)void action(async(api,current)=>{const data=await api.lesson(course.id,id);if(current()){setLesson(data);top();}});}
 function openPost(id:number){void action(async(api,current)=>{const data=await api.activity(id);if(current()){setPost(data);setTab('Comunidad');setCourse(null);setLesson(null);top();}});}
 function changeTab(name:Tab){requestId.current++;setBusy(false);setTab(name);setCourse(null);setLesson(null);setPost(null);setError('');setNotice('');top();}
 function refresh(){void action(async(api,current)=>{
  const identityRequest=++identityId.current,identity=await api.me();if(!current())return;if(identityRequest===identityId.current)setUser(identity);
  if(course){const data=await api.course(course.id),step=lesson?await api.lesson(course.id,lesson.id):null;if(current()){setCourse(data);setLesson(step);setCourses(old=>old.map(x=>x.id===data.id?data:x));}}
  else if(post){const data=await api.activity(post.id);if(current())setPost(data);}
  else if(tab==='Comunidad'){const data=await api.community();if(current()){setPosts(data.items);setCommunityError('');}}

  else {const data=await api.courses();if(current()){setCourses(data.items);setCoursesPage(data.next_page);}if(tab==='Inicio'&&identity.capabilities.community){const feed=await api.community();if(current()){setPosts(feed.items);setCommunityError('');}}}
 });}
 async function login(){setBusy(true);setError('');setNotice('');try{const session=await signIn();if(session)await connect(session);}catch(e){setError(e instanceof Error?e.message:'No pudimos iniciar sesión.');}finally{setBusy(false);}}
 async function leave(){const api=apiRef.current;leaving.current=true;setBusy(true);setError('');try{await api?.logout();setNotice('Sesión cerrada. Nos vemos pronto.');}catch{setNotice('Se borró la sesión de este dispositivo. No pudimos confirmar el cierre en la academia.');}finally{clear();leaving.current=false;top();}}
 function complete(){if(course&&lesson)void action(async(api,current)=>{const data=await api.complete(course.id,lesson.id);if(current()){setLesson(data.lesson);setCourse({...course,progress:data.progress,lessons:course.lessons?.map(l=>l.id===lesson.id?{...l,completed:true}:l)});setCourses(old=>old.map(x=>x.id===course.id?{...x,progress:data.progress}:x));setNotice('Tu avance se guardó en la academia.');refreshIdentity();}});}
 function visit(url:string|null|undefined){if(!url)return;try{const target=new URL(url),origin=new URL(b?.site_url??process.env.EXPO_PUBLIC_WORDPRESS_URL??'https://www.aurenfx.com').origin;if(target.origin!==origin)throw new Error();if(Platform.OS==='web')globalThis.location.assign(url);else void Linking.openURL(url).catch(()=>setError('No pudimos abrir la academia.'));}catch{setError('No pudimos abrir este enlace.');}}
 function updateActivity(item:Activity){refreshIdentity();setPosts(old=>old.some(x=>x.id===item.id)?old.map(x=>x.id===item.id?item:x):[item,...old]);setPost(old=>old?.id===item.id?item:old);}
 function deleteActivity(id:number){refreshIdentity();setPosts(old=>old.filter(x=>x.id!==id));setPost(old=>old?.id===id?null:old);}
 function resumeCourse(id:number){void action(async(api,current)=>{const full=await api.course(id);if(!current())return;const next=full.lessons?.find(x=>x.available&&!x.completed)??full.lessons?.find(x=>x.available);const detail=next?await api.lesson(id,next.id):null;if(current()){setTab('Cursos');setCourse(full);setLesson(detail);setPost(null);top();}});}
 const social=apiRef.current&&user?<SocialScreen key={user.id} api={apiRef.current} user={user} initialPost={post} onChanged={updateActivity} onDeleted={deleteActivity} onNavigate={top} onBlocked={id=>{setPosts(old=>old.filter(x=>x.author.id!==id));setPost(old=>old?.author.id===id?null:old);}} onBack={()=>{setPost(null);top();}}/>:null;
 const inProgress=courses.find(x=>x.progress.completed>0&&x.progress.percent<100)??courses.find(x=>x.progress.percent<100)??courses[0];
 const visibleCourses=courses.filter(x=>(x.title+' '+x.summary).toLocaleLowerCase('es').includes(courseSearch.toLocaleLowerCase('es'))).filter(x=>filter==='all'||(filter==='done'?x.progress.percent===100:x.progress.completed>0&&x.progress.percent<100));
 const unread=user?.unread_notifications??0;
 function showLevel(){setAchievement(null);setGamificationOpen(true);refreshIdentity();}
 function showAchievement(item:GamiAchievement){setAchievement(item);setGamificationOpen(true);}
 function moreCourses(){if(coursesPage)void action(async(api,current)=>{const data=await api.courses(coursesPage);if(current()){setCourses(old=>[...old,...data.items.filter(item=>!old.some(x=>x.id===item.id))]);setCoursesPage(data.next_page);}});}
 return <AppUpdatesProvider><SafeAreaProvider><SafeAreaView style={s.root}><StatusBar style="light"/><View style={s.shell}>
  <View style={s.header}><Pressable accessibilityRole="button" accessibilityLabel="Ir al inicio" onPress={()=>user&&changeTab('Inicio')} style={{minHeight:44,justifyContent:'center'}}><Brand url={user?.branding?.logo_url}/></Pressable>{user?<View style={[u.row,{gap:9}]}><CreditBadge data={user.gamification} onPress={showLevel}/><Pressable accessibilityRole="button" accessibilityLabel="Ver mi perfil" onPress={()=>changeTab('Perfil')} style={{padding:3}}><Avatar url={user.avatar_url} name={user.name} size={35}/></Pressable></View>:<Text style={s.headerLabel}>TU ACADEMIA</Text>}</View>
  <ReleaseBanner busy={busy}/>
  {(b?.environment==='local'||user?.environment==='local')&&<Text style={s.testBanner}>PRUEBAS LOCALES · CONTENIDO DE INTEGRACIÓN</Text>}
  <ScrollView ref={scroll} contentContainerStyle={s.content} keyboardShouldPersistTaps="handled" refreshControl={user&&tab!=='Comunidad'&&tab!=='Avisos'?<RefreshControl refreshing={false} onRefresh={refresh} tintColor={c.mint} enabled={!busy}/>:undefined}>
   {!!error&&<View accessibilityRole="alert" style={s.error}><Text style={s.errorText}>{error}</Text>{user&&<Pressable accessibilityRole="button" onPress={refresh}><Text style={u.link}>Volver a intentar</Text></Pressable>}</View>}
   {!!notice&&<Text accessibilityLiveRegion="polite" style={s.notice}>{notice}</Text>}
   {busy&&<View style={[u.row,{gap:10,paddingBottom:18}]}><ActivityIndicator size="small" color={c.mint}/><Text style={u.caption}>Conectando con tu academia…</Text></View>}
   {!user?<View style={s.welcome}><View style={s.welcomeArt}><View style={s.welcomeAccent}/><Brand large/><Text style={[u.eyebrow,{letterSpacing:3}]}>ACADEMIA & COMUNIDAD</Text></View><Text style={u.eyebrow}>APRENDE. CONECTA. AVANZA.</Text><Text accessibilityRole="header" style={s.hero}>Tu camino{`\n`}<Text style={{color:c.mint}}>empieza aquí.</Text></Text><Text style={s.lead}>Tus cursos, tu comunidad y tu progreso. Sigue aprendiendo con Auren, estés donde estés.</Text><Button disabled={busy} onPress={()=>void login()}>Entrar a mi academia →</Button>{b&&<View style={[u.between,{flexWrap:'wrap'}]}><Pressable accessibilityRole="link" onPress={()=>visit(b.register_url)}><Text style={u.link}>Crear una cuenta</Text></Pressable><Pressable accessibilityRole="link" onPress={()=>visit(b.lost_password_url)}><Text style={u.caption}>Recuperar acceso</Text></Pressable></View>}{b?.nonce&&!!apiRef.current&&<Pressable accessibilityRole="button" onPress={()=>void leave()}><Text style={[u.caption,{textAlign:'center'}]}>Cambiar de cuenta</Text></Pressable>}<View style={[u.row,{gap:8,justifyContent:'center'}]}><ShieldCheck size={15} color={c.muted}/><Text style={u.caption}>Tu misma cuenta de Auren. Tu aprendizaje conectado.</Text></View><Text style={[u.caption,{textAlign:'center'}]}>El trading implica riesgo de pérdida. Aprender también es aprender a gestionar el riesgo.</Text><WebInstallHelp/></View>
   :lesson&&course?<LessonScreen course={course} lesson={lesson} busy={busy} onBack={back} onLesson={openLesson} onComplete={complete} onRefresh={refresh} onVisit={()=>visit(lesson.url)}/>
   :course?<CourseScreen course={course} busy={busy} onBack={back} onLesson={openLesson} onResume={()=>resumeCourse(course.id)} onRefresh={refresh}/>
   :post?social
   :tab==='Comunidad'?social
   :tab==='Avisos'?apiRef.current&&<NotificationsScreen key={user.id} api={apiRef.current} onCount={n=>setUser(old=>old?{...old,unread_notifications:n}:old)} onActivity={item=>{setPost(item);setTab('Comunidad');top();}}/>
   :tab==='Perfil'?<ProfileScreen user={user} courses={courses} more={!!coursesPage} busy={busy} onEdit={()=>setEditProfile(true)} onBlocked={()=>setBlockedOpen(true)} onLeave={()=>void leave()} onLevel={showLevel} onAchievement={showAchievement} onError={setError} onCourses={()=>changeTab('Cursos')}/>
   :tab==='Cursos'?<CatalogScreen courses={visibleCourses} search={courseSearch} filter={filter} busy={busy} error={error} more={!!coursesPage} onSearch={setCourseSearch} onFilter={setFilter} onCourse={openCourse} onResume={resumeCourse} onRefresh={refresh} onMore={moreCourses}/>
   :<HomeScreen user={user} course={inProgress} posts={posts} communityError={communityError} busy={busy} onTab={changeTab} onCourse={openCourse} onResume={resumeCourse} onPost={openPost} onLevel={showLevel} onAchievement={showAchievement}/>}
  </ScrollView>
  {blockedOpen&&apiRef.current&&user&&<BlockedMembers key={user.id} api={apiRef.current} onClose={()=>setBlockedOpen(false)}/>}
  {editProfile&&apiRef.current&&user&&<ProfileEditor key={user.id} api={apiRef.current} onClose={()=>setEditProfile(false)} onSaved={identity=>{setUser(identity);setEditProfile(false);setNotice('Tu perfil se guardó en Auren.');}}/>}
  {gamificationOpen&&user&&<GamificationSheet user={user} achievement={achievement?user.gamification?.achievements.find(a=>a.id===achievement.id)??achievement:null} onClose={()=>{setGamificationOpen(false);setAchievement(null);}}/>}
  {user&&<View style={s.tabs}>{tabs.map(({name,icon:Icon})=><Pressable key={name} accessibilityRole="tab" accessibilityLabel={name} accessibilityState={{selected:tab===name}} aria-selected={tab===name} onPress={()=>changeTab(name)} style={[s.tab,tab===name&&s.tabActive]}><View style={s.tabIcon}><Icon size={23} strokeWidth={tab===name?2.2:1.6} color={tab===name?c.mint:c.muted} accessible={false}/>{name==='Avisos'&&unread>0&&<View style={s.tabDot}/>}</View><Text style={[s.tabLabel,tab===name&&{color:c.mint}]}>{name}</Text></Pressable>)}</View>}
 </View></SafeAreaView></SafeAreaProvider></AppUpdatesProvider>;
}
const s=StyleSheet.create({
 root:{flex:1,backgroundColor:c.bg},shell:{flex:1,width:'100%',maxWidth:620,alignSelf:'center'},header:{paddingHorizontal:16,paddingVertical:8,minHeight:65,borderBottomColor:'#052e49',borderBottomWidth:1,flexDirection:'row',alignItems:'center',justifyContent:'space-between',gap:8,zIndex:10},headerLabel:{fontFamily:font.medium,fontSize:9,letterSpacing:1.4,color:c.muted},content:{padding:16,paddingTop:18,paddingBottom:28,flexGrow:1},
 welcome:{gap:23,maxWidth:470,width:'100%',alignSelf:'center'},hero:{fontFamily:font.heavy,fontSize:42,lineHeight:51,color:c.text,letterSpacing:-1.6},lead:{fontFamily:font.regular,fontSize:16,lineHeight:27,color:c.muted},welcomeArt:{height:156,borderRadius:20,borderWidth:1,borderColor:c.line,backgroundColor:'#00233c',alignItems:'center',justifyContent:'center',gap:19,overflow:'hidden',marginBottom:2},welcomeAccent:{position:'absolute',top:0,height:3,left:44,right:44,backgroundColor:c.mint,borderBottomLeftRadius:3,borderBottomRightRadius:3},
 error:{padding:17,gap:12,backgroundColor:'#30202c',borderWidth:1,borderColor:'#62404d',borderRadius:13,marginBottom:20},errorText:{fontFamily:font.regular,color:'#f9c9d6',fontSize:14,lineHeight:23},notice:{fontFamily:font.medium,color:c.mint,fontSize:13,lineHeight:22,marginBottom:18},testBanner:{fontFamily:font.medium,fontSize:9,textAlign:'center',padding:8,color:'#ffdfa9',backgroundColor:'#32291d'},
 tabs:{flexDirection:'row',padding:6,paddingBottom:4,borderTopWidth:1,borderTopColor:c.line,backgroundColor:'#001526',borderTopLeftRadius:18,borderTopRightRadius:18},tab:{flex:1,minHeight:58,alignItems:'center',justifyContent:'center',gap:3,borderRadius:12},tabActive:{backgroundColor:'#04315b'},tabIcon:{width:38,height:28,alignItems:'center',justifyContent:'center'},tabLabel:{fontFamily:font.medium,fontSize:10,color:c.muted},tabDot:{position:'absolute',right:3,top:1,width:7,height:7,borderRadius:4,backgroundColor:c.mint},
});
