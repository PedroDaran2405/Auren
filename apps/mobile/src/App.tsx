import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, AppState, BackHandler, Image, Linking, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaProvider, SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { useFonts } from 'expo-font';
import { PlusJakartaSans_400Regular } from '@expo-google-fonts/plus-jakarta-sans/400Regular';
import { PlusJakartaSans_600SemiBold } from '@expo-google-fonts/plus-jakarta-sans/600SemiBold';
import { PlusJakartaSans_700Bold } from '@expo-google-fonts/plus-jakarta-sans/700Bold';
import { PlusJakartaSans_800ExtraBold } from '@expo-google-fonts/plus-jakarta-sans/800ExtraBold';
import { ApiError, AurenApi, type Course, type Identity, type Lesson, type Session } from '@auren/api-client';
import { apiBase, bootstrap, clearSession, restoreSession, signIn } from './auth';
import Video from './Video';
import YouTubePlayer from './YouTubePlayer';

type Tab = 'Inicio' | 'Academia' | 'Perfil';
const palette = { bg:'#000918',surface:'#0b172a',line:'#21304a',text:'#f3f6ff',muted:'#a5b4ce',blue:'#3d5cff',cyan:'#80e6ff' };
function Button({children,onPress,secondary=false,disabled=false}:{children:React.ReactNode;onPress:()=>void;secondary?:boolean;disabled?:boolean}) {
  return <Pressable accessibilityRole="button" disabled={disabled} onPress={onPress} style={({pressed})=>[s.button,secondary&&s.buttonSecondary,disabled&&{opacity:.5},pressed&&{opacity:.8}]}><Text style={s.buttonText}>{children}</Text></Pressable>;
}
function ProgressBar({course}:{course:Course}) {
  return <View style={{gap:9}}><View style={s.progressLine}><Text style={s.small}>{course.progress.total ? `${course.progress.completed} de ${course.progress.total} actividades` : 'Contenido por confirmar'}</Text><Text style={s.percent}>{course.progress.percent}%</Text></View><View accessibilityRole="progressbar" accessibilityValue={{min:0,max:100,now:course.progress.percent}} accessibilityLabel={`Avance de ${course.title}`} style={s.track}><View style={[s.fill,{width:`${course.progress.percent}%`}]} /></View></View>;
}
function CourseCard({course,onPress}:{course:Course;onPress:()=>void}) {
  return <Pressable accessibilityRole="button" accessibilityLabel={`Abrir ${course.title}`} onPress={onPress} style={({pressed})=>[s.courseCard,pressed&&{opacity:.85}]}>
    {course.image_url ? <Image source={{uri:course.image_url}} style={s.courseImage} accessibilityLabel=""/> : <View style={s.courseGraphic}><View style={s.orbit}/><View style={s.orbitInner}/><Text style={s.graphicText}>A</Text></View>}
    <View style={s.courseBody}><Text style={s.eyebrow}>{course.access.label.toUpperCase()}</Text><Text style={s.cardTitle}>{course.title}</Text>{!!course.summary&&<Text numberOfLines={2} style={s.body}>{course.summary}</Text>}<ProgressBar course={course}/><Text style={s.link}>Ver curso  ↗</Text></View>
  </Pressable>;
}

export default function App() {
  useFonts({PlusJakartaSans_400Regular,PlusJakartaSans_600SemiBold,PlusJakartaSans_700Bold,PlusJakartaSans_800ExtraBold});
  const [user,setUser]=useState<Identity|null>(null);
  const [courses,setCourses]=useState<Course[]>([]);
  const [nextPage,setNextPage]=useState<number|null>(null);
  const [tab,setTab]=useState<Tab>('Inicio');
  const [course,setCourse]=useState<Course|null>(null);
  const [lesson,setLesson]=useState<Lesson|null>(null);
  const [busy,setBusy]=useState(true);
  const [error,setError]=useState('');
  const [notice,setNotice]=useState('');
  const apiRef=useRef<AurenApi|null>(null);
  const generation=useRef(0);
  const requestId=useRef(0);
  const mounted=useRef(true);
  const logout=useRef(false);
  const b=bootstrap();

  const clear=useCallback(()=>{
    generation.current++; requestId.current++; apiRef.current?.dispose();apiRef.current=null;
    setUser(null);setCourses([]);setCourse(null);setLesson(null);setNextPage(null);setTab('Inicio');setBusy(false);
    void clearSession().catch(()=>setError('No pudimos borrar la sesión del dispositivo. Intenta cerrar sesión de nuevo.'));
  },[]);
  const expire=useCallback(()=>{clear();setError('Tu sesión terminó. Vuelve a entrar para continuar.');},[clear]);
  const connect=useCallback(async(session:Session|null)=>{
    const base=apiBase();
    if (!base) {setBusy(false);return;}
    apiRef.current?.dispose();
    const epoch=++generation.current;
    const api=new AurenApi({baseUrl:base,nonce:bootstrap()?.nonce,token:session?.access_token,allowLocal:bootstrap()?.environment==='local',onExpired:expire});
    apiRef.current=api;setBusy(true);setError('');
    try {
      const identity=await api.me();
      if(epoch!==generation.current||!mounted.current)return;
      setUser(identity);
      const data=await api.courses();
      if(epoch!==generation.current||!mounted.current)return;
      setCourses(data.items);setNextPage(data.next_page);
    }catch(e){if(epoch===generation.current&&mounted.current)setError(e instanceof Error?e.message:'No pudimos conectar con la academia.');}
    finally{if(epoch===generation.current&&mounted.current)setBusy(false);}
  },[expire]);
  useEffect(()=>{
    mounted.current=true;
    void restoreSession().then(session=>{
      if(!mounted.current)return;
      if(Platform.OS!=='web'&&!session){setBusy(false);return;}
      if(Platform.OS==='web'&&!bootstrap()?.nonce){setBusy(false);return;}
      void connect(session);
    }).catch(()=>{setBusy(false);setError('No pudimos recuperar tu sesión. Vuelve a entrar.');});
    return()=>{mounted.current=false;apiRef.current?.dispose();};
  },[connect]);
  useEffect(()=>{const sub=BackHandler.addEventListener('hardwareBackPress',()=>{if(lesson){requestId.current++;setBusy(false);setLesson(null);return true;}if(course){requestId.current++;setBusy(false);setCourse(null);return true;}return false;});return()=>sub.remove();},[course,lesson]);
  useEffect(()=>{const sub=AppState.addEventListener('change',state=>{if(state==='active'&&apiRef.current){void apiRef.current.me().catch(()=>{});}});return()=>sub.remove();},[]);

  async function action(run:(api:AurenApi,current:()=>boolean)=>Promise<void>) {
    const api=apiRef.current;if(!api)return;
    const epoch=generation.current;const id=++requestId.current;setBusy(true);setError('');setNotice('');
    try{await run(api,()=>mounted.current&&epoch===generation.current&&id===requestId.current&&api===apiRef.current&&!logout.current);}catch(e){if(epoch===generation.current&&id===requestId.current)setError(e instanceof Error?e.message:'No pudimos completar la solicitud.');}
    finally{if(epoch===generation.current&&id===requestId.current)setBusy(false);}
  }
  function openCourse(id:number){void action(async (api,current)=>{const data=await api.course(id);if(current()){setCourse(data);setLesson(null);}});}
  function openLesson(id:number){if(course)void action(async (api,current)=>{const data=await api.lesson(course.id,id);if(current())setLesson(data);});}
  function refresh(){void action(async (api,current)=>{
    if(course){const data=await api.course(course.id);const updatedLesson=lesson?await api.lesson(course.id,lesson.id):null;if(current()){setCourse(data);setLesson(updatedLesson);setCourses(old=>old.map(x=>x.id===data.id?data:x));}}
    else{const data=await api.courses();if(current()){setCourses(data.items);setNextPage(data.next_page);}}
  });}
  async function login(){setBusy(true);setError('');setNotice('');try{const session=await signIn();if(session)await connect(session);}catch(e){setError(e instanceof Error?e.message:'No pudimos iniciar sesión.');}finally{setBusy(false);}}
  async function leave(){
    const api=apiRef.current;logout.current=true;setBusy(true);
    try{await api?.logout();setNotice('Sesión cerrada.');}catch{setNotice('Se borró la sesión de este dispositivo. No pudimos confirmar el cierre en la academia; vuelve a entrar cuando tengas conexión.');}
    finally{clear();logout.current=false;}
  }
  function complete(){if(course&&lesson)void action(async (api,current)=>{const result=await api.complete(course.id,lesson.id);if(current()){setLesson(result.lesson);setCourse({...course,progress:result.progress,lessons:course.lessons?.map(l=>l.id===lesson.id?{...l,completed:true}:l)});setCourses(old=>old.map(c=>c.id===course.id?{...c,progress:result.progress}:c));setNotice('Tu avance se guardó en la academia.');}});}
  function visit(url:string){
    const origin=new URL(b?.site_url??process.env.EXPO_PUBLIC_WORDPRESS_URL??'https://www.aurenfx.com').origin;
    try{const u=new URL(url);if(u.origin!==origin)throw new Error();void Linking.openURL(url).catch(()=>setError('No pudimos abrir la academia.'));}catch{setError('No pudimos abrir este enlace.');}
  }
  const inProgress=courses.find(c=>c.progress.completed>0&&c.progress.percent<100)??courses.find(c=>c.progress.percent<100);
  return <SafeAreaProvider><SafeAreaView style={s.root}><StatusBar style="light"/>
    <View style={s.shell}>
      <View style={s.header}><Text style={s.brand}>auren<Text style={{color:palette.cyan}}>.</Text></Text><View style={s.badge}><View style={s.dot}/><Text style={s.small}>TU ACADEMIA</Text></View></View>
      {(b?.environment==='local'||user?.environment==='local')&&<Text style={s.testBanner}>PRUEBAS LOCALES · CONTENIDO DE INTEGRACIÓN</Text>}
      <ScrollView contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">
        {!!error&&<View accessibilityRole="alert" style={s.errorBox}><Text style={s.errorText}>{error}</Text>{user&&<Pressable accessibilityRole="button" onPress={refresh}><Text style={s.link}>Volver a intentar</Text></Pressable>}</View>}
        {!!notice&&<Text accessibilityLiveRegion="polite" style={s.notice}>{notice}</Text>}
        {busy&&<View style={s.loading}><ActivityIndicator color={palette.cyan}/><Text style={s.small}>Conectando con tu academia…</Text></View>}
        {!user?<View style={s.welcome}>
          <Text style={s.eyebrow}>APRENDE. PRACTICA. AVANZA.</Text>
          <Text accessibilityRole="header" style={s.hero}>Tu siguiente{`\n`}nivel empieza{`\n`}<Text style={{color:palette.cyan}}>aquí.</Text></Text>
          <Text style={s.lead}>Tu academia, tu comunidad y una ruta para entender el trading. A tu ritmo.</Text>
          <View style={s.welcomeArt}><View style={s.artGrid}/><View style={[s.artBar,{height:45,left:'14%'}]}/><View style={[s.artBar,{height:90,left:'34%'}]}/><View style={[s.artBar,{height:75,left:'54%'}]}/><View style={[s.artBar,{height:125,left:'74%',backgroundColor:palette.cyan}]}/><Text style={s.artCaption}>El conocimiento es tu punto de partida.</Text></View>
          <Button disabled={busy} onPress={()=>void login()}>Entrar a mi academia  →</Button>
          {b&&<View style={s.loginLinks}><Pressable accessibilityRole="link" onPress={()=>visit(b.register_url)}><Text style={s.link}>Crear una cuenta</Text></Pressable><Pressable accessibilityRole="link" onPress={()=>visit(b.lost_password_url)}><Text style={s.small}>Recuperar acceso</Text></Pressable></View>}
          <Text style={s.footnote}>Aprender a operar también es aprender a gestionar el riesgo. El trading implica riesgo de pérdida.</Text>
        </View>:lesson&&course?<View style={s.stack}>
          <Pressable accessibilityRole="button" onPress={()=>{requestId.current++;setBusy(false);setLesson(null);setError('');}}><Text style={s.link}>←  {course.title}</Text></Pressable>
          <Text style={s.eyebrow}>TU LECCIÓN</Text><Text accessibilityRole="header" style={s.title}>{lesson.title}</Text>
          {lesson.video?.kind==='youtube'&&lesson.video.video_id?<YouTubePlayer key={lesson.id} id={lesson.video.video_id}/>:lesson.video?.kind==='direct'&&lesson.video.url?<Video key={lesson.id} url={lesson.video.url}/>:<View style={s.infoPanel}><Text style={s.body}>{lesson.video?'El video de esta lección se abre en la academia.':'Esta lección no tiene un video disponible en la app.'}</Text></View>}
          {!!lesson.text&&<Text selectable style={s.lessonText}>{lesson.text}</Text>}
          {lesson.completed?<View style={s.successPanel}><Text style={s.success}>✓  Lección completada</Text><Text style={s.body}>Tu avance está guardado en la academia.</Text></View>:lesson.completion_mode==='app'?<Button disabled={busy} onPress={complete}>Marcar como completada</Button>:<View style={s.stack}><Text style={s.body}>Continúa esta actividad en la academia. Al volver, actualiza tu avance.</Text><Button onPress={()=>visit(lesson.url)}>Continuar en la academia  ↗</Button></View>}
          <Button secondary disabled={busy} onPress={refresh}>Actualizar mi avance</Button>
          {lesson.completion_mode==='app'&&<Pressable accessibilityRole="link" onPress={()=>visit(lesson.url)}><Text style={s.link}>Abrir lección en la academia ↗</Text></Pressable>}
        </View>:course?<View style={s.stack}>
          <Pressable accessibilityRole="button" onPress={()=>{requestId.current++;setBusy(false);setCourse(null);setError('');}}><Text style={s.link}>←  Mis cursos</Text></Pressable>
          <Text style={s.eyebrow}>{course.access.label.toUpperCase()}</Text><Text accessibilityRole="header" style={s.title}>{course.title}</Text>
          {!!course.summary&&<Text style={s.body}>{course.summary}</Text>}<ProgressBar course={course}/>
          <View style={s.sectionHeading}><Text style={s.sectionTitle}>Tu ruta de aprendizaje</Text><Text style={s.small}>{course.lessons?.length??0} lecciones</Text></View>
          {course.lessons?.length?course.lessons.map((l,i)=><Pressable key={l.id} accessibilityRole="button" accessibilityLabel={`Lección ${i+1}: ${l.title}${l.completed?', completada':''}`} disabled={!l.available||busy} onPress={()=>openLesson(l.id)} style={s.lessonRow}><View style={[s.lessonNumber,l.completed&&s.doneNumber]}><Text style={l.completed?s.doneText:s.numberText}>{l.completed?'✓':String(i+1).padStart(2,'0')}</Text></View><View style={{flex:1,gap:5}}><Text style={s.rowTitle}>{l.title}</Text><Text style={s.small}>{l.completed?'Completada':l.available?'Lista para continuar':'Completa los requisitos anteriores'}</Text></View><Text style={s.arrow}>{l.available?'›':'—'}</Text></Pressable>):<View style={s.infoPanel}><Text style={s.body}>Aún no hay lecciones disponibles en este curso.</Text></View>}
          <Button secondary disabled={busy} onPress={refresh}>Actualizar curso</Button>
        </View>:tab==='Perfil'?<View style={s.stack}>
          <Text style={s.eyebrow}>MI CUENTA</Text><Text accessibilityRole="header" style={s.title}>{user.name}</Text><Text style={s.body}>Tu cuenta de Auren conecta tu aprendizaje aquí y en la academia.</Text>
          <View style={s.infoPanel}><Text style={s.sectionTitle}>Tu acceso</Text><Text style={s.body}>Los cursos disponibles se consultan con los permisos de tu cuenta en la academia.</Text></View>
          <Button secondary onPress={refresh} disabled={busy}>Actualizar mis cursos</Button><Button onPress={()=>void leave()} disabled={busy}>Cerrar sesión</Button>
        </View>:<View style={s.stack}>
          <Text style={s.eyebrow}>{tab==='Inicio'?'TU ESPACIO PARA AVANZAR':'APRENDE A TU RITMO'}</Text>
          <Text accessibilityRole="header" style={s.title}>{tab==='Inicio'?`Hola, ${user.name.split(' ')[0]}.`:'Tu academia'}</Text>
          <Text style={s.lead}>{tab==='Inicio'?'Un paso más en tu aprendizaje. Retoma donde te quedaste.':'Tus cursos y lecciones, conectados con tu cuenta de Auren.'}</Text>
          {tab==='Inicio'&&inProgress?<><View style={s.sectionHeading}><Text style={s.sectionTitle}>Continúa aprendiendo</Text><Text style={s.small}>TU RUTA</Text></View><CourseCard course={inProgress} onPress={()=>openCourse(inProgress.id)}/><Button secondary onPress={()=>setTab('Academia')}>Ver todos mis cursos</Button></>:courses.length?<View style={s.courseList}>{courses.map(c=><CourseCard key={c.id} course={c} onPress={()=>openCourse(c.id)}/>)}</View>:!busy&&!error?<View style={s.infoPanel}><Text style={s.sectionTitle}>Tu aprendizaje está por comenzar</Text><Text style={s.body}>No hay cursos disponibles en esta página. Actualiza tu acceso o consulta la academia.</Text><Button secondary onPress={refresh}>Actualizar acceso</Button></View>:null}
          {nextPage&&<Button secondary disabled={busy} onPress={()=>void action(async (api,current)=>{const data=await api.courses(nextPage);if(current()){setCourses(old=>[...old,...data.items.filter(c=>!old.some(o=>o.id===c.id))]);setNextPage(data.next_page);}})}>Cargar más cursos</Button>}
          <View style={s.learningNote}><Text style={s.eyebrow}>PASO A PASO</Text><Text style={s.body}>La constancia se construye con cada lección. Tu progreso se guarda en Auren.</Text></View>
        </View>}
      </ScrollView>
      {user&&<View style={s.tabs}>{(['Inicio','Academia','Perfil'] as Tab[]).map((name,i)=><Pressable key={name} accessibilityRole="tab" accessibilityState={{selected:tab===name}} onPress={()=>{requestId.current++;setBusy(false);setTab(name);setCourse(null);setLesson(null);setError('');setNotice('');}} style={s.tab}><Text style={[s.tabIcon,tab===name&&s.active]}>{['⌂','▤','○'][i]}</Text><Text style={[s.tabLabel,tab===name&&s.active]}>{name}</Text></Pressable>)}</View>}
    </View></SafeAreaView></SafeAreaProvider>;
}
const s=StyleSheet.create({
 root:{flex:1,backgroundColor:palette.bg},shell:{flex:1,width:'100%',maxWidth:940,alignSelf:'center'},header:{paddingHorizontal:24,paddingVertical:18,flexDirection:'row',alignItems:'center',justifyContent:'space-between',borderBottomWidth:1,borderBottomColor:'#152238'},brand:{fontFamily:'PlusJakartaSans_800ExtraBold',fontSize:32,color:palette.text,letterSpacing:-1.6},badge:{flexDirection:'row',gap:8,alignItems:'center'},dot:{width:6,height:6,borderRadius:4,backgroundColor:palette.cyan},content:{padding:24,paddingBottom:44,flexGrow:1},welcome:{gap:24,maxWidth:600,width:'100%',alignSelf:'center',paddingTop:18},hero:{fontFamily:'PlusJakartaSans_800ExtraBold',fontSize:48,lineHeight:57,color:palette.text,letterSpacing:-2},eyebrow:{fontFamily:'PlusJakartaSans_700Bold',fontSize:11,letterSpacing:1.8,color:palette.cyan},lead:{fontFamily:'PlusJakartaSans_400Regular',fontSize:17,lineHeight:27,color:palette.muted},body:{fontFamily:'PlusJakartaSans_400Regular',fontSize:15,lineHeight:24,color:palette.muted},small:{fontFamily:'PlusJakartaSans_400Regular',fontSize:12,lineHeight:19,color:palette.muted},title:{fontFamily:'PlusJakartaSans_800ExtraBold',fontSize:32,lineHeight:42,letterSpacing:-1,color:palette.text},sectionTitle:{fontFamily:'PlusJakartaSans_700Bold',fontSize:19,color:palette.text},sectionHeading:{flexDirection:'row',justifyContent:'space-between',alignItems:'center',gap:12,flexWrap:'wrap',marginTop:14},button:{backgroundColor:palette.blue,borderRadius:14,minHeight:54,paddingHorizontal:22,paddingVertical:16,alignItems:'center',justifyContent:'center'},buttonSecondary:{backgroundColor:palette.surface,borderWidth:1,borderColor:palette.line},buttonText:{fontFamily:'PlusJakartaSans_700Bold',fontSize:15,color:palette.text,textAlign:'center'},link:{fontFamily:'PlusJakartaSans_600SemiBold',fontSize:14,lineHeight:24,color:palette.cyan},loginLinks:{flexDirection:'row',justifyContent:'space-between',gap:12,flexWrap:'wrap'},footnote:{fontFamily:'PlusJakartaSans_400Regular',fontSize:12,lineHeight:20,color:palette.muted},stack:{gap:20},welcomeArt:{height:205,borderWidth:1,borderColor:palette.line,borderRadius:22,backgroundColor:palette.surface,overflow:'hidden'},artGrid:{position:'absolute',left:20,right:20,top:28,bottom:55,borderBottomWidth:1,borderTopWidth:1,borderColor:'#233552'},artBar:{position:'absolute',bottom:55,width:'12%',borderRadius:7,backgroundColor:'#3d5cff'},artCaption:{position:'absolute',bottom:15,left:20,color:palette.muted,fontSize:11,fontFamily:'PlusJakartaSans_400Regular'},loading:{flexDirection:'row',gap:10,paddingVertical:12},errorBox:{backgroundColor:'#30202a',borderColor:'#674255',borderWidth:1,borderRadius:12,padding:16,gap:12,marginBottom:20},errorText:{fontFamily:'PlusJakartaSans_400Regular',color:'#ffd3dc',lineHeight:23},notice:{color:palette.cyan,lineHeight:24,paddingBottom:18},courseList:{gap:24},courseCard:{borderRadius:22,borderWidth:1,borderColor:palette.line,backgroundColor:palette.surface,overflow:'hidden'},courseImage:{width:'100%',height:180,backgroundColor:'#162b45'},courseGraphic:{height:150,backgroundColor:'#10244c',alignItems:'center',justifyContent:'center',overflow:'hidden'},orbit:{position:'absolute',width:260,height:260,borderRadius:140,borderWidth:1,borderColor:'#3b5bb5',transform:[{scaleY:.5},{rotate:'-30deg'}]},orbitInner:{position:'absolute',width:160,height:160,borderRadius:90,borderWidth:1,borderColor:palette.cyan,transform:[{scaleY:.5},{rotate:'30deg'}]},graphicText:{color:palette.text,fontFamily:'PlusJakartaSans_800ExtraBold',fontSize:58},courseBody:{padding:22,gap:17},cardTitle:{fontFamily:'PlusJakartaSans_700Bold',fontSize:23,lineHeight:32,color:palette.text},progressLine:{flexDirection:'row',justifyContent:'space-between'},percent:{fontFamily:'PlusJakartaSans_700Bold',fontSize:12,color:palette.cyan},track:{height:5,borderRadius:5,backgroundColor:'#243149',overflow:'hidden'},fill:{height:5,backgroundColor:palette.cyan},infoPanel:{padding:23,gap:14,borderRadius:18,backgroundColor:palette.surface,borderWidth:1,borderColor:palette.line},lessonRow:{flexDirection:'row',alignItems:'center',gap:14,paddingVertical:18,borderBottomWidth:1,borderBottomColor:palette.line},lessonNumber:{width:42,height:42,borderRadius:12,backgroundColor:'#14223b',alignItems:'center',justifyContent:'center'},doneNumber:{backgroundColor:'#123939'},numberText:{color:palette.muted,fontFamily:'PlusJakartaSans_600SemiBold'},doneText:{color:palette.cyan,fontSize:21},rowTitle:{color:palette.text,fontSize:15,lineHeight:23,fontFamily:'PlusJakartaSans_600SemiBold'},arrow:{color:palette.muted,fontSize:25},lessonText:{color:'#d7e0f0',fontFamily:'PlusJakartaSans_400Regular',fontSize:17,lineHeight:30},successPanel:{backgroundColor:'#0d2b2e',padding:22,gap:10,borderRadius:16},success:{color:palette.cyan,fontFamily:'PlusJakartaSans_700Bold',fontSize:17},learningNote:{borderTopColor:palette.line,borderTopWidth:1,marginTop:12,paddingTop:25,gap:12},tabs:{flexDirection:'row',justifyContent:'space-around',paddingVertical:10,borderTopWidth:1,borderTopColor:palette.line,backgroundColor:'#071225'},tab:{minWidth:80,minHeight:54,alignItems:'center',justifyContent:'center',gap:5},tabIcon:{fontSize:24,color:palette.muted},tabLabel:{fontFamily:'PlusJakartaSans_600SemiBold',fontSize:11,color:palette.muted},active:{color:palette.cyan},testBanner:{fontSize:10,textAlign:'center',padding:8,color:'#ffe1a6',backgroundColor:'#30271b'}
});
